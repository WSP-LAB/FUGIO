<?php
error_reporting(E_ALL & ~E_NOTICE);
if(PHP_VERSION >= "7.2"){
  require_once __DIR__ . '/../Lib/rabbitmq_php7/vendor/autoload.php';
}
else{
  require_once __DIR__ . '/../Lib/rabbitmq_php/vendor/autoload.php';
}
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
# Connect RabbitMQ

$array_key_list_r353t = array();
$argv_target_list_r353t = ["GET", "POST", "COOKIE", "REQUEST", "FILES", "SESSION", "SERVER", "ENV"];
foreach ($argv_target_list_r353t as $argv_target_r353t) {
  $argv_decor_r353t = '_' . $argv_target_r353t;
  $argv_list_r353t[$argv_target_r353t] = $ {
    $argv_decor_r353t
  };
}
# Collect all parameters

$argv_list_r353t['AVAILABLE_MAGIC_METHODS'] = array();
class dummy_class_r353t {
  public $used_methods = array();
  public function __construct() {
    $this->pushMethod("__construct()");
  }
  public function __destruct() {
    $this->pushMethod("__destruct()");
  }
  public function __call($name, $arguments) {
    $this->pushMethod("__call(" . $name . ")");
  }
  public static function __callStatic($name, $arguments) {
    $this->pushMethod("__callStatic(" . $name . ")");
  }
  public function __get($name) {
    $this->pushMethod("__get(" . $name . ")");
  }
  public function __set($name, $value) {
    $this->pushMethod("__set(" . $name . ")");
  }
  public function __isset($name) {
    $this->pushMethod("__isset(" . $name . ")");
  }
  public function __unset($name) {
    $this->pushMethod("__unset(" . $name . ")");
  }
  public function __sleep() {
    $this->pushMethod("__sleep()");
  }
  public function __wakeup() {
    $this->pushMethod("__wakeup()");
  }
  public function __toString() {
    $this->pushMethod("__toString()");
    return "toString Triggered!";
  }
  public function __invoke() {
    $this->pushMethod("__invoke()");
  }
  public function __set_state() {
    $this->pushMethod("__set_state()");
  }
  public function __clone() {
    $this->pushMethod("__clone()");
  }
  public function __debuginfo() {
    $this->pushMethod("__debuginfo()");
  }
  private function pushMethod($method) {
    array_push($this->used_methods, $method);
  }
}
# Dummy class for magic method test

function get_class_methods_r353t($clsname) {
  $cls = new ReflectionClass($clsname);
  $cls_file = $cls->getFileName();
  $methods = Array();
  $traitMethods = array();
  $tmp_list = Array();
  array_push($tmp_list, $cls);
  while ($cur_cls = array_shift($tmp_list)) {
    $traits = $cur_cls->getTraits();
    foreach ($traits as $trait) {
      foreach ($trait->getMethods() as $method) {
        $traitMethods[] = array('TRAIT' => $trait->getName(), 'FILE' => $trait->getFileName(), 'LINE' => $method->getStartLine(), 'METHOD' => $method->getName());
      }
      array_push($tmp_list, $trait);
    }
    if (!$cur_cls->isTrait() && $cur_cls->getParentClass()) {
      array_push($tmp_list, $cur_cls->getParentClass());
    }
  }
  foreach ($cls->getMethods() as $func) {
    $func_class = $func->class;
    $func_class = str_replace("\0", "", $func_class);
    $func_class = str_replace("/", "_", $func_class);
    $func_name = $func->name; // NAME
    $func_real_name = $func_name;
    $func_real_class = $func_class; // CLASS
    $func_real_file = $func->getFileName();
    $func_line = $func->getStartLine();
    foreach ($traitMethods as $method) {
      if ($func_real_file == $method['FILE'] && $func_line == $method['LINE']) {
        $func_real_class = $method['TRAIT'];
        $func_real_name = $method['METHOD'];
        break;
      }
    }
    if ($func->isPublic()) $func_visibility = 1;
    if ($func->isProtected()) $func_visibility = 2;
    if ($func->isPrivate()) $func_visibility = 4;
    $method = Array("METHOD_INFO" => Array("METHOD_REAL_NAME" => $func_real_name, "METHOD_CLASS" => $func_real_class, "METHOD_FILE" => $func_real_file, "METHOD_VISIBILITY" => $func_visibility, "METHOD_STATIC" => $func->isStatic() ? True : False), "PARAMS" => Array());
    foreach ($func->getParameters() as $func_params) {
      $param_name = "$" . $func_params->name; // PARAM_NAME
      $param_default_value = NULL; // PARAM_DEFAULT_VALUE
      if ($func_params->isDefaultValueAvailable()) {
        $param_default_value = true;
      }
      $method_params = Array($param_name => Array("PARAM_DEFAULT_VALUE" => $param_default_value));
      array_push($method["PARAMS"], $method_params);
    }
    $methods[$func_name] = $method;
  }
  return $methods;
}

