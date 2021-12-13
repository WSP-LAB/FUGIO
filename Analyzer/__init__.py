import os

PUBLIC = 1
PROTECTED = 2
PRIVATE = 4
STATIC = 8
ABSTRACT = 16
FINAL = 32
INTERFACE = 64
TRAIT = 128

EXCLUDED_CLASSES = ['dummy_class_r353t',
                    'ComposerAutoloaderInit*',
                    # 'Composer\\\\Autoload\\\\ClassLoader',
                    # 'Composer\\\\Autoload\\\\ComposerStaticInit*',
                    'Composer\\\\*',
                    'PhpAmqpLib\\\\*',
                    'class@anonymous*'
                    ]
EXCLUDED_CLASSES_REGEX = '(' + ')|('.join(EXCLUDED_CLASSES) + ')'
EXCLUDED_FUNCTIONS = ['Composer\\\\*']
EXCLUDED_FUNCTIONS_REGEX = '(' + ')|('.join(EXCLUDED_FUNCTIONS) + ')'

EXCLUDED_DIRS = ['%s/typo3temp/var/cache',
                 '%s/e107_system/416f4602e3/cache']

EXCLUDED_FILES = ['%s/vendor/analog/analog/lib/Analog/Handler/Null.php',
                 '%s/vendor/symfony/symfony/src/Symfony/Component/VarDumper/Tests/Fixtures/NotLoadableClass.php']

MAGIC_METHODS = [
                #  '__construct',
                 '__destruct',
                 '__call',
                 '__callStatic',
                 '__get',
                 '__set',
                 '__isset',
                 '__unset',
                 '__sleep',
                 '__wakeup',
                 '__toString',
                #  '__invoke',
                 '__set_state',
                 '__clone',
                #  '__debugInfo'
                ]

