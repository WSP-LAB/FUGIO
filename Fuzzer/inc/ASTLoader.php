<?php
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

function ASTLoad($file_name, $nodeVisitor=NULL){
    if(PHP_VERSION >= "7.2"){
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    else{
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);   
    }
    $traverser = new NodeTraverser;

    if ($nodeVisitor == NULL) {
        try {
            $code = file_get_contents($file_name);
            $ast = $parser->parse($code);
            return $ast;
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
    }

    else {
        $traverser->addVisitor($nodeVisitor);

        try {
            $code = file_get_contents($file_name);
            $ast = $parser->parse($code);
            $ast = $traverser->traverse($ast);
            return $ast;
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
    }
}

function ASTLoadByCode($code, $nodeVisitor=NULL){
    if(PHP_VERSION >= "7.2"){
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    else{
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);   
    }
    $traverser = new NodeTraverser;
    if ($nodeVisitor == NULL) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }

    else {
        $traverser->addVisitor($nodeVisitor);
        try {
            $ast = $parser->parse($code);
            $traverser->traverse($ast);
            return $ast;
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
    }
}

function IncLoad($file_name, $nodeVisitor=NULL){
    if(PHP_VERSION >= "7.2"){
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    else{
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);   
    }
    $traverser = new NodeTraverser;
    
    if ($nodeVisitor == NULL) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }

    else {
        $traverser->addVisitor($nodeVisitor);

        try {
            $code = file_get_contents($file_name);
            $ast = $parser->parse($code);
            $inc_parser_result = $traverser->traverse($ast);
            return $inc_parser_result;
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
    }
}

function ConstraintLoad($code, $nodeVisitor=NULL){
    if(PHP_VERSION >= "7.2"){
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    else{
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);   
    }
    $traverser = new NodeTraverser;
    if ($nodeVisitor == NULL) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }

    else {
        $traverser->addVisitor($nodeVisitor);

        try {
            $ast = $parser->parse($code);
            $constraint_parser_result = $traverser->traverse($ast);
            return $constraint_parser_result;
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
    }

}

function InstrumentationByAST($ast, $nodeVisitor=NULL){
    if(PHP_VERSION >= "7.2"){
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    else{
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);   
    }
    $traverser = new NodeTraverser;
    
    if ($nodeVisitor == NULL) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }

    else {
        $traverser->addVisitor($nodeVisitor);

        try {
            $inst_ast = $traverser->traverse($ast);
            return $inst_ast;
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
    }
}



?>
