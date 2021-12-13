<?php
use PhpParser\PrettyPrinter;

function codePrint($ast){
    $prettyPrinter = new PrettyPrinter\Standard;
    return $prettyPrinter->prettyPrintFile($ast);
}
?>