# SINKS = [
#     # cross-site scripting
#     'echo', 'print', 'print_r', 'exit', 'die', 'printf', 'vprintf', 'trigger_error',
#     'user_error', 'odbc_result_all', 'ovrimos_result_all', 'ifx_htmltbl_result',
#     # HTTP header injections
#     'header',
#     # session fixation
#     'setcookie', 'setrawcookie', 'session_id',
#     # code evaluating functions
#     'assert', 'create_function', 'eval', 'mb_ereg_replace', 'mb_eregi_replace',
#     'preg_filter', 'preg_replace', 'preg_replace_callback',
#     # reflection injection
#     'event_buffer_new', 'event_set', 'iterator_apply',
#     'forward_static_call', 'forward_static_call_array',
#     'call_user_func', 'call_user_func_array',
#     'array_diff_uassoc', 'array_diff_ukey', 'array_filter', 'array_intersect_uassoc',
#     'array_intersect_ukey', 'array_map', 'array_reduce', 'array_udiff',
#     'array_udiff_assoc', 'array_udiff_uassoc', 'array_uintersect',
#     'array_uintersect_assoc', 'array_uintersect_uassoc', 'array_walk',
#     'array_walk_recursive', 'assert_options', 'ob_start',
#     'register_shutdown_function', 'register_tick_function',
#     'runkit_method_add', 'runkit_method_copy', 'runkit_method_redefine',
#     'runkit_method_rename', 'runkit_function_add', 'runkit_function_copy',
#     'runkit_function_redefine', 'runkit_function_rename',
#     'session_set_save_handler', 'set_error_handler', 'set_exception_handler',
#     'spl_autoload', 'spl_autoload_register', 'sqlite_create_aggregate',
#     'sqlite_create_function', 'stream_wrapper_register',
#     'uasort', 'uksort', 'usort', 'yaml_parse', 'yaml_parse_file', 'yaml_parse_url',
#     'eio_busy', 'eio_chmod', 'eio_chown', 'eio_close', 'eio_custom', 'eio_dup2',
#     'eio_fallocate', 'eio_fchmod', 'eio_fchown', 'eio_fdatasync', 'eio_fstat',
#     'eio_fstatvfs', 'preg_replace_callback', 'dotnet_load',
#     # file inclusion functions
#     'include', 'include_once', 'parsekit_compile_file', 'php_check_syntax',
#     'require', 'require_once', 'runkit_import', 'set_include_path', 'virtual',
#     # file affecting functions
#     'bzread', 'bzflush', 'dio_read', 'eio_readdir', 'fdf_open',
#     'file', 'file_get_contents', 'finfo_file', 'fflush', 'fgetc', 'fgetcsv',
#     'fgets', 'fgetss', 'fread', 'fpassthru', 'fscanf', 'ftok', 'get_meta_tags',
#     'glob', 'gzfile', 'gzgetc', 'gzgets', 'gzgetss', 'gzread', 'gzpassthru',
#     'highlight_file', 'imagecreatefrompng', 'imagecreatefromjpg', 'imagecreatefromgif',
#     'imagecreatefromgd2', 'imagecreatefromgd2part', 'imagecreatefromgd',
#     'opendir', 'parse_ini_file', 'php_strip_whitespace', 'readfile', 'readgzfile',
#     'readlink', 'scandir', 'show_source', 'stream_get_contents', 'stream_get_line',
#     'xdiff_file_bdiff', 'xdiff_file_bpatch', 'xdiff_file_diff_binary', 'xdiff_file_diff',
#     'xdiff_file_merge3', 'xdiff_file_patch_binary', 'xdiff_file_patch',
#     'xdiff_file_rabdiff', 'yaml_parse_file', 'zip_open',
#     # XXE
#     'simplexml_load_string', 'simplexml_load_file', 'simplexml_import_dom',
#     # file or file system affecting functions
#     'bzwrite', 'chmod', 'chgrp', 'chown', 'copy', 'dio_write', 'eio_chmod', 'eio_chown',
#     'eio_mkdir', 'eio_mknod', 'eio_rmdir', 'eio_write', 'eio_unlink', 'error_log',
#     'event_buffer_write', 'file_put_contents', 'fputcsv', 'fputs', 'fprintf', 'ftruncate',
#     'fwrite', 'gzwrite', 'gzputs', 'mkdir', 'move_uploaded_file',
#     'posix_mknod', 'recode_file', 'rename', 'rmdir', 'shmop_write', 'touch', 'unlink',
#     'vfprintf', 'xdiff_file_bdiff', 'xdiff_file_bpatch', 'xdiff_file_diff_binary',
#     'xdiff_file_diff', 'xdiff_file_merge3', 'xdiff_file_patch_binary',
#     'xdiff_file_patch', 'xdiff_file_rabdiff', 'yaml_emit_file',
#     # OS Command executing functions
#     'backticks', 'exec', 'expect_popen', 'passthru', 'pcntl_exec', 'popen', 'proc_open',
#     'shell_exec', 'system', 'mail', 'mb_send_mail',
#     'w32api_invoke_function', 'w32api_register_function',
#     # SQL executing functions
#     'dba_open', 'dba_popen', 'dba_insert', 'dba_fetch', 'dba_delete', 'dbx_query',
#     'odbc_do', 'odbc_exec', 'odbc_execute', 'db2_exec' , 'db2_execute',
#     'fbsql_db_query', 'fbsql_query', 'ibase_query', 'ibase_execute', 'ifx_query',
#     'ifx_do', 'ingres_query', 'ingres_execute', 'ingres_unbuffered_query',
#     'msql_db_query', 'msql_query', 'msql', 'mssql_query', 'mssql_execute',
#     'mysql_db_query', 'mysql_query', 'mysql_unbuffered_query', 'mysqli_stmt_execute',
#     'mysqli_query', 'mysqli_real_query', 'mysqli_master_query',
#     'oci_execute', 'ociexecute', 'ovrimos_exec', 'ovrimos_execute', 'ora_do', 'ora_exec',
#     'pg_query', 'pg_send_query', 'pg_send_query_params', 'pg_send_prepare', 'pg_prepare',
#     'sqlite_open', 'sqlite_popen', 'sqlite_array_query', 'arrayQuery',
#     'singleQuery', 'sqlite_query', 'sqlite_exec', 'sqlite_single_query',
#     'sqlite_unbuffered_query', 'sybase_query', 'sybase_unbuffered_query',
#     # xpath injection
#     'xpath_eval', 'xpath_eval_expression', 'xptr_eval',
#     # ldap injection
#     'ldap_add', 'ldap_delete', 'ldap_list', 'ldap_read', 'ldap_search',
#     # connection handling functions
#     'curl_setopt', 'curl_setopt_array', 'cyrus_query', 'error_log', 'fsockopen',
#     'ftp_chmod', 'ftp_exec', 'ftp_delete', 'ftp_fget', 'ftp_get', 'ftp_nlist',
#     'ftp_nb_fget', 'ftp_nb_get', 'ftp_nb_put', 'ftp_put', 'get_headers', 'imap_open',
#     'imap_mail', 'mail', 'mb_send_mail', 'ldap_connect', 'msession_connect', 'pfsockopen',
#     'session_register', 'socket_bind', 'socket_connect', 'socket_send', 'socket_write',
#     'stream_socket_client', 'stream_socket_server', 'printer_open',
#     # other critical functions
#     'dl', 'ereg', 'eregi', 'ini_set', 'ini_restore', 'runkit_constant_redefine',
#     'runkit_method_rename', 'sleep', 'usleep', 'extract', 'mb_parse_str', 'parse_str',
#     'putenv', 'set_include_path', 'apache_setenv', 'define', 'is_a', 'method_exists',
# ]

