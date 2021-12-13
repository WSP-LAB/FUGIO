import json
import pika
import subprocess
import re

from . import *
from .Units import *

class ASTParser():
    def __init__(self, host):
        self.host = host

    def _init_rabbitmq(self, host, userid, userpw, userQueue):
        cred = pika.PlainCredentials(userid, userpw)
        self.connection = pika.BlockingConnection(
            pika.ConnectionParameters(
                host=host,
                credentials=cred,
            )
        )
        self.channel = self.connection.channel()
        self.res = self.channel.queue_declare(queue=userQueue)

    def _callback(self, ch, method, properties, body):
        try:
            self.ast = json.loads(body)
            self.channel.stop_consuming()
        except Exception as e:
            print("[!] ERROR while queue deliever")
            print (body)
            print(e)

    def _get_ast(self, file_name):
        cmd = ['php', PARSER, file_name, self.host, self.target]
        out = subprocess.run(cmd,
                             stdout=subprocess.PIPE,
                             stderr=subprocess.PIPE)
        # print (out.stdout.decode('utf-8'))
        print (out.stderr.decode('utf-8'))

        queue_name = 'ast_channel{}'.format(self.target.replace('/', '_'))
        self._init_rabbitmq(self.host, 'fugio', 'fugio_password', queue_name)
        self.channel.basic_consume(
            queue=queue_name,
            on_message_callback=self._callback,
            auto_ack=True)
        self.channel.start_consuming()

    def _get_file(self, file_name, target_file_list):
        if file_name not in target_file_list.keys():
            target_file_list[file_name] = File(file_name)
        return target_file_list[file_name]

    def _get_class(self, _file, class_name):
        if class_name not in _file.class_list.keys():
            _file.class_list[class_name] = Class(class_name)
        return _file.class_list[class_name]

    def _get_type(self, value):
        type_list = []
        if value & PUBLIC:
            type_list.append(PUBLIC)
        if value & PROTECTED:
            type_list.append(PROTECTED)
        if value & PRIVATE:
            type_list.append(PRIVATE)
        if value & STATIC:
            type_list.append(STATIC)
        if value & ABSTRACT:
            type_list.append(ABSTRACT)
        if value & FINAL:
            type_list.append(FINAL)
        if value & INTERFACE:
            type_list.append(INTERFACE)
        if value & TRAIT:
            type_list.append(TRAIT)
        return type_list

    def _get_static(self, value):
        if value & STATIC:
            return True
        else:
            return False

    def _get_visibility(self, value):
        if value & PUBLIC:
            return 'public'
        if value & PROTECTED:
            return 'protected'
        if value & PRIVATE:
            return 'private'
        else:
            return 'public'

    def _update_methods(self, file_name, class_name, _class, methods):
        for method_name, method_info in methods.items():
            # Get method
            if method_name not in _class.method_list.keys():
                _class.method_list[method_name] = Method(method_name)
            _method = _class.method_list[method_name]

            # Update method
            _method.type = self._get_type(method_info['TYPE'])
            _method.static = self._get_static(method_info['TYPE'])
            _method.visibility = self._get_visibility(method_info['TYPE'])
            _method.real_class = class_name
            _method.real_file = file_name
            _method.real_name = method_name

            # Update parameters
            params = method_info['PARAMS']
            if len(params) > 0:
                for idx, (param_name, param_info) in enumerate(params.items()):
                    _method.param_list[param_name] = {'INDEX': idx,
                                                      'DEFAULT': \
                                                        param_info['DEFAULT']}

            _method.call_list = method_info['CALLS']
            _method.var_list = method_info['VARS']
            _method.taint_list = method_info['TAINT']
            if len(method_info['FOR']) == 0:
                _method.for_list = dict()
            else:
                _method.for_list = method_info['FOR']
            if len(method_info['ARRAY_ACCESS']) == 0:
                _method.array_access_list = dict()
            else:
                _method.array_access_list = method_info['ARRAY_ACCESS']
            _method.string_list = method_info['STRING']

    def _update_props(self, file_name, class_name, _class, properties):
        for prop_name, prop_info in properties.items():
            # Get prop
            if prop_name not in _class.prop_list.keys():
                _class.prop_list[prop_name] = Property(prop_name)
            _property = _class.prop_list[prop_name]

            # Update property
            _property.type = self._get_type(prop_info['TYPE'])
            _property.static = self._get_static(prop_info['TYPE'])
            _property.value = prop_info['DEFAULT']
            _property.visibility = self._get_visibility(prop_info['TYPE'])
            _property.real_class = class_name
            _property.real_file = file_name

    def parse(self, file_name, target_file_list, target):
        self.target = target

        # Get File
        _file = self._get_file(file_name, target_file_list)

        self._get_ast(file_name)
        # print (self.ast)

        if len(self.ast) > 0:
            if len(self.ast['functions']) > 0:
                for name, info in self.ast['functions'].items():
                    if not re.match(EXCLUDED_FUNCTIONS_REGEX, name):
                        if info['TYPE'] == 'FuncDecl':
                            func_name = name
                            func_info = info

                            new_func = Method(func_name)
                            params = func_info['PARAMS']
                            if len(params) > 0:
                                for idx, (param_name, param_info) in \
                                                    enumerate(params.items()):
                                    new_func.param_list[param_name] = \
                                        {'INDEX': idx,
                                         'DEFAULT': param_info['DEFAULT']}

                            new_func.call_list = func_info['CALLS']
                            new_func.code = func_info['DECL']
                            new_func.taint_list = func_info['TAINT']
                            new_func.namespace = func_info['NAMESPACE']
                            new_func.uses = func_info['USES']
                            new_func.real_name = func_info['NAME']
                            if len(func_info['FOR']) == 0:
                                new_func.for_list = {}
                            else:
                                new_func.for_list = func_info['FOR']
                            if len(func_info['ARRAY_ACCESS']) == 0:
                                new_func.array_access_list = {}
                            else:
                                new_func.array_access_list = func_info['ARRAY_ACCESS']
                            _file.func_list[func_name] = new_func

            if len(self.ast['classes']) > 0:
                for name, info in self.ast['classes'].items():
                    if not re.match(EXCLUDED_CLASSES_REGEX, name):
                        class_name = name
                        class_info = info

                        parents = class_info['PARENTS']
                        implements = class_info['IMPLEMENTS']
                        traits = class_info['TRAITS']
                        properties = class_info['PROPS']
                        methods = class_info['METHODS']
                        class_type = class_info['TYPE']
                        uses = class_info['USES']

                        # Get class
                        _class = self._get_class(_file, class_name)
                        _class.real_name = class_info['NAME']
                        if parents is None or len(parents) == 0:
                            _class.parents = []
                        else:
                            _class.parents = parents
                        if implements is None or len(implements) == 0:
                            _class.implements = []
                        else:
                            _class.implements = implements
                        if traits is None or len(traits) == 0:
                            _class.traits = []
                        else:
                            _class.traits = traits
                        if len(uses) == 0:
                            _class.uses = {}
                        else:
                            _class.uses = uses
                        _class.type = self._get_type(class_type)
                        _class.namespace = class_info['NAMESPACE']
                        _class.code = class_info['DECL']

                        # Update method & props
                        if len(methods) > 0:
                            self._update_methods(file_name, class_name,
                                                 _class, methods)
                        if len(properties) > 0:
                            self._update_props(file_name, class_name,
                                               _class, properties)
