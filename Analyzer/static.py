import os
import re
import pickle

from . import *
from .Units import *
from .parser import ASTParser
from .chain import ChainAnalyzer

class StaticAnalyzer:
    def __init__(self, rabbitmq_ip):
        self.parser = ASTParser(rabbitmq_ip)
        self.target_file_list = {}

    def parse_file(self, target):
        self.parser.parse(target, self.target_file_list, self.target)
        return self.target_file_list

    def parse(self, target):
        self.target = target
        dump_file = '%s/%s.dump' %(DUMP_DIR, self.target.replace('/', '.')[1:])
        if os.path.isfile(dump_file):
            with open(dump_file, 'rb') as f:
                self.target_file_list = pickle.load(f)
                return self.target_file_list

        if os.path.isfile(self.target):
            self.parse_file(self.target)

        for dir_list, subdir_list, file_list in os.walk(self.target):
            pass_flag = False
            for excluded_dir in EXCLUDED_DIRS:
                if dir_list.startswith(excluded_dir%self.target):
                    pass_flag = True
                    break
            if pass_flag:
                continue
            for fname in file_list:
                if fname.split('.')[-1] == 'php' or \
                   fname.split('.')[-1] == 'inc' or \
                   fname.split('.')[-1] == 'module':
                    file_name = ('%s/%s' %(dir_list, fname)).replace('//', '/')
                    pass_flag = False
                    for excluded_file in EXCLUDED_FILES:
                        if file_name.startswith(excluded_file%self.target):
                            pass_flag = True
                            break
                    if pass_flag:
                        continue
                    print ('%s' %(file_name))
                    self.parse_file(file_name)

        with open(dump_file, 'wb') as f:
            pickle.dump(self.target_file_list, f)

        print ('Done! (%d)' %len(self.target_file_list))
        return self.target_file_list

    def find_chain(self):
        chain_list = ChainAnalyzer(self.target_file_list).find_chain()

        dump_file = '%s/%s.chain' %(DUMP_DIR, self.target.replace('/', '.')[1:])
        with open(dump_file, 'wb') as f:
            pickle.dump(chain_list, f)

    def update_info(self, target_class, target_function, declared_classes,
                    declared_interfaces, declared_traits, target_file_list):
        new_target_class = {}
        new_target_function = {}

        for file_name in target_file_list:
            _file = target_file_list[file_name]

            for func_name in _file.func_list:
                if func_name in target_function:
                    new_target_function[func_name] = target_function[func_name]
                else:
                    func_info = {}
                    func_info['FILE_NAME'] = file_name
                    new_target_function[func_name] = func_info

            for class_name in list(_file.class_list):
                _class = _file.class_list[class_name]
                if class_name in target_class:
                    new_target_class[class_name] = target_class[class_name]
                elif class_name in declared_classes and \
                     declared_classes[class_name]['INTERNAL']:
                    del _file.class_list[class_name]
                elif class_name in declared_interfaces and \
                     declared_interfaces[class_name]['INTERNAL']:
                    del _file.class_list[class_name]
                elif class_name in declared_traits and \
                     declared_traits[class_name]['INTERNAL']:
                    del _file.class_list[class_name]
                else:
                    class_info = {}
                    class_info['FILE_NAME'] = file_name
                    class_info['CLASS_PARENTS'] = _class.parents
                    class_info['INTERFACES'] = _class.implements
                    class_info['TRAITS'] = _class.traits
                    class_info['METHODS'] = _class.method_list
                    class_info['PROPS'] = _class.prop_list
                    class_info['CLASS_TYPE'] = _class.type
                    new_target_class[class_name] = class_info

        return new_target_class, new_target_function