SINKS = [
    # local file inclusion (LFI)
    'include', 'include_once', 'php_check_syntax',
    'require', 'require_once', 'set_include_path', 'virtual',

    # XML external entity injection (XXE)
    'simplexml_load_string', 'simplexml_load_file', 'simplexml_import_dom',

    # file affecting functions
    'bzread', 'bzflush', 'dio_read', 'eio_readdir', 'fdf_open',
    'file', 'file_get_contents', 'finfo_file', 'fflush', 'fgetc', 'fgetcsv',
    'fgets', 'fgetss', 'fread', 'fpassthru', 'fscanf', 'ftok', 'get_meta_tags',
    'glob', 'gzfile', 'gzgetc', 'gzgets', 'gzgetss', 'gzread', 'gzpassthru',
    'highlight_file', 'imagecreatefrompng', 'imagecreatefromjpg', 'imagecreatefromgif',
    'imagecreatefromgd2', 'imagecreatefromgd2part', 'imagecreatefromgd',
    'opendir', 'parse_ini_file', 'php_strip_whitespace', 'readfile', 'readgzfile',
    'readlink', 'scandir', 'show_source', 'stream_get_contents', 'stream_get_line',
    'xdiff_file_bdiff', 'xdiff_file_bpatch', 'xdiff_file_diff_binary', 'xdiff_file_diff',
    'xdiff_file_merge3', 'xdiff_file_patch_binary', 'xdiff_file_patch',
    'xdiff_file_rabdiff', 'yaml_parse_file', 'zip_open',
    # file or file system affecting functions
    'bzwrite', 'chmod', 'chgrp', 'chown', 'copy', 'dio_write', 'eio_chmod', 'eio_chown',
    'eio_mkdir', 'eio_mknod', 'eio_rmdir', 'eio_write', 'eio_unlink', 'error_log',
    'event_buffer_write', 'file_put_contents', 'fputcsv', 'fputs', 'fprintf', 'ftruncate',
    'fwrite', 'gzwrite', 'gzputs', 'mkdir', 'move_uploaded_file',
    'posix_mknod', 'recode_file', 'rename', 'rmdir', 'shmop_write', 'touch', 'unlink',
    'vfprintf', 'xdiff_file_bdiff', 'xdiff_file_bpatch', 'xdiff_file_diff_binary',
    'xdiff_file_diff', 'xdiff_file_merge3', 'xdiff_file_patch_binary',
    'xdiff_file_patch', 'xdiff_file_rabdiff', 'yaml_emit_file',

    # SQL executing functions
    'dba_open', 'dba_popen', 'dba_insert', 'dba_fetch', 'dba_delete', 'dbx_query',
    'odbc_do', 'odbc_exec', 'odbc_execute', 'db2_exec' , 'db2_execute',
    'fbsql_db_query', 'fbsql_query', 'ibase_query', 'ibase_execute', 'ifx_query',
    'ifx_do', 'ingres_query', 'ingres_execute', 'ingres_unbuffered_query',
    'msql_db_query', 'msql_query', 'msql', 'mssql_query', 'mssql_execute',
    'mysql_db_query', 'mysql_query', 'mysql_unbuffered_query', 'mysqli_stmt_execute',
    'mysqli_query', 'mysqli_real_query', 'mysqli_master_query',
    'oci_execute', 'ociexecute', 'ovrimos_exec', 'ovrimos_execute', 'ora_do', 'ora_exec',
    'pg_query', 'pg_send_query', 'pg_send_query_params', 'pg_send_prepare', 'pg_prepare',
    'sqlite_open', 'sqlite_popen', 'sqlite_array_query', 'arrayQuery',
    'singleQuery', 'sqlite_query', 'sqlite_exec', 'sqlite_single_query',
    'sqlite_unbuffered_query', 'sybase_query', 'sybase_unbuffered_query',
]

