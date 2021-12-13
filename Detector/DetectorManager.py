import sys
sys.path.append("..")

import multiprocessing
import pika
import json
from datetime import datetime
from Utils.makeHTTP import HTTPGen
import urllib.parse
import base64
import time
import argparse
import requests

from . import *
from Proxy.proxy import Proxy
from Analyzer.analyzer import Analyzer

class DetectorManager:
    def __init__(self, target, rabbitmq_ip, php_ver, all_files, cpus):
        self.procs = []
        self.target = target
        self.rabbitmq_ip = rabbitmq_ip
        self.php_ver = php_ver
        self.all_files = all_files
        self.cpus = cpus
        pass

    def startManager(self, thread_count, host):
        for i in range(thread_count):
            proc = multiprocessing.Process(
                target=self._init_rabbitmq,
                args=(host, 'fugio','fugio_password','trigger_func_channel', i))
            self.procs.append(proc)
            proc.start()

    def _init_rabbitmq(self, host, userid, userpw, userQueue, proc_idx):
        while True:
            try:
                cred = pika.PlainCredentials(userid, userpw)
                connection = pika.BlockingConnection(
                    pika.ConnectionParameters(host=host, credentials=cred))
                channel = connection.channel()
                channel.queue_declare(queue=userQueue)
                channel.basic_consume(
                    queue='trigger_func_channel',
                    on_message_callback=self._callback,
                    auto_ack=True)
                channel.start_consuming()
            except pika.exceptions.StreamLostError as e:
                self._init_rabbitmq(host, userid, userpw,userQueue, proc_idx)

    def _isB64Encoded(self, enc_string, charset="utf-8"):
        try:
            enc_len = len(enc_string)
            if(enc_len % 4 == 0):
                b64_pad = enc_len
            else:
                b64_pad = (4 - (enc_len % 4)) + enc_len
            enc_bytes = bytes(enc_string, charset).ljust(b64_pad, b'=')
            dec_bytes = base64.b64decode(enc_bytes)
            if(enc_bytes == base64.b64encode(dec_bytes)):
                return True
            else:
                return False
        except:
            return False

    def _B64Decode(self, enc_bytes):
        try:
            enc_len = len(enc_bytes)
            if(enc_len % 4 == 0):
                b64_pad = enc_len
            else:
                b64_pad = (4 - (enc_len % 4)) + enc_len
            enc_data = enc_bytes.ljust(b64_pad, '=')
            return base64.b64decode(enc_data).decode("utf-8")
        except:
            return False

    def _callback(self, ch, method, properties, body):
        try:
            callback_data = json.loads(body)
        except:
            print("[!] ERROR while queue deliever")
            return

        '''
        body = GET / POST / COOKIE / REQUEST / FILES / SESSION / SERVER / ENV
               TRIGGER_FUNC / FUNC_ARGV
               CLASSESS / USER_CLASSES / USER_FUNCTIONS
               INCLUDED_FILES / GLOBALS / CONSTANTS / EVAL_CODE
        '''
        trigger_func = callback_data["TRIGGER_FUNC"]
        # try:
        #     trigger_file = callback_data['GLOBALS']["_SERVER"]["SCRIPT_FILENAME"]
        # except:
        #     trigger_file = callback_data["SERVER"]["SCRIPT_FILENAME"]
        # try:
        #     trigger_time = datetime.fromtimestamp(
        #                             callback_data['GLOBALS']["_SERVER"]["REQUEST_TIME"])
        # except:
        #     trigger_time = datetime.fromtimestamp(callback_data["SERVER"]["REQUEST_TIME"])
        trigger_file = ''
        trigger_time = datetime.now()

        user_argvs_get = callback_data["GET"]
        user_argvs_post = callback_data["POST"]
        user_argvs_cookie = callback_data["COOKIE"]
        user_argvs_request = callback_data["REQUEST"]
        user_classes = callback_data["USER_CLASSES"]
        declared_classes = callback_data["CLASSES"]
        included_files = callback_data["INCLUDED_FILES"]
        global_vars = callback_data['GLOBALS']
        constant_vars = callback_data['CONSTANTS']
        eval_code = callback_data['EVAL_CODE']
        user_functions = callback_data['USER_FUNCTIONS']
        autoload_functions = callback_data['AUTOLOAD']
        declared_interfaces = callback_data["INTERFACES"]
        declared_functions = callback_data["FUNCTIONS"]
        declared_traits = callback_data["TRAITS"]
        if "CLASS_ALIAS" in callback_data:
            class_alias = callback_data["CLASS_ALIAS"]
        else:
            class_alias = []

        func_argvs = callback_data["FUNC_ARGV"]
        Verification_1st = True
        ARGV_ENCODE = 'None'

        print("[#] Exploitable!")
        print(" - Injected Function: {}()".format(trigger_func))
        # print(" - File: {}".format(trigger_file))
        # print(" - Time: {}\n\n".format(trigger_time))


        if(Verification_1st):
            start_time = time.time()
            available_magic_method_list = []
            for method in callback_data['AVAILABLE_MAGIC_METHODS']:
                available_magic_method_list.append(method.split('(')[0])

            available_magic_method_list = MAGIC_METHODS
            print ("MAGIC METHODS: {}".format(available_magic_method_list))
            useless_key = ['_GET', '_POST', '_COOKIE', '_SERVER',
                           '_ENV', '_REQUEST', '_FILES', 'GLOBALS',
                           'argv_target_list_r353t', 'argv_target_r353t',
                           'argv_decor_r353t', 'argv_list_r353t',
                           'dummy_class_r353t_this', ]
            for k in useless_key:
                if k in global_vars:
                    del global_vars[k]

            autoload_functions.remove([{}, 'loadClass'])
            if len(autoload_functions) > 0:
                autoload = True
            else:
                autoload = False
            # print ("AUTOLOAD: {}".format(autoload_functions))
            print ("DECL_CLASSES: {} (After autoload)".format(
                   len(declared_classes)))
            print ("DECL_INTERFACES: {} (After autoload)".format(
                   len(declared_interfaces)))
            print ("DECL_TRAITS: {} (After autoload)".format(
                   len(declared_traits)))
            print ("USER_CLASSES: {} (After autoload)".format(
                   len(user_classes)))

            self.analyzer = Analyzer(self.target, self.rabbitmq_ip)
            target_function, target_class = self.analyzer.preprocess(
                                                            user_classes,
                                                            user_functions,
                                                            declared_interfaces,
                                                            declared_classes,
                                                            declared_traits,
                                                            class_alias,
                                                            self.all_files,
                                                            eval_code)

            fuzz_dir = self.analyzer.generate_PUT(user_classes,
                                                declared_classes,
                                                declared_interfaces,
                                                declared_traits,
                                                global_vars,
                                                constant_vars,
                                                user_functions,
                                                declared_functions,
                                                trigger_time,
                                                class_alias,
                                                self.all_files)

            proxy = Proxy(self.target, self.rabbitmq_ip, self.php_ver, self.cpus)
            proxy.instrument(fuzz_dir)

            target_class = self.analyzer.find_chain(fuzz_dir, target_class,
                                                    target_function, class_alias,
                                                    available_magic_method_list, proxy,
                                                    self.cpus)
            end_time = time.time()
            print ('Total time: {}(s)'.format(end_time - start_time))
        sys.exit()
