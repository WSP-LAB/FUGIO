<?php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MyNodeVisitor extends NodeVisitorAbstract
{
	public $user_classes;
	public $class_name;
	public $class_info;
	public $prop_name;
	public $prop_info;
	public $method_name;
	public $method_info;
	public $call_list = Array();

	private function parsesParams(array $params) {
		$param_list = Array();
		foreach ($params as $param) {
			$param_list[$param->name] = Array(
				"DEFAULT" => $param->default
			);
		}
		return $param_list;
	}

	private function parseArgs(array $args) {
		$arg_list = Array();
		foreach ($args as $arg) {
			array_push($arg_list, $arg->value);
		}
		return $arg_list;
	}

	public function beforeTraverse(array $nodes) {
		$this->user_classes = Array();
	}

	public function enterNode(Node $node) {
		if ($node instanceof Node\Stmt\Class_) {
			$this->class_name = $node->name;
			$this->class_info = Array(
				"TYPE" => $node->type,
				"PARENTS" => $node->extends->parts,
				"PROPS" => Array(),
				"METHODS" => Array()
			);
		}
		if ($node instanceof Node\Stmt\Property) {
			$this->prop_info = Array(
				"TYPE" => $node->type
			);
		}
		if ($node instanceof Node\Stmt\PropertyProperty) {
			$this->prop_name = $node->name;
			$this->prop_info["DEFAULT"] = $node->default->value;
		}
		if ($node instanceof Node\Stmt\ClassMethod) {
			$this->method_name = $node->name;
			$this->method_info = Array(
				"TYPE" => $node->type,
				"PARAMS" => $this->parsesParams($node->params)
			);
			$this->call_list = Array();
		}
		if ($node instanceof Node\Expr\FuncCall) {
			$call_info = Array(
				"TYPE" => 'FuncCall',
				"FUNCTION" => $node->name->parts[0],
				"ARGS" => $this->parseArgs($node->args)
			);
			array_push($this->call_list, $call_info);
		}

		if ($node instanceof Node\Expr\MethodCall) {
			$call_info = Array(
				"TYPE" => 'MethodCall',
				"CLASS" => $node->var,
				"FUNCTION" => $node->name,
				"ARGS" => $this->parseArgs($node->args)
			);
			array_push($this->call_list, $call_info);
		}

		if ($node instanceof Node\Expr\StaticCall) {
			$call_info = Array(
				"TYPE" => 'StaticCall',
				"CLASS" => $node->class->parts[0],
				"FUNCTION" => $node->name,
				"ARGS" => $this->parseArgs($node->args)
			);
			array_push($this->call_list, $call_info);
		}

		if ($node instanceof Node\Expr\Print_) {
			$call_info = Array(
				"TYPE" => 'FuncCall',
				"FUNCTION" => 'print',
				"ARGS" => $node->expr
			);
			array_push($this->call_list, $call_info);
		}

		if ($node instanceof Node\Stmt\Echo_) {
			$call_info = Array(
				"TYPE" => 'FuncCall',
				"FUNCTION" => 'echo',
				"ARGS" => $node->exprs
			);
			array_push($this->call_list, $call_info);
		}

		if ($node instanceof Node\Expr\Assign) {
			$expr = $node->expr;
			if ($expr instanceof Node\Expr\New_) {
				$call_info = Array(
					"TYPE" => 'New',
					"FUNCTION" => $expr->class->parts[0],
					"VAR" => $node->var->name,
					"ARGS" => $this->parseArgs($expr->args)
				);
				array_push($this->call_list, $call_info);
			}
		}
    }
    /*
	public function leaveNode(Node $node) {
		if ($node instanceof Node\Stmt\Class_) {
			$this->user_classes[$this->class_name] = $this->class_info;
		}
		if ($node instanceof Node\Stmt\Property) {
			$this->class_info["PROPS"][$this->prop_name] = $this->prop_info;
		}
		if ($node instanceof Node\Stmt\ClassMethod) {
			$this->method_info["CALLS"] = $this->call_list;
			$this->class_info["METHODS"][$this->method_name] = $this->method_info;
			$this->call_list = Array();
		}
	}
	public function afterTraverse(array $nodes) {
		# send_data
		var_export($this->user_classes);
		global $channel;
		$msg = new AMQPMessage(json_encode($this->user_classes));
		$channel->basic_publish($msg, '', 'ast_channel');
    }
    */
}
?>