METHOD_SINKS = {
    # SQLi
    "query": range(1, 3),
    "real_query": range(1, 2),

    # XXE
    "loadXML": range(1, 3),
}

SINKS = [
    'unlink', 'rmdir', 'copy', 'fputs', 'fwrite', 'file_put_contents', 'mkdir', 'symlink', 'fputcsv', 'fprintf', 'link', 'chmod', 'chown', 'chgrp', 'touch', 'ftruncate', 'mysql_db_query', 'mysql_query', 'mysql_unbuffered_query', 'mysqli_stmt_execute', 'mysqli_execute', 'mysqli_query', 'mysqli_real_query', 'mysqli_multi_query', 'odbc_do', 'odbc_exec', 'odbc_execute', 'sqlsrv_execute', 'sqlsrv_query', 'include', 'include_once', 'require', 'require_once', 'simplexml_load_string', 'simplexml_load_file', 'simplexml_import_dom', 'exec', 'passthru', 'popen', 'proc_open', 'shell_exec', 'system', 'escapeshellcmd', 'echo', 'print', 'exit', 'die', 'print_r', 'printf', 'trigger_error', 'user_error', 'eval', 'call_user_func', 'call_user_func_array', 'preg_replace_callback', 'preg_replace_callback_array', 'array_diff_uassoc', 'array_diff_ukey', 'array_filter', 'array_intersect_uassoc', 'array_intersect_ukey', 'array_map', 'array_reduce', 'array_udiff', 'array_udiff_assoc', 'array_udiff_uassoc', 'array_uintersect', 'array_uintersect_assoc', 'array_uintersect_uassoc', 'array_walk', 'array_walk_recursive', 'ob_start', 'register_shutdown_function', 'register_tick_function', 'set_error_handler', 'set_exception_handler', 'spl_autoload_register', 'uasort', 'uksort', 'usort'
]

METHOD_SINKS = {
    "multi_query": range(1, 2),
    "real_query": range(1, 2),
    "query": range(1, 5),
    "exec": range(1, 2),
    "prepare": range(1, 3),
    # "execute": range(1, 2),
    "loadXML": range(1, 3)
}