function get_class_props_r353t($clsname) {
  $cls = new ReflectionClass($clsname);
  // $default_properties = $cls->getDefaultProperties();
  $class_props = Array();
  foreach ($cls->getProperties() as $prop) {
    $prop_class = $prop->class;
    $prop_class = str_replace("\0", "", $prop_class);
    $prop_class = str_replace("/", "_", $prop_class);
    $prop_name = $prop->name;
    $prop_real_class = $prop_class;
    $prop_cls = new ReflectionClass($prop->class);
    if ($prop->isPublic()) $prop_visibility = 1;
    if ($prop->isProtected()) $prop_visibility = 2;
    if ($prop->isPrivate()) $prop_visibility = 4;
    $class_prop = Array("PROP_INFO" => Array("PROP_CLASS" => $prop_real_class, "PROP_FILE" => $prop_cls->getFileName(), "PROP_VISIBILITY" => $prop_visibility, "PROP_STATIC" => $prop->isStatic() ? True : False, "PROP_DEFAULT_VALUE" => null
    // [!] Temporary... checking is default value used in chain analyzer?
    // "PROP_DEFAULT_VALUE" => is_object($default_properties[$prop_name]) ? "[!] TODO: Need to handle default value (OBJECT)" : $default_properties[$prop_name]
    ));
    $class_props[$prop_name] = $class_prop;
  }
  return $class_props;
}

function get_user_classes_r353t($trigger_func, $func_argv) {
  $user_classes = Array();
  $declared_list = array_merge(get_declared_classes(), get_declared_interfaces());
  $declared_list = filter_allowed_classes(
    array_merge($declared_list, get_declared_traits()),
    $trigger_func,
    $func_argv);
  foreach ($declared_list as $clsname) {
    $cls = new ReflectionClass($clsname);
    $cls_org = $cls;
    if ($cls->isUserDefined()) {
      $class_parent = array();
      while ($parent = $cls->getParentClass()) {
        $parent_name = $parent->getName();
        $parent_name = str_replace("\0", "", $parent_name);
        $parent_name = str_replace("/", "_", $parent_name);
        $class_parent[] = array("NAME" => $parent_name, "FILE" => $parent->getFileName());
        $cls = $parent;
      }
      $cls_type = 0;
      if ($cls_org->isAbstract()) $cls_type = 16;
      if ($cls_org->isFinal()) $cls_type = 32;
      if ($cls_org->isInterface()) $cls_type = 64;
      $class_implement = array();
      $interfaces_cls = $cls_org->getInterfaces();
      foreach ($interfaces_cls as $interface_cls) {
        $interface_name = $interface_cls->getName();
        $interface_name = str_replace("\0", "", $interface_name);
        $interface_name = str_replace("/", "_", $interface_name);
        array_push($class_implement, array("NAME" => $interface_name, "FILE" => $interface_cls->getFileName()));
      }
      $class_trait = array();
      $traits_cls = $cls_org->getTraits();
      foreach ($traits_cls as $trait_cls) {
        $trait_name = $trait_cls->getName();
        $trait_name = str_replace("\0", "", $trait_name);
        $trait_name = str_replace("/", "_", $trait_name);
        array_push($class_trait, array("NAME" => $trait_name, "FILE" => $trait_cls->getFileName()));
      }
      $cls_info = Array("FILE_NAME" => $cls_org->getFileName(), "CLASS_PARENTS" => $class_parent, "INTERFACES" => $class_implement, "TRAITS" => $class_trait, "CLASS_TYPE" => $cls_type, "METHODS" => get_class_methods_r353t($clsname), "PROPS" => get_class_props_r353t($clsname));
      $clsname = str_replace("\0", "", $clsname);
      $clsname = str_replace("/", "_", $clsname);
      $user_classes[$clsname] = $cls_info;
    }
  }
  return $user_classes;
}
# Collect class informations

function get_user_function_r353t() {
  $user_functions = get_defined_functions() ['user'];
  $user_func_list = array();
  foreach ($user_functions as $user_function) {
    $func_info = array();
    $ref_func = new ReflectionFunction($user_function);
    $func_info["FILE"] = $ref_func->getFileName();
    $param_info = array();
    foreach ($ref_func->getParameters() as $func_params) {
      $param_name = "$" . $func_params->name; // PARAM_NAME
      $param_default_value = NULL; // PARAM_DEFAULT_VALUE
      if ($func_params->isDefaultValueAvailable()) {
        $param_default_value = true;
      }
      $method_params = Array("PARAM_DEFAULT_VALUE" => $param_default_value);
      $param_info[$param_name] = $method_params;
    }
    $func_info["PARAMS"] = $param_info;
    $user_function = str_replace("\0", "", $user_function);
    $user_function = str_replace("/", "_", $user_function);
    $user_func_list[$user_function] = $func_info;
  }
  return $user_func_list;
}

