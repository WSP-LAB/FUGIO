#!/usr/bin/python3 -B
import os
import sys
import pika
import ast
import json
import copy
import subprocess
import requests
from multiprocessing import *

from . import *
from Utils import arg

class Proxy:
    def __init__(self, target, host, php_ver, cpus):
        self.target = os.path.realpath(target)
        self.php_ver = php_ver
        self.userid = 'fugio'
        self.userpw = 'fugio_password'
        self.host = host
        self.manager = Manager()
        self.lock = self.manager.Lock()
        self.total_chain_cnt = self.manager.Value('i', 0)
        self.run_chain_cnt = self.manager.Value('i', 0)
        self.finish_chain = self.manager.Value('i', 0)
        self.max_process = self.manager.Value('i', cpus/4)
        self.connection = None
        self.channel = None
        self.timeout = 100
        self.args = arg.parse()

    def _init_rabbitmq(self, queue_name, delete=True):
        if self.connection is None or self.connection.is_closed:
            cred = pika.PlainCredentials(self.userid, self.userpw)
            self.connection = pika.BlockingConnection(
                pika.ConnectionParameters(
                    host=self.host,
                    credentials=cred,
                )
            )
            self.channel = self.connection.channel()
        elif self.connection.is_open and self.channel.is_closed:
            self.channel = self.connection.channel()
        if delete:
            self.channel.queue_declare(queue=queue_name, auto_delete = True)
        else:
            self.channel.queue_declare(queue=queue_name)
        return self.channel

    # Chain Analyzer -> Fuzz
    def _recv_chain_info(self):
        queue_name = '{}_fuzz_channel'.format(self.target.replace('/', '.')[1:])
        while True:
            try:
                channel = self._init_rabbitmq(queue_name, delete=False)
                channel.basic_consume(queue=queue_name,
                                      on_message_callback=\
                                      self._recv_chain_info_callback)
                channel.start_consuming()
            except pika.exceptions.StreamLostError:
                pass

    def _recv_chain_info_callback(self, ch, method, properties, body):
        queue_name = '{}_fuzz_channel'.format(self.target.replace('/', '.')[1:])
        try:
            chain_info = ast.literal_eval(body.decode('utf-8'))
            channel = self._init_rabbitmq(queue_name, delete=False)
            channel.basic_ack(method.delivery_tag)
        except:
            e = sys.exc_info()[0]
            print("[!] ERROR in _recv_chain_info_callback: {}".format(e))
            return

        file_path = chain_info['file_path']
        proc_id = int(chain_info['proc_id'])
        chain_id = int(chain_info['chain_id'])
        chain_len = chain_info['chain_len']
        # print ("[RECV] proc{}_{}.chain (len: {})".format(proc_id,
        #                                                  chain_id,
        #                                                  chain_len))

        self.lock.acquire()
        if proc_id not in self.proc_id_list:
            self.proc_id_list[proc_id] = proc_id
            new_chain_list = []
        else:
            new_chain_list = copy.deepcopy(self.generated_chain_list[proc_id])

        chain_info['run'] = False
        new_chain_list.append(chain_info)
        self.total_chain_cnt.value = self.total_chain_cnt.value + 1
        self.generated_chain_list[proc_id] = new_chain_list
        self.lock.release()

    def _run_fuzz(self, file_path):
        file_path = os.path.realpath(file_path)
        queue_name = file_path.replace('/', '_')
        queue_name = queue_name.replace('.', '_')
        queue_name_result = queue_name + '_result'
        proc = Process(target=self._recv_fuzz_result, args=(queue_name_result,))
        proc.start()

        cmd = ['php', FUZZER, 'put-head.php', 'put-body.php',
               file_path, self.host, str(self.timeout)]
        # print (' '.join(cmd))
        try:
            ret = subprocess.call(cmd,
                                  stdout=subprocess.DEVNULL,
                                  stderr=subprocess.DEVNULL,
                                  timeout=self.timeout+5)

        except subprocess.TimeoutExpired:
            finish_msg = {"result": "FINISH"}
            channel = self._init_rabbitmq(queue_name_result)
            channel.basic_publish(exchange='',
                                  routing_key=queue_name_result,
                                  body=json.dumps(finish_msg))

    # Fuzz_manager -> Fuzz
    def _recv_fuzz_result(self, queue_name):
        global ret
        ret = True
        channel = None

        def cb(ch, method, properties, body):
            global ret
            if body is not None:
                try:
                    body = json.loads(body)
                    result = body["result"]
                    if result == "FINISH":
                        ret = False
                    else:
                        chain_info = os.path.realpath(body["chain"])
                        chain_info = chain_info.split('/')[-1]
                        proc_id = int(chain_info.split('_')[0][len('proc'):])
                        print ('Result: {} ({}, {})'.format(body,
                                                            result,
                                                            proc_id))
                        if result == "EXPLOITABLE":
                            self.lock.acquire()
                            if proc_id not in self.finished_list:
                                self.finished_list[proc_id] = proc_id
                            self.lock.release()
                            self._send_fuzz_result('proc{}'.format(proc_id))
                            ret = False
                        elif result == "PROBABLY_EXPLOITABLE":
                            self.lock.acquire()
                            if proc_id not in self.sink_reach_list:
                                self.sink_reach_list[proc_id] = proc_id
                            self.lock.release()
                        else: # "DEBUG"
                            with open(FAIL_LOG, 'a+') as f:
                                f.write('{}: {}\n'.format(body['chain'],
                                                        body['message']))
                            ret = False
                except:
                    e = sys.exc_info()[0]
                    print("[!] ERROR in _recv_fuzz_result: {}".format(e))
                    ret = False

            if ret == False:
                channel.stop_consuming()

        while ret:
            try:
                channel = self._init_rabbitmq(queue_name)
                channel.basic_consume(queue=queue_name,
                                      on_message_callback=cb,
                                      auto_ack=True)
                channel.start_consuming()
            except pika.exceptions.StreamLostError:
                pass

    # Fuzz -> Chain Analyzer
    def _send_fuzz_result(self, proc_id):
        queue_name = '{}_fuzz_result_channel'.format(
                                            self.target.replace('/', '.')[1:])
        channel = self._init_rabbitmq(queue_name)
        channel.basic_publish(exchange='', routing_key=queue_name, body=proc_id)

    def instrument(self, fuzz_dir):
        fuzz_dir = os.path.realpath(fuzz_dir)
        inst = INST
        '''
        if self.php_ver == 5:
            inst = INST
        elif self.php_ver == 7:
            inst = INST7
        '''
        cmd = ['php', inst,
               '{}/put-head.php'.format(fuzz_dir),
               '{}/put-body.php'.format(fuzz_dir)]
        print ('Instrumentation ... ({})'.format(' '.join(cmd)))
        p = subprocess.Popen(cmd, close_fds=True)
        p.wait()

    def _schedule_proc(self):
        import random
        import time
        from datetime import datetime
        from dateutil.relativedelta import relativedelta

        start_time = datetime.now()

        running_list = {}
        proc_list = {}
        finish = False
        running_proc_cnt = 0
        msg = ''
        finish_chain_analysis = False

        while finish == False:
            self.lock.acquire()
            full_list = []
            for value in self.proc_id_list:
                if value != EMPTY:
                    full_list.append(value)
            success_list = []
            finished_list = []
            for value in self.finished_list:
                if value != EMPTY:
                    finished_list.append(value)
                    success_list.append(value)
            if self.finish_chain.value == 1:
                for proc_id in full_list:
                    if proc_id not in finished_list and \
                       proc_id not in running_list:
                        cnt = 0
                        for chain in self.generated_chain_list[proc_id]:
                            if chain['run'] == False:
                                cnt += 1
                        if cnt == 0:
                            finished_list.append(proc_id)
            sink_reach_list = []
            for value in self.sink_reach_list:
                if value != EMPTY and value not in success_list:
                    sink_reach_list.append(value)
            skip_chain_cnt = 0
            for proc_id in success_list:
                for chain in self.generated_chain_list[proc_id]:
                    if chain['run'] == False:
                        skip_chain_cnt += 1
            run_chain_cnt = self.run_chain_cnt.value
            total_chain_cnt = self.total_chain_cnt.value
            if self.finish_chain.value == 1 and \
               running_proc_cnt == 0 and \
               run_chain_cnt == total_chain_cnt:
               #run_chain_cnt+skip_chain_cnt == total_chain_cnt:
                finish = True
            self.lock.release()
            # print ('[FUZZER] Full List: {}'.format(full_list))
            # print ('[FUZZER] Running List: {}'.format(running_list))
            # print ('[FUZZER] Finished List: {}'.format(finished_list))

            end_time = datetime.now()
            diff = relativedelta(end_time, start_time)
            msg = "\n[+] Time: {:02d}:{:02d}:{:02d}:{:02d}\n".format(
                diff.days,
                diff.hours,
                diff.minutes,
                diff.seconds
            )

            if self.sink_func_len != 0:
                msg += "[+] Fuzzer - Chain Found: {}/{} ({} %)\n".format(
                    len(full_list),
                    self.sink_func_len,
                    round(len(full_list)*100/float(self.sink_func_len), 2)
                )
            else:
                msg += "[+] Fuzzer - Chain Found: {}/{} (0.0 %)\n".format(
                    len(full_list),
                    self.sink_func_len
                )
            if total_chain_cnt != 0:
                msg += "[+] Fuzzer - Chain Tried: {}/{} ({} %) - Skip: {}\n".format(
                        run_chain_cnt,
                        total_chain_cnt,
                        round(float(run_chain_cnt)*100/
                            float(total_chain_cnt), 2),
                        skip_chain_cnt,
                    )
                msg += "[+] Fuzzer - Chain Tried (+ Skip): {}/{} ({} %)\n".format(
                    run_chain_cnt+skip_chain_cnt,
                    total_chain_cnt,
                    round(float(run_chain_cnt+skip_chain_cnt)*100/
                          float(total_chain_cnt), 2)
                )
            else:
                msg += "[+] Fuzzer - Chain Tried: 0/0 (0.0 %) - Skip: 0\n"
                msg += "[+] Fuzzer - Chain Tried (+ Skip): 0/0 (0.0 %)\n"

            msg += "[+] Fuzzer - Running: {}/{} ({} %)\n".format(
                running_proc_cnt,
                int(self.max_process.value),
                round(running_proc_cnt*100/float(self.max_process.value), 2)
            )
            if len(full_list) != 0:
                msg += "[+] Fuzzer - Sink Reach: {}/{} ({} %)\n".format(
                    len(sink_reach_list),
                    len(full_list),
                    round(len(sink_reach_list)*100/float(len(full_list)), 2)
                )
            else:
                msg += "[+] Fuzzer - Sink Reach: 0/0 (0.0 %)\n"
            if len(full_list) != 0:
                msg += "[+] Fuzzer - Success: {}/{} ({} %)\n".format(
                len(success_list),
                len(full_list),
                round(len(success_list)*100/float(len(full_list)), 2)
                )
            else:
                msg += "[+] Fuzzer - Success: 0/0 (0.0 %)\n"
            print (msg)

            if finish_chain_analysis == False and self.finish_chain.value == 1:
                finish_chain_analysis = True

            # if diff.days == 1:
            if diff.hours == 12:
                return

            random.shuffle(full_list)
            for proc_id in full_list:
                if running_proc_cnt >= self.max_process.value:
                    break
                #if proc_id in finished_list:
                #    continue
                if proc_id in running_list and \
                   len(full_list) - len(finished_list) > self.max_process.value:
                    continue
                if proc_id in running_list and \
                   not all(proc in list(running_list) + finished_list \
                           for proc in full_list):
                    continue
                else:
                    proc = None
                    self.lock.acquire()

                    for idx, chain in sorted(enumerate(
                                        self.generated_chain_list[proc_id]),
                                        key=lambda x: (x[1]['chain_len'],
                                                       x[1]['depth'],
                                                       x[1]['idx'],
                                                       x[1]['no_this'])):
                        if chain['run'] == False:
                            # print (chain)
                            proc = Process(target=self._run_fuzz,
                                           name='{}_{}'.format(chain['proc_id'],
                                                            chain['chain_id']),
                                           args=(chain['file_path'], ))
                            proc_list[proc.name] = {'proc': proc, 'cnt': 0}
                            if proc_id not in running_list:
                                running_list[proc_id] = [proc.name]
                            else:
                                running_list[proc_id].append(proc.name)
                            running_proc_cnt += 1
                            new_chain_list = copy.deepcopy(
                                            self.generated_chain_list[proc_id])
                            new_chain = copy.deepcopy(chain)
                            new_chain['run'] = True
                            new_chain_list[idx] = new_chain
                            self.generated_chain_list[proc_id] = new_chain_list
                            self.run_chain_cnt.value = \
                                                    self.run_chain_cnt.value + 1
                            # print (self.generated_chain_list[proc_id][idx])
                            break
                    self.lock.release()
                    if proc is not None:
                        proc.start()
                        proc.join(0.001)

            for proc_name, proc_info in list(proc_list.items()):
                proc = proc_info['proc']
                proc_cnt = proc_info['cnt']
                proc_id = int(proc_name.split('_')[0])
                # print ('{}: {} {}'.format(proc_name, proc_cnt, proc))
                # if proc_id in finished_list and proc.is_alive():
                #    proc.terminate()
                if proc.is_alive() and proc_cnt > self.timeout + 10:
                    proc.terminate()
                if proc_id in running_list: # and proc_id not in finished_list:
                    proc.join(0.001)
                if not proc.is_alive():
                    running_list[proc_id].remove(proc_name)
                    del proc_list[proc_name]
                    if len(running_list[proc_id]) == 0:
                        del running_list[proc_id]
                    running_proc_cnt -= 1
                if proc.is_alive():
                    proc_list[proc_name]['cnt'] += 1
            time.sleep(1)

    def set_flag(self, cpus):
        self.lock.acquire()
        self.finish_chain.value = 1
        self.max_process.value = cpus
        self.lock.release()

    def run(self, sink_func_len):
        self.sink_func_len = sink_func_len
        self.proc_id_list = self.manager.list([EMPTY] * sink_func_len)
        self.generated_chain_list = self.manager.list([EMPTY] * sink_func_len)
        self.finished_list = self.manager.list([EMPTY] * sink_func_len)
        self.sink_reach_list = self.manager.list([EMPTY] * sink_func_len)

        proc = Process(target=self._schedule_proc, args=())
        proc.start()
        proc2 = Process(target=self._recv_chain_info, args=())
        proc2.start()
        return proc, proc2
