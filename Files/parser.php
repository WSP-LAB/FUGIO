<?php
if (PHP_VERSION >= "7.2") {
  define("PHP7", true);
}
else {
  define("PHP7", false);
}

if (PHP7) {
  require_once __DIR__ . '/../Lib/rabbitmq_php7/vendor/autoload.php';
  require __DIR__ . '/../Lib/PHP-Parser7/vendor/autoload.php';
}
else {
  require_once __DIR__ . '/../Lib/rabbitmq_php/vendor/autoload.php';
  require __DIR__ . '/../Lib/PHP-Parser/vendor/autoload.php';
}

error_reporting(E_ALL & ~E_NOTICE);
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection($argv[2], 5672, 'fugio', 'fugio_password');
$channel = $connection->channel();
$queue_name = 'ast_channel' . str_replace('/', '_', $argv[3]);
$channel->queue_declare($queue_name, false, false, false, false);

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

class MyNodeVisitor extends NodeVisitorAbstract
{
  public $start = 0;
  public $pretty_printer;
  public $user_classes;
  public $user_functions;
  public $class_name;
  public $class_info;
  public $func_name;
  public $func_info;
  public $prop_name;
  public $prop_info;
  public $method_name;
  public $method_info;
  public $trait_info;
  public $namespace = '';
  public $pass = False;
  public $call_list = Array();
  public $var_list = Array();
  public $replace_flag = False;
  public $replace_var_list = Array();
  public $taint_list = Array();
  public $uses = Array();
  public $depth_cnt = 0;
  public $include_this = 0;
  public $idx = 0;
  public $func_idx = Array();
  public $for_list = Array();
  public $array_access_list = Array();
  public $string_list = Array();

  public $global_vars = ['$GLOBALS', '$_SERVER', '$_REQUEST', '$_POST', '$_GET',
                         '$_FILES', '$_ENV', '$_COOKIE', '$_SESSION'];

  private function parsesParams(array $params) {
    $param_list = Array();
    foreach ($params as $param) {
      if (PHP7) {
        $name = $this->toString($param->var); // Expr\Variable | Expr\Error
      }
      else {
        $name = "$" . $this->toString($param->name);
      }
      $param_list[$name] = Array(
        "DEFAULT" => $this->toString($param->default)
      );
    }
    return $param_list;
  }

  private function parseArgs(array $args) {
    $arg_list = Array();
    foreach ($args as $arg) {
      array_push($arg_list, $this->toString($arg->value));
    }
    return $arg_list;
  }

  private function toString($arg) {
    if ($arg == null) {
      return $arg;
    }
    else if (is_string($arg)) {
      return $arg;
    }
    else if ($arg instanceof Node\Scalar\EncapsedStringPart) {
      return $arg->value;
    }
    else if ($arg instanceof Node\Name) {
      return $arg->toString();
    }
    else if ($arg instanceof Node\Identifier) {
      return $arg->toString();
    }
    else if ($arg instanceof Node\Expr) {
      return $this->pretty_printer->prettyPrintExpr($arg);
    }
    else if (is_array($arg)) {
      return utf8_encode($this->pretty_printer->prettyPrint($arg));
    }
    else {
      return utf8_encode($this->pretty_printer->prettyPrint(array($arg)));
    }
  }

  private function addNamespace($name, $uses = true) {
    if ($name instanceof Node\Name\FullyQualified) {  // \Name
      return $this->toString($name);
    }
    if (!is_string($name)) {
      $name = $this->toString($name);
    }
    if ($uses) {
      $split_name = explode('\\', $name);
      $key = $split_name[0];
      array_shift($split_name);
      if (array_key_exists($key, $this->uses)) {
        $return_value = $this->uses[$key]['VALUE'];
        if ($split_name) {
          $return_value .= '\\'.join($split_name);
        }
        return $return_value;
      }
    }
    if ($this->namespace != '') {
      return $this->namespace . '\\' . $name;
    }
    return $name;
  }

  private function getCode($node) {
    $code = '';
    $code .= "namespace " . $this->namespace . " {\n";
    foreach($this->uses as $use) {
      $code .= "use " . $use["DECL"] . ";\n";
    }
    $code .= $this->toString($node);
    $code .= "\n}";
    return $code;
  }