function isPharDetected_r353t($case, $validator_md5) {
  if (substr($case, 0, 7) == "phar://") {
    if (function_exists('override_is_file')) {
      $func_is_file = 'override_is_file';
    } else {
      $func_is_file = 'is_file';
    }
    if (function_exists('override_md5_file')) {
      $func_md5_file = 'override_md5_file';
    } else {
      $func_md5_file = 'md5_file';
    }
    $case_explode = explode("/", substr($case, 7));
    $case_path = array();
    foreach ($case_explode as $case_frag) {
      array_push($case_path, $case_frag);
      if ($case_frag == "") {
        continue;
      }
      $path_str = implode("/", $case_path);
      if ($func_is_file($path_str) and $func_md5_file($path_str) == $validator_md5) {
        return True;
      }
    }
  }
  return False;
}

function get_declared_classes_r353t($trigger_func, $func_argv) {
  $declared_classes = array();
  foreach (filter_allowed_classes(get_declared_classes(), $trigger_func, $func_argv) as $clsname) {
    $cls = new \ReflectionClass($clsname);
    $clsname = str_replace("\0", "", $clsname);
    $clsname = str_replace("/", "_", $clsname);
    $declared_classes[$clsname] = array("INTERNAL" => $cls->isInternal(), "FILE" => $cls->getFileName());
  }
  return $declared_classes;
}

function get_declared_interfaces_r353t() {
  $interfaces = array();
  foreach (get_declared_interfaces() as $interface) {
    $reflect = new \ReflectionClass($interface);
    $intname = $reflect->getName();
    $intname = str_replace("\0", "", $intname);
    $intname = str_replace("/", "_", $intname);
    $interfaces[$intname] = array("INTERNAL" => $reflect->isInternal(), "FILE" => $reflect->getFileName());
  }
  return $interfaces;
}

function get_declared_traits_r353t() {
  $traits = array();
  foreach (get_declared_traits() as $trait) {
    $reflect = new \ReflectionClass($trait);
    $traitname = $reflect->getName();
    $traitname = str_replace("\0", "", $traitname);
    $traitname = str_replace("/", "_", $traitname);
    $traits[$traitname] = array("INTERNAL" => $reflect->isInternal(), "FILE" => $reflect->getFileName());
  }
  return $traits;
}

function filter_allowed_classes($array, $trigger_func, $func_argv) {
  $return_array = $array;
  if ($trigger_func == "unserialize" && count($func_argv) > 1) {
      if (array_key_exists("allowed_classes", $func_argv[1])) {
          if (gettype($func_argv[1]["allowed_classes"]) == "boolean") {
              if ($func_argv[1]["allowed_classes"] == false) {
                  $return_array = Array();
              } else if ($func_argv[1]["allowed_classes"] == true) {
                  $return_array = $array;
              }
          } else if (gettype($func_argv[1]["allowed_classes"]) == "array") {
              $allowed_classes = $func_argv[1]["allowed_classes"];
              $return_array = array_intersect($array, $allowed_classes);
          }
      }
  }
  return $return_array;
}

