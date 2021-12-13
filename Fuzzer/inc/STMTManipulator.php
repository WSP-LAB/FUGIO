<?php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;

require("NormalNodeVisitor.php");
require("ConstraintVisitor.php");

class ManipulateNodeVisitor extends NodeVisitorAbstract
{
    public $prettyPrinter;
    public $factory;
    public $constraint_hash = array();
    public $fuzz_info;
    public $fuzz_goal_path = array();
    public $ifConstraints = array();
    public $currentTraverseStatus = array();
    public $methodFunctions = array();
    public $goalSink = array();
    public $inst_file_type;

    public function __construct($file_type){
        $this->inst_file_type = $file_type;
    }

    public function addIfConstraints($hash, $cond_type, $cond){
        if(strpos($cond, "->") !== false){
            if($cond_type == "FOREACH"){
                $constraint_cond = "is_array(" . $cond . ")";
            }
            else{ // IF, SWITCH
                $constraint_cond = $cond;
            }
            $condition_code = "<?php\n";
            $condition_code .= $constraint_cond;
            $condition_code .= ";\n?>";
            $constraint_parser = new ConstraintNodeVisitor;
            $constraint_parser->cond_type = $cond_type;
            ConstraintLoad($condition_code, $constraint_parser);
            $value_info = $constraint_parser->GetValueInfo();
            $value_info['from'] = $cond_type;
            if($value_info['result'] !== "NOTHING" and
               is_string($value_info['prop_name'])){
                array_push($this->ifConstraints,
                    array(
                        "hash" => $hash,
                        "cond" => $value_info
                    )
                );
            }
        }
    }

