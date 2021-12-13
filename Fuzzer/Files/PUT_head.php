<?php
if(function_exists('uopz_allow_exit')){
  uopz_allow_exit(true);
}
if(PHP_VERSION >= "7.2"){
    require_once __DIR__ . '/../../../../Lib' .  '/rabbitmq_php7/vendor/autoload.php';
}
else{
    require_once __DIR__ . '/../../../../Lib' .  '/rabbitmq_php/vendor/autoload.php';
}
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$fuzzer_error_display = false;
ini_set("display_errors", $fuzzer_error_display);
chdir(__DIR__);

spl_autoload_register(function ($class_name) {
    global $REDECLARED_CLASSES;
    $class_name = strtolower($class_name);
    if (array_key_exists($class_name, $REDECLARED_CLASSES)) {
        $index = rand(0, $REDECLARED_CLASSES[$class_name]-1);
        $redeclared_file_name = 'inst-redec_class-' . $class_name . '_' . $index . '.php';
        include_once $redeclared_file_name;
    }
    else{
        $GLOBALS['Feedback_cls']->addDebugMessage(
            "[#] CLASS does not exists: " . $class_name
        );
    }
});

class ConstraintFeedback
{
    public $BranchPath;
    public $FinishFlag;
    public $goalPath;
    public $ifConstraints;
    public $controllableArgv;

    public $init_output = array();
    public $feedback_case;
    public $class_invoke;

    function __construct(){
        $this->BranchPath = array();
        $this->FinishFlag = False;
        $this->goalPath = array();
        $this->ifConstraints = array();
        $this->controllableArgv = array();

        $this->class_invoke = array();

        $this->feedback_case = array(
            "case" => "NORMAL",
            "msg" => ""
        );
        $this->debug_message = array();
    }
    function isBranchPassed($stmt_id, $type, $call_stack, $argv_array = array()){
        array_push($this->BranchPath,
            Array(
                "hash" => $stmt_id,
                "type" => $type,
                "call_stack" => $call_stack,
                "argvs" => $argv_array
            )
        );

        foreach($this->goalPath as &$goalPath){
            if($goalPath['hash'] == $stmt_id){
                $goalPath['hitCount'] += 1;
                break;
            }
        }

        return True;
    }

    function addDebugMessage($msg){
        array_push($this->debug_message, $msg);
    }

    function arrayFetch($hash, $var_string, $dim_string, $var, $dim, $trace_info){
        $fetch_info = array(
            "var_string" => $var_string,
            "dim_string" => $dim_string,
            "var" => $var,
            "dim" => $dim,
        );

        $this->isBranchPassed($hash, "ArrayFetch", $trace_info, $fetch_info);

        return $dim;
    }

    function switchWrapped($prefix, $cond){
        return $cond;
    }

    function foreachWrapped($prefix, $cond){
        return $cond;
    }

    function syntaxWrapped(){
        $sink_func = func_get_arg(0);
        $call_stack = func_get_arg(1);
        $sink_hash = func_get_arg(2);
        $sink_class = func_get_arg(3);
        $sink_method = func_get_arg(4);
        $sink_index = func_get_arg(5);


        $args = array();
        for($syntax_argv = 6; $syntax_argv < func_num_args(); $syntax_argv++){
            array_push($args, func_get_arg($syntax_argv));
        }

        $this->isBranchPassed($sink_hash,
            "FUNC(" . $sink_class . "::" . $sink_method . ")-" .
            $sink_func. "-" . $sink_index,
            $call_stack, $args);

        if($sink_func == "echo"){
            // Echo always return nothing.
        }

        elseif($sink_func == "print"){
            return print(func_get_arg(5));
        }
        elseif($sink_func == "exit"){
            if(func_num_args() >= 6){
                return exit(func_get_arg(5));
            }
            else{
                return exit;
            }
        }
        elseif($sink_func == "die"){
            if(func_num_args() >= 6){
                return die(func_get_arg(5));
            }
            else{
                return die;
            }
        }
        elseif($sink_func == "include"){
            return include(func_get_arg(5));
        }
        elseif($sink_func == "include_once"){
            return include_once(func_get_arg(5));
        }
        elseif($sink_func == "require"){
            return require(func_get_arg(5));
        }
        elseif($sink_func == "require_once"){
            return require_once(func_get_arg(5));
        }

        elseif($sink_func == "eval"){
            return eval(func_get_arg(5));
        }

    }