  public function beforeTraverse(array $nodes) {
    $this->pretty_printer = new PrettyPrinter\Standard();
    $this->user_classes = Array();
    $this->user_functions = Array();
  }

  public function enterNode(Node $node) {
    $this->idx += 1;

    if ($this->pass)
      return;

    if ($node instanceof Node\Stmt\If_) {
      if ($node->stmts[0] instanceof Node\Stmt\Class_ ||
        $node->stmts[0] instanceof Node\Stmt\Interface_ ||
        $node->stmts[0] instanceof Node\Stmt\Function_) {
        if ($this->toString($node->cond) == '\false'){
          $this->pass = True;
        }
      }
    }

    if ($node instanceof Node\Expr\Variable) {
      if ($node->name == 'this') {
        $this->include_this = 1;
      }
    }

    if ($node instanceof Node\Stmt\Class_) {
      if ($this->start == 0) {
        $this->class_name = $this->addNamespace($node->name, false);
        $parents = array();
        if ($node->extends != null) {
          array_push($parents,
                     array('NAME' => $this->addNamespace($node->extends),
                           'TYPE' => 'CLASS')); // null | Node\Name
        }
        $implements = array();
        foreach ($node->implements as $implement) {       // Node\Name[]
          array_push($implements,
                     array('NAME' => $this->addNamespace($implement),
                           'TYPE' => 'INTERFACE'));
        }
        if (PHP7) {
          $this->class_info = Array(
            "TYPE" => $node->flags,
            "NAME" => $this->toString($node->name),
            "PARENTS" => $parents,
            "IMPLEMENTS" => $implements,
            "PROPS" => Array(),
            "METHODS" => Array(),
            "TRAITS" => Array(),
            "NAMESPACE" => $this->namespace,
            "USES" => $this->uses,
            "DECL" => $this->getCode($node)
          );
        }
        else {
          $this->class_info = Array(
            "TYPE" => $node->type,
            "NAME" => $this->toString($node->name),
            "PARENTS" => $parents,
            "IMPLEMENTS" => $implements,
            "PROPS" => Array(),
            "METHODS" => Array(),
            "TRAITS" => Array(),
            "NAMESPACE" => $this->namespace,
            "USES" => $this->uses,
            "DECL" => $this->getCode($node)
          );
        }
      }
      $this->start += 1;
    }

    if ($node instanceof Node\Stmt\Interface_) {
      if ($this->start == 0) {
        $this->class_name = $this->addNamespace($node->name, false);
        $implements = array();
        foreach ($node->extends as $extend) {       // Node\Name[]
          array_push($implements,
                     array('NAME' => $this->addNamespace($extend),
                           'TYPE' => 'INTERFACE'));
        }
        $this->class_info = Array(
          "TYPE" => 64,
          "NAME" => $this->toString($node->name),
          "PARENTS" => Array(),
          "IMPLEMENTS" => $implements,          // null | Node\Name[]
          "PROPS" => Array(),
          "METHODS" => Array(),
          "TRAITS" => Array(),
          "NAMESPACE" => $this->namespace,
          "USES" => $this->uses,
          "DECL" => $this->getCode($node)
        );
      }
      $this->start += 1;
    }

    if ($node instanceof Node\Stmt\Trait_) {
      if ($this->start == 0) {
        $this->class_name = $this->addNamespace($node->name, false);
        $this->class_info = Array(
          "TYPE" => 128,
          "NAME" => $this->toString($node->name),
          "PARENTS" => Array(),
          "IMPLEMENTS" => Array(),
          "PROPS" => Array(),
          "METHODS" => Array(),
          "TRAITS" => Array(),
          "NAMESPACE" => $this->namespace,
          "USES" => $this->uses,
          "DECL" => $this->getCode($node)
        );
      }
      $this->start += 1;
    }

    if ($node instanceof Node\Stmt\Function_) {
      $this->init();
      if ($this->start == 0) {
        if ($node->name != '__autoload') {
          $this->taint_list = Array();
          $this->func_name = $this->addNamespace($node->name);
          $this->replace_var_list = Array();
          foreach ($node->params as $param) {
            if (PHP7) {
              $name = $this->toString($param->var);
            }
            else {
              $name = '$' . $this->toString($param->name);
            }
            if (in_array($name, $this->global_vars)) {
              $this->replace_flag = True;
              array_push($this->replace_var_list, $name);
              if (PHP7) {
                $param->var .= '_dup';
              }
              else {
                $param->name .= '_dup';
              }
            }
            $this->taint_list[$name] = array(array("IDX" => $this->idx,
                                                    "TYPE" => "ARG",
                                                    "ROOT" => $name));
          }
          $this->func_info = Array(
            "TYPE" => "FuncDecl",
            "NAME" => $this->toString($node->name),
            "PARAMS" => $this->parsesParams($node->params),
            "NAMESPACE" => $this->namespace,
            "USES" => $this->uses,
            "DECL" => $this->getCode($node)
          );
          $this->call_list = Array();
        }
      }
      $this->start += 1;
    }

    if ($this->start > 1)
      return;

    if ($node instanceof Node\Stmt\TraitUse) {
      $this->trait_info = Array("TRAITS" => Array(), "ADAPTIONS" => Array());
      foreach($node->traits as $trait) {
        array_push($this->trait_info["TRAITS"],
                   array('NAME' => $this->addNamespace($trait),
                         'TYPE' => 'TRAIT'));
      }

      foreach($node->adaptations as $adap) {
        if ($adap instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
          $adap_info = Array("TYPE" => 'PRECEDENCE');
          $adap_info["TRAIT"] = $this->addNamespace($adap->trait);  // Node\Name
          $adap_info["METHOD"] = $this->toString($adap->method);    // string
                                                          // (| Node\Identifier)
          foreach($adap->insteadof as $inst) {  // Node\Name[]
            $adap_info["INSTEAD"][] = $this->addNamespace($inst);
          }
        }
        else if ($adap instanceof Node\Stmt\TraitUseAdaptation\Alias) {
          $adap_info = Array("TYPE" => 'ALIAS');
          $adap_info["TRAIT"] = $this->addNamespace($adap->trait);  // null |
                                                                    // Node\Name
          $adap_info["METHOD"] = $this->toString($adap->method);    // string
                                                          // (| Node\Identifier)
          $adap_info["NEW_MODIFIER"] = $adap->newModifier;        // null | int
          $adap_info["NEW_NAME"] = $this->toString($adap->newName); // null |
                                                                    // string
                                                          // (| Node\Identifier)
        }
        $this->trait_info["ADAPTIONS"][] = $adap_info;
      }
    }

    if ($node instanceof Node\Stmt\Property) {
      if (PHP7) {
        $this->prop_info = Array(
          "TYPE" => $node->flags
        );
      }
      else {
        $this->prop_info = Array(
          "TYPE" => $node->type
        );
      }
    }

    if ($node instanceof Node\Stmt\PropertyProperty) {
      $this->prop_name = $this->toString($node->name);    // string
                                                  // (| Node\VarLikeIdentifier)
      $this->prop_info["DEFAULT"]
                = $this->toString($node->default);        // null | Expr
    }

    if ($node instanceof Node\Stmt\ClassMethod) {
      $this->init();
      $this->taint_list = Array();
      $this->taint_list['$this'] = array(array("IDX" => $this->idx,
                                               "TYPE" => "PROP",
                                               "ROOT" => '$this'));
      $this->method_name = $this->toString($node->name);  // string
                                                          // (| Node\Identifier)
      $this->replace_var_list = Array();
      // echo "$this->method_name\n";
      foreach ($node->params as $param) {
        if (PHP7) {
          $name = $this->toString($param->var);
        }
        else {
          $name = '$' . $this->toString($param->name);
        }
        if (in_array($name, $this->global_vars)) {
          $this->replace_flag = True;
          array_push($this->replace_var_list, $name);
          if (PHP7) {
            $param->var .= '_dup';
          }
          else {
            $param->name .= '_dup';
          }
        }
        $this->taint_list[$name] = array(array("IDX" => $this->idx,
                                                  "TYPE" => "ARG",
                                                  "ROOT" => $name));
      }
      if (PHP7) {
        $this->method_info = Array(
          "TYPE" => $node->flags,                           // int
          "PARAMS" => $this->parsesParams($node->params)    // Node\Param[]
        );
      }
      else {
        $this->method_info = Array(
          "TYPE" => $node->type,                            // int
          "PARAMS" => $this->parsesParams($node->params)    // Node\Param[]
        );
      }
      $this->call_list = Array();
    }

    if ($node instanceof Node\Expr\FuncCall ||
        $node instanceof Node\Expr\MethodCall ||
        $node instanceof Node\Expr\StaticCall ||
        $node instanceof Node\Expr\Print_ ||
        $node instanceof Node\Stmt\Echo_ ||
        $node instanceof Node\Expr\Exit_ ||
        $node instanceof Node\Expr\Include_ ||
        $node instanceof Node\Expr\Eval_) {
      $this->include_this = 0;
    }

    if ($node instanceof Node\Expr\FuncCall) {
      $func_name = $this->toString($node->name);
      if (!array_key_exists($func_name, $this->func_idx)) {
        $this->func_idx[$func_name] = 1;
      }
      else {
        $this->func_idx[$func_name] += 1;
      }
    }

    if ($node instanceof Node\Expr\Print_) {
      $func_name = 'print';
      if (!array_key_exists($func_name, $this->func_idx)) {
        $this->func_idx[$func_name] = 1;
      }
      else {
        $this->func_idx[$func_name] += 1;
      }
    }

    if ($node instanceof Node\Stmt\Echo_) {
      $func_name = 'echo';
      if (!array_key_exists($func_name, $this->func_idx)) {
        $this->func_idx[$func_name] = 1;
      }
      else {
        $this->func_idx[$func_name] += 1;
      }
    }

    if ($node instanceof Node\Expr\Exit_) {
      $KIND_EXIT = 1;
      $KIND_DIE = 2;

      $func_name = 'exit';
      if ($node->getAttribute('kind') == $KIND_EXIT) {
        $func_name = 'exit';
      }
      elseif ($node->getAttribute('kind') == $KIND_DIE) {
        $func_name = 'die';
      }

      if (!array_key_exists($func_name, $this->func_idx)) {
        $this->func_idx[$func_name] = 1;
      }
      else {
        $this->func_idx[$func_name] += 1;
      }
    }

    if ($node instanceof Node\Expr\Include_) {
      $TYPE_INCLUDE = 1;
      $TYPE_INCLUDE_ONCE = 2;
      $TYPE_REQUIRE = 3;
      $TYPE_REQUIRE_ONCE = 4;

      $func_name = 'include';
      if ($node->type == $TYPE_INCLUDE) {
        $func_name = 'include';
      }
      elseif ($node->type == $TYPE_INCLUDE_ONCE) {
        $func_name = 'include_once';
      }
      elseif ($node->type == $TYPE_REQUIRE) {
        $func_name = 'require';
      }
      elseif ($node->type == $TYPE_REQUIRE_ONCE) {
        $func_name = 'require_once';
      }

      if (!array_key_exists($func_name, $this->func_idx)) {
        $this->func_idx[$func_name] = 1;
      }
      else {
        $this->func_idx[$func_name] += 1;
      }
    }

    if ($node instanceof Node\Expr\Eval_) {
      $func_name = 'eval';
      if (!array_key_exists($func_name, $this->func_idx)) {
        $this->func_idx[$func_name] = 1;
      }
      else {
        $this->func_idx[$func_name] += 1;
      }
    }

    if ($node instanceof Node\Expr\Assign) {
      $expr = $node->expr;
      if ($expr instanceof Node\Expr\New_) {
        $this->include_this = 0;
      }
      $var_info = Array(
        "TYPE" => "Def",
        "VAR" => $this->toString($node->var)        // Expr
      );
      array_push($this->var_list, $var_info);
    }

    if ($node instanceof Node\Expr\AssignOp) {
      $var_info = Array(
        "TYPE" => "Def",
        "VAR" => $this->toString($node->var)        // Expr
      );
      array_push($this->var_list, $var_info);
    }

    if ($node instanceof Node\Expr\AssignRef) {
      $var_info = Array(
        "TYPE" => "Def",
        "VAR" => $this->toString($node->var)        // Expr
      );
      array_push($this->var_list, $var_info);
    }

    if ($node instanceof Node\Expr\PropertyFetch) {
      $var_info = Array(
        "TYPE" => "Use",
        "VAR" => $this->toString($node->var).       // Expr
             '->'.
             $this->toString($node->name)           // string | Expr
                                                    // (| Identifier)
      );
      array_push($this->var_list, $var_info);
    }

    if ($node instanceof Node\Expr\PropertyFetch) {
      $flag = False;
      $new_var_info = array();
      $var = $this->toString($node);

      if (!array_key_exists($var, $this->taint_list)) {
        $new_var_info = array();
      }
      else {
        $new_var_info = $this->taint_list[$var];
      }

      foreach ($this->taint_list as $var_name => $var_info) {
        if (strpos($this->toString($node->var), $var_name) !== False &&
            $this->in_expr($var_name, $node->var)) {
          $flag = True;
          foreach($var_info as $info) {
            $new_info["IDX"] = $this->idx;
            $new_info["TYPE"] = $info["TYPE"];
            $new_info["ROOT"] = $info["ROOT"];

            $dup = False;
            foreach($new_var_info as $item) {
              if ($item["TYPE"] == $new_info["TYPE"] &&
                  $item["ROOT"] == $new_info["ROOT"]) {
                $dup = True;
                break;
              }
            }

            if ($dup == False) {
              $new_var_info[] = $new_info;
            }
          }
        }
      }
      if ($flag) {
        $this->taint_list[$var] = $new_var_info;
        // echo $this->toString($node) . "\n";
        // var_dump($this->taint_list);
      }
    }

    if ($node instanceof Node\Arg){
      $node->byRef = False;
    }

    if ($node instanceof Node\Stmt\Use_) {
      foreach ($node->uses as $use) {
        if ($use->alias) {
          $key = $this->toString($use->alias);    // null | string
                                                  // (| Identifier)
        }
        else {
          $key = $this->toString($use->name);     // Node\Name
          $key = explode('\\', $key);
          $key = end($key);
        }
        $this->uses[$key] = array("VALUE" => $this->toString($use->name),
                                  "DECL" => $this->toString($use));
      }
    }

    if ($node instanceof Node\Stmt\Namespace_) {
      $this->namespace = $this->toString($node->name); // null | Node\Name
      $this->uses = array();
    }

    if ($node instanceof Node\Expr\Variable) {
      if ($this->replace_flag) {
        if (in_array($node->name, $this->replace_var_list)) { // string | Expr
          $node->name .= '_dup';
        }
      }
    }

    if ($node instanceof Node\Expr\ArrayDimFetch) {
      $this->array_access_list[$this->toString($node->var)] = $this->toString($node->dim);
    }

    if ($node instanceof Node\Scalar\Encapsed) {
      foreach($node->parts as $part) {
        if (!$part instanceof Node\Scalar\EncapsedStringPart) {
          $this->string_list[] = $this->toString($part);
        }
      }
    }

    if ($node instanceof Node\Expr\BinaryOp\Concat) {
      if (!$node->left instanceof Node\Scalar\String_ &&
          !$node->left instanceof Node\Scalar\Encapsed) {
        $this->string_list[] = $this->toString($node->left);
      }
      if (!$node->right instanceof Node\Scalar\String_ &&
          !$node->right instanceof Node\Scalar\Encapsed) {
        $this->string_list[] = $this->toString($node->right);
      }
    }

    if ($node instanceof Node\Stmt\Foreach_) {
      // $this->for_list[] = $this->toString($node->expr);
      $this->for_list[$this->toString($node->expr)] =
                        array($this->toString($node->keyVar),
                              $this->toString($node->valueVar));
      $node_var_list = array($node->keyVar, $node->valueVar);
      foreach($node_var_list as $node_var) {
        $flag = False;
        if ($node_var != null) {
          $var = $this->toString($node_var);

          if (!array_key_exists($var, $this->taint_list)) {
            $new_var_info = array();
          }
          else {
            $new_var_info = $this->taint_list[$var];
          }

          foreach ($this->taint_list as $var_name => $var_info) {
            if ($this->in_expr($var_name, $node->expr)) {
              $flag = True;
              foreach($var_info as $info) {
                $new_info["IDX"] = $this->idx;
                $new_info["TYPE"] = $info["TYPE"];
                $new_info["ROOT"] = $info["ROOT"];

                $dup = False;
                foreach($new_var_info as $item) {
                  if ($item["TYPE"] == $new_info["TYPE"] &&
                      $item["ROOT"] == $new_info["ROOT"]) {
                    $dup = True;
                    break;
                  }
                }

                if ($dup == False) {
                  $new_var_info[] = $new_info;
                }
              }
            }
          }
          if ($flag) {
            $this->taint_list[$var] = $new_var_info;
            // echo $this->toString($node) . "\n";
            // var_dump($this->taint_list);
          }
        }
      }
    }

    if ($node instanceof Node\Expr\Assign ||
        $node instanceof Node\Expr\AssignOp ||
        $node instanceof Node\Expr\AssignRef) {

      $flag = False;
      if ($node->var instanceof Node\Expr\ArrayDimFetch) {
        $var = $this->toString($node->var->var);
      }
      else {
        $var = $this->toString($node->var);
      }

      if (!array_key_exists($var, $this->taint_list)) {
        $new_var_info = array();
      }
      else {
        $new_var_info = $this->taint_list[$var];
      }
      foreach ($this->taint_list as $var_name => $var_info) {
        if (strpos($this->toString($node->expr), $var_name) !== False &&
            $this->in_expr($var_name, $node->expr)) {
          $flag = True;
          foreach($var_info as $info) {
            $new_info["IDX"] = $this->idx;
            $new_info["TYPE"] = $info["TYPE"];
            $new_info["ROOT"] = $info["ROOT"];

            $dup = False;
            foreach($new_var_info as $item) {
              if ($item["TYPE"] == $new_info["TYPE"] &&
                  $item["ROOT"] == $new_info["ROOT"]) {
                $dup = True;
                break;
              }
            }

            if ($dup == False) {
              $new_var_info[] = $new_info;
            }
          }
        }
      }

      if ($flag) {
        $this->taint_list[$var] = $new_var_info;
        // echo $this->toString($node) . "\n";
        // var_dump($this->taint_list);
      }
    }

    if ($node instanceof Node\Expr\Ternary ||
        $node instanceof Node\Stmt\Case_ ||
        $node instanceof Node\Stmt\Do_ ||
        $node instanceof Node\Stmt\ElseIf_ ||
        $node instanceof Node\Stmt\Switch_ ||
        $node instanceof Node\Stmt\While_ ||
        $node instanceof Node\Stmt\If_ ||
        $node instanceof Node\Stmt\For_ ) {
      $this->depth_cnt += 1;
    }
  }

