/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2010 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Stefan Esser                                                 |
  +----------------------------------------------------------------------+
*/

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "zend_compile.h"
#include "php_crawlhelper.h"


PHP_FUNCTION(isset_log);
static zend_function_entry isset_log_functions[] = {
    PHP_FE(isset_log, NULL)
    {NULL, NULL, NULL}
};


zend_module_entry crawlhelper_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"crawlhelper",
    isset_log_functions,
	PHP_MINIT(crawlhelper),
	PHP_MSHUTDOWN(crawlhelper),
	PHP_RINIT(crawlhelper),
	PHP_RSHUTDOWN(crawlhelper),
	PHP_MINFO(crawlhelper),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1",
#endif
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_CRAWLHELPER
ZEND_GET_MODULE(crawlhelper)
#endif

ZEND_BEGIN_MODULE_GLOBALS(crawlhelper)
    zval *new_array;
ZEND_END_MODULE_GLOBALS(crawlhelper)

#ifdef ZTS
#define COUNTER_G(v) TSRMG(crawlhelper_globals_id, zend_crawlhelper_globals *, v)
#else
#define COUNTER_G(v) (crawlhelper_globals.v)
#endif

ZEND_DECLARE_MODULE_GLOBALS(crawlhelper)

static zend_op_array *(*orig_compile_string)(zval *source_string, char *filename TSRMLS_DC);
static zend_bool crawlhelper_hooked = 0;

static zend_op_array *crawlhelper_compile_string(zval *source_string, char *filename TSRMLS_DC)
{
  return NULL;
	// int c, len, yes;
	// char *copy;

	// /* Ignore non string eval() */
	// if (Z_TYPE_P(source_string) != IS_STRING) {
	// 	return orig_compile_string(source_string, filename TSRMLS_CC);
	// }
	
	// len  = Z_STRLEN_P(source_string);
	// copy = estrndup(Z_STRVAL_P(source_string), len);
 //    add_next_index_string(COUNTER_G(new_array), copy, 1);

	// return orig_compile_string(source_string, filename TSRMLS_CC);
}

static int do_isset_handler(ZEND_OPCODE_HANDLER_ARGS){
  char *copy;
  zval *op2_value;
  int op2_len;
  zend_op_array *symbol_table;
  zval **copied_st;
  
  zend_op_array *global_symbol_table = &EG(active_symbol_table);
  zend_op_array *opa = execute_data->op_array;
  zend_op *opline = execute_data->opline;
  znode_op first_op = opline->op1;
  znode_op second_op = opline->op2;
  znode_op op_result = opline->result;
  ulong ext_value = opline->extended_value;
  zend_uchar opcode = opline->opcode;
  zend_uchar fisrt_op_type = opline->op1_type;
  zend_uchar second_op_type = opline->op2_type;
  zend_uchar op_result_type = opline->result_type;
  

  
  // php_printf("FileName: %s\n", opa->filename);
  // php_printf("LineNo: %d\n", opline->lineno);
  
  if(second_op_type == IS_CV){ // Variables
    // MAKE_STD_ZVAL(copied_st);
    // copied_st = zend_get_compiled_variable_value(global_symbol_table, 0);
    // for(int i=0; i < opa->last_var; i++){
    // copied_st = zend_get_compiled_variable_name(opa, 0, 0);
    

    zend_uint cv_num = second_op.zv;
    copied_st = zend_get_compiled_variable_value(execute_data, cv_num);
    if(Z_TYPE_PP(copied_st) == IS_STRING){
      op2_value = Z_STRVAL_PP(copied_st);
      op2_len = Z_STRLEN_PP(copied_st);
      copy = estrndup(op2_value, op2_len);
      add_next_index_string(COUNTER_G(new_array), copy, 1);
    }


    // php_printf("%s", Z_STRVAL_PP(copied_st));
    // }

    // zend_hash_quick_find(, );
    // php_printf("%s", copied_st);
    // if (!EG(active_symbol_table)) {
    //   zend_rebuild_symbol_table(TSRMLS_C);
    // }
    // MAKE_STD_ZVAL(copied_st);
    // php_printf("%s", (EG(current_execute_data)->CVs[0]));
    // array_init_size(copied_st, zend_hash_num_elements(EG(active_symbol_table)));
    
    // zval ***cvs = &EG(current_execute_data)->CVs;
    // php_printf("%p\n", Z_STRVAL(cvs[0]));
    // zend_compiled_variable *funcName = >call->func->common.function_name;
    // SEND_VAR(second_op);
    // DO_FCALL("phpinfo");
    // op2_value = global_symbol_table->vars->name;
    // zend_rebuild_symbol_table();
    // php_printf("%d", global_symbol_table->last_var);
    // php_printf("ABCD");
    
    
    // ZVAL_ARR(&tmp, symbol_table);
    // zval *new_variable; 
    // MAKE_STD_ZVAL(new_variable);
    // ZEND_SET_SYMBOL(global_symbol_table, "new_variable_name", new_variable); 
    // php_printf("%p\n", global_symbol_table->arData[0]);
    // php_printf("%p", global_symbol_table);
    // global_symbol_table
    // php_var_dump(EG(active_symbol_table), 0);
    // printf( "%d\n", global_symbol_table->vars);
    // printf("%p", opa);
  }
  else if(second_op_type == IS_CONST){ // String
    // php_printf("%p\n", second_op.zv);

    if(Z_TYPE_P(second_op.zv) == IS_STRING){
      op2_value = Z_STRVAL_P(second_op.zv);
      op2_len = Z_STRLEN_P(second_op.zv);  
      copy = estrndup(op2_value, op2_len);
      add_next_index_string(COUNTER_G(new_array), copy, 1);
    }
  }
  // else{
  //   php_printf("C");
  // }
  // php_printf(second_op.zv);
  // php_printf("%d", IS_);
  // op2_len  = Z_STRLEN_P(second_op.zv);
  // copy = estrndup(op2_value, op2_len);

  
  
  
   // php_printf("%d", opline->result);


  return ZEND_USER_OPCODE_DISPATCH;
}