function saveDatas_r353t($argv_list, $trigger_func, $func_argv, $inject_idxs) {
  global $argv_list_r353t;
  $inject_points = explode(",", $inject_idxs);
  $detect_flag = False;
  // Debugging
  // $log_fd = fopen("/tmp/point_log.txt", "a");
  // $log_data = var_export(array_merge(
  //   array($trigger_func),
  //   array($func_argv),
  //   array($_SERVER['PHP_SELF']),
  //   array(var_export($_GET, true)),
  //   array(var_export($_POST, true))
  // ), ture) . "\n";
  // fwrite($log_fd, $log_data);
  // fclose($log_fd);
  // return 1;

  foreach ($inject_points as $inject_point) {
    if (array_key_exists($inject_point - 1, $func_argv) and gettype($func_argv[$inject_point - 1]) == "string") {
      $detect_message = $func_argv[$inject_point - 1];
      if (strpos($detect_message, 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}') !== FALSE or isPharDetected_r353t($detect_message, '{{__POINT_PHAR_HASH__}}')) {
        $detect_flag = True;
        break;
      }
    }
  }
  if ($detect_flag == False) {
    return False;
  }

  error_reporting(E_ALL);
  ini_set("display_errors", 1);
  ini_set('log_errors', 1);

  $class_name_list = Array('{{__CLASS_LIST__}}');
  $class_list = Array();
  $pid_list = Array();

  if ($trigger_func == "unserialize" && count($func_argv) > 1) {
      if (array_key_exists("allowed_classes", $func_argv[1])) {
          if (gettype($func_argv[1]["allowed_classes"]) == "boolean") {
              if ($func_argv[1]["allowed_classes"] == false) {
                  $class_name_list = Array();
              } else if ($func_argv[1]["allowed_classes"] == true) {
                  $class_name_list = $class_name_list;
              }
          } else if (gettype($func_argv[1]["allowed_classes"]) == "array") {
              $allowed_classes = $func_argv[1]["allowed_classes"];
              $class_name_list = array_intersect($class_name_list, $allowed_classes);
          }
      }
  }

  foreach($class_name_list as $cls_name) {
    $pid = pcntl_fork();
    // error_log("Fork: $cls_name\n");
    if ($pid == -1) error_log("Fail to fork\n");
    elseif ($pid) {
      // Parent
      $pid_list[$pid] = $cls_name;
    }
    else {
      // Child
      try {
        register_shutdown_function('AutoloadErrorHandler');
        class_exists($cls_name);
      }
      catch (Exception $e) {
        shell_exec("kill -6 " . posix_getpid());
      }
      shell_exec("kill -9 " . posix_getpid());
      exit;
    }
  }

  $try_classes = array();
  while (count($pid_list) > 0) {
    error_log("count: " . count($pid_list));
    foreach ($pid_list as $pid => $cls_name) {
      $res = pcntl_waitpid($pid, $status, WNOHANG);

      // If the process has already exited
      if ($res == -1 || $res > 0) {
        unset($pid_list[$pid]);
        if (pcntl_wifexited($status)) {
          // Error
          // error_log("Error: $cls_name\n");
        }
        else if (pcntl_wifsignaled($status)) {
          if (pcntl_wtermsig($status) == 9) {
            // Success
            // error_log("Success: $cls_name\n");
            $class_list[] = $cls_name;
          }
          else if (pcntl_wtermsig($status) == 6) {
            // Error
            // error_log("Error: $cls_name\n");
          }
        }
      }
      if ($res == 0) {
        if (!array_key_exists($cls_name, $try_classes)) {
          $try_classes[$cls_name] = 1;
        }
        else {
          $try_classes[$cls_name] += 1;
        }
        if ($try_classes[$cls_name] > 10) {
          unset($pid_list[$pid]);
          shell_exec("kill -6 " . $pid);
        }
      }
    }
    sleep(1);
  }

  error_log(var_export($class_list, True));

  foreach($class_list as $cls_name) {
    error_log("$cls_name");
    class_exists($cls_name);
  }

  $argv_list_r353t['TRIGGER_FUNC'] = $trigger_func;
  $argv_list_r353t['FUNC_ARGV'] = $func_argv;
  $argv_list_r353t['CLASSES'] = get_declared_classes_r353t($trigger_func, $func_argv);
  $argv_list_r353t['INTERFACES'] = get_declared_interfaces_r353t();
  $argv_list_r353t['TRAITS'] = get_declared_traits_r353t();
  $argv_list_r353t['USER_CLASSES'] = get_user_classes_r353t($trigger_func, $func_argv);
  $argv_list_r353t['INCLUDED_FILES'] = get_included_files();
  $copied_globals = new ArrayObject($GLOBALS);
  $argv_list_r353t['GLOBALS'] = $copied_globals->getArrayCopy();
  $argv_list_r353t['EVAL_CODE'] = eval_log();
  $argv_list_r353t['USER_FUNCTIONS'] = get_user_function_r353t();
  // $argv_list['FUNCTIONS'] = get_defined_functions();
  $argv_list_r353t['CONSTANTS'] = get_defined_constants(true)['user'];
  $argv_list_r353t['AUTOLOAD'] = spl_autoload_functions();
  $argv_list_r353t['AVAILABLE_MAGIC_METHODS'] = array();
  $connection = new AMQPStreamConnection('{{__POINT_RABBITMQ_IP__}}', {{__POINT_RABBITMQ_PORT__}}, '{{__POINT_RABBITMQ_ID__}}', '{{__POINT_RABBITMQ_PW__}}');
  $channel = $connection->channel();
  $channel->queue_declare('trigger_func_channel', false, false, false, false);
  if (PHP_VERSION >= "5.5.0") {
    $encoded_data = json_encode($argv_list_r353t, JSON_PARTIAL_OUTPUT_ON_ERROR);
  } else {
    $encoded_data = jsond_encode($argv_list_r353t,
                                 JSOND_PARTIAL_OUTPUT_ON_ERROR);
  }
  $msg = new AMQPMessage($encoded_data);
  $channel->basic_publish($msg, '', 'trigger_func_channel');
}

function AutoloadErrorHandler() {
  shell_exec("kill -6 " . posix_getpid());
}

$argv_list_r353t['FUNCTIONS'] = get_defined_functions();