  public function leaveNode(Node $node) {
    if ($this->pass) {
      if ($node instanceof Node\Stmt\If_) {
        if ($node->stmts[0] instanceof Node\Stmt\Class_ ||
          $node->stmts[0] instanceof Node\Stmt\Interface_ ||
          $node->stmts[0] instanceof Node\Stmt\Function_) {
          if ($this->toString($node->cond) == 'false'){
            $this->pass = False;
          }
        }
      }
      else {
        return;
      }
    }

    if ($node instanceof Node\Stmt\Class_) {
      if ($this->start == 1) {
        $this->class_info["DECL"] = $this->getCode($node);
        $this->user_classes[$this->class_name] = $this->class_info;
      }
      $this->start -= 1;
    }

    if ($node instanceof Node\Stmt\Interface_) {
      if ($this->start == 1) {
        $this->class_info["DECL"] = $this->getCode($node);
        $this->user_classes[$this->class_name] = $this->class_info;
      }
      $this->start -= 1;
    }

    if ($node instanceof Node\Stmt\Trait_) {
      if ($this->start == 1) {
        $this->class_info["DECL"] = $this->getCode($node);
        $this->user_classes[$this->class_name] = $this->class_info;
      }
      $this->start -= 1;
    }

    if ($node instanceof Node\Stmt\Function_) {
      if ($this->start == 1) {
        if ($node->name == '__autoload') {
          return NodeTraverser::REMOVE_NODE;
        }
        $this->replace_flag = False;
        $this->func_info["DECL"] = $this->getCode($node);
        $this->func_info["CALLS"] = $this->call_list;
        $this->func_info["TAINT"] = $this->taint_list;
        $this->func_info["FOR"] = $this->for_list;
        $this->func_info["ARRAY_ACCESS"] = $this->array_access_list;
        $this->func_info["STRING"] = $this->string_list;
        $this->user_functions[$this->func_name] = $this->func_info;
        $this->call_list = Array();
      }
      $this->start -= 1;
    }

    if ($this->start > 1)
      return;

    if ($node instanceof Node\Stmt\TraitUse) {
      $this->class_info["TRAITS"][] = $this->trait_info;
    }

    if ($node instanceof Node\Stmt\Property) {
      $this->class_info["PROPS"][$this->prop_name] = $this->prop_info;
    }

    if ($node instanceof Node\Stmt\ClassMethod) {
      $this->replace_flag = False;
      $this->method_info["CALLS"] = $this->call_list;
      $this->method_info["VARS"] = $this->var_list;
      $this->method_info["TAINT"] = $this->taint_list;
      $this->method_info["FOR"] = $this->for_list;
      $this->method_info["ARRAY_ACCESS"] = $this->array_access_list;
      $this->method_info["STRING"] = $this->string_list;
      $this->class_info["METHODS"][$this->method_name] = $this->method_info;
      $this->call_list = Array();
      $this->var_list = Array();
      // echo "[*] TAINT LIST\n";
      // var_dump($this->taint_list);
    }

    if ($node instanceof Node\Expr\FuncCall) {
      if ($node->name == 'spl_autoload_register') {
        $node->name = new Node\Name('X_spl_autoload_register');
      }
      else {
        $call_info = Array(
          "TYPE" => 'FuncCall',
          "FUNCTION" => $this->toString($node->name),     // Node\Name | Expr
          "ARGS" => $this->parseArgs($node->args),        // Node\Arg[]
          "THIS" => $this->include_this,
          "DEPTH" => $this->depth_cnt,
          "IDX" => $this->idx,
          "ORDER" => $this->func_idx[$this->toString($node->name)]
        );
        array_push($this->call_list, $call_info);
      }
    }

    if ($node instanceof Node\Expr\MethodCall) {
      $call_info = Array(
        "TYPE" => 'MethodCall',
        "CLASS" => $this->toString($node->var),         // Expr
        "FUNCTION" => $this->toString($node->name),     // string | Expr
        "ARGS" => $this->parseArgs($node->args),        // Node\Arg[]
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => null
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Expr\StaticCall) {
      $call_info = Array(
        "TYPE" => 'StaticCall',
        "CLASS" => $this->toString($node->class),       // Node\Name | Expr
        "FUNCTION" => $this->toString($node->name),     // string | Expr
        "ARGS" => $this->parseArgs($node->args),        // Node\Arg[]
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => null
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Expr\Print_) {
      $call_info = Array(
        "TYPE" => 'FuncCall',
        "FUNCTION" => 'print',
        "ARGS" => Array($this->toString($node->expr)),    // Expr
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => $this->func_idx['print']
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Stmt\Echo_) {
      $call_info = Array(
        "TYPE" => 'FuncCall',
        "FUNCTION" => 'echo',
        "ARGS" => Array($this->toString($node->exprs)),   // Expr[]
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => $this->func_idx['echo']
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Expr\Exit_) {
      $KIND_EXIT = 1;
      $KIND_DIE = 2;

      $func_name = 'exit';
      if ($node->getAttribute('kind') == $KIND_EXIT) {
        $func_name = 'exit';
      }
      elseif ($node->getAttribute('kind') == $KIND_DIE) {
        $func_name = 'die';
      }

      $call_info = Array(
        "TYPE" => 'FuncCall',
        "FUNCTION" => $func_name,
        "ARGS" => Array($this->toString($node->expr)),    // Expr
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => $this->func_idx[$func_name]
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Expr\Include_) {
      $TYPE_INCLUDE = 1;
      $TYPE_INCLUDE_ONCE = 2;
      $TYPE_REQUIRE = 3;
      $TYPE_REQUIRE_ONCE = 4;

      $func_name = 'include';
      if ($node->type == $TYPE_INCLUDE) {
        $func_name = 'include';
      }
      elseif ($node->type == $TYPE_INCLUDE_ONCE) {
        $func_name = 'include_once';
      }
      elseif ($node->type == $TYPE_REQUIRE) {
        $func_name = 'require';
      }
      elseif ($node->type == $TYPE_REQUIRE_ONCE) {
        $func_name = 'require_once';
      }

      $call_info = Array(
        "TYPE" => 'FuncCall',
        "FUNCTION" => $func_name,
        "ARGS" => Array($this->toString($node->expr)),    // Expr
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => $this->func_idx[$func_name]
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Expr\Eval_) {
      $call_info = Array(
        "TYPE" => 'FuncCall',
        "FUNCTION" => 'eval',
        "ARGS" => Array($this->toString($node->expr)),  // Expr
        "THIS" => $this->include_this,
        "DEPTH" => $this->depth_cnt,
        "IDX" => $this->idx,
        "ORDER" => $this->func_idx['eval']
      );
      array_push($this->call_list, $call_info);
    }

    if ($node instanceof Node\Stmt\Namespace_) {
      $this->namespace = '';
    }

    if ($node instanceof Node\Expr\Assign) {
      if ($expr instanceof Node\Expr\New_) {
        $call_info = Array(
          "TYPE" => 'New',
          "FUNCTION" => $this->toString($expr->class),  // Node\Name | Expr |
                                                        // Node\Stmt\Class_
          "VAR" => $this->toString($node->var),         // Expr
          "ARGS" => $this->parseArgs($expr->args),      // Node\Arg[]
          "THIS" => $this->include_this,
          "DEPTH" => $this->depth_cnt,
          "IDX" => $this->idx
        );
        array_push($this->call_list, $call_info);
      }
    }

    if ($node instanceof Node\Expr\Ternary ||
        $node instanceof Node\Stmt\Case_ ||
        $node instanceof Node\Stmt\Do_ ||
        $node instanceof Node\Stmt\ElseIf_ ||
        $node instanceof Node\Stmt\Switch_ ||
        $node instanceof Node\Stmt\While_ ||
        $node instanceof Node\Stmt\If_ ||
        $node instanceof Node\Stmt\For_ ) {
      $this->depth_cnt -= 1;
    }
  }

  public function afterTraverse(array $nodes) {
    // send_data
    $msg = ['classes'=> $this->user_classes,
           'functions'=> $this->user_functions];
    var_export($msg);
    global $channel;
    global $queue_name;
    $encode_msg = json_encode($msg);
    if ($encode_msg === false) {
      if (json_last_error() == JSON_ERROR_UTF8) {
        $encode_msg = json_encode($this->utf8ize($msg));
      }
    }
    $msg = new AMQPMessage($encode_msg);
    $channel->basic_publish($msg, '', $queue_name);
  }

  private function traverse_expr($value, $key, $needle) {
    // var_dump($value);
    if (is_object($value)) {
      if ($value instanceof PhpParser\Comment) {
        return;
      }
      // echo "  Compare: " . $this->toString($value);
      if ($needle == $this->toString($value)) {
        // echo "  [True]\n";
        $this->result = True;
      }
      else {
        // echo "  [False]\n";
        array_walk_recursive($value, array($this, 'traverse_expr'), $needle);
      }
    }
  }

  private function in_expr($needle, $haystack) {
    // echo "[*] Find: $needle in " . $this->toString($haystack) . "\n";

    if ($needle == $this->toString($haystack)) {
      // echo "  => True\n";
      return True;
    }

    $this->result = False;
    array_walk_recursive($haystack, array($this, 'traverse_expr'), $needle);
    // echo "  => ";
    // var_dump($this->result);
    return $this->result;
  }

  private function utf8ize($mixed) {
    if (is_array($mixed)) {
      foreach ($mixed as $key => $value) {
        $mixed[$key] = $this->utf8ize($value);
      }
    } else if (is_string ($mixed)) {
      return utf8_encode($mixed);
    }
    return $mixed;
  }

  private function init() {
    $this->depth_cnt = 0;
    $this->idx = 0;
    $this->func_idx = Array();
    $this->for_list = Array();
    $this->array_access_list = Array();
    $this->string_list = Array();
  }
}

if (PHP7) {
  $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
}
else {
  $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
}
$traverser = new NodeTraverser;

$traverser->addVisitor(new MyNodeVisitor);

try {
  $code = file_get_contents($argv[1]);
  $ast = $parser->parse($code);
  $ast = $traverser->traverse($ast);
} catch (Error $error) {
  echo "Parse error: {$error->getMessage()}\n";
  return;
}

// $dumper = new NodeDumper;
// echo $dumper->dump($ast) . "\n";
?>