    public function ConstraintSeparationAndRegister($ast_cond){
        $result_cond = $ast_cond;

        $conditions = array();
        if($ast_cond instanceof Node\Expr\BinaryOp\BooleanAnd or
            $ast_cond instanceof Node\Expr\BinaryOp\BooleanOr or
            $ast_cond instanceof Node\Expr\BinaryOp\LogicalAnd or
            $ast_cond instanceof Node\Expr\BinaryOp\LogicalOr or
            $ast_cond instanceof Node\Expr\BinaryOp\LogicalXor ){
            if(array_key_exists("left", $ast_cond)){
                $conditions['left'] = $ast_cond->left;
            }
            if(array_key_exists("right", $ast_cond)){
                $conditions['right'] = $ast_cond->right;
            }
        }

        if(count($conditions) == 0){
            $full_cond_inst = new Node\Expr\BinaryOp\BooleanAnd(
                new Node\Expr\BinaryOp\BooleanAnd(
                    new Node\Expr\MethodCall(
                        new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                        new Node\Scalar\String_("Feedback_cls")),
                        "isBranchPassed",
                        array(
                            new Node\Arg(new Node\Scalar\String_(spl_object_hash($result_cond))),
                            new Node\Arg(new Node\Scalar\String_("COND-PRE")),
                            new Node\Arg(new Node\Expr\Array_(array()))
                        )
                    ),
                    $result_cond
                ),
                new Node\Expr\MethodCall(
                    new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                    new Node\Scalar\String_("Feedback_cls")),
                    "isBranchPassed",
                    array(
                        new Node\Arg(new Node\Scalar\String_(spl_object_hash($result_cond))),
                        new Node\Arg(new Node\Scalar\String_("COND-POST")),
                        new Node\Arg(new Node\Expr\Array_(array()))
                    )
                )
            );
            $this->addIfConstraints(
                spl_object_hash($result_cond),
                "IF",
                $this->prettyPrinter->prettyPrintExpr($result_cond)
            );
            return $full_cond_inst;
        }

        foreach($conditions as $position => $sub_cond){
            if($sub_cond instanceof Node\Expr\BinaryOp\BooleanAnd){
                $result_cond->$position = $this->ConstraintSeparationAndRegister($sub_cond);
            }
            elseif($sub_cond instanceof Node\Expr\BinaryOp\BooleanOr){
                $result_cond->$position = $this->ConstraintSeparationAndRegister($sub_cond);
            }
            elseif($sub_cond instanceof Node\Expr\BinaryOp\LogicalAnd){
                $result_cond->$position = $this->ConstraintSeparationAndRegister($sub_cond);
            }
            elseif($sub_cond instanceof Node\Expr\BinaryOp\LogicalOr){
                $result_cond->$position = $this->ConstraintSeparationAndRegister($sub_cond);
            }
            elseif($sub_cond instanceof Node\Expr\BinaryOp\LogicalXor){
                $result_cond->$position = $this->ConstraintSeparationAndRegister($sub_cond);
            }
            else{
                $this->addIfConstraints(
                    spl_object_hash($sub_cond),
                    "IF",
                    $this->prettyPrinter->prettyPrintExpr($sub_cond)
                );
                $sub_cond_inst = new Node\Expr\BinaryOp\BooleanAnd(
                    new Node\Expr\BinaryOp\BooleanAnd(
                        new Node\Expr\MethodCall(
                            new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                            new Node\Scalar\String_("Feedback_cls")),
                            "isBranchPassed",
                            array(
                                new Node\Arg(new Node\Scalar\String_(spl_object_hash($sub_cond))),
                                new Node\Arg(new Node\Scalar\String_("COND-PRE")),
                                new Node\Arg(new Node\Expr\Array_(array()))
                            )
                        ),
                        $sub_cond
                    ),
                    new Node\Expr\MethodCall(
                        new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                        new Node\Scalar\String_("Feedback_cls")),
                        "isBranchPassed",
                        array(
                            new Node\Arg(new Node\Scalar\String_(spl_object_hash($sub_cond))),
                            new Node\Arg(new Node\Scalar\String_("COND-POST")),
                            new Node\Arg(new Node\Expr\Array_(array()))
                        )
                    )
                );
                $result_cond->$position = $sub_cond_inst;
            }
        }
        return $result_cond;
    }
    /*
    public function addInstMethodHash($hash, $type, $class, $method){
      $this->inst_hash[$class][$method] = array(
        "hash" => $hash,
        "type" => $type,
        "func" => array()
      );
    }

    public function addInstFuncHash($hash, $type, $class, $method, $func){
      array_push($this->inst_hash[$class_name][$method_name]['func'], array(
        "hash" => $hash,
        "func" => $func,
        "type" => $type
      ));
    }
    */
    public function beforeTraverse(array $nodes) {
        // global $INFO_FILE;
        // $chains_info = new SplFileObject($INFO_FILE);
        // foreach($chains_info as $chain_idx => $content){
        //   $decoded_content = json_decode($content);
        //   if($decoded_content){
        //     $this->fuzz_info[$chain_idx] = $decoded_content;
        //     $this->goalSink[$chain_idx] = array(
        //           'sinkFunc' => end($this->fuzz_info[$chain_idx]->chain)->sink,
        //           'sinkClass' => end($this->fuzz_info[$chain_idx]->chain)->real_class,
        //           'sinkMethod' => end($this->fuzz_info[$chain_idx]->chain)->method
        //       );
        //     $this->fuzz_goal_path[$chain_idx] = array();
        //   }
        // }

        $this->prettyPrinter = new PrettyPrinter\Standard;
        $this->factory = new BuilderFactory;
        $this->ifConstraints = array();
        $this->currentTraverseStatus = array(
            'class' => '',
            'method' => '',
            'function' => ''
        );


        // $PUT_HEAD_FILE = __DIR__ . "/../Files/PUT_head.php";
        // $PUT_HEAD_NODES = ASTLoad($PUT_HEAD_FILE, new NormalNodeVisitor);

        // $PUT_HEAD_NODES = array_reverse($PUT_HEAD_NODES);
        // foreach($PUT_HEAD_NODES as $PUT_HEAD_NODE){
        //     array_unshift($nodes, $PUT_HEAD_NODE);
        // }
        return $nodes;
    }

    private function GetArrayFetchVar($node){
        if($node->var instanceof Node\Expr\ArrayDimFetch){
            $recursive_var = $this->GetArrayFetchVar($node->var);
            return $recursive_var;
        }
        else{
            return $node->var;
        }
    }

    public function afterTraverse(array $nodes){
        $NAMESPACE_FOOTER = new Node\Stmt\Namespace_(null, array());
        if($this->inst_file_type != "Body"){
            $fuzz_init_if_code = '<?php' . "\n";
            $fuzz_init_if_code .= 'if(getenv("FUZZ_CMD") === "FuzzerInit"){' . "\n";
            $fuzz_init_if_code .= '}' . "\n";
            $fuzz_init_if_ast = ASTLoadBycode($fuzz_init_if_code, new NormalNodeVisitor);

            foreach($this->ifConstraints as $ifConstraint){
                $inst_if_cond = new Node\Expr\MethodCall(
                    new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                    new Node\Scalar\String_("Feedback_cls")),
                    "initIfConstraint",
                    array(
                        new Node\Arg(new Node\Scalar\String_($ifConstraint['hash'])),
                        new Node\Arg(new Node\Expr\Array_(
                            array(
                                new Node\Scalar\String_($ifConstraint['cond']['result']),
                                new Node\Scalar\String_($ifConstraint['cond']['prop_name']),
                                new Node\Scalar\String_($ifConstraint['cond']['type']),
                                new Node\Scalar\String_($ifConstraint['cond']['value']),
                                new Node\Scalar\String_($ifConstraint['cond']['from'])
                            )
                            )
                        )
                    )
                );
                array_push($fuzz_init_if_ast[0]->stmts, $inst_if_cond);
            }
            array_push($NAMESPACE_FOOTER->stmts, $fuzz_init_if_ast[0]);
            array_push($nodes, $NAMESPACE_FOOTER);
            return $nodes;
        }

        $PUT_HEAD_FILE = __DIR__ . "/../Files/PUT_head.php";
        $PUT_HEAD_NODES = ASTLoad($PUT_HEAD_FILE, new NormalNodeVisitor);
        $PUT_HEAD_NODES = new Node\Stmt\Namespace_(null, $PUT_HEAD_NODES);
        array_unshift($nodes, $PUT_HEAD_NODES);

        $assign_entry_magic_method = new Node\Expr\Assign (
            new Node\Expr\Variable("entry_magic_method"),
            new Node\Expr\FuncCall(
                new Node\Name("getenv"),
                array(
                    new Node\Arg(new Node\Scalar\String_("ENTRY_MAGIC_METHOD"))
                    )
                )
            // new Node\Scalar\String_($this->fuzz_info->chain[0]->method)
        );
        array_push($NAMESPACE_FOOTER->stmts, $assign_entry_magic_method);
        array_push($nodes, $NAMESPACE_FOOTER);

        $PUT_TAIL_FILE = __DIR__ . "/../Files/PUT_tail.php";
        $PUT_TAIL_NODES = ASTLoad($PUT_TAIL_FILE, new NormalNodeVisitor);
        $PUT_TAIL_NODES = new Node\Stmt\Namespace_(null, $PUT_TAIL_NODES);
        array_push($nodes, $PUT_TAIL_NODES);

        return $nodes;
    }

    public function enterNode(Node $node) {
        if($this->inst_file_type == "Body"){
            return $node;
        }

        // Set currentTraverse Class and Method
        if($node instanceof Node\Stmt\Class_){
            $this->currentTraverseStatus['class'] = $node->name;
            $this->currentTraverseStatus['function'] = NULL;
            $this->methodFunctions = array();
        }
        if($node instanceof Node\Stmt\ClassMethod){
            $this->currentTraverseStatus['method'] = $node->name;
            $this->currentTraverseStatus['function'] = NULL;
            $this->methodFunctions = array();
        }
        if($node instanceof Node\Stmt\Function_){
            $this->currentTraverseStatus['function'] = $node->name;
            $this->currentTraverseStatus['class'] = NULL;
            $this->currentTraverseStatus['method'] = NULL;
            $this->methodFunctions = array();
        }

        if($this->currentTraverseStatus['class'] == "ConstraintFeedback"){
            return;
        }

        if($node instanceof Node\Expr\ArrayDimFetch){
            if(array_key_exists("var", $node) and
                $node->var instanceof Node\Expr\Variable and
                $node->var->name == "GLOBALS" and
                array_key_exists("dim", $node) and
                $node->dim instanceof Node\Scalar\String_ and
                $node->dim->value == "Feedback_cls"){
                // Do nothing when $GLOBALS['Feedback_cls'];
                return $node;
            }
            else{
                $arr_var = $node->var;
                $arr_dim = $node->dim;
                if($arr_dim instanceof Node\Scalar\String_){
                    $arr_dim_string = $arr_dim;
                }
                else{
                    $arr_dim_string = new Node\Scalar\String_($this->toString($arr_dim));
                }

                if($arr_dim == NULL){
                    return $node;
                }
                else{
                    $first_var = $this->GetArrayFetchVar($node);

                    $inst_arr_dim = new Node\Expr\MethodCall(
                            new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                            new Node\Scalar\String_("Feedback_cls")),
                            "ArrayFetch",
                            array(
                                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                                new Node\Arg(new Node\Scalar\String_($this->toString($first_var))),
                                new Node\Arg($arr_dim_string),
                                new Node\Arg($first_var),
                                new Node\Arg($arr_dim),
                                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace")))
                            )
                        );
                    $inst_arr_fetch = clone $node;
                    $inst_arr_fetch->dim = $inst_arr_dim;

                    // $x = $this->prettyPrinter->prettyPrintExpr($inst_arr_fetch);
                    // var_dump($x);
                    // exit();
                    return $inst_arr_fetch;
                }
            }
        }

        // Add spl_object_hash on target method
        if($node instanceof Node\Stmt\Class_ and
            $node->name != "ConstraintFeedback"){
            foreach($node->stmts as $class_stmt){
                if($class_stmt instanceof Node\Stmt\ClassMethod and
                    $class_stmt->stmts != NULL){
                    $this->currentTraverseStatus['class'] = $node->name;
                    $this->currentTraverseStatus['method'] = $class_stmt->name;
                    $inst_method_call = new Node\Expr\MethodCall(
                        new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                        new Node\Scalar\String_("Feedback_cls")),
                        "isBranchPassed",
                        array(
                            new Node\Arg(new Node\Scalar\String_(spl_object_hash($class_stmt))),
                            new Node\Arg(new Node\Scalar\String_("METHOD-ENTRY")),
                            new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace")))
                        )
                    );
                    array_unshift($class_stmt->stmts, $inst_method_call);
                    /*
                    $this->addInstMethodHash(
                      spl_object_hash($class_stmt),
                      "METHOD-ENTRY",
                      $this->currentTraverseStatus['class'],
                      $this->currentTraverseStatus['method']);
                    */
                }
            }
            return $node;
        }

        // Add spl_object_hash on sink method (End-Point)
        if($node instanceof Node\Expr\FuncCall and
           empty($node->name->parts) == FALSE and
           end($node->name->parts) != "debug_backtrace" ){ // [TODO] is it Right?
            if(!array_key_exists(end($node->name->parts), $this->methodFunctions)){
                $this->methodFunctions[end($node->name->parts)] = 1;
            }
            else{
                $this->methodFunctions[end($node->name->parts)] += 1;
            }

            $inst_sink_call_args = array(
                new Node\Arg(new Node\Scalar\String_(end($node->name->parts))),
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method'])),
                new Node\Arg(new Node\Scalar\String_($this->methodFunctions[end($node->name->parts)]))
            );
            foreach($node->args as $sink_arg){
                array_push($inst_sink_call_args, $sink_arg);
            }
            $inst_sink_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "funcWrapped",
                $inst_sink_call_args
            );

            /*
            $this->addInstFuncHash(spl_object_hash($node),
                                  "FUNC",
                                  $this->currentTraverseStatus['class'],
                                  $this->currentTraverseStatus['method'],
                                  end($node->name->parts));
            */
            return $inst_sink_call;
        }

        // Other sink (Echo)
        if($node instanceof Node\Stmt\Echo_) {
            if(!array_key_exists('echo', $this->methodFunctions)){
                $this->methodFunctions['echo'] = 1;
            }
            else{
                $this->methodFunctions['echo'] += 1;
            }

            $inst_stmt_call_args = array(
                new Node\Arg(new Node\Scalar\String_("echo")),
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method'])),
                new Node\Arg(new Node\Scalar\String_($this->methodFunctions['echo']))
            );

            foreach($node->exprs as $stmt_arg){
                array_push($inst_stmt_call_args, $stmt_arg);
            }

            $inst_stmt_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "syntaxWrapped",
                $inst_stmt_call_args
            );
            /*
            $this->addInstFuncHash(spl_object_hash($node),
                                  "FUNC",
                                  $this->currentTraverseStatus['class'],
                                  $this->currentTraverseStatus['method'],
                                  "echo");
            */
            return $inst_stmt_call;

        }
        // Other sink (Print)
        if($node instanceof Node\Expr\Print_) {
            if(!array_key_exists('print', $this->methodFunctions)){
                $this->methodFunctions['print'] = 1;
            }
            else{
                $this->methodFunctions['print'] += 1;
            }

            $inst_stmt_call_args = array(
                new Node\Arg(new Node\Scalar\String_("print")),
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method'])),
                new Node\Arg(new Node\Scalar\String_($this->methodFunctions['print']))
            );

            array_push($inst_stmt_call_args, $node->expr);

            $inst_stmt_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "syntaxWrapped",
                $inst_stmt_call_args
            );
            /*
            $this->addInstFuncHash(spl_object_hash($node),
                                  "FUNC",
                                  $this->currentTraverseStatus['class'],
                                  $this->currentTraverseStatus['method'],
                                  "print");
            */
            return $inst_stmt_call;

        }

        // Other sink (Exit)
        if($node instanceof Node\Expr\Exit_) {

            if($node->getAttribute('kind') == 1){
                $stmt_name = "exit";
            }
            elseif ($node->getAttribute('kind') == 2){
                $stmt_name = "die";
            }
            if(!array_key_exists($stmt_name, $this->methodFunctions)){
                $this->methodFunctions[$stmt_name] = 1;
            }
            else{
                $this->methodFunctions[$stmt_name] += 1;
            }

            $inst_stmt_call_args = array(
                new Node\Arg(new Node\Scalar\String_($stmt_name)),
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method'])),
                new Node\Arg(new Node\Scalar\String_($this->methodFunctions[$stmt_name])),
            );

            if($node->expr != NULL){
                array_push($inst_stmt_call_args, $node->expr);
            }

            $inst_stmt_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "syntaxWrapped",
                $inst_stmt_call_args
            );

            return $inst_stmt_call;
        }

        // Other sink (Include)
        if($node instanceof Node\Expr\Include_) {

            if ($node->type == 1) {
                $stmt_name = 'include';
            }
            elseif ($node->type == 2) {
                $stmt_name = 'include_once';
            }
            elseif ($node->type == 3) {
                $stmt_name = 'require';
            }
            elseif ($node->type == 4) {
                $stmt_name = 'require_once';
            }

            if(!array_key_exists($stmt_name, $this->methodFunctions)){
                $this->methodFunctions[$stmt_name] = 1;
            }
            else{
                $this->methodFunctions[$stmt_name] += 1;
            }

            $inst_stmt_call_args = array(
                new Node\Arg(new Node\Scalar\String_($stmt_name)),
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method'])),
                new Node\Arg(new Node\Scalar\String_($this->methodFunctions[$stmt_name]))
            );


            array_push($inst_stmt_call_args, $node->expr);

            $inst_stmt_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "syntaxWrapped",
                $inst_stmt_call_args
            );
            /*
            $this->addInstFuncHash(spl_object_hash($node),
                                  "FUNC",
                                  $this->currentTraverseStatus['class'],
                                  $this->currentTraverseStatus['method'],
                                  $stmt_name);
            */
            return $inst_stmt_call;

        }
        // Other sink (eval)
        if($node instanceof Node\Expr\Eval_) {
            if(!array_key_exists('eval', $this->methodFunctions)){
                $this->methodFunctions['eval'] = 1;
            }
            else{
                $this->methodFunctions['eval'] += 1;
            }

            $inst_stmt_call_args = array(
                new Node\Arg(new Node\Scalar\String_("eval")),
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method'])),
                new Node\Arg(new Node\Scalar\String_($this->methodFunctions['eval']))
            );

            array_push($inst_stmt_call_args, $node->expr);

            $inst_stmt_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "syntaxWrapped",
                $inst_stmt_call_args
            );

            /*
            $this->addInstFuncHash(spl_object_hash($node),
                                  "FUNC",
                                  $this->currentTraverseStatus['class'],
                                  $this->currentTraverseStatus['method'],
                                  "eval");
            */


            return $inst_stmt_call;

        }
        /*
        // Static call sink
        if($node instanceof Node\Expr\StaticCall) {
            if($this->static_call_change_flag){
                $this->static_call_change_flag = False;
                return $node;
            }

            if(!($node->class instanceof Node\Expr)) {
                $class_name = new Node\Scalar\String_($node->class);
            }
            else{
                $class_name = $node->class;
            }

            if(!($node->name instanceof Node\Expr)){
                $method_name = new Node\Scalar\String_($node->name);
            }
            else{
                $method_name = $node->name;
            }

            $inst_method_call_args = array(
                new Node\Arg($class_name), // CLASS
                // new Node\Arg(new Node\Scalar\String_("A")),
                new Node\Arg($method_name), // Method
                new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method']))
            );

            $method_call = new Node\Arg($node);
            array_push($inst_method_call_args, $method_call);

            $inst_method_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "methodWrapped",
                $inst_method_call_args
            );

            $this->static_call_change_flag  = True;

            return $inst_method_call;

        }
        */

        if($node instanceof Node\Stmt\Foreach_){
            if(PHP_VERSION < "5.5" and
                $node->byRef == true){
                // https://bugs.php.net/bug.php?id=67633
                return $node;
            }

            $foreach_suffix = new Node\Expr\MethodCall(
                      new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                      new Node\Scalar\String_("Feedback_cls")),
                      "isBranchPassed",
                      array(
                          new Node\Arg(new Node\Scalar\String_(spl_object_hash($node->expr))),
                          new Node\Arg(new Node\Scalar\String_("COND-FOREACH")),
                          new Node\Arg(new Node\Expr\Array_(array()))
                      ));

            $inst_foreach_args = array(
                new Node\Arg($foreach_suffix),
                new Node\Arg($node->expr)
                // new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
            );

            $foreach_wrapped_expr = new Node\Expr\MethodCall(
                                  new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                                  new Node\Scalar\String_("Feedback_cls")),
                                  "foreachWrapped",
                                  $inst_foreach_args
                              );

            $foreach_aux_construct = array();
            $foreach_aux_construct['keyVar'] = $node->keyVar;
            $foreach_aux_construct['byRef'] = $node->byRef;
            $foreach_aux_construct['stmts'] = $node->stmts;

            $inst_foreach = new Node\Stmt\Foreach_($foreach_wrapped_expr, $node->valueVar, $foreach_aux_construct);

            $this->addIfConstraints(
                spl_object_hash($node->expr),
                "FOREACH",
                $this->prettyPrinter->prettyPrintExpr($node->expr)
            );

            return $inst_foreach;

        }
        if($node instanceof Node\Stmt\Switch_){
            $switch_cond = $node->cond;

            $new_cases = array();
            foreach($node->cases as $idx => $case){
                if($case->cond == NULL){ // default
                    $post_type_name = "COND-CASE-DEFAULT";
                    $new_case_cond = NULL;
                }
                else{
                    $post_type_name = "COND-CASE-POST";
                    $switch_suffix = new Node\Expr\MethodCall(
                              new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                              new Node\Scalar\String_("Feedback_cls")),
                              "isBranchPassed",
                              array(
                                  new Node\Arg(new Node\Scalar\String_(spl_object_hash($case))),
                                  new Node\Arg(new Node\Scalar\String_("COND-CASE-PRE")),
                                  new Node\Arg(new Node\Expr\Array_(array()))
                              ));


                    $inst_case_args = array(
                        new Node\Arg($switch_suffix),
                        new Node\Arg($case->cond)
                        // new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                    );

                    $new_case_cond = new Node\Expr\MethodCall(
                                         new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                                         new Node\Scalar\String_("Feedback_cls")),
                                         "switchWrapped",
                                         $inst_case_args
                                     );
                    $eq_case_cond = new Node\Expr\BinaryOp\Equal($switch_cond, $case->cond);
                    $this->addIfConstraints(
                        spl_object_hash($case),
                        "SWITCH",
                        $this->prettyPrinter->prettyPrintExpr($eq_case_cond)
                    );
               }

               $new_case_stmts = $case->stmts;
               $new_case_stmt = new Node\Expr\MethodCall(
                                    new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                                    new Node\Scalar\String_("Feedback_cls")),
                                    "isBranchPassed",
                                    array(
                                        new Node\Arg(new Node\Scalar\String_(spl_object_hash($case))),
                                        new Node\Arg(new Node\Scalar\String_($post_type_name)),
                                        new Node\Arg(new Node\Expr\Array_(array()))
                                    ));
               array_unshift($new_case_stmts, $new_case_stmt);

               $new_inst_case = new Node\Stmt\Case_($new_case_cond, $new_case_stmts);
               array_push($new_cases, $new_inst_case);
            }

            $new_switch_case = new Node\Stmt\Switch_($switch_cond, $new_cases);

            return $new_switch_case;
        }

        if($node instanceof Node\Stmt\If_) {

            if($node->else != NULL){
                $bak_if_cond = clone $node->cond;
                $bak_elif_conds = array();
                foreach($node->elseifs as $elif){
                    array_push($bak_elif_conds, clone $elif->cond);
                }
            }

            $branch_passed_if = $this->ConstraintSeparationAndRegister($node->cond);

            // Elseif Instrumentation
            $branch_passed_elif_array = array();
            foreach($node->elseifs as $elif){
                $branch_passed_elif = $this->ConstraintSeparationAndRegister($elif->cond);
                $branch_elif = new Node\Stmt\ElseIf_($branch_passed_elif, $elif->stmts);
                array_push($branch_passed_elif_array, $branch_elif);
            }

            // Else Instrumentation
            if($node->else != NULL){
                $branch_else_stmt = $node->else->stmts;
                $else_new_stmt = new Node\Expr\MethodCall(
                            new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                            new Node\Scalar\String_("Feedback_cls")),
                            "isBranchPassed",
                            array(
                                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node->else))),
                                new Node\Arg(new Node\Scalar\String_("COND-ELSE")),
                                new Node\Arg(new Node\Expr\Array_(array()))
                            ));
                array_unshift($branch_else_stmt, $else_new_stmt);
                $branch_passed_else = new Node\Stmt\Else_(
                    $branch_else_stmt
                );

                // (else) addConstraints
                $else_condition = new Node\Expr\BooleanNot($bak_if_cond);
                foreach($bak_elif_conds as $elif_condition){
                    $else_condition = new Node\Expr\BinaryOp\LogicalAnd(
                        $else_condition,
                        new Node\Expr\BooleanNot($elif_condition)
                    );
                }
                $this->addIfConstraints(
                    spl_object_hash($node->else),
                    "IF",
                    $this->prettyPrinter->prettyPrintExpr($else_condition)
                );
            }
            else{
                $branch_passed_else = NULL;
            }

            // Return New Node (If Instrumentation)
            $branch_if = new Node\Stmt\If_($branch_passed_if,
                array('stmts' => $node->stmts,
                    'elseifs' => $branch_passed_elif_array,
                    'else' => $branch_passed_else)
                );

            return $branch_if;
        }


        // Method call sink
        if($node instanceof Node\Expr\MethodCall){

            if(empty($node->var->dim) == FALSE and
               empty($node->var->dim->value) == FALSE and
               $node->var->dim->value == "Feedback_cls"){
                // $Feedback_cls
                    return $node;
            }

            if(empty($node->var->var) == FALSE and
               empty($node->var->var->dim) == FALSE and
               empty($node->var->var->dim->value) == FALSE and
               $node->var->var->dim->value == "Feedback_cls"){
                // (Redundent) $Feedback_cls->getMethodWrapped()->func_1()
                    return $node;
            }

            /*
            if(empty($node->var->var)){
                // (Last one) $this->
                var_dump($node->var->name);
            }
            */

            if(!($node->name instanceof Node\Expr)){
                $method_name = new Node\Scalar\String_($node->name);
            }
            else{
                $method_name = $node->name;
            }

            if(!($node->var instanceof Node\Expr\MethodCall)){
                $first_inst_method_call_args = array(
                    new Node\Arg($node->var), // CLASS
                    new Node\Arg(new Node\Scalar\String_($this->toString($node->var))),
                    // new Node\Arg(new Node\Scalar\String_('')), // Method
                    // new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                    new Node\Arg(new Node\Scalar\String_(spl_object_hash($node->var))),
                    // new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                    // new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method']))
                );

                $first_method_call = $node->var;
                array_push($first_inst_method_call_args, $first_method_call);

                $first_inst_method_call = new Node\Expr\MethodCall(
                    new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                    new Node\Scalar\String_("Feedback_cls")),
                    "setMethodWrapped",
                    $first_inst_method_call_args
                );
            }
            else{
                $first_inst_method_call = $node->var;
            }

            $inst_method_call_args = array(
                new Node\Arg($first_inst_method_call), // CLASS
                new Node\Arg(new Node\Scalar\String_($this->toString($node->var))),
                new Node\Arg(new Node\Scalar\String_(spl_object_hash($node))),
            );

            $next_method_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "getMethodWrapped",
                array(
                    new Node\Arg(new Node\Scalar\String_(spl_object_hash($node->var))),
                    new Node\Arg($method_name), // Method
                    new Node\Arg(new Node\Expr\FuncCall(new Node\Name("debug_backtrace"))),
                    new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['class'])),
                    new Node\Arg(new Node\Scalar\String_($this->currentTraverseStatus['method']))
                )
            );
            $next_method_call = new Node\Expr\MethodCall(
                $next_method_call,
                $node->name,
                $node->args
            );

            $method_call = new Node\Arg($next_method_call);
            array_push($inst_method_call_args, $method_call);

            $inst_method_call = new Node\Expr\MethodCall(
                new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("GLOBALS"),
                new Node\Scalar\String_("Feedback_cls")),
                "setMethodWrapped",
                $inst_method_call_args
            );

            return $inst_method_call;
        }

    }
    public function leaveNode(Node $node){



    }

    private function toString($arg) {
        if ($arg == null) {
            return $arg;
        }
        else if (is_string($arg)) {
            return $arg;
        }
        else if ($arg instanceof Node\Name) {
            return $arg->toString();
        }
        else if ($arg instanceof Node\Expr) {
            return $this->prettyPrinter->prettyPrintExpr($arg);
        }
        else {
            return utf8_encode($this->prettyPrinter->prettyPrint($arg));
        }
    }
}


