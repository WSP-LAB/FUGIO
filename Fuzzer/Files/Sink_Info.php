<?php
// Control = Require / Option
// Goal = MY_FILE, MY_DIR, ...
// GoalType = Literal / Replace
// ValidType = Complete / Include

// -------------------- FILE Delete --------------------
$SINK_ARGV = array();
$SINK_ARGV['FILE_DELETE'] = array();
$SINK_ARGV['FILE_DELETE']['FuncCall'] = array();
$SINK_ARGV['FILE_DELETE']['MethodCall'] = array();
$SINK_ARGV['FILE_DELETE']['Syntax'] = array();
$SINK_ARGV['FILE_DELETE']['FuncCall']['unlink'] = array();
$SINK_ARGV['FILE_DELETE']['FuncCall']['unlink'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_DELETE']['FuncCall']['unlink'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE';
$SINK_ARGV['FILE_DELETE']['FuncCall']['unlink'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_DELETE']['FuncCall']['unlink'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_DELETE']['FuncCall']['rmdir'] = array();
$SINK_ARGV['FILE_DELETE']['FuncCall']['rmdir'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_DELETE']['FuncCall']['rmdir'][0]['ARGV_CAND'][0]['Goal'] = 'MY_DIR';
$SINK_ARGV['FILE_DELETE']['FuncCall']['rmdir'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_DELETE']['FuncCall']['rmdir'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

// -------------------- FILE Create --------------------
$SINK_ARGV['FILE_CREATE'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall'] = array();
$SINK_ARGV['FILE_CREATE']['MethodCall'] = array();
$SINK_ARGV['FILE_CREATE']['Syntax'] = array();

$SINK_ARGV['FILE_CREATE']['FuncCall']['fopen'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['fopen'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fopen'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE_NO_EXISTS';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fopen'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fopen'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fopen'][1]['Control'] = 'Option';

$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][1]['ARGV_CAND'][0]['Goal'] = 'MY_FILE_NO_EXISTS';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][1]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['copy'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs']['MAX_PE'] = True;
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs'][1]['ARGV_CAND'][0]['Goal'] = '<?php echo `ls`;';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputs'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite']['MAX_PE'] = True;
$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite'][1]['ARGV_CAND'][0]['Goal'] = '<?php echo `ls`;';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fwrite'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_CREATE']['FuncCall']['file_put_contents'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['file_put_contents'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['file_put_contents'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['file_put_contents'][1]['ARGV_CAND'][0]['Goal'] = '<?php echo `ls`;';
$SINK_ARGV['FILE_CREATE']['FuncCall']['file_put_contents'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['FILE_CREATE']['FuncCall']['file_put_contents'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['ARGV_CAND'][0]['Goal'] = 'MY_DIR_NO_EXISTS';
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['ARGV_CAND'][1]['Goal'] = 'MY_DIR';
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['ARGV_CAND'][1]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['mkdir'][0]['ARGV_CAND'][1]['ValidType'] = 'Include';

$SINK_ARGV['FILE_CREATE']['FuncCall']['symlink'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['symlink'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['symlink'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['symlink'][1]['ARGV_CAND'][0]['Goal'] = 'MY_FILE_NO_EXISTS';
$SINK_ARGV['FILE_CREATE']['FuncCall']['symlink'][1]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['symlink'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';


$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv']['MAX_PE'] = True;
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv'][1]['ARGV_CAND'][0]['Goal'] = array('\"<?php echo `ls`; ?>');
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fputcsv'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';


$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf']['MAX_PE'] = True;
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'][1]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'][2]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'][2]['ARGV_CAND'][0]['Goal'] = '<?php echo `ls`;';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'][2]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['FILE_CREATE']['FuncCall']['fprintf'][2]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_CREATE']['FuncCall']['link'] = array();
$SINK_ARGV['FILE_CREATE']['FuncCall']['link'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_CREATE']['FuncCall']['link'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_CREATE']['FuncCall']['link'][1]['ARGV_CAND'][0]['Goal'] = 'MY_FILE_NO_EXISTS';
$SINK_ARGV['FILE_CREATE']['FuncCall']['link'][1]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_CREATE']['FuncCall']['link'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

// -------------------- FILE Modify --------------------
$SINK_ARGV['FILE_MODIFY'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall'] = array();
$SINK_ARGV['FILE_MODIFY']['MethodCall'] = array();
$SINK_ARGV['FILE_MODIFY']['Syntax'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chmod'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chmod'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chmod'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chmod'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chmod'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_MODIFY']['FuncCall']['chown'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chown'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chown'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chown'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chown'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_MODIFY']['FuncCall']['chgrp'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chgrp'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chgrp'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chgrp'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['chgrp'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_MODIFY']['FuncCall']['touch'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall']['touch'][0]['Control'] = 'Require';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['touch'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE_NO_EXISTS';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['touch'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['touch'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate'] = array();
$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate']['MAX_PE'] = True;
$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate'][0]['Control'] = 'Option';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate'][1]['Control'] = 'Require';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate'][1]['ARGV_CAND'][0]['Goal'] = '1337'; # Size
$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['FILE_MODIFY']['FuncCall']['ftruncate'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

// -------------------- SQL Injection --------------------
/*
$SINK_ARGV['SQL_INJECTION'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall'] = array();
$SINK_ARGV['SQL_INJECTION']['MethodCall'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_db_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_db_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_db_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_db_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_db_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_unbuffered_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_unbuffered_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_unbuffered_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_unbuffered_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysql_unbuffered_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_stmt_execute'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_stmt_execute']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_stmt_execute']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_stmt_execute']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_stmt_execute']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_execute'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_execute']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_execute']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_execute']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_execute']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_real_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_real_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_real_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_real_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_real_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_multi_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_multi_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_multi_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_multi_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['mysqli_multi_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['MethodCall']['multi_query'] = array();
$SINK_ARGV['SQL_INJECTION']['MethodCall']['real_query'] = array();
$SINK_ARGV['SQL_INJECTION']['MethodCall']['query'] = array();
$SINK_ARGV['SQL_INJECTION']['MethodCall']['prepare'] = array();

$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_do'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_do']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_do']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_do']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_do']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_exec'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_exec']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_exec']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_exec']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_exec']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_execute'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_execute']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_execute']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_execute']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['odbc_execute']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_execute'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_execute']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_execute']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_execute']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_execute']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_query'] = array();
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_query']['ANY']['Control'] = 'Require';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_query']['ANY']['ARGV_CAND'][0]['Goal'] = '3133731337';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_query']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['SQL_INJECTION']['FuncCall']['sqlsrv_query']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['SQL_INJECTION']['MethodCall']['exec'] = array();
// $SINK_ARGV['SQL_INJECTION']['MethodCall']['execute'] = array();
*/

// -------------------- Local File Inclusion --------------------
$SINK_ARGV['LFI'] = array();
$SINK_ARGV['LFI']['FuncCall'] = array();
$SINK_ARGV['LFI']['MethodCall'] = array();
$SINK_ARGV['LFI']['Syntax'] = array();
$SINK_ARGV['LFI']['Syntax']['include'] = array();
$SINK_ARGV['LFI']['Syntax']['include'][0]['Control'] = 'Require';
$SINK_ARGV['LFI']['Syntax']['include'][0]['ARGV_CAND'][0]['Goal'] = 'LFI_FILE';
$SINK_ARGV['LFI']['Syntax']['include'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['LFI']['Syntax']['include'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['LFI']['Syntax']['include_once'] = array();
$SINK_ARGV['LFI']['Syntax']['include_once'][0]['Control'] = 'Require';
$SINK_ARGV['LFI']['Syntax']['include_once'][0]['ARGV_CAND'][0]['Goal'] = 'LFI_FILE';
$SINK_ARGV['LFI']['Syntax']['include_once'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['LFI']['Syntax']['include_once'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['LFI']['Syntax']['require'] = array();
$SINK_ARGV['LFI']['Syntax']['require'][0]['Control'] = 'Require';
$SINK_ARGV['LFI']['Syntax']['require'][0]['ARGV_CAND'][0]['Goal'] = 'LFI_FILE';
$SINK_ARGV['LFI']['Syntax']['require'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['LFI']['Syntax']['require'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['LFI']['Syntax']['require_once'] = array();
$SINK_ARGV['LFI']['Syntax']['require_once'][0]['Control'] = 'Require';
$SINK_ARGV['LFI']['Syntax']['require_once'][0]['ARGV_CAND'][0]['Goal'] = 'LFI_FILE';
$SINK_ARGV['LFI']['Syntax']['require_once'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['LFI']['Syntax']['require_once'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

// -------------------- XXE --------------------
$SINK_ARGV['XXE'] = array();
$SINK_ARGV['XXE']['FuncCall'] = array();
$SINK_ARGV['XXE']['MethodCall'] = array();
$SINK_ARGV['XXE']['Syntax'] = array();
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_string'] = array();
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_string']['MAX_PE'] = True;
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_string'][0]['Control'] = 'Require';
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_string'][0]['ARGV_CAND'][0]['Goal'] = 'TODO'; # TODO
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_string'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_string'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XXE']['FuncCall']['simplexml_load_file'] = array();
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_file']['MAX_PE'] = True;
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_file'][0]['Control'] = 'Require';
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_file'][0]['ARGV_CAND'][0]['Goal'] = 'MY_FILE'; # TODO
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_file'][0]['ARGV_CAND'][0]['GoalType'] = 'Replace';
$SINK_ARGV['XXE']['FuncCall']['simplexml_load_file'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XXE']['FuncCall']['simplexml_import_dom'] = array();
$SINK_ARGV['XXE']['FuncCall']['simplexml_import_dom']['MAX_PE'] = True;
$SINK_ARGV['XXE']['FuncCall']['simplexml_import_dom'][0]['Control'] = 'Require';
$SINK_ARGV['XXE']['FuncCall']['simplexml_import_dom'][0]['ARGV_CAND'][0]['Goal'] = 'TODO'; # TODO
$SINK_ARGV['XXE']['FuncCall']['simplexml_import_dom'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XXE']['FuncCall']['simplexml_import_dom'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XXE']['MethodCall']['loadXML'] = array();

// -------------------- Command Injection --------------------
$COMMON_CMDI_PAYLOAD = 'cat /dev/null';
$SINK_ARGV['COMMAND_INJECTION'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall'] = array();
$SINK_ARGV['COMMAND_INJECTION']['MethodCall'] = array();
$SINK_ARGV['COMMAND_INJECTION']['Syntax'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['exec'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['exec'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['exec'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['exec'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['exec'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['passthru'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['passthru'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['passthru'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['passthru'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['passthru'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['popen'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['popen'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['popen'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['popen'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['popen'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['proc_open'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['proc_open'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['proc_open'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['proc_open'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['proc_open'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['shell_exec'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['shell_exec'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['shell_exec'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['shell_exec'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['shell_exec'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['system'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['system'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['system'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['system'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['system'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['escapeshellcmd'] = array();
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['escapeshellcmd'][0]['Control'] = 'Require';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['escapeshellcmd'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_CMDI_PAYLOAD;
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['escapeshellcmd'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['COMMAND_INJECTION']['FuncCall']['escapeshellcmd'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

// -------------------- XSS --------------------
$COMMON_XSS_PAYLOAD = '<script>alert(1);</script>';
$IMG_XSS_PAYLOAD = '<img src="any_image.png" onerror="alert(1);" onload="alert(2);" onfocus="alert(3);" onmouseover="alert(4);" onmouseout="alert(5);">';
$SINK_ARGV['XSS'] = array();
$SINK_ARGV['XSS']['FuncCall'] = array();
$SINK_ARGV['XSS']['MethodCall'] = array();
$SINK_ARGV['XSS']['Syntax'] = array();
$SINK_ARGV['XSS']['Syntax']['echo'] = array();
$SINK_ARGV['XSS']['Syntax']['echo']['ANY']['Control'] = 'Require';
$SINK_ARGV['XSS']['Syntax']['echo']['ANY']['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['Syntax']['echo']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['Syntax']['echo']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['Syntax']['print'] = array();
$SINK_ARGV['XSS']['Syntax']['print'][0]['Control'] = 'Require';
$SINK_ARGV['XSS']['Syntax']['print'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['Syntax']['print'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['Syntax']['print'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['Syntax']['exit'] = array();
$SINK_ARGV['XSS']['Syntax']['exit'][0]['Control'] = 'Require';
$SINK_ARGV['XSS']['Syntax']['exit'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['Syntax']['exit'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['Syntax']['exit'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['Syntax']['die'] = array();
$SINK_ARGV['XSS']['Syntax']['die'][0]['Control'] = 'Require';
$SINK_ARGV['XSS']['Syntax']['die'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['Syntax']['die'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['Syntax']['die'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['FuncCall']['print_r'] = array();
$SINK_ARGV['XSS']['FuncCall']['print_r'][0]['Control'] = 'Require';
$SINK_ARGV['XSS']['FuncCall']['print_r'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['FuncCall']['print_r'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['FuncCall']['print_r'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['XSS']['FuncCall']['print_r'][1]['Control'] = 'Require';
$SINK_ARGV['XSS']['FuncCall']['print_r'][1]['UsableDefault'] = True;
$SINK_ARGV['XSS']['FuncCall']['print_r'][1]['ARGV_CAND'][0]['Goal'] = False;
$SINK_ARGV['XSS']['FuncCall']['print_r'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['FuncCall']['print_r'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['FuncCall']['printf'] = array();
$SINK_ARGV['XSS']['FuncCall']['printf'][0]['Control'] = 'Option';
$SINK_ARGV['XSS']['FuncCall']['printf'][1]['Control'] = 'Require';
$SINK_ARGV['XSS']['FuncCall']['printf'][1]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['FuncCall']['printf'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['FuncCall']['printf'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['FuncCall']['trigger_error'] = array();
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['Control'] = 'Require';
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['ARGV_CAND'][1]['Goal'] = $IMG_XSS_PAYLOAD;
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['ARGV_CAND'][1]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['FuncCall']['trigger_error'][0]['ARGV_CAND'][1]['ValidType'] = 'Include';

$SINK_ARGV['XSS']['FuncCall']['user_error'] = array();
$SINK_ARGV['XSS']['FuncCall']['user_error'][0]['Control'] = 'Require';
$SINK_ARGV['XSS']['FuncCall']['user_error'][0]['ARGV_CAND'][0]['Goal'] = $COMMON_XSS_PAYLOAD;
$SINK_ARGV['XSS']['FuncCall']['user_error'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['XSS']['FuncCall']['user_error'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

// -------------------- RCE --------------------
$SINK_ARGV['RCE'] = array();
$SINK_ARGV['RCE']['FuncCall'] = array();
$SINK_ARGV['RCE']['MethodCall'] = array();
$SINK_ARGV['RCE']['Syntax'] = array();
$SINK_ARGV['RCE']['Syntax']['eval'] = array();
$SINK_ARGV['RCE']['Syntax']['eval'][0]['Control'] = 'Require';
$SINK_ARGV['RCE']['Syntax']['eval'][0]['ARGV_CAND'][0]['Goal'] = 'system(\'ls\');';
$SINK_ARGV['RCE']['Syntax']['eval'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['Syntax']['eval'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['call_user_func'] = array();
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][0]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][0]['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][1]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][1]['UsableDefault'] = True;
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][1]['ARGV_CAND'][0]['Goal'] = 313373133731337;
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['call_user_func'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'] = array();
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][0]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][0]['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][1]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][1]['ARGV_CAND'][0]['Goal'] = Array(313373133731337);
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['call_user_func_array'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['preg_replace'] = array();
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][0]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][0]['ARGV_CAND'][0]['Goal'] = '//e';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][1]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][1]['ARGV_CAND'][0]['Goal'] = 'phpinfo()';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][2]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][2]['UsableDefault'] = True;
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][2]['ARGV_CAND'][0]['Goal'] = ' ';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][2]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['preg_replace'][2]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['mail'] = array();
$SINK_ARGV['RCE']['FuncCall']['mail'][0]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['mail'][1]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['mail'][2]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['mail'][3]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['mail'][3]['ARGV_CAND'][0]['Goal'] = '/bin/bash -c';
$SINK_ARGV['RCE']['FuncCall']['mail'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['mail'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

/*
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback'] = array();
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback'][0]['Control'] = 'Option';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback'][1]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback'][1]['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback'][1]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback'][1]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback_array'] = array();
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback_array'][0]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback_array'][0]['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback_array'][0]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['preg_replace_callback_array'][0]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_diff_uassoc'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_diff_uassoc'][2]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_diff_uassoc'][2]['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_diff_uassoc'][2]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_diff_uassoc'][2]['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_diff_ukey'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_diff_ukey'][2]['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_diff_ukey'][2]['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_diff_ukey'][2]['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_diff_ukey'][2]['ARGV_CAND'][0]['ValidType'] = 'Include';

# TODO Belows
$SINK_ARGV['RCE']['FuncCall']['array_filter'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_filter']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_filter']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_filter']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_filter']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_intersect_uassoc'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_intersect_uassoc']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_intersect_uassoc']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_intersect_uassoc']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_intersect_uassoc']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_intersect_ukey'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_intersect_ukey']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_intersect_ukey']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_intersect_ukey']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_intersect_ukey']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_map'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_map']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_map']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_map']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_map']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_reduce'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_reduce']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_reduce']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_reduce']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_reduce']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_udiff'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_udiff']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_udiff']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_udiff']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_udiff']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_udiff_assoc'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_udiff_assoc']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_udiff_assoc']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_udiff_assoc']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_udiff_assoc']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_udiff_uassoc'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_udiff_uassoc']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_udiff_uassoc']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_udiff_uassoc']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_udiff_uassoc']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_uintersect'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_uintersect']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_uintersect_assoc'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_assoc']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_assoc']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_assoc']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_assoc']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_uintersect_uassoc'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_uassoc']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_uassoc']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_uassoc']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_uintersect_uassoc']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_walk'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_walk']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_walk']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_walk']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_walk']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['array_walk_recursive'] = array();
$SINK_ARGV['RCE']['FuncCall']['array_walk_recursive']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['array_walk_recursive']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['array_walk_recursive']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['array_walk_recursive']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['ob_start'] = array();
$SINK_ARGV['RCE']['FuncCall']['ob_start']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['ob_start']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['ob_start']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['ob_start']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['register_shutdown_function'] = array();
$SINK_ARGV['RCE']['FuncCall']['register_shutdown_function']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['register_shutdown_function']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['register_shutdown_function']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['register_shutdown_function']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['register_tick_function'] = array();
$SINK_ARGV['RCE']['FuncCall']['register_tick_function']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['register_tick_function']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['register_tick_function']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['register_tick_function']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['set_error_handler'] = array();
$SINK_ARGV['RCE']['FuncCall']['set_error_handler']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['set_error_handler']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['set_error_handler']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['set_error_handler']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['set_exception_handler'] = array();
$SINK_ARGV['RCE']['FuncCall']['set_exception_handler']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['set_exception_handler']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['set_exception_handler']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['set_exception_handler']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['spl_autoload_register'] = array();
$SINK_ARGV['RCE']['FuncCall']['spl_autoload_register']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['spl_autoload_register']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['spl_autoload_register']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['spl_autoload_register']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['uasort'] = array();
$SINK_ARGV['RCE']['FuncCall']['uasort']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['uasort']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['uasort']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['uasort']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['uksort'] = array();
$SINK_ARGV['RCE']['FuncCall']['uksort']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['uksort']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['uksort']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['uksort']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';

$SINK_ARGV['RCE']['FuncCall']['usort'] = array();
$SINK_ARGV['RCE']['FuncCall']['usort']['ANY']['Control'] = 'Require';
$SINK_ARGV['RCE']['FuncCall']['usort']['ANY']['ARGV_CAND'][0]['Goal'] = 'phpinfo';
$SINK_ARGV['RCE']['FuncCall']['usort']['ANY']['ARGV_CAND'][0]['GoalType'] = 'Literal';
$SINK_ARGV['RCE']['FuncCall']['usort']['ANY']['ARGV_CAND'][0]['ValidType'] = 'Include';
*/

function GetSinkInfo($sink, $sink_type = 'FuncCall') {
  global $SINK_ARGV;
  $ret_sink_class_arr = array();
  foreach ($SINK_ARGV as $sink_vuln => $sink_class) {
    $ret_sink_class['class'] = $sink_vuln;
    foreach ($sink_class as $sink_ast => $sink_names) {
      if ($sink_type == 'FuncCall') { // FuncCall, Syntax
        if ($sink_ast == 'MethodCall') {
          continue;
        }
      }
      elseif ($sink_type == 'Syntax') {
        if ($sink_ast != 'Syntax') { // Syntax
          continue;
        }
      }
      elseif ($sink_type == 'MethodCall') {
       if ($sink_ast != 'MethodCall') { // Method
          continue;
        }
      }
      foreach ($sink_names as $sink_name => $sink_argv) {
        if ($sink_name == $sink) {
          $ret_sink_class['ast'] = $sink_ast;
          $ret_sink_class['name'] = $sink_name;
          if (array_key_exists("MAX_PE", $sink_argv)) {
            $ret_sink_class['max_pe'] = $sink_argv['MAX_PE'];
            unset($sink_argv['MAX_PE']);
          }
          $ret_sink_class['argvs'] = $sink_argv;
          array_push($ret_sink_class_arr, $ret_sink_class);
        }
      }
    }
  }
  return $ret_sink_class_arr;
}

?>
