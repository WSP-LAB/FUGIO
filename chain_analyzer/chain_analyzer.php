<?php
ini_set("highlight.comment", "#C0C0C0");
ini_set("highlight.string", "#000000");

$file_path = $_GET['file_path'];
$file_name = basename($file_path);
$file_dir = dirname($file_path);

echo "<h1> $file_name </h1>";
$data = file_get_contents($file_path);
$chain_list = json_decode($data, true)['chain'];

echo "<ul>";
echo "<li> Chain Path: $file_path </li>";
echo "<li> Chain Length: " . count($chain_list) . "</li>";
echo "<li> Sink: " . end($chain_list)['sink'] .
     " (" . end($chain_list)['class'] . "(" . end($chain_list)['real_class'] . ")" .
     "::" . end($chain_list)['method'] . ")</li>";
echo "</ul>";

echo "<ol>";
foreach($chain_list as $idx=>$chain){
    echo "<li>";
    if ($idx == count($chain_list) - 1) {
        echo '[Sink] ' . $chain['sink'] . ' (' . $chain['order'] . ')';
    }
    else {
        if ($chain['real_class'] == '') {
            echo $chain['method'];
        }
        else {
            echo $chain['class'] . "(" . $chain['real_class'] . ")::" . $chain['method'];
        }
        if (array_key_exists('implicit', $chain)) {
            echo " (" . $chain['implicit']['DATA']['method'] . ")";
        }
        if (array_key_exists('string', $chain)) {
            echo " (" . $chain['string']['expr'] . ")";
        }
    }
    echo "</br>";
}
echo "</ol>";

if (PHP_VERSION >= "7.2") {
    require __DIR__ . '/../Lib/PHP-Parser7/vendor/autoload.php';
}
else {
    require __DIR__ . '/../Lib/PHP-Parser/vendor/autoload.php';
}
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

class MyNodeVisitor extends NodeVisitorAbstract
{
    public $pretty_printer;
    public $user_classes;
    public $user_functions;
    public $class_name;
    public $class_info;
    public $func_name;
    public $func_info;
    public $method_name;
    public $method_info;

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

    public function beforeTraverse(array $nodes) {
		$this->pretty_printer = new PrettyPrinter\Standard();
		$this->user_classes = Array();
    }

    public function enterNode(Node $node) {
		if ($node instanceof Node\Stmt\Class_) {
			$this->class_name = $this->toString($node->name);
			$this->class_info = Array(
				"METHODS" => Array(),
				"DECL" => $this->toString(array($node))
			);
		}
		if ($node instanceof Node\Stmt\Interface_) {
			$this->class_name = $this->toString($node->name);
			$this->class_info = Array(
				"METHODS" => Array(),
				"DECL" => $this->toString(array($node))
			);
		}
		if ($node instanceof Node\Stmt\Function_) {
            $this->func_name = $this->toString($node->name);
            $this->func_info = Array(
                "DECL" => $this->toString(array($node))
            );
        }
        if ($node instanceof Node\Stmt\ClassMethod) {
			$this->method_name = $this->toString($node->name);
			$this->method_info = Array(
				"DECL" => $this->toString(array($node))
			);
        }
    }
    public function leaveNode(Node $node) {
		if ($node instanceof Node\Stmt\Class_) {
			$this->user_classes[$this->class_name] = $this->class_info;
		}
		if ($node instanceof Node\Stmt\Interface_) {
			$this->user_classes[$this->class_name] = $this->class_info;
		}
		if ($node instanceof Node\Stmt\Function_) {
			$this->user_functions[$this->func_name] = $this->func_info;
        }
        if ($node instanceof Node\Stmt\ClassMethod) {
			$this->class_info["METHODS"][$this->method_name] = $this->method_info;
        }
    }
    public function afterTraverse(array $nodes) {
        global $chain;
        global $next_chain_method;

        echo "<li><b>";
        if ($chain['real_class'] == '') {
            echo $chain['method'] .
						     ' (<a href="display_code.php?file_path=' . $chain['real_file'] . '">' . $chain['real_file'] . "</a>)";
        }
        else {
            echo $chain['class'] . "(" . $chain['real_class'] . ")::" . $chain['method'] .
                 ' (<a href="display_code.php?file_path=' . $chain['real_file'] . '">' . $chain['real_file'] . "</a>)";
        }
        echo "</b></br>";

        $class_name = $chain['real_class'];
        $method_name = $chain['method'];

        if (strpos($class_name, '\\') !== false) {
            $new_class_name = array();
            $new_class_name = explode('\\', $class_name);
            $class_name = end($new_class_name);
        }

        if ($class_name == ''){
            $stmts = $this->user_functions[$method_name]['DECL'];
        }
        else {
            $stmts = $this->user_classes[$class_name]['METHODS'][$method_name]['DECL'];
        }
        $text = highlight_string("<?php " . $stmts, true);
        $text = str_replace(
            "<span style=\"color: #0000BB\">&lt;?php&nbsp;</span>", "", $text);
        $text = str_replace(
            "<span style=\"color: #0000BB\">$next_chain_method</span>",
            "<span style=\"color: #FF0000; font-weight:bold\">$next_chain_method</span>", $text
        );
        echo $text;
        echo "</br></br></br>";
    }
}

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
$traverser = new NodeTraverser;
$traverser->addVisitor(new MyNodeVisitor);

echo '<hr align="left" width="80%">';
echo "<ol>";
foreach($chain_list as $idx=>$chain){
    if ($idx == count($chain_list) - 1) {
        continue;
    }
    else if ($idx == count($chain_list) - 2) {
        $next_chain_method = $chain_list[$idx+1]['sink'];
    }
    else if (array_key_exists('implicit', $chain)) {
        $next_chain_method = $chain['implicit']['DATA']['method'];
    }
    else {
        $next_chain_method = $chain_list[$idx+1]['method'];
    }
    $target_file_path = $chain['real_file'];
    $code = file_get_contents($target_file_path);
    $ast = $parser->parse($code);
    $ast = $traverser->traverse($ast);
}
echo "</ol>";
?>