PHP_MINIT_FUNCTION(crawlhelper)
{
	if (crawlhelper_hooked == 0) {
		crawlhelper_hooked = 1;
    zend_set_user_opcode_handler(ZEND_ISSET_ISEMPTY_DIM_OBJ, do_isset_handler);
    // orig_isset_isempty_dim_obj = zend_isset_isempty_dim_obj;
		// zend_isset_isempty_dim_obj = crawlhelper_isset_isempty_dim_obj;
    }
    return SUCCESS;
}

PHP_RINIT_FUNCTION(crawlhelper){
    MAKE_STD_ZVAL(COUNTER_G(new_array));
    array_init(COUNTER_G(new_array));
    return SUCCESS;
}


PHP_MSHUTDOWN_FUNCTION(crawlhelper)
{
	if (crawlhelper_hooked == 1) {
		crawlhelper_hooked = 0;
    zend_set_user_opcode_handler(ZEND_ISSET_ISEMPTY_DIM_OBJ, NULL);
    // zend_isset_isempty_dim_obj = orig_isset_isempty_dim_obj;
    }
	return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(crawlhelper){
    return SUCCESS;
}

PHP_MINFO_FUNCTION(crawlhelper)
{
  php_info_print_table_start();
	php_info_print_table_header(2, "crawlhelper support", "enabled");
	php_info_print_table_end();
}

PHP_FUNCTION(isset_log)
{
    // int array_count;
    HashTable *arr_hash;
    HashPosition pointer;
    zval **data;
    
    arr_hash = Z_ARRVAL_P(COUNTER_G(new_array));
    // array_count = zend_hash_num_elements(arr_hash);
    
    zend_hash_internal_pointer_reset_ex(arr_hash, &pointer);
    array_init(return_value); 

    for (zend_hash_internal_pointer_reset_ex(arr_hash, &pointer);
        zend_hash_get_current_data_ex(arr_hash, (void**) &data, &pointer) == SUCCESS;
        zend_hash_move_forward_ex(arr_hash, &pointer)){
        add_next_index_string(return_value, Z_STRVAL_PP(data), 1);
    }
    return;
}
