<?php
if (PHP_VERSION >= "7.2") {
	require __DIR__ . '/../Lib/PHP-Parser7/vendor/autoload.php';
	require("inc/STMTManipulator7.php");
}
else {
	require __DIR__ . '/../Lib/PHP-Parser/vendor/autoload.php';
	require("inc/STMTManipulator.php");
}

require("inc/IncludeParseVisitor.php");
require("inc/MyNodeVisitor.php");
require("inc/ASTLoader.php");
require("inc/CodePrinter.php");

$HEAD_FILE = $argv[1];
$BODY_FILE = $argv[2];
// $INFO_FILE = $argv[3];
$OUTPUT_FILE = realpath(dirname($BODY_FILE)) . "/inst_PUT.php";

/*
use PhpParser\NodeDumper;
$dumper = new NodeDumper;
$ast = astLoad($BODY_FILE, new MyNodeVisitor);
echo $dumper->dump($ast);
echo "\n===============================\n";
*/


/*
echo "[#] Target PHP - Before....\n";
$beforeAst = astLoad($TARGET_FILE, new MyNodeVisitor);
echo codePrint($beforeAst);
*/

// echo "\n======================================================\n";
// echo "[#] Target PHP - After....\n";
STMTManipulate($HEAD_FILE, $BODY_FILE);

// $head_AST = ASTLoad($HEAD_FILE);
// $ManipulatedAst = STMTManipulate($TARGET_FILE);

// $fullAST = array_merge($head_AST, $ManipulatedAst);
// file_put_contents($OUTPUT_FILE, codePrint($fullAST));
// exit($OUTPUT_FILE);

// file_put_contents($OUTPUT_FILE, codePrint($ManipulatedAst));
?>
