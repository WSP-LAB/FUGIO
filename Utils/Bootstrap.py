import os
import subprocess
import json
import shutil

class Bootstrap():
    def __init__(self):
        pass

    def makePharValidator(self, doc_root):
        PHAR_GENERATOR = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__)), "../"), "Files/phar_generator/generator.php")
        PHAR_GEN_DIR = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__)), "../"), "Files/phar_generator/")
        ORG_PHAR_VALIDATOR = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__)), "../"), "Files/phar_generator/dummy_class_r353t.png")
        gen_dir = "{}/{}".format(doc_root, "phar_validator")
        gen_file = "{}/{}".format(gen_dir, "dummy_class_r353t.png")

        if(os.path.isdir(gen_dir) == False):
            os.mkdir(gen_dir)

        org_cwd = os.getcwd()
        os.chdir(PHAR_GEN_DIR)
        validator_md5 = subprocess.check_output(["php", PHAR_GENERATOR])
        os.chdir(org_cwd)
        shutil.move(ORG_PHAR_VALIDATOR, gen_file)
        return validator_md5.decode("utf-8")

    def makeHookFile(self, rabbitmq_ip, doc_root, validator_md5,
                     apply_extension, php_ver, class_list):
        if(doc_root[-1] == "/"):
            new_doc_root = doc_root[:-1]
        else:
            new_doc_root = doc_root
        PHAR_VALIDATOR = gen_dir = "{}/{}/{}".format(new_doc_root, "phar_validator", "dummy_class_r353t.png")

        SENSITIVE_FUNCTIONS_FILE = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__)), "../"), "Files/sensitive_functions_list.txt")
        SENSITIVE_FUNCTION_OUTPUT = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__)), "../"), "Files/hook_sensitive_functions.php")
        FILE_HOOK_HEADER = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__))), "HookFiles/HookHead.php")
        FUNC_PARSER = os.path.join(os.path.join(os.path.dirname(os.path.realpath(__file__)), "../"), "Files/FunctionParser.php")

        RESERVED_KEYWORDS = {
            "{{__POINT_PHAR_HASH__}}": validator_md5,
            "{{__POINT_RABBITMQ_IP__}}": rabbitmq_ip,
            "{{__POINT_RABBITMQ_PORT__}}": "5672",
            "{{__POINT_RABBITMQ_ID__}}": "fugio",
            "{{__POINT_RABBITMQ_PW__}}": "fugio_password",
            "{{__CLASS_LIST__}}": "', '".join(class_list)
        }

        # Init Hook File Contents
        hook_file_contents = ""

        # Hook Header
        hook_header_fd = open(FILE_HOOK_HEADER, "r")
        hook_file_header = ""
        while True:
            hook_header_line = hook_header_fd.readline()
            if not hook_header_line:
                break
            else:
                hook_file_header += hook_header_line
        hook_header_fd.close()
        for reserved_keyword, replace_keyword in RESERVED_KEYWORDS.items():
            hook_file_header = hook_file_header.replace(reserved_keyword, replace_keyword)

        # Hook Body
        func_names = ""
        hook_func_inject_point = dict()
        with open(SENSITIVE_FUNCTIONS_FILE,"r") as sf:
            for line in sf:
                func_name = line.split("|")[0]
                if(len(func_name) <= 1 or func_name[:1] == "#"):
                    continue
                inject_idxs = line.split("|")[1]
                hook_func_inject_point[func_name] = inject_idxs.strip()
                func_names += func_name + "|"
        func_names = func_names[:-1]
        parse_output = subprocess.check_output(["php", FUNC_PARSER, func_names])
        decoded_parse_output = json.loads(parse_output)

        hook_file_body = ""

        if(apply_extension == "runkit"):
          if(php_ver == 5):
            hook_file_body += ("runkit_function_copy('class_alias', 'override_class_alias');\n"
                               "runkit_function_redefine('class_alias', '$original, $alias, $autoload = TRUE',\n"
                               "'global $argv_list_r353t;\n"
                               "$alias_result = override_class_alias($original, $alias, $autoload);\n"
                               "if($alias_result){\n"
                               "   class_alias_logging($original, $alias);\n"
                               "}\n"
                               "return $alias_result;');\n")
            for func_name, func_infos in decoded_parse_output.items():
              argv_contents = ""
              for func_info in func_infos:
                argv_contents += "${}".format(func_info['name'])
                if func_info['option'] == True:
                    argv_contents += " = \"DEFAULT_VALUE_EXISTS\", "
                else:
                    argv_contents += ", "
              argv_contents = argv_contents[:-2]

              hook_file_body += ("\n"
                                 "runkit_function_copy('" + func_name + "', 'override_" + func_name + "');\n"
                                 "runkit_function_redefine('" + func_name + "', '" + argv_contents + "',\n"
                                 "'global $argv_list_r353t;\n"
                                 "saveDatas_r353t($argv_list_r353t, \"" + func_name + "\", func_get_args(), \"" + hook_func_inject_point[func_name] + "\");\n"
                                 "$result = call_user_func_array(\"override_" + func_name + "\", func_get_args());\n"
                                 "return $result;');\n")


          else:
            print("[!] Runkit does not support PHP 7")
            exit()

        else: # uopz
          if(php_ver == 5):
              hook_file_body += ("$class_alias_logging_r353t = function(){\n"
                                 "  global $argv_list_r353t;\n"
                                 "  if (!array_key_exists('CLASS_ALIAS', $argv_list_r353t)){\n"
                                 "    $argv_list_r353t['CLASS_ALIAS'] = array();\n"
                                 "  }\n"
                                 "  array_push($argv_list_r353t['CLASS_ALIAS'], array(func_get_arg(0), func_get_arg(1)));\n"
                                 "  $result = call_user_func_array('override_class_alias', func_get_args());\n"
                                 "  return $result;\n"
                                 "};\n"
                                 "uopz_rename('class_alias', 'override_class_alias');\n"
                                 "uopz_function('class_alias', $class_alias_logging_r353t);\n")
              hook_file_body += ("$hook_array_key_exists_func_r353t = function(){\n"
                                 "    if(!in_array(func_get_arg(0), $GLOBALS['array_key_list_r353t'])){\n"
                                 "        array_push($GLOBALS['array_key_list_r353t'], func_get_arg(0));\n"
                                 "    }\n"
                                 "    $result = call_user_func_array('override_array_key_exists', func_get_args());\n"
                                 "    return $result;\n"
                                 "};\n"
                                 "uopz_rename('array_key_exists', 'override_array_key_exists');\n"
                                 "uopz_function('array_key_exists', $hook_array_key_exists_func_r353t);\n")
              for func_name, func_infos in decoded_parse_output.items():
                  hook_file_body += ("\n"
                                    "$hook_" + func_name + "_func_r353t = function(){\n"
                                    "  global $argv_list_r353t;\n"
                                    "  saveDatas_r353t($argv_list_r353t, \"" + func_name + "\", func_get_args(), \"" + hook_func_inject_point[func_name] + "\");\n"
                                    "  $result = call_user_func_array('override_" + func_name + "', func_get_args());\n"
                                    "return $result;\n"
                                    "};\n"
                                    "uopz_rename('" + func_name + "', 'override_" + func_name + "');\n"
                                    "uopz_function('" + func_name + "', $hook_" + func_name + "_func_r353t);\n")
          else: # PHP 7
              hook_file_body += "\nuopz_allow_exit(true);\n"
              hook_file_body += ("$class_alias_logging_r353t = function(){\n"
                                 "  global $argv_list_r353t;\n"
                                 "  if (!array_key_exists('CLASS_ALIAS', $argv_list_r353t)){\n"
                                 "    $argv_list_r353t['CLASS_ALIAS'] = array();\n"
                                 "  }\n"
                                 "  array_push($argv_list_r353t['CLASS_ALIAS'], array(func_get_arg(0), func_get_arg(1)));\n"
                                 "};\n"
                                 "uopz_set_hook('class_alias', $class_alias_logging_r353t);\n")
              for func_name, func_infos in decoded_parse_output.items():
                  hook_file_body += ("\n"
                                    "$hook_" + func_name + "_func_r353t = function(){\n"
                                    "  global $argv_list_r353t;\n"
                                    "  saveDatas_r353t($argv_list_r353t, \"" + func_name + "\", func_get_args(), \"" + hook_func_inject_point[func_name] + "\");\n"
                                    "};\n"
                                    "uopz_set_hook('" + func_name + "', $hook_" + func_name + "_func_r353t);\n")

        # Hook Tail
        hook_file_tail = "?>"


        hook_file_contents = hook_file_header + hook_file_body + hook_file_tail
        with open(SENSITIVE_FUNCTION_OUTPUT, "w") as sf_o:
            sf_o.write(hook_file_contents)