# HERE IS COVERED SINK (GOAL)
SINKS = [ # FILE DELETE
          'unlink', 'rmdir',
          # FILE CREATE
          'copy', 'fputs', 'fwrite', 'file_put_contents', 'mkdir', 'symlink',
          'fputcsv', 'fprintf', 'link',
          # FILE MODIFY
          'chmod', 'chown', 'chgrp', 'touch', 'ftruncate',
          # SQL Injection
        #   'mysql_db_query', 'mysql_query',
        #   'mysql_unbuffered_query', 'mysqli_stmt_execute',
        #   'mysqli_execute', 'mysqli_query',
        #   'mysqli_real_query', 'mysqli_multi_query', 'odbc_do',
        #   'odbc_exec', 'odbc_execute', 'sqlsrv_execute', 'sqlsrv_query',
          # LFI
          'include', 'include_once', 'require', 'require_once',
          # XXE
          'simplexml_load_string', 'simplexml_load_file',
          'simplexml_import_dom',
          # COMMAND INJECTION
          'exec', 'passthru', 'popen', 'proc_open', 'shell_exec',
          'system', 'escapeshellcmd',
          # XSS
          'echo', 'print', 'exit', 'die', 'print_r', 'printf',
          'trigger_error', 'user_error',
          # RCE
          'eval', 'call_user_func', 'call_user_func_array',
        #   'preg_replace_callback',
        #   'preg_replace_callback_array', 'array_diff_uassoc',
        #   'array_diff_ukey', 'array_filter',
        #   'array_intersect_uassoc', 'array_intersect_ukey',
        #   'array_map', 'array_reduce',
        #   'array_udiff', 'array_udiff_assoc',
        #   'array_udiff_uassoc', 'array_uintersect',
        #   'array_uintersect_assoc', 'array_uintersect_uassoc',
        #   'array_walk', 'array_walk_recursive',
        #   'ob_start', 'register_shutdown_function',
        #   'register_tick_function', 'set_error_handler',
        #   'set_exception_handler', 'spl_autoload_register',
        #   'uasort', 'uksort', 'usort'
          'preg_replace',
        ]

TAINT = 0
OPTIONAL = 1

SINKS = {
  # FILE DELETE
  'unlink': [[TAINT]],
  'rmdir': [[TAINT]],
  # FILE CREATE
  'fopen': [[TAINT], [TAINT, r'[waxc\+]']],
  'file_put_contents': [[OPTIONAL], [TAINT]],
  'fwrite': [[OPTIONAL], [TAINT]],
  'fputs': [[OPTIONAL], [TAINT]],
  'mkdir': [[TAINT]],
  'copy': [[OPTIONAL], [TAINT]],
  'link': [[OPTIONAL], [TAINT]],
  'symlink': [[OPTIONAL], [TAINT]],
  # FILE MODIFY
  'chmod': [[TAINT]],
  'chown': [[TAINT]],
  'chgrp': [[TAINT]],
  'touch': [[TAINT]],
  # COMMAND INJECTION
  'exec': [[TAINT]],
  'passthru': [[TAINT]],
  'popen': [[TAINT]],
  'proc_open': [[TAINT]],
  'shell_exec': [[TAINT]],
  'system': [[TAINT]],
  'escapeshellcmd': [[TAINT]],
  # RCE
  'eval': [[TAINT]],
  'call_user_func': [[TAINT]],
  'call_user_func_array': [[TAINT]],
  'preg_replace': [[TAINT], [TAINT]],
  'mail': [[OPTIONAL], [OPTIONAL], [OPTIONAL], [OPTIONAL], [TAINT]]
}

METHOD_SINKS = {}

IMPLICIT_CALLS = {
  'unlink': ['__toString'],
  'file_exists': ['__toString'],
  'file_put_contents': ['__toString']
}

INTERNAL = 'INTERNAL'

PARSER = os.path.dirname(os.path.abspath(__file__)) + '/../Files/parser.php'
DUMP_DIR = os.path.dirname(os.path.abspath(__file__)) + '/../Files/dump_files'
EVAL_FILE = os.path.dirname(os.path.abspath(__file__)) + '/../Files/eval_file.php'
FUZZ_DIR = os.path.dirname(os.path.abspath(__file__)) + '/../Files/fuzzing'

dir_list = [DUMP_DIR, FUZZ_DIR]
for d in dir_list:
    if not os.path.isdir(d):
        os.mkdir(d)
