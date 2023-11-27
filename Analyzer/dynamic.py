import re

from . import *
from .Units import *

class DynamicAnalyzer:
    def __init__(self):
        pass

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

    def _get_visibility(self, value):
        if value & PUBLIC:
            return 'public'
        if value & PROTECTED:
            return 'protected'
        if value & PRIVATE:
            return 'private'
        else:
            return 'public'

    def _update_methods(self, file_name, _class, methods):
        for method_name, method_info in methods.items():
            # Get method
            if method_name not in _class.method_list.keys():
                _class.method_list[method_name] = Method(method_name)
            _method = _class.method_list[method_name]

            # Update method
            _method_info = method_info['METHOD_INFO']
            _method.type = self._get_type(_method_info['METHOD_VISIBILITY'])
            _method.static = _method_info['METHOD_STATIC']
            _method.visibility = self._get_visibility(_method_info['METHOD_VISIBILITY'])
            _method.real_class = _method_info['METHOD_CLASS']
            _method.real_file = _method_info['METHOD_FILE']
            _method.real_name = _method_info['METHOD_REAL_NAME']

            # Update parameters
            _param_list = method_info['PARAMS']
            for idx, _param in enumerate(_param_list):
                for param_name, param_info in _param.items():
                    _method.param_list[param_name] = {'INDEX': idx,
                                'DEFAULT': param_info['PARAM_DEFAULT_VALUE']}

    def _update_props(self, file_name, _class, properties):
        for prop_name, prop_info in properties.items():
            # Get prop
            if prop_name not in _class.prop_list.keys():
                _class.prop_list[prop_name] = Property(prop_name)
            _property = _class.prop_list[prop_name]

            # Update property
            _prop_info = prop_info['PROP_INFO']
            _property.type = self._get_type(_prop_info['PROP_VISIBILITY'])
            _property.static = _prop_info['PROP_STATIC']
            _property.value = _prop_info['PROP_DEFAULT_VALUE']
            _property.visibility = self._get_visibility(_prop_info['PROP_VISIBILITY'])
            _property.real_class = _prop_info['PROP_CLASS']
            _property.real_file = _prop_info['PROP_FILE']

    def update_info(self, user_classes, user_functions, declared_classes,
                    declared_interfaces, declared_traits, class_alias, target_file_list):
        class_alias_list = {}
        for original, alias in class_alias:
            class_alias_list[alias.lower()] = original.lower()

        target_class = {}
        target_function = {}

        for func_name, func_info in user_functions.items():
            if not re.match(EXCLUDED_FUNCTIONS_REGEX, func_name):
                func_file = func_info['FILE']

                if 'eval()\'d code' in func_file:
                    _file = target_file_list[EVAL_FILE]
                elif func_file in target_file_list:
                    _file = target_file_list[func_file]
                else:
                    print ('NO FUNC FILE: {} - {}'.format(func_file, func_name))
                    continue

                in_list = False
                for f_name in _file.func_list:
                    if f_name.lower() == func_name:
                        func_name = f_name
                        in_list = True
                        break
                if in_list == False:
                    _file.func_list[func_name] = Method(func_name)
                _func = _file.func_list[func_name]

                params = func_info['PARAMS']
                if len(params) > 0:
                    for idx, (param_name, param_info) in \
                                                    enumerate(params.items()):
                        _func.param_list[param_name] = {'INDEX': idx,
                                'DEFAULT': param_info['PARAM_DEFAULT_VALUE']}

                if 'eval()\'d code' in func_file:
                    if func_file not in target_file_list:
                        target_file_list[func_file] = File(func_file)
                    _file = target_file_list[func_file]
                    _file.func_list[func_name] = _func

                new_func_info = {}
                new_func_info['FILE_NAME'] = func_file
                target_function[func_name] = new_func_info

        # If PHP returns an empty array, Python treats it as a list.
        if isinstance(user_classes, list):
            user_classes = {}

        for class_name, class_info in user_classes.items():
            if not re.match(EXCLUDED_CLASSES_REGEX, class_name):
                file_name = class_info['FILE_NAME']
                parents = class_info['CLASS_PARENTS']
                interfaces = class_info['INTERFACES']
                traits = class_info['TRAITS']
                methods = class_info['METHODS']
                properties = class_info['PROPS']
                class_type = class_info['CLASS_TYPE']

                new_parents = []
                new_implements = []

                # Update interfaces
                for interface in interfaces:
                    implement_info = {}
                    implement_info['TYPE'] = 'INTERFACE'
                    implement_info['NAME'] = interface['NAME']
                    if interface['NAME'] in declared_interfaces:
                        if declared_interfaces[interface['NAME']]['INTERNAL']:
                            implement_info['FILE'] = INTERNAL
                        else:
                            implement_info['FILE'] = \
                                declared_interfaces[interface['NAME']]['FILE']
                    else:
                        if interface['FILE'] == False:
                            implement_info['FILE'] = None
                        else:
                            implement_info['FILE'] = interface['FILE']
                    new_implements.append(implement_info)

                # Update parents
                for parent in parents:
                    parent_info = {}
                    parent_info['TYPE'] = 'CLASS'
                    parent_info['NAME'] = parent['NAME']
                    if parent['NAME'] in declared_classes:
                        if declared_classes[parent['NAME']]['INTERNAL']:
                            parent_info['FILE'] = INTERNAL
                        else:
                            parent_info['FILE'] = \
                                declared_classes[parent['NAME']]['FILE']
                    else:
                        if parent['FILE'] == False:
                            parent_info['FILE'] = None
                        else:
                            parent_info['FILE'] = parent['FILE']
                    new_parents.append(parent_info)

                # Get File
                if 'eval()\'d code' in file_name:
                    _file = target_file_list[EVAL_FILE]
                elif file_name in target_file_list:
                    _file = target_file_list[file_name]
                else:
                    print ('NO CLASS FILE: {} - {}'.format(file_name, class_name))
                    continue

                # Get class
                if class_name not in _file.class_list.keys():
                    _file.class_list[class_name] = Class(class_name)
                _class = _file.class_list[class_name]
                _class.parents = new_parents
                _class.implements = new_implements
                _class.type = self._get_type(class_type)
                if class_name.lower() in class_alias_list:
                    original = class_alias_list[class_name.lower()]
                    _class.code = "namespace {"
                    _class.code += "class_alias('{}', '{}');".format(original,
                                                                     class_name)
                    _class.code += "}\n"
                elif "\\" + class_name.lower() in class_alias_list:
                    original = class_alias_list["\\" + class_name.lower()]
                    _class.code = "namespace {"
                    _class.code += "class_alias('{}', '{}');".format(original,
                                                                     "\\" + \
                                                                     class_name)
                    _class.code += "}\n"

                # Update traits
                new_traits = []
                for trait_info in _class.traits:
                    new_trait_list = []
                    for trait in trait_info['TRAITS']:
                        new_trait = {}
                        new_trait['TYPE'] = 'TRAIT'
                        new_trait['NAME'] = trait['NAME']
                        if trait['NAME'] in declared_traits:
                            if declared_traits[trait['NAME']]['INTERNAL']:
                                new_trait['FILE'] = INTERNAL
                            else:
                                new_trait['FILE'] = \
                                    declared_traits[trait['NAME']]['FILE']
                        else:
                            new_trait['FILE'] = ''
                        new_trait_list.append(new_trait)
                    new_traits.append({'TRAITS': new_trait_list,
                                       'ADAPTIONS': trait_info['ADAPTIONS']})

                _class.traits = new_traits

                # Update method & props
                if len(methods) > 0:
                    self._update_methods(file_name, _class, methods)
                if len(properties) > 0:
                    self._update_props(file_name, _class, properties)

                if 'eval()\'d code' in file_name:
                    if file_name not in target_file_list:
                        target_file_list[file_name] = File(file_name)
                    _file = target_file_list[file_name]
                    _file.class_list[class_name] = _class

                new_class_info = {}
                new_class_info['FILE_NAME'] = file_name
                new_class_info['CLASS_PARENTS'] = _class.parents
                new_class_info['INTERFACES'] = _class.implements
                new_class_info['TRITS'] = _class.traits
                new_class_info['METHODS'] = _class.method_list
                new_class_info['PROPS'] = _class.prop_list
                new_class_info['CLASS_TYPE'] = _class.type
                target_class[class_name] = new_class_info

        return target_class, target_function