    function funcWrapped(){
        global $REDECLARED_FUNCIONS;
        $sink_func = func_get_arg(0);
        $call_stack = func_get_arg(1);
        $sink_hash = func_get_arg(2);
        $sink_class = func_get_arg(3);
        $sink_method = func_get_arg(4);
        $sink_index = func_get_arg(5);

        if(!function_exists($sink_func)){
            $lower_func = strtolower($sink_func);
            if (array_key_exists($lower_func, $REDECLARED_FUNCIONS)) {
                if(!function_exists($lower_func)) {
                    $index = rand(0, $REDECLARED_FUNCIONS[$lower_func]-1);
                    $redeclared_file_name = 'inst-redec_func-' . $lower_func . '_' . $index . '.php';
                    include $redeclared_file_name;
                }
            }
            else{
                $this->addDebugMessage(
                    "[#] FUNCTION does not exists:: " . $sink_func
                );
            }
        }

        $args = array();
        for($func_argv = 6; $func_argv < func_num_args(); $func_argv++){
            array_push($args, func_get_arg($func_argv));
        }

        $this->isBranchPassed($sink_hash,
            "FUNC(" . $sink_class . "::" . $sink_method . ")-" .
            $sink_func . "-" . $sink_index,
            $call_stack, $args);

        return call_user_func_array($sink_func, $args);
    }

    function setMethodWrapped($class, $prop_string, $hash, $invoke_result){
        // $this->addDebugMessage($hash);
        $this->class_invoke[$hash] = array(
                                            "call" => $invoke_result,
                                            "prop" => $prop_string
                                        );
        // echo "[#] Set: " . $hash . "\n";
        return $invoke_result;
    }
    function getMethodWrapped($hash, $invoke_method, $call_stack, $sink_class, $sink_method){
        if(gettype($this->class_invoke[$hash]['call']) == "object"){
            $invoke_class = get_class($this->class_invoke[$hash]['call']);
        }
        else{
            $invoke_class = $this->class_invoke[$hash]['call'];
        }

        // echo "[#] METHOD(" . $sink_class . "::" . $sink_method . ")-" . $invoke_class . "::" . $invoke_method . "\n";

        $this->isBranchPassed($hash,
            "METHOD(" . $sink_class . "::" . $sink_method . ")-" . $invoke_class . "::" . $invoke_method,
            $call_stack);

        return $this->class_invoke[$hash]['call'];
    }

    function initPath($chain_hash, $type, $class, $method, $dep_hash = NULL){
        array_push($this->goalPath,
            Array(
                "hash" => $chain_hash,
                "type" => $type,
                "dep_hash" => $dep_hash,
                "class" => $class,
                "method" => $method,
                "hitCount" => 0
            )
        );
        return True;
    }
    function initIfConstraint($hash, $cond){
        $this->ifConstraints[$hash] = $cond;
        return True;
    }
    function initControllableArgv($gadget_level, $argv, $argv_info, $value = NULL){
        $argv_token = explode("->", $argv);
        $current_prop = &$this->controllableArgv[$gadget_level];
        for($i = 0; $i < count($argv_token); $i++){
            if(!isset($current_prop[$argv_token[$i]]['deps'])){
                // first visit to leaf node
                $current_prop[$argv_token[$i]]['name'] = $argv_token[$i];
                $current_prop[$argv_token[$i]]['value'] = $value;
                $current_prop[$argv_token[$i]]['visibility'] = NULL;
                $current_prop[$argv_token[$i]]['type'] = "Unknown"; // Temp
                $current_prop[$argv_token[$i]]['deps'] = array();
            }
            else{
                // Not first visit
                // P.S) We do not need consider visibility of class method.
                // Becase, next class method was executed by own class method.
                $current_prop[$argv_token[$i]]['type'] = "Object"; // Temp
            }

            if($i == count($argv_token) - 1){
                $current_prop[$argv_token[$i]]['info'] = $argv_info;

                // Make amendments (type)
                if(empty($argv_info) == FALSE){
                    $current_prop[$argv_token[$i]]['type'] = $argv_info[0]['type'];
                }
            }

            $current_prop = &$current_prop[$argv_token[$i]]['deps'];
        }
    }

    function __destruct(){ // Do not execute another class __destruct.
        exit();
    }

