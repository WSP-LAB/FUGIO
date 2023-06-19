import os
import re
import shutil
import json
import copy

from . import *
from .static import StaticAnalyzer
from .dynamic import DynamicAnalyzer
from .chain import ChainAnalyzer

class Analyzer:
    def __init__(self, target, rabbitmq_ip):
        self.target = os.path.realpath(target)
        self.rabbitmq_ip = rabbitmq_ip
        self.s_analyzer = StaticAnalyzer(rabbitmq_ip)
        self.target_file_list = self.s_analyzer.parse(self.target)
        # if find_all:
        #     self.s_analyzer.find_chain()
        self.d_analyzer = DynamicAnalyzer()
        self.class_list = self.get_class_list()

    def get_class_list(self):
        class_list = []
        for target_file in self.target_file_list:
            _file = self.target_file_list[target_file]
            for class_name in _file.class_list:
                # if not re.match(EXCLUDED_CLASSES_REGEX, class_name):
                class_list.append(class_name)
        print ("CLASSES: {} (Before autoload)".format(len(class_list)))
        return class_list

    def _find_file(self, target_class_name, declared_classes,
                   declared_interfaces, declared_traits):
        file_name_list = []

        for dec_class in declared_classes:
            if target_class_name.lower() == dec_class.lower() and \
               declared_classes[dec_class]['INTERNAL']:
                    print ("[#] Declared class {}".format(target_class_name))
                    return INTERNAL

        for dec_interface in declared_interfaces:
            if target_class_name.lower() == dec_interface.lower() and \
               declared_interfaces[dec_interface]['INTERNAL']:
                    print ("[#] Declared interface {}".format(
                                                            target_class_name))
                    return INTERNAL

        for dec_trait in declared_traits:
            if target_class_name.lower() == dec_trait.lower() and \
               declared_traits[dec_trait]['INTERNAL']:
                    print ("[#] Declared trait {}".format(target_class_name))
                    return INTERNAL

        # if target_class_name.lower() in declared_classes and \
        #    declared_classes[target_class_name]['INTERNAL']:
        #     print ("[#] Declared class {}".format(target_class_name))
        #     return INTERNAL
        # elif target_class_name in declared_interfaces and \
        #      declared_interfaces[target_class_name]['INTERNAL']:
        #     print ("[#] Declared interface {}".format(target_class_name))
        #     return INTERNAL
        # elif target_class_name in declared_traits and \
        #      declared_traits[target_class_name]['INTERNAL']:
        #     print ("[#] Declared trait {}".format(target_class_name))
        #     return INTERNAL
        # else:
        for file_name in self.target_file_list:
            _file = self.target_file_list[file_name]
            for class_name in _file.class_list:
                if target_class_name == class_name:
                    file_name_list.append(file_name)

        if len(file_name_list) == 1:
            return file_name_list[0]

        elif len(file_name_list) < 1:
            print ("[#] Can't find file - class {}".format(target_class_name))
            return None

        elif len(file_name_list) > 1:
            print ("[#] Found too many files - class {}".format(
                                                            target_class_name))
            #TODO: select only one file
            return file_name_list[0]

    def _get_hierarchy(self, declared_classes,
                       declared_interfaces, declared_traits):
        class_hierarchy = {}

        # Update parent info
        for file_name in self.target_file_list:
            _file = self.target_file_list[file_name]
            for class_name in _file.class_list:
                _class = _file.class_list[class_name]
                for idx, parent in enumerate(_class.parents):
                    parent_file_name = self._find_file(parent['NAME'],
                                                       declared_classes,
                                                       declared_interfaces,
                                                       declared_traits)
                    parent_info = {}
                    parent_info['TYPE'] = parent['TYPE']
                    parent_info['NAME'] = parent['NAME']
                    parent_info['FILE'] = parent_file_name
                    if idx == 0:
                        _class.parents = [parent_info]
                    else:
                        _class.parents.append(parent_info)
                for idx, implement in enumerate(_class.implements):
                    implement_file_name = self._find_file(implement['NAME'],
                                                          declared_classes,
                                                          declared_interfaces,
                                                          declared_traits)
                    implement_info = {}
                    implement_info['TYPE'] = implement['TYPE']
                    implement_info['NAME'] = implement['NAME']
                    implement_info['FILE'] = implement_file_name
                    if idx == 0:
                        _class.implements = [implement_info]
                    else:
                        _class.implements.append(implement_info)
                key = '{}::{}'.format(file_name, class_name)
                if key in class_hierarchy:
                    print ("[#] Duplicate class in same file: {}".format(key))
                class_hierarchy[key] = {'PARENTS': _class.parents,
                                        'IMPLEMENTS': _class.implements}

        # Update hierarchy
        for file_name in self.target_file_list:
            _file = self.target_file_list[file_name]
            for class_name in _file.class_list:
                # print (class_name)
                _class = _file.class_list[class_name]
                if INTERFACE in _class.type:
                    class_type = 'INTERFACE'
                else:
                    class_type = 'CLASS'

                new_parents = [{'TYPE': class_type,
                                'NAME': class_name,
                                'FILE': file_name}]
                tmp_list = _class.parents
                # print ('[old_parents] {}: {}'.format(class_name, tmp_list))
                while len(tmp_list) > 0:
                    parent = tmp_list.pop(0)
                    if parent not in new_parents:
                        new_parents.append(parent)

                    p_key = '{}::{}'.format(parent['FILE'], parent['NAME'])
                    if p_key not in class_hierarchy:
                        continue

                    grand_parents = class_hierarchy[p_key]['PARENTS']
                    for grand_parent in grand_parents:
                        if grand_parent not in new_parents:
                            tmp_list.append(grand_parent)
                _class.parents = new_parents

                new_implements = [{'TYPE': class_type,
                                   'NAME': class_name,
                                   'FILE': file_name}]
                tmp_list = []
                for parent in _class.parents:
                    if parent['FILE'] and parent['FILE'] != INTERNAL:
                        f = self.target_file_list[parent['FILE']]
                        c = f.class_list[parent['NAME']]
                        tmp_list += c.implements
                # print ('[old_implements] {}: {}'.format(class_name, tmp_list))
                while len(tmp_list) > 0:
                    implement = tmp_list.pop(0)
                    if implement not in new_implements:
                        new_implements.append(implement)

                    i_key = '{}::{}'.format(implement['FILE'],
                                            implement['NAME'])
                    if i_key not in class_hierarchy:
                        continue

                    grand_parents = class_hierarchy[i_key]['IMPLEMENTS']
                    for grand_parent in grand_parents:
                        if grand_parent not in new_implements:
                            tmp_list.append(grand_parent)
                _class.implements = new_implements

                key = '{}::{}'.format(file_name, class_name)
                class_hierarchy[key] = {'PARENTS': new_parents,
                                        'IMPLEMENTS': new_implements}
                # print ('[new_parents] {}: {}'.format(class_name, new_parents))
                # print ('[new_implements] {}: {}'.format(class_name, new_implements))

        # print ("=============")
        # print (class_hierarchy)
        # print ("=============")
        return class_hierarchy

    def _update_static_info(self, declared_classes,
                            declared_interfaces, declared_traits):
        undefined_class = []

        for file_name in self.target_file_list:
            _file = self.target_file_list[file_name]
            for class_name in _file.class_list:
                _class = _file.class_list[class_name]

                new_method_list = copy.deepcopy(_class.method_list)
                new_prop_list = copy.deepcopy(_class.prop_list)
                parents = _class.parents + _class.implements

                for parent in parents:
                    parent_file = parent['FILE']
                    parent_name = parent['NAME']

                    if parent_file is None:
                        if parent_name.lower() not in undefined_class:
                            undefined_class.append(parent_name.lower())
                        continue

                    if parent_file == INTERNAL:
                        continue

                    # if parent_file not in self.target_file_list.keys() and \
                    #    parent_name.lower() in [x.lower() for x in declared_classes] or \
                    #    parent_name.lower() in [x.lower() for x in declared_interfaces]:
                    #    # INTERNAL
                    #     continue

                    _p_file = self.target_file_list[parent_file]
                    _p_class = _p_file.class_list[parent_name]

                    for method_name, method_info in _p_class.method_list.items():
                        if method_name not in new_method_list:
                            new_method_list[method_name] = method_info


                    for prop_name, prop_info in _p_class.prop_list.items():
                        if prop_name not in new_prop_list:
                            new_prop_list[prop_name] = prop_info

                traits = _class.traits
                trait_alias_list = {}
                trait_instead_list = {}
                trait_method_list = {}
                new_traits = []

                for trait_info in traits:
                    new_trait_list = []
                    for adaption in trait_info['ADAPTIONS']:
                        adapt_type = adaption['TYPE']
                        if adapt_type == 'PRECEDENCE':
                            method_name = adaption['METHOD']
                            instead_info = {}
                            instead_info['TRAIT'] = adaption['TRAIT']
                            instead_info['INSTEAD'] = adaption['INSTEAD']
                            if method_name not in trait_instead_list:
                                trait_instead_list[method_name] = instead_info
                            else:
                                print ("[#] Duplicate instead: {}".format(method_name))

                        elif adapt_type == 'ALIAS':
                            method_name = adaption['METHOD']
                            alias_info = {}
                            alias_info['TRAIT'] = adaption['TRAIT']
                            alias_info['NEW_MODIFIER'] = adaption['NEW_MODIFIER']
                            alias_info['NEW_NAME'] = adaption['NEW_NAME']
                            if method_name not in trait_alias_list:
                                trait_alias_list[method_name] = alias_info
                            else:
                                print ("[#] Duplicate alias: {}".format(method_name))

                    for trait in trait_info['TRAITS']:
                        trait_name = trait['NAME']
                        if 'FILE' in trait and trait['FILE']:
                            trait_file = trait['FILE']
                        else:
                            trait_file = self._find_file(trait_name,
                                                        declared_classes,
                                                        declared_interfaces,
                                                        declared_traits)

                        if trait_file is None:
                            if trait_name.lower() not in undefined_class:
                                undefined_class.append(trait_name.lower())
                            continue

                        elif trait_file == INTERNAL:
                            continue

                        new_trait = {}
                        new_trait['TYPE'] = 'TRAIT'
                        new_trait['NAME'] = trait_name
                        new_trait['FILE'] = trait_file
                        new_trait_list.append(new_trait)

                        _t_file = self.target_file_list[trait_file]
                        _t_class = _t_file.class_list[trait_name]

                        for method_name, method_info in _t_class.method_list.items():
                            new_mod = ''
                            if method_name in trait_instead_list:
                                if trait_name in trait_instead_list[method_name]['INSTEAD']:
                                    continue
                            if method_name in trait_alias_list:
                                if trait_name == trait_alias_list[method_name]['TRAIT']:
                                    if trait_alias_list[method_name]['NEW_MODIFIER']:
                                        new_mod = \
                                            trait_alias_list[method_name]['NEW_MODIFIER']
                                    method_name = \
                                    trait_alias_list[method_name]['NEW_NAME']

                            if method_name not in new_method_list:
                                if new_mod:
                                    method_info.visibility = new_mod
                                if method_name not in trait_method_list:
                                    trait_method_list[method_name] = method_info
                                    new_method_list[method_name] = method_info
                                else:
                                    print ("[#] Duplicate method {}".format(
                                                                method_name))

                    new_traits.append({'TRAITS': new_trait_list,
                                       'ADAPTIONS': trait_info['ADAPTIONS']})
                _class.traits = new_traits

                # print ('======={}======='.format(class_name))
                # print ('[BEFORE] METHOD: ')
                # for method, method_info in _class.method_list.items():
                #     print ('  - {}: {}'.format(method, vars(method_info)))
                # print ('[BEFORE] PROP: ')
                # for prop, prop_info in _class.prop_list.items():
                #     print ('  - {}: {}'.format(prop, vars(prop_info)))
                _class.method_list = new_method_list
                _class.prop_list = new_prop_list
                # print ('[AFTER] METHOD: ')
                # for method, method_info in _class.method_list.items():
                #     print ('  - {}: {}'.format(method, vars(method_info)))
                # print ('[AFTER] PROP: ')
                # for prop, prop_info in _class.prop_list.items():
                #     print ('  - {}: {}'.format(prop, vars(prop_info)))

        old_len = -1
        new_len = len(undefined_class)
        while old_len != new_len:
            old_len = new_len
            for key, value in self.class_hierarchy.items():
                parents = value['PARENTS'] + value['IMPLEMENTS'][1:]
                class_name = parents[0]['NAME']
                for parent in parents:
                    parent_name = parent['NAME']
                    if parent_name.lower() in undefined_class:
                        if class_name.lower() not in undefined_class:
                            undefined_class.append(class_name.lower())
            new_len = len(undefined_class)

        self.undefined_class = undefined_class
        return undefined_class

    def _update_target_class(self, target_class):
        new_target_class = {}

        for class_name, class_info in target_class.items():
            if class_name.lower() in self.undefined_class:
                continue
            else:
                key = '{}::{}'.format(class_info['FILE_NAME'], class_name)
                new_class_info = {}
                new_class_info['FILE_NAME'] = class_info['FILE_NAME']
                new_class_info['CLASS_PARENTS'] = \
                                        self.class_hierarchy[key]['PARENTS']
                new_class_info['INTERFACES'] = \
                                        self.class_hierarchy[key]['IMPLEMENTS']
                new_class_info['METHODS'] = class_info['METHODS']
                new_class_info['PROPS'] = class_info['PROPS']
                new_class_info['CLASS_TYPE'] = class_info['CLASS_TYPE']
                new_target_class[class_name] = new_class_info
        return new_target_class

    def preprocess(self, user_classes, user_functions, declared_interfaces,
                   declared_classes, declared_traits, class_alias, all_files,
                   eval_code):
        with open(EVAL_FILE, 'wb') as f:
            f.write(b'<?php\n')
            for code in eval_code:
                f.write(code.encode('utf-8'))
                f.write(b"\n")
            f.write(b'?>')
        self.target_file_list = self.s_analyzer.parse_file(EVAL_FILE)

        # If PHP returns an empty array, Python treats it as a list.
        if isinstance(user_classes, list):
            user_classes = {}

        target_class, target_function = \
                        self.d_analyzer.update_info(user_classes,
                                                    user_functions,
                                                    declared_classes,
                                                    declared_interfaces,
                                                    declared_traits,
                                                    class_alias,
                                                    self.target_file_list)
        del self.target_file_list[EVAL_FILE]
        if all_files:
            target_class, target_function = \
                        self.s_analyzer.update_info(target_class,
                                                    target_function,
                                                    declared_classes,
                                                    declared_interfaces,
                                                    declared_traits,
                                                    self.target_file_list)

        self.class_hierarchy = self._get_hierarchy(declared_classes,
                                                   declared_interfaces,
                                                   declared_traits)
        undefined_class = self._update_static_info(declared_classes,
                                                   declared_interfaces,
                                                   declared_traits)

        if all_files:
            target_class = self._update_target_class(target_class)
        # self.dump_class_structure()

        return target_function, target_class

    def find_chain(self, fuzz_dir, target_class, target_function, class_alias,
                available_magic_method_list, proxy, cpus):
        chain_list = ChainAnalyzer(self.target_file_list).find_chain(
                                                    self,
                                                    fuzz_dir,
                                                    self.rabbitmq_ip,
                                                    self.target,
                                                    self.class_hierarchy,
                                                    target_class,
                                                    target_function,
                                                    class_alias,
                                                    available_magic_method_list,
                                                    proxy,
                                                    cpus)

        return chain_list

    def analyze_chain(self, chain_info):
        for i, gadget in enumerate(chain_info['gadget_info'][:-1]):
            next_gadget = chain_info['gadget_info'][i+1]
            for var_name, var_data in gadget['var_list'].items():
                var_info_list = var_data['candidates']
                for j, var_info in enumerate(var_info_list):
                    if 'method' in var_info.keys():
                        if var_info['method'] == next_gadget['method'] and \
                           var_info['value']['class'] == next_gadget['class'] \
                           and var_info['value']['file'] == next_gadget['file']:
                            var_info['deterministic'] = True
                            chain_info['gadget_info'][i]['var_list'][var_name]['candidates'] = [var_info]
                            break
                        else:
                            var_info['deterministic'] = False
                    else:
                        var_info['deterministic'] = False
        return chain_info

    def _get_param_cnt_range(self, param_list):
        min_cnt = 0
        max_cnt = len(param_list)
        for param, default in param_list.items():
            if default is None:
                min_cnt += 1
        return range(min_cnt, max_cnt+1)

    def _find_prop(self, target_classes, target_prop):
        prop_list = []
        for class_name, class_info in target_classes.items():
            file_name = class_info['FILE_NAME']
            _file = self.target_file_list[file_name]
            _class = _file.class_list[class_name]
            for prop_name, prop_info in _class.prop_list.items():
                if prop_name == target_prop:
                    prop_list.append({'file': file_name,
                                      'class': class_name,
                                      'visibility': prop_info.visibility})
        return prop_list

    def _find_method(self, target_classes, method, arg_cnt):
        prop_list = []
        for class_name, class_info in target_classes.items():
            file_name = class_info['FILE_NAME']
            _file = self.target_file_list[file_name]
            _class = _file.class_list[class_name]
            if INTERFACE in _class.type or ABSTRACT in _class.type:
                continue

            for method_name, method_info in _class.method_list.items():
                param_cnt_range = self._get_param_cnt_range(
                                                    method_info.param_list)
                if method_name == method and arg_cnt in param_cnt_range:
                    prop_list.append({'file': file_name,
                                      'class': class_name,
                                      'visibility': method_info.visibility})
        return prop_list

    def _get_visibility(self, prop_list, prop):
        if prop in prop_list:
            return prop_list[prop].visibility
        else:
            return 'public'

    def _get_prop_method_list(self, var_list, prop_name):
        prop_list = []
        method_list = []
        for var_info in var_list[prop_name]['candidates']:
            if 'prop' in var_info.keys():
                prop_list.append(var_info['prop'])
            elif 'method' in var_info.keys():
                method_list.append(var_info['method'])
        return list(set(prop_list)), list(set(method_list))

    def _add_var_list(self, var_list, prop_name, prop_info, var_info):
        if prop_name in var_list.keys():
            old_var_info_list = var_list[prop_name]['candidates']
            if len(old_var_info_list) > 0:
                if old_var_info_list[0]['type'] == 'Unknown':
                    if var_info['type'] == 'Object':
                        new_var_info = var_info.copy()
                        new_var_info['visibility'] = \
                                            old_var_info_list[0]['visibility']
                        var_list[prop_name] = {'data': prop_info,
                                               'candidates': [new_var_info]}
                    elif var_info not in old_var_info_list:
                        var_list[prop_name]['candidates'].append(
                                                                var_info.copy())
                else:
                    new_var_info = var_info.copy()
                    new_var_info['visibility'] = \
                                            old_var_info_list[0]['visibility']
                    if new_var_info not in old_var_info_list:
                        var_list[prop_name]['candidates'].append(new_var_info)
                    # old_var_info_list.append(new_var_info)
                    # del_list = []
                    # prop_list, method_list = self._get_prop_method_list(
                    #                                                 var_list,
                    #                                                 prop_name)
                    # for old_var_info in old_var_info_list:
                    #     file_name = old_var_info['value']['file']
                    #     class_name = old_var_info['value']['class']
                    #     _file = self.target_file_list[file_name]
                    #     _class = _file.class_list[class_name]
                    #     _prop_list = _class.prop_list
                    #     _method_list = _class.method_list
                    #     for prop in prop_list:
                    #         if prop not in _prop_list:
                    #             del_list.append(old_var_info)
                    #     for method in method_list:
                    #         if method not in _method_list:
                    #             del_list.append(old_var_info)
                    # for item in del_list:
                    #     if item in old_var_info_list:
                    #         old_var_info_list.remove(item)
        else:
            var_list[prop_name] = {'data': prop_info,
                                   'candidates': [var_info.copy()]}
        return var_list

    def _get_parents(self, file_name, class_name, real_class_name):
        key = '{}::{}'.format(file_name, class_name)
        parents = []
        for parent in self.class_hierarchy[key]['PARENTS']:
            if parent['TYPE'] == 'CLASS':
                parents.append(parent)
            if parent['NAME'] == real_class_name:
                break
        return parents

    def _find_prop_info(self, file_name, class_name, prop_name):
        key = '{}::{}'.format(file_name, class_name)
        for parent in self.class_hierarchy[key]['PARENTS']:
            if parent['FILE'] == INTERNAL:
                continue
            p_file = self.target_file_list[parent['FILE']]
            p_class = p_file.class_list[parent['NAME']]

            prop_list = p_class.prop_list
            for prop, prop_info in prop_list.items():
                if prop == prop_name:
                    prop_info = {'name': prop,
                                 'visibility': prop_info.visibility,
                                 'class': class_name,
                                 'real_class': prop_info.real_class,
                                 'file': file_name,
                                 'real_file': prop_info.real_file,
                                 'parents': self._get_parents(file_name,
                                                              class_name,
                                                        prop_info.real_class)}
                    return prop_info
        return None

    def get_variable(self, chain, target_classes):
        chain_info = {}
        chain_info['gadget_info'] = copy.deepcopy(chain)
        prop_candidates = {}
        method_candidates = {}
        foreach_candidates = {}

        for i, (gadget, gadget_info) in enumerate(zip(chain, chain_info['gadget_info'])):
            var_list = {}

            real_file_name = gadget['real_file']
            real_class_name = gadget['real_class']
            file_name = gadget['file']
            class_name = gadget['class']
            method_name = gadget['method']

            if class_name == '' and real_class_name == '':
                gadget_info['var_list'] = var_list
                continue

            _file = self.target_file_list[file_name]
            _class = _file.class_list[class_name]

            key = '{}::{}'.format(file_name, class_name)
            gadget_info['prop_list'] = []
            for parent in self.class_hierarchy[key]['PARENTS']:
                if parent['FILE'] == INTERNAL:
                    continue
                p_file = self.target_file_list[parent['FILE']]
                p_class = p_file.class_list[parent['NAME']]

                prop_list = p_class.prop_list
                for prop, prop_info in prop_list.items():
                    new_prop_info = {'name': prop,
                                     'visibility': prop_info.visibility,
                                     'class': class_name,
                                     'real_class': prop_info.real_class,
                                     'file': file_name,
                                     'real_file': prop_info.real_file,
                                     'parents': self._get_parents(file_name,
                                                                  class_name,
                                                        prop_info.real_class)}
                    if new_prop_info not in gadget_info['prop_list']:
                        gadget_info['prop_list'].append(new_prop_info)

            _file = self.target_file_list[real_file_name]
            _class = _file.class_list[real_class_name]
            _method = _class.method_list[method_name]
            this_prop_list = _class.prop_list

            for var in _method.var_list:
                prop_name = var['VAR']
                if prop_name.startswith('$this->'):
                    if prop_name in var_list:
                        continue

                    prop_list = prop_name.split('->')
                    rev_prop_list = prop_list[:0:-1]
                    class_candidates = []

                    for i, prop in enumerate(rev_prop_list):
                        if prop.startswith('$'):
                            continue
                        if prop.startswith('{$'):
                            continue

                        if '[' in prop:
                            new_prop = prop[:prop.find('[')]
                            prop_list[prop_list.index(prop)] = new_prop
                            prop = new_prop
                        prop_full_name = '->'.join(prop_list[:prop_list.index(prop)+1])

                        if i == 0: # property
                            var_info = {}
                            var_info['type'] = 'Unknown'
                            if len(prop_list) == i+2:   # $this->prop
                                prop_info = self._find_prop_info(file_name,
                                                                 class_name,
                                                                 prop)
                                if prop_info is None:
                                    prop_info = {'name': prop,
                                                 'visibility': 'public',
                                                 'class': class_name,
                                                 'real_class': class_name,
                                                 'file': file_name,
                                                 'real_file': file_name,
                                                 'parents': self._get_parents(
                                                                    file_name,
                                                                    class_name,
                                                                    class_name)}
                                var_info['value'] = {'file': file_name,
                                                    'class': class_name}
                                var_info['visibility'] = \
                                            self._get_visibility(
                                                                this_prop_list,
                                                                prop)
                                # print ("Case 1: $this->prop")
                                # print ("{}: {} / {}".format(prop_full_name,
                                                            # prop_info, var_info))
                                var_list = self._add_var_list(var_list,
                                                              prop_full_name,
                                                              prop_info,
                                                              var_info)
                            else:   # $this->...->prop
                                candidates = self._find_prop(target_classes,
                                                             prop)
                                for cand in candidates:
                                    prop_info = self._find_prop_info(
                                                                cand['file'],
                                                                cand['class'],
                                                                prop)
                                    class_info = {'file': cand['file'],
                                                  'class': cand['class']}
                                    var_info['value'] = class_info
                                    var_info['visibility'] = cand['visibility']
                                    # print ("Case 2: $this->...->prop")
                                    # print ("{}: {} / {}".format(prop_full_name,
                                    # prop_info, var_info))
                                    var_list = self._add_var_list(var_list,
                                                                prop_full_name,
                                                                prop_info,
                                                                var_info)
                                    class_candidates.append(class_info)

                        else: # object
                            var_info = {}
                            var_info['type'] = 'Object'
                            right_prop = rev_prop_list[i-1]
                            var_info['prop'] = right_prop
                            for cand in class_candidates:
                                var_info['value'] = cand
                                if len(prop_list) == i+2:   # $this->prop->...
                                    prop_info = self._find_prop_info(file_name,
                                                                     class_name,
                                                                     prop)
                                    if prop_info is None:
                                        prop_info = {'name': prop,
                                                    'visibility': 'public',
                                                    'class': class_name,
                                                    'real_class': class_name,
                                                    'file': file_name,
                                                    'real_file': file_name,
                                                    'parents':
                                                            self._get_parents(
                                                                    file_name,
                                                                    class_name,
                                                                    class_name)}
                                    var_info['visibility'] = \
                                                self._get_visibility(
                                                                this_prop_list,
                                                                prop)
                                    # print ("Case 3: $this->prop->...")
                                    # print ("{}: {} / {}".format(prop_full_name,
                                    # prop_info, var_info))
                                    var_list = self._add_var_list(var_list,
                                                                prop_full_name,
                                                                prop_info,
                                                                var_info)
                                else:   # $this->...->prop->...
                                    new_class_candidates = []
                                    prop_cand = self._find_prop(target_classes,
                                                                prop)
                                    for p_cand in prop_cand:
                                        prop_info = self._find_prop_info(
                                                                p_cand['file'],
                                                                p_cand['class'],
                                                                prop)
                                        var_info['visibility'] = \
                                                            p_cand['visibility']
                                        # print ("Case 4: $this->...->prop->...")
                                        # print ("{}: {} / {}".format(prop_full_name,
                                        # prop_info, var_info))
                                        var_list = self._add_var_list(var_list,
                                                                prop_full_name,
                                                                prop_info,
                                                                var_info)
                                        new_class_candidates.append({
                                                    'file': p_cand['file'],
                                                    'class': p_cand['class']})
                                    class_candidates = new_class_candidates

                        if 'foreach' in gadget:
                            # print (gadget['foreach'])
                            expr = gadget['foreach']['expr']
                            if prop_full_name == expr:
                                var_info = {}
                                var_info['type'] = 'Object'
                                prop_info = self._find_prop_info(file_name,
                                                                 class_name,
                                                                 prop)
                                var_info['value'] = {'file': gadget['foreach']['file'],
                                                    'class': gadget['foreach']['class']}
                                var_info['visibility'] = self._get_visibility(
                                                             this_prop_list, prop)
                                # print (var_list)
                                var_list = self._add_var_list(var_list,
                                                              prop_full_name,
                                                              prop_info,
                                                              var_info)
                                # print (var_list)

                        if 'array_access' in gadget:
                            expr = gadget['array_access']['expr']
                            if prop_full_name == expr:
                                var_info = {}
                                var_info['type'] = 'Object'
                                prop_info = self._find_prop_info(file_name,
                                                                 class_name,
                                                                 prop)
                                var_info['value'] = {
                                                'file': gadget['array_access']['file'],
                                                'class': gadget['array_access']['class']}
                                var_info['visibility'] = self._get_visibility(
                                                            this_prop_list, prop)
                                # print (var_list)
                                var_list = self._add_var_list(var_list,
                                                              prop_full_name,
                                                              prop_info,
                                                              var_info)
                                # print (var_list)

                        if 'implicit' in gadget:
                            next_gadget = chain[i+1]
                            expr = gadget['implicit']['ARGS'][0] # [FIXME] arguments
                            if prop_full_name == expr:
                                var_info = {}
                                var_info['type'] = 'Object'
                                prop_info = self._find_prop_info(file_name,
                                                                 class_name,
                                                                 prop)
                                var_info['value'] = {'file': next_gadget['file'],
                                                    'class': next_gadget['class']}
                                var_info['visibility'] = self._get_visibility(
                                                            this_prop_list, prop)
                                # print (var_list)
                                var_list = self._add_var_list(var_list,
                                                              prop_full_name,
                                                              prop_info,
                                                              var_info)
                                # print (var_list)

                else:
                    prop_list = prop_name.split('->')
                    rev_prop_list = prop_list[:0:-1]
                    for i, prop in enumerate(rev_prop_list):
                        candidates = self._find_prop(target_classes, prop)
                        for cand in candidates:
                            if prop in prop_candidates.keys():
                                if cand not in prop_candidates[prop]:
                                    prop_candidates[prop].append(cand)
                            else:
                                prop_candidates[prop] = [cand]

            call_list = _method.call_list
            for call in call_list:
                if call['TYPE'] == "MethodCall":
                    func_class = call["CLASS"]
                    func_name = call['FUNCTION']
                    if call['ARGS'] is not None:
                        arg_cnt = len(call['ARGS'])
                    else:
                        arg_cnt = 0
                    if func_class.startswith('$this->'):  # $this->...->method()
                        if '(' in func_class:
                            continue
                        if '[' in func_class:
                            func_class = func_class[:func_class.find('[')]
                        var_info = {}
                        var_info['type'] = 'Object'
                        var_info['method'] = func_name
                        candidates = self._find_method(target_classes,
                                                       func_name, arg_cnt)
                        # print ('candidates')
                        # print (candidates)
                        for cand in candidates:
                            if func_class in var_list:
                                prop_info = var_list[func_class]['data']
                            else:
                                prop_info = {'name': func_class.split('->')[-1],
                                             'visibility': '',
                                             'class': '',
                                             'real_class': '',
                                             'file': '',
                                             'real_file': '',
                                             'parents': self._get_parents(
                                                                    file_name,
                                                                    class_name,
                                                                    class_name)}
                            class_info = {'file': cand['file'],
                                          'class': cand['class']}
                            var_info['value'] = class_info
                            var_info['visibility'] = cand['visibility']
                            # print ("Case 5: $this->...->method()")
                            # print ("{}: {} / {}".format(func_class, prop_info, var_info))
                            var_list = self._add_var_list(var_list, func_class,
                                                          prop_info, var_info)
                    elif func_class != '$this':
                        candidates = self._find_method(target_classes,
                                                       func_name, arg_cnt)
                        for cand in candidates:
                            if func_name in method_candidates.keys():
                                if cand not in method_candidates[func_name]:
                                    method_candidates[func_name].append(cand)
                            else:
                                method_candidates[func_name] = [cand]
            gadget_info['var_list'] = var_list

            for expr, key_value in _method.for_list.items():
                if expr.startswith('$this->'):
                    expr = expr[len('$this->'):]
                    for item in key_value:
                        if item:
                            for call in _method.call_list:
                                if call['TYPE'] == "MethodCall":
                                    func_class = call["CLASS"]
                                    func_name = call['FUNCTION']
                                    if call['ARGS'] is not None:
                                        arg_cnt = len(call['ARGS'])
                                    else:
                                        arg_cnt = 0
                                    if func_class == item:
                                        candidates = self._find_method(target_classes,
                                                                       func_name, arg_cnt)
                                        for cand in candidates:
                                            cand['method'] = func_name

                                            key = '{}::{}'.format(cand['file'],
                                                                  cand['class'])
                                            cand['prop_list'] = []
                                            for parent in \
                                                self.class_hierarchy[key]['PARENTS']:
                                                if parent['FILE'] == INTERNAL:
                                                    continue
                                                p_file = \
                                                    self.target_file_list[parent['FILE']]
                                                p_class = \
                                                    p_file.class_list[parent['NAME']]

                                                prop_list = p_class.prop_list
                                                for prop, prop_info in prop_list.items():
                                                    new_prop_info = {
                                                      'name': prop,
                                                      'visibility': prop_info.visibility,
                                                      'class': cand['class'],
                                                      'real_class': prop_info.real_class,
                                                      'file': cand['file'],
                                                      'real_file': prop_info.real_file,
                                                      'parents': self._get_parents(
                                                                    cand['file'],
                                                                    cand['class'],
                                                                    prop_info.real_class
                                                                )
                                                    }
                                                    if new_prop_info not in \
                                                       cand['prop_list']:
                                                        cand['prop_list'].append(
                                                                            new_prop_info)
                                            if expr in foreach_candidates:
                                                if cand not in foreach_candidates[expr]:
                                                    foreach_candidates[expr].append(cand)
                                            else:
                                                foreach_candidates[expr] = [cand]

        chain_info['prop_candidates'] = prop_candidates
        chain_info['method_candidates'] = method_candidates
        chain_info['foreach_candidates'] = foreach_candidates

        return chain_info

    def get_global_vars(self, global_vars):
        def convert(var, indent):
            if var is None:
                return 'NULL'
            elif type(var) == str:
                var = var.replace("'", "\\'")
                return '\'{}\''.format(var)
            elif type(var) == list:
                return convert_arr(var, indent)
            elif type(var) == dict:
                return convert_dict(var, indent)
            else:
                return '{}'.format(var)

        def convert_dict(var, indent):
            arr = 'array(\n'
            for k, v in var.items():
                arr += '  ' * indent
                arr += '\'{}\''.format(k)
                arr += ' => '
                arr += convert(v, indent+1)
                arr += ',\n'
            arr += '  ' * (indent-1)
            arr += ')'
            return arr

        def convert_arr(var, indent):
            arr = '['
            for v in var:
                arr += convert(v, indent+1)
                arr += ', '
            arr += ']'
            return arr

        code = ''
        for key, var in global_vars.items():
            if '-' in key:
                key = key.replace('-', '_')
            code += '${} = '.format(key)
            code += convert(var, 1)
            code += ';\n'

        return code

    def get_constants(self, constants):
        code = ''
        if constants is not None:
            for k, v in constants.items():
                if k == 'STDOUT': # STDOUT already defined.
                    continue

                if type(v) == str:
                    v = v.replace('\'','\\\'')
                    code += 'define(\'{}\', \'{}\');\n'.format(k, v)
                else:
                    if v is None:
                        v = 'NULL'
                    code += 'define(\'{}\', {});\n'.format(k, v)
        return code

    def _encode_php(self, code):
        new_code = '<?php\n'
        new_code += code
        new_code += '?>\n'
        return new_code.encode()

    def generate_PUT(self, user_classes, declared_classes, declared_interfaces,
                     declared_traits, global_vars, constants, user_functions,
                     declared_functions, trigger_time, class_alias, all_files):
        print ("[*] Generate PUT")
        fuzz_dir = '%s/%s.%s/PUT/' %(FUZZ_DIR,
                                    self.target.replace('/', '.')[1:],
                                    trigger_time.strftime('%y%m%d%H%M%S'))
        if not os.path.isdir(fuzz_dir):
            os.makedirs(fuzz_dir)

        class_alias_list = {}
        for original, alias in class_alias:
            class_alias_list[alias.lower()] = original.lower()

        put_head_filepath = fuzz_dir + 'put-head.php'
        put_body_filepath = fuzz_dir + 'put-body.php'
        put_filepath = fuzz_dir + '{}-{}.php'
        put_file_list = []

        put_head_code = ''
        put_head_code += 'namespace {\n'
        put_head_code += self.get_global_vars(global_vars)
        put_head_code += self.get_constants(constants)

        def write_code(item_name, item_info, cls=True, write=True):
            if cls:
                f_name = put_filepath.format('class', item_name.lower())
            else:
                f_name = put_filepath.format('func', item_name.lower())
            inc_code = ''
            if cls:
                inc_code += 'namespace {\n'
                parents = item_info.parents[1:] + item_info.implements[1:]
                for p in parents:
                    if p['FILE'] == INTERNAL:
                        continue
                    inc_code += 'include_once "{}";\n'.format(
                                    put_filepath.format('class',
                                    p['NAME'].lower().replace('\\', '\\\\')))

                traits = item_info.traits
                for trait_info in traits:
                    for t in trait_info['TRAITS']:
                        if t['FILE'] == INTERNAL:
                            continue
                        inc_code += 'include_once "{}";\n'.format(
                                    put_filepath.format('class',
                                    t['NAME'].lower().replace('\\', '\\\\')))

                if item_name in class_alias_list:
                    org_class_name = class_alias_list[item_name]
                    if org_class_name.startswith('\\'):
                        org_class_name = org_class_name[1:]
                    inc_code += 'include_once "{}";\n'.format(
                                put_filepath.format('class',
                                org_class_name.lower().replace('\\', '\\\\')))
                inc_code += '}\n'
            if write:
                with open(f_name, 'wb') as f:
                    f.write(self._encode_php(inc_code + item_info.code + '\n'))
                    put_file_list.append(f_name)
            else:
                return inc_code + item_info.code + '\n'

        redeclared_function = {}
        redeclared_class = {}

        # Get function
        function_list = []
        for func_name, func_info in user_functions.items():
            if func_name.lower() in declared_functions['internal']:
                print ('[#] Internal function: {}'.format(func_name))
                function_list.append(func_name.lower())

            elif not re.match(EXCLUDED_FUNCTIONS_REGEX, func_name):
                func_file = func_info['FILE']

                if func_file in self.target_file_list:
                    _file = self.target_file_list[func_file]

                    for def_func_name, def_func_info in _file.func_list.items():
                        if def_func_name.lower() == func_name:
                            write_code(def_func_name, def_func_info, cls=False)
                            function_list.append(def_func_name.lower())

        if all_files:
            autoload_function_list = {}
            redeclared_function_list = []
            for file_name in self.target_file_list:
                _file = self.target_file_list[file_name]
                for func_name, func_info in _file.func_list.items():
                    if func_name.lower() in function_list:
                        continue
                    elif func_name.lower() in declared_functions['internal']:
                        print ('[#] Internal function: {}'.format(func_name))
                        function_list.append(func_name.lower())
                    elif func_name.lower() in autoload_function_list and \
                         file_name not in \
                                    autoload_function_list[func_name.lower()]:
                        autoload_function_list[func_name.lower()].append(
                                                                    file_name)
                        redeclared_function_list.append(func_name.lower())
                        print ('[#] Redeclared function: {}'.format(func_name))
                    elif func_name.lower() in autoload_function_list and \
                         file_name in autoload_function_list[func_name.lower()]:
                        continue
                    else:
                        autoload_function_list[func_name.lower()] = [file_name]

            for file_name in self.target_file_list:
                _file = self.target_file_list[file_name]
                for func_name, func_info in _file.func_list.items():
                    if func_name.lower() in redeclared_function_list:
                        code_info = {'name': func_info.real_name,
                                     'namespace': func_info.namespace,
                                     'code': func_info.code + '\n',
                                     'uses': func_info.uses}
                        if func_name.lower() not in redeclared_function:
                            redeclared_function[func_name.lower()] = []
                        if code_info not in \
                                        redeclared_function[func_name.lower()]:
                            redeclared_function[func_name.lower()].append(
                                                                    code_info)
                    elif func_name.lower() in autoload_function_list:
                        write_code(func_name, func_info, cls=False)
                        function_list.append(func_name.lower())

        # Get Class, Interface
        class_list = []

        declared_flag = False

        # If PHP returns an empty array, Python treats it as a list.
        if isinstance(user_classes, list):
            user_classes = {}

        for class_name, class_info in user_classes.items():
            if not re.match(EXCLUDED_CLASSES_REGEX, class_name) and \
               class_info['FILE_NAME'].startswith(self.target):
                key = '{}::{}'.format(class_info['FILE_NAME'], class_name)
                tmp_list = copy.deepcopy(self.class_hierarchy[key]['IMPLEMENTS'])

                while len(tmp_list) > 0:
                    implement = tmp_list.pop(0)
                    implement_name = implement['NAME']
                    implement_file = implement['FILE']
                    implement_type = implement['TYPE']

                    if implement_type == 'CLASS':
                        continue

                    if implement_name.lower() not in class_list:
                        if implement_file == INTERNAL:
                            print ('[#] Internal interface: {}'.format(
                                                                implement_name))
                            class_list.append(implement_name.lower())
                        else:
                            all_declared = True
                            i_key = '{}::{}'.format(implement_file,
                                                    implement_name)
                            for sub in \
                                self.class_hierarchy[i_key]['IMPLEMENTS'][1:]:
                                if sub['NAME'].lower() not in class_list:
                                    all_declared = False
                            if all_declared == False:
                                tmp_list.append(implement)
                                continue

                            i_file = self.target_file_list[implement_file]
                            i_class = i_file.class_list[implement_name]
                            if i_class.code is None:
                                print ('[#] No code: {}({})'.format(
                                                                implement_name,
                                                                implement_file))
                            else:
                                write_code(implement_name, i_class)
                                class_list.append(implement_name.lower())

        for class_name, class_info in user_classes.items():
            if not re.match(EXCLUDED_CLASSES_REGEX, class_name) and \
               class_info['FILE_NAME'].startswith(self.target):
                key = '{}::{}'.format(class_info['FILE_NAME'], class_name)
                parents = self.class_hierarchy[key]['PARENTS']

                for parent in parents[::-1]:
                    parent_name = parent['NAME']
                    parent_file = parent['FILE']
                    parent_type = parent['TYPE']

                    if parent_type == 'INTERFACE':
                        continue

                    if parent_name.lower() not in class_list:
                        if parent_file == INTERNAL:
                            print ('[#] Internal class: {}'.format(parent_name))
                            class_list.append(parent_name.lower())
                        else:
                            p_file = self.target_file_list[parent_file]
                            p_class = p_file.class_list[parent_name]
                            if p_class.code is None:
                                print ('[#] No code: {}({})'.format(parent_name,
                                                                parent_file))
                            else:
                                traits = p_class.traits
                                for trait_info in traits:
                                    for trait in trait_info['TRAITS']:
                                        trait_name = trait['NAME']
                                        trait_file = trait['FILE']
                                        if trait_name.lower() not in class_list:
                                            if trait_file == INTERNAL:
                                                print ('[#] Internal trait: \
                                                        {}'.format(trait_name))
                                                class_list.append(
                                                            trait_name.lower())
                                            else:
                                                t_file = \
                                                self.target_file_list[trait_file]
                                                t_class = \
                                                t_file.class_list[trait_name]
                                                if t_class.code is None:
                                                    print ('[#] No code: \
                                                        {}({})'.format(
                                                                    trait_name,
                                                                    trait_file))
                                                else:
                                                    write_code(trait_name,
                                                               t_class)
                                                    class_list.append(
                                                            trait_name.lower())

                                write_code(parent_name, p_class)
                                class_list.append(parent_name.lower())

                if class_name.lower() not in class_list:
                    file_name = class_info['FILE_NAME']
                    if 'eval()' in file_name:
                        file_name = EVAL_FILE
                    _file = self.target_file_list[file_name]
                    _class = _file.class_list[class_name]
                    if _class.code is None:
                        print ('[#] No code: {}({})'.format(class_name,
                                                            file_name))
                    else:
                        write_code(class_name, _class)
                        class_list.append(class_name.lower())

        if all_files:
            autoload_class_list = {}
            redeclared_class_list = []

            for key in sorted(self.class_hierarchy,
                              key=lambda k: \
                                len(self.class_hierarchy[k]['IMPLEMENTS'])):

                implements = self.class_hierarchy[key]['IMPLEMENTS']
                class_name = implements[0]['NAME']
                class_file = implements[0]['FILE']

                if class_name.lower() in self.undefined_class:
                    continue
                if class_file == INTERNAL:
                    continue
                for dec_class in declared_classes:
                    if class_name.lower() == dec_class.lower() and \
                       declared_classes[dec_class]['INTERNAL']:
                            print ("[#] Declared class {}".format(class_name))
                            class_list.append(class_name.lower())
                            declared_flag = True
                            break

                if declared_flag:
                    declared_flag = False
                    continue

                for implement in implements[::-1]:
                    implement_name = implement['NAME']
                    implement_file = implement['FILE']
                    implement_type = implement['TYPE']
                    if implement_type == 'CLASS':
                        continue

                    if implement_name.lower() not in class_list:
                        if implement_file == INTERNAL:
                            print ('[#] Internal interface: {}'.format(
                                                                implement_name))
                            class_list.append(implement_name.lower())
                        elif implement_name.lower() in autoload_class_list and \
                             implement_file not in \
                                    autoload_class_list[implement_name.lower()]:
                            redeclared_class_list.append(implement_name.lower())
                            print ('[#] Redeclared interface: {}'.format(
                                                                implement_name))
                        elif implement_name.lower() in autoload_class_list and \
                            implement_file in \
                                    autoload_class_list[implement_name.lower()]:
                            continue
                        else:
                            autoload_class_list[implement_name.lower()] = \
                                                                [implement_file]

            for key in sorted(self.class_hierarchy,
                              key=lambda k: \
                                    len(self.class_hierarchy[k]['PARENTS'])):

                parents = self.class_hierarchy[key]['PARENTS']
                class_name = parents[0]['NAME']
                class_file = parents[0]['FILE']

                if class_name.lower() in self.undefined_class:
                    continue
                if class_file == INTERNAL:
                    continue
                for dec_class in declared_classes:
                    if class_name.lower() == dec_class.lower() and \
                       declared_classes[dec_class]['INTERNAL']:
                            print ("[#] Declared class {}".format(class_name))
                            class_list.append(class_name.lower())
                            declared_flag = True
                            break

                if declared_flag:
                    declared_flag = False
                    continue

                for parent in parents[::-1]:
                    parent_name = parent['NAME']
                    parent_file = parent['FILE']
                    parent_type = parent['TYPE']
                    if parent_type == 'INTERFACE':
                        continue

                    if parent_name.lower() not in class_list:
                        if parent_file == INTERNAL:
                            print ('[#] Internal class: {}'.format(parent_name))
                            class_list.append(parent_name.lower())
                        elif parent_name.lower() in autoload_class_list and \
                             parent_file not in \
                                    autoload_class_list[parent_name.lower()]:
                            redeclared_class_list.append(parent_name.lower())
                            print ('[#] Redeclared class: {}'.format(
                                                                parent_name))
                        elif parent_name.lower() in autoload_class_list and \
                             parent_file in \
                                    autoload_class_list[parent_name.lower()]:
                            continue
                        else:
                            autoload_class_list[parent_name.lower()] = \
                                                                [parent_file]

            old_len = -1
            new_len = len(redeclared_class_list)
            while old_len != new_len:
                old_len = new_len
                for key, value in self.class_hierarchy.items():
                    parents = value['PARENTS'] + value['IMPLEMENTS'][1:]
                    class_name = parents[0]['NAME']
                    class_type = parents[0]['TYPE']
                    for parent in parents:
                        parent_name = parent['NAME']
                        if parent_name.lower() in redeclared_class_list:
                            if class_name.lower() not in redeclared_class_list:
                                redeclared_class_list.append(class_name.lower())
                new_len = len(redeclared_class_list)

            for key in sorted(self.class_hierarchy,
                              key=lambda k: \
                                    len(self.class_hierarchy[k]['IMPLEMENTS'])):
                tmp_list = copy.deepcopy(
                                        self.class_hierarchy[key]['IMPLEMENTS'])
                class_name = tmp_list[0]['NAME']
                class_file = tmp_list[0]['FILE']

                if class_name.lower() in self.undefined_class:
                    continue
                if class_file == INTERNAL:
                    continue
                for dec_class in declared_classes:
                    if class_name.lower() == dec_class.lower() and \
                       declared_classes[dec_class]['INTERNAL']:
                            print ("[#] Declared class {}".format(class_name))
                            class_list.append(class_name.lower())
                            declared_flag = True
                            break

                if declared_flag:
                    declared_flag = False
                    continue

                while len(tmp_list) > 0:
                    implement = tmp_list.pop(0)
                    implement_name = implement['NAME']
                    implement_file = implement['FILE']
                    implement_type = implement['TYPE']
                    if implement_type == 'CLASS':
                        continue

                    if implement_name.lower() not in class_list:
                        i_file = self.target_file_list[implement_file]
                        i_class = i_file.class_list[implement_name]

                        if i_class.code is None:
                            print ('[#] No code: {}({})'.format(implement_name,
                                                                implement_file))

                        elif implement_name.lower() in redeclared_class_list:
                            new_code = write_code(implement_name,
                                                 i_class, write=False)
                            code_info = {'name': i_class.real_name,
                                         'namespace': i_class.namespace,
                                         'inc_code': new_code,
                                         'code': i_class.code,
                                         'uses': i_class.uses}
                            if implement_name.lower() not in redeclared_class:
                                redeclared_class[implement_name.lower()] = []
                            if code_info not in \
                                    redeclared_class[implement_name.lower()]:
                                redeclared_class[implement_name.lower()].append(
                                                                    code_info)

                        elif implement_name.lower() in autoload_class_list:
                            all_declared = True
                            i_key = '{}::{}'.format(implement_file,
                                                    implement_name)
                            for sub in \
                                self.class_hierarchy[i_key]['IMPLEMENTS'][1:]:
                                if sub['NAME'].lower() not in class_list:
                                    all_declared = False
                            if all_declared == False:
                                tmp_list.append(implement)
                                continue
                            write_code(implement_name, i_class)
                            class_list.append(implement_name.lower())

            for key in sorted(self.class_hierarchy,
                              key=lambda k: \
                                    len(self.class_hierarchy[k]['PARENTS'])):

                parents = self.class_hierarchy[key]['PARENTS']
                class_name = parents[0]['NAME']
                class_file = parents[0]['FILE']

                if class_name.lower() in self.undefined_class:
                    continue
                if class_file == INTERNAL:
                    continue
                for dec_class in declared_classes:
                    if class_name.lower() == dec_class.lower() and \
                       declared_classes[dec_class]['INTERNAL']:
                            print ("[#] Declared class {}".format(class_name))
                            class_list.append(class_name.lower())
                            declared_flag = True
                            break

                if declared_flag:
                    declared_flag = False
                    continue

                for parent in parents[::-1]:
                    parent_name = parent['NAME']
                    parent_file = parent['FILE']
                    parent_type = parent['TYPE']

                    if parent_type == 'INTERFACE':
                        continue

                    if parent_name.lower() not in class_list:
                        p_file = self.target_file_list[parent_file]
                        p_class = p_file.class_list[parent_name]

                        if p_class.code is None:
                            print ('[#] No code: {}({})'.format(
                                                parent_name, parent_file))

                        elif parent_name.lower() in redeclared_class_list:
                            new_code = write_code(parent_name,
                                                  p_class, write=False)
                            code_info = {'name': p_class.real_name,
                                         'namespace': p_class.namespace,
                                         'inc_code': new_code,
                                         'code': p_class.code,
                                         'uses': p_class.uses}
                            if parent_name.lower() not in redeclared_class:
                                redeclared_class[parent_name.lower()] = []
                            if code_info not in \
                                        redeclared_class[parent_name.lower()]:
                                redeclared_class[parent_name.lower()].append(
                                                                    code_info)

                        elif parent_name.lower() in autoload_class_list:
                            traits = p_class.traits
                            for trait_info in traits:
                                for trait in trait_info['TRAITS']:
                                    trait_name = trait['NAME']
                                    trait_file = trait['FILE']
                                    if trait_name.lower() not in class_list:
                                        if trait_file == INTERNAL:
                                            print ('[#] Internal trait: \
                                                    {}'.format(trait_name))
                                            class_list.append(
                                                        trait_name.lower())
                                        else:
                                            t_file = \
                                            self.target_file_list[trait_file]
                                            t_class = \
                                            t_file.class_list[trait_name]
                                            if t_class.code is None:
                                                print ('[#] No code: \
                                                        {}({})'.format(
                                                                    trait_name,
                                                                    trait_file))
                                            else:
                                                write_code(trait_name, t_class)
                                                class_list.append(
                                                            trait_name.lower())

                            write_code(parent_name, p_class)
                            class_list.append(parent_name.lower())

        put_head_code += '$REDECLARED_FUNCIONS = array();\n'
        put_head_code += '$REDECLARED_CLASSES = array();\n'

        for func_name, func_code_list in redeclared_function.items():
            # Include identically defined function
            if len(func_code_list) == 1:
                with open(put_filepath.format('func',
                                              func_name.lower()), 'wb') as f:
                    f.write(self._encode_php(func_code_list[0]['code'] + '\n'))
                    put_file_list.append(put_filepath.format('func',
                                                             func_name.lower()))
            else:
                put_head_code += '$REDECLARED_FUNCIONS[\'{}\'] = {};\n'.format(
                                                                    func_name,
                                                            len(func_code_list))
                for idx, func_code in enumerate(func_code_list):
                    filepath = fuzz_dir + 'redec_func-{}_{}.php'.format(
                                                                    func_name,
                                                                    idx)
                    with open(filepath, 'wb') as f:
                        f.write(self._encode_php(func_code['code']))

        exclude_class_list = []
        for class_name, class_code_list in redeclared_class.items():
            if len(class_code_list) == 1:
                exclude_class_list.append(class_name.lower())

        new_exclude_class_list = []
        for target_class_name in exclude_class_list:
            for key, value in self.class_hierarchy.items():
                parents = value['PARENTS'] + value['IMPLEMENTS'][1:]
                class_name = parents[0]['NAME']
                if target_class_name == class_name.lower():
                    flag = True
                    for parent in parents:
                        parent_name = parent['NAME']
                        if parent_name.lower() not in redeclared_class:
                            continue
                        elif parent_name.lower() in redeclared_class and \
                             parent_name.lower() in exclude_class_list:
                            continue
                        else:
                            flag = False
                    if flag:
                        new_exclude_class_list.append(class_name.lower())

        complete_exclude_class_list = copy.deepcopy(new_exclude_class_list)
        for target_class_name in new_exclude_class_list:
            for key, value in self.class_hierarchy.items():
                parents = value['PARENTS'] + value['IMPLEMENTS'][1:]
                class_name = parents[0]['NAME']
                if target_class_name == class_name.lower():
                    for parent in parents:
                        parent_name = parent['NAME']
                        if parent_name.lower() not in redeclared_class:
                            continue
                        elif parent_name.lower() in redeclared_class and \
                             parent_name.lower() not in new_exclude_class_list:
                                complete_exclude_class_list.remove(
                                                            target_class_name)
                                break

        for class_name, class_code_list in redeclared_class.items():
            if class_name in complete_exclude_class_list:
                with open(put_filepath.format('class',
                                              class_name.lower()), 'wb') as f:

                    f.write(self._encode_php(class_code_list[0]['inc_code'] + \
                                             '\n'))
                    put_file_list.append(put_filepath.format('class',
                                                            class_name.lower()))
            else:
                put_head_code += '$REDECLARED_CLASSES[\'{}\'] = {};\n'.format(
                                                                    class_name,
                                                        len(class_code_list))
                for idx, class_code in enumerate(class_code_list):
                    filepath = fuzz_dir + 'redec_class-{}_{}.php'.format(
                                                                class_name, idx)
                    with open(filepath, 'wb') as f:
                        code = 'namespace {}\n' + class_code['code']
                        f.write(self._encode_php(code))

        put_head_code += '}\n'

        with open(put_head_filepath, 'wb') as f:
            f.write(self._encode_php(put_head_code))

        with open(put_body_filepath, 'wb') as f:
            code = ''
            for f_name in put_file_list:
                code += 'include_once "{}";\n'.format(
                        f_name.replace('\\', '\\\\'))
            f.write(self._encode_php(code))

        print (put_head_filepath)
        print (put_body_filepath)
        return fuzz_dir

    def get_code(self, fuzz_dir, chain, chain_info):
        fuzz_code = dict()
        fuzz_code['var_list'] = chain_info
        fuzz_code['chain'] = chain
        fuzz_code = json.dumps(fuzz_code)
        return fuzz_code

    def dump_class_structure(self):
        for file_name in self.target_file_list.keys():
            print ('File: {}'.format(file_name))
            _file = self.target_file_list[file_name]
            for func_name, func_info in _file.func_list.items():
                print ('  Function: {}'.format(func_name))
                print ('    PARAMS: ')
                for param, default in func_info.param_list.items():
                    try:
                        print ('      - {} ({})'.format(param, default))
                    except UnicodeEncodeError:
                        print ('      - {} ({})'.format(param,
                                                    default.encode('utf-8')))
                print ('    CALLS: ')
                for call in func_info.call_list:
                    try:
                        print ('      - [{}] {} ({})'.format(call['TYPE'],
                                                         call['FUNCTION'],
                                                         call['ARGS']))
                    except:
                        print ('      - [{}] {}'.format(call['TYPE'],
                                                         call['FUNCTION']))
                print ('    TAINT_LIST: {}'.format(func_info.taint_list))

            for class_name, class_info in _file.class_list.items():
                print ('  Class: {}'.format(class_name))
                print ('    PARENTS: ')
                for parent in class_info.parents:
                    try:
                        print ('      - {} ({})'.format(parent['NAME'],
                                                        parent['FILE']))
                    except:
                        print ('      - {}'.format(parent))
                print ('    IMPLEMENTS: ')
                for implement in class_info.implements:
                    try:
                        print ('      - {} ({})'.format(implement['NAME'],
                                                        implement['FILE']))
                    except:
                        print ('      - {}'.format(implement))
                print ('    TRAITS: ')
                for trait in class_info.traits:
                    print ('      - {} ({})'.format(trait['TRAITS'],
                                                    trait['ADAPTIONS']))
                for method_name, method_info in class_info.method_list.items():
                    print ('    Method: {} ({})'.format(method_name,
                                                        method_info.real_name))
                    print ('      CLASS: {} ({})'.format(method_info.real_class,
                                                       method_info.real_file))
                    print ('      TYPE: {}'.format(method_info.type))
                    print ('      VISIBILITY: {}'.format(
                                                        method_info.visibility))
                    print ('      STATIC: {}'.format(method_info.static))
                    print ('      PARAMS: ')
                    for param, default in method_info.param_list.items():
                        try:
                            print ('        - {} ({})'.format(param, default))
                        except UnicodeEncodeError:
                            print ('        - {} ({})'.format(param,
                                                    default.encode('utf-8')))
                    print ('      CALLS: ')
                    for call in method_info.call_list:
                        try:
                            print ('        - [{}] {} ({})'.format(call['TYPE'],
                                                            call['FUNCTION'],
                                                            call['ARGS']))
                        except UnicodeEncodeError:
                            print ('        - [{}] {} ({})'.format(call['TYPE'],
                                    call['FUNCTION'],
                                    b''.join(arg.encode('utf-8') \
                                             for arg in call['ARGS'])))
                    print ('      VARS: ')
                    for var in method_info.var_list:
                        print ('        - [{}] {}'.format(var['TYPE'],
                                                          var['VAR']))
                    print ('      TAINT_LIST: {}'.format(method_info.taint_list))
                    print ('      FOR_LIST: {}'.format(method_info.for_list))
                    print ('      ARR_ACCESS_LIST: {}'.format(method_info.array_access_list))
                    print ('      STRING_LIST: {}'.format(method_info.string_list))
                for prop_name, prop_info in class_info.prop_list.items():
                    print ('    Prop: {}'.format(prop_name))
                    print ('      CLASS: {} ({})'.format(prop_info.real_class,
                                                       prop_info.real_file))
                    print ('      TYPE: {}'.format(prop_info.type))
                    print ('      VISIBILITY: {}'.format(prop_info.visibility))
                    print ('      STATIC: {}'.format(prop_info.static))
                    print ('      VALUE: ')
                    if prop_info.value:
                        if type(prop_info.value) == dict:
                            for var, value in prop_info.value.items():
                                print ('        - {} ({})'.format(
                                                    var.encode('utf-8'),
                                                    str(value).encode('utf-8')))
                        else:
                            value = str(prop_info.value)
                            print ('        - {}'.format(value.encode('utf-8')))
                if 'eval()\'d code' in file_name:
                    print ('      DECL: {}'.format(class_info.code))