function PrintInstrumentationProgress($inst_count, $inst_file, $inst_put_child_file){
    echo "[#] Instrumentating...\n";
    echo "[+] File: "  . substr(pathinfo($inst_file)['basename'],5) . " (" . $inst_put_child_file['file_type'] . ") \n";
    echo "[+] Progress: " . $inst_count['finish'] . " / " . $inst_count['full'] .
         " (" . round($inst_count['finish'] / $inst_count['full'] * 100 , 2) . " %)\n";
    echo "[+] Class: " . $inst_count['class'] . "\n";
    echo "[+] Function: " . $inst_count['function'] . "\n";
    echo "[+] Redeclared Class: " . $inst_count['redec_class'] . "\n";
    echo "[+] Redeclared Function: " . $inst_count['redec_function'] . "\n\n";
}

function STMTManipulate($head_file, $body_file){
    $inst_count = array();
    $put_body = IncLoad($body_file, new IncludeParseVisitor);
    $inst_put_body = codePrint($put_body['ast_nodes']) . "\n?>";
    $inst_file_body = STMTManipulateChild($body_file, $put_body['ast_nodes'],
                                         $put_body['file_type'], $head_file);
    $need_inst_array = $put_body['inc_files'];
    $inst_count['full'] = count($need_inst_array);
    $inst_count['finish'] = 0;
    $inst_count['class'] = 0;
    $inst_count['function'] = 0;
    $inst_count['redec_class'] = 0;
    $inst_count['redec_function'] = 0;

    $put_dir = pathinfo($body_file)['dirname'];
    $redeclared_paths = glob($put_dir . "/redec_*-*.php");
    $redeclared_files = array();
    $redeclared_classes_count = array();
    $redeclared_funcs_count = array();


    foreach($redeclared_paths as $redeclared_path){
        array_push($redeclared_files, $redeclared_path);
        $inst_count['full'] += 1;
    }

    foreach($need_inst_array as $need_inst_file){
        $inst_put_child_file = IncLoad($need_inst_file, new IncludeParseVisitor);
        $inst_file = STMTManipulateChild($need_inst_file,
                            $inst_put_child_file['ast_nodes'],
                            $inst_put_child_file['file_type']);
        if($inst_put_child_file['file_type'] == "Class"){
            $inst_count['class']++;
        }
        elseif($inst_put_child_file['file_type'] == "Function"){
            $inst_count['function']++;
        }
        $inst_count['finish']++;
        PrintInstrumentationProgress($inst_count, $inst_file, $inst_put_child_file);
    }

    foreach($redeclared_files as $redeclared_file){
        $inst_put_child_file = IncLoad($redeclared_file, new IncludeParseVisitor);
        $inst_file = STMTManipulateChild($redeclared_file,
                            $inst_put_child_file['ast_nodes'],
                            $inst_put_child_file['file_type']);

        $redec_file_name = pathinfo($redeclared_file)['filename'];
        $redec_full_length = strlen($redec_file_name);
        $redec_name_array = explode("_", $redec_file_name);
        $redec_index_length = strlen(end($redec_name_array));
        if($inst_put_child_file['file_type'] == "Class"){
            $file_prefix = "redec_class-";
            $prefix_length = strlen($file_prefix);
            $fetch_length = $redec_full_length - $prefix_length - (1 + $redec_index_length);
            $redec_class_name = substr($redec_file_name, $prefix_length, $fetch_length);
            if(!array_key_exists($redec_class_name, $redeclared_classes_count)){
                $redeclared_classes_count[$redec_class_name] = 0;
            }
            $redeclared_classes_count[$redec_class_name] += 1;
            $inst_count['redec_class']++;
        }
        elseif($inst_put_child_file['file_type'] == "Function"){
            $file_prefix = "redec_func-";
            $prefix_length = strlen($file_prefix);
            $fetch_length = $redec_full_length - $prefix_length - (1 + $redec_index_length);
            $redec_func_name = substr($redec_file_name, $prefix_length, $fetch_length);
            if(!array_key_exists($redec_func_name, $redeclared_funcs_count)){
                $redeclared_funcs_count[$redec_func_name] = 0;
            }
            $redeclared_funcs_count[$redec_func_name] += 1;
            $inst_count['redec_function']++;
        }
        PrintInstrumentationProgress($inst_count, $inst_file, $inst_put_child_file);
        $inst_count['finish']++;
    }

    echo "[#] Instrumentation Finish!\n";
    return $inst_file_body;
}
function STMTManipulateChild($file, $ast, $file_type, $head_file = NULL){
    $file_info = pathinfo($file);
    $inst_file_dir = $file_info['dirname'];
    $inst_file_name = "inst-" . $file_info['basename'];
    $inst_file_path = $inst_file_dir . "/" . $inst_file_name;
    if($file_type == "Body"){
        $inst_file_name = "inst_PUT.php";
        $inst_file_path = $inst_file_dir . "/" . $inst_file_name;
        $namespace_wrapper_body = array(new Node\Stmt\Namespace_(null, $ast));
        $inst_body_ast = InstrumentationByAST($namespace_wrapper_body, new ManipulateNodeVisitor($file_type));
        $inst_body_code = CodePrint($inst_body_ast);
        $inst_head_code = file_get_contents($head_file);
        file_put_contents($inst_file_path, $inst_head_code. $inst_body_code);
    }
    elseif($file_type == "Class"){
        $inst_class_ast_head = array(array_shift($ast));
        $namespace_prefix_body = new Node\Stmt\Namespace_(null);
        array_push($ast, $namespace_prefix_body);
        $inst_class_ast_body = InstrumentationByAST($ast, new ManipulateNodeVisitor($file_type));
        $inst_class_code_head = CodePrint($inst_class_ast_head) . "\n?>\n";
        $inst_class_code_body = CodePrint($inst_class_ast_body);
        $inst_class_code_full = $inst_class_code_head . $inst_class_code_body;
        file_put_contents($inst_file_path, $inst_class_code_full);
    }
    elseif($file_type == "Function"){
        $inst_func_ast = InstrumentationByAST($ast, new ManipulateNodeVisitor($file_type));
        $inst_func_code = CodePrint($inst_func_ast);
        file_put_contents($inst_file_path, $inst_func_code);

    }
    return $inst_file_path;
}
?>