    static function _fatalHandler(){
        $error = error_get_last();
        if($error['type'] == E_ERROR and
            substr($error['message'], 0, 24) == "Call to undefined method"){
            $undefined_method = substr(
                $error['message'],
                25,
                strlen($error['message']) - 25
            );

            $GLOBALS['Feedback_cls']->feedback_case['case'] = "ERROR";
            $GLOBALS['Feedback_cls']->feedback_case['msg'] = array(
                'type' => "UNDEF_METHOD",
                'contents' => $undefined_method
            );
        }
        elseif($error['type'] == E_ERROR and
            substr($error['message'],0,26) == "Call to a member function " and
            substr($error['message'], -16) == " on a non-object"){
            $called_method = substr($error['message'], 26, strlen($error['message'])-(26+16));
            // $GLOBALS['Feedback_cls']->addDebugMessage("CALLED_METHOD: " . $called_method);
            // $GLOBALS['Feedback_cls']->addDebugMessage($error);

            $GLOBALS['Feedback_cls']->feedback_case['case'] = "ERROR";
            $GLOBALS['Feedback_cls']->feedback_case['msg'] = array(
                'type' => "NON_OBJECT_METHOD",
                'contents' => $called_method,
                'class_invoke' => $GLOBALS['Feedback_cls']->class_invoke
            );
        }
        elseif($error['type'] == E_ERROR and
            substr($error['message'], 0, 34) == "Cannot instantiate abstract class "){

            $abstract_class = substr(
                $error['message'],
                34,
                strlen($error['message']) - 34
            );

            $GLOBALS['Feedback_cls']->feedback_case['case'] = "ERROR";
            $GLOBALS['Feedback_cls']->feedback_case['msg'] = array(
                'type' => 'ABSTRACT_CLASS',
                'contents' => $abstract_class
            );
        }
        /*
        else{
            $GLOBALS['Feedback_cls']->feedback_case['case'] = "ERROR";
            $GLOBALS['Feedback_cls']->feedback_case['msg'] = $error['message'];

        }
         */

        $goalPath = $GLOBALS['Feedback_cls']->goalPath;
        $feedback_case = $GLOBALS['Feedback_cls']->feedback_case;
        $branchHit = $GLOBALS['Feedback_cls']->BranchPath;

        $feedback_output = array(
            "goalPath" => $goalPath,
            "branchHit" => $branchHit,
            "case" => $feedback_case,
            "debug_message" => $GLOBALS['Feedback_cls']->debug_message
        );

        // RabbitMQ connection & produce MQ
        $rabbitmq_ip = getenv("RABBITMQ_IP");
        $rabbitmq_port = intval(getenv("RABBITMQ_PORT"));
        $rabbitmq_id = getenv("RABBITMQ_ID");
        $rabbitmq_password = getenv("RABBITMQ_PASSWORD");
        $rabbitmq_channel = getenv("RABBITMQ_CHANNEL");

        $connection = new AMQPStreamConnection($rabbitmq_ip,
            $rabbitmq_port,
            $rabbitmq_id,
            $rabbitmq_password);

        /*
        try{
            $connection = new AMQPStreamConnection($rabbitmq_ip,
                $rabbitmq_port,
                $rabbitmq_id,
                $rabbitmq_password);
        }
        catch(Exception $e){
            // "[!] Error RabbitMQ Connection Error";
        }
         */
        $channel = $connection->channel();
        $channel->queue_declare($rabbitmq_channel, false, false, false, true);
        if(getenv("FUZZ_CMD") == "FuzzerInit"){
            $msg = new AMQPMessage(serialize($GLOBALS['Feedback_cls']->init_output));
        }
        else{
            $msg = new AMQPMessage(serialize($feedback_output));
        }
        $channel->basic_publish($msg, '', $rabbitmq_channel);
    }
    static function _noticeHandler($errno, $errstr, $errfile, $errline){
        if($errno == E_NOTICE and
            substr($errstr, 0, 18) == "Undefined property") {
            $omit_property = explode(": ", $errstr)[1];
            $GLOBALS['Feedback_cls']->feedback_case['case'] = "NOTICE";
            $GLOBALS['Feedback_cls']->feedback_case['msg'] = array(
                "type" => "UNDEF_PROP",
                "contents" => $omit_property
            );
            exit();
        }
        else{
            // Execute default handler.
            return false;
        }
    }
}

register_shutdown_function("ConstraintFeedback::_fatalHandler");
set_error_handler("ConstraintFeedback::_noticeHandler");
srand(getenv("SEED_VALUE"));

$Feedback_cls = new ConstraintFeedback();
?>
