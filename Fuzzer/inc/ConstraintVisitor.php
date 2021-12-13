<?php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ConstraintNodeVisitor extends NodeVisitorAbstract
{
  public function beforeTraverse(array $nodes) {
    $this->stop_flag = False;
    $this->constraint_info = array(
      "result" => "NOTHING",
      "prop_name" => NULL,
      "type" => NULL,
      "value" => NULL
    );
  }

  private function SetConstraint($result, $prop_name, $type, $value){
    if($prop_name instanceof Node\Expr\Variable){
      // Dynamic property (Pass)
      // $obj->${$prop_1}
      return;
    }
    elseif($prop_name instanceof Node\Expr\BinaryOp\Concat){
      // Concat property (Pass)
      // $obj->{$type . "_restriction"} == 0
      return;
    }
    
    if(PHP_VERSION >= "7.2"){
      if($prop_name instanceof Node\Identifier){
        $constraint_prop_name = $prop_name->name;
      }
      elseif($prop_name instanceof Node\Scalar\String_){
        $constraint_prop_name = $prop_name->value;
      }
      else{
	return; // [TODO] Ignore all.
	var_dump($result, $prop_name, $type, $value);
        exit("[#] Need to Fix constraint AST - 1 (PHP 7.x)");
      }
    }
    else{
      $constraint_prop_name = $prop_name;
    }


    $this->constraint_info['result'] = $result;
    $this->constraint_info['prop_name'] = $constraint_prop_name;
    $this->constraint_info['type'] = $type;
    $this->constraint_info['value'] = $value;
  }

  public function GetValueInfoByAST($ast){
    $result['result'] = "NOTHING";
    $result['type'] = NULL;
    $result['value'] = NULL;

    if($ast instanceof Node\Scalar\String_){
      $result['result'] = "TYPE_VALUE";
      $result['type'] = "String";
      $result['value'] = $ast->value;
    }
    elseif($ast instanceof Node\Scalar\LNumber){
      $result['result'] = "TYPE_VALUE";
      $result['type'] = "Int";
      $result['value'] = $ast->value;
    }
    elseif($ast instanceof Node\Scalar\DNumber){
      $result['result'] = "TYPE_VALUE";
      $result['type'] = "Float";
      $result['value'] = $ast->value;
    }
    elseif($ast instanceof Node\Expr\ConstFetch){
      if($ast->name instanceof Node\Name and
         is_array($ast->name->parts) and
         count($ast->name->parts) >= 1){
        if(strtolower($ast->name->parts[0]) == "true" or
           strtolower($ast->name->parts[0]) == "false"){
          $result['result'] = "TYPE_VALUE";
          $result['type'] = "Boolean";
          $result['value'] = $ast->name->parts[0];
        }
      }
    }
    return $result;
  }

  public function enterNode(Node $node) {
    if($this->stop_flag == True){
      return $node;
    }
    $this->stop_flag = True;
    
    if(PHP_VERSION >= "7.2"){
      if($node instanceof Node\Stmt\Expression){
        $target_node = $node->expr;
      }
      else{
        exit("[#] Need to Fix constraint AST - 2 (PHP 7.x)");
      }
    }
    else{
      $target_node = $node;
    }
    // Array type hinting
    if($target_node instanceof Node\Expr\FuncCall and
       $target_node->name instanceof Node\Name and
       is_array($target_node->name->parts) and
       count($target_node->name->parts) >= 1){

      if($target_node->name->parts[0] == "is_array"){ // Array type
        if($target_node->args[0]->value instanceof Node\Expr\PropertyFetch){ // Arg is prop fetch
          $prop_name = $target_node->args[0]->value->name;
          $this->SetConstraint("ONLY_TYPE", $prop_name, "Array", "NULL");
        }
      }
      elseif($target_node->name->parts[0] == "is_bool"){
        if($target_node->args[0]->value instanceof Node\Expr\PropertyFetch){ // Arg is prop fetch
          $prop_name = $target_node->args[0]->value->name;
          $this->SetConstraint("ONLY_TYPE", $prop_name, "Boolean", "NULL");
        }
      }
      elseif($target_node->name->parts[0] == "is_double" or
             $target_node->name->parts[0] == "is_float" or
             $target_node->name->parts[0] == "is_real"){
        if($target_node->args[0]->value instanceof Node\Expr\PropertyFetch){ // Arg is prop fetch
          $prop_name = $target_node->args[0]->value->name;
          $this->SetConstraint("ONLY_TYPE", $prop_name, "Float", "NULL");
        }
      }
      elseif($target_node->name->parts[0] == "is_int" or
            $target_node->name->parts[0] == "is_integer" or
            $target_node->name->parts[0] == "is_long"){
        if($target_node->args[0]->value instanceof Node\Expr\PropertyFetch){ // Arg is prop fetch
          $prop_name = $target_node->args[0]->value->name;
          $this->SetConstraint("ONLY_TYPE", $prop_name, "Int", "NULL");
        }
      }
      elseif($target_node->name->parts[0] == "is_string"){
        if($target_node->args[0]->value instanceof Node\Expr\PropertyFetch){ // Arg is prop fetch
          $prop_name = $target_node->args[0]->value->name;
          $this->SetConstraint("ONLY_TYPE", $prop_name, "String", "NULL");
        }
      }

    }
    // $this->prop_1 == "XXXX" (Type & Value hinting)
    elseif($target_node instanceof Node\Expr\BinaryOp\Equal or
       $target_node instanceof Node\Expr\BinaryOp\NotEqual or
       $target_node instanceof Node\Expr\BinaryOp\Identical or
       $target_node instanceof Node\Expr\BinaryOp\NotIdentical){
      // left or right value is property?
      $left_prop_name = NULL;
      $right_prop_name = NULL;
      if($target_node->left instanceof Node\Expr\PropertyFetch){
        $left_prop_name = $target_node->left->name;
      }
      if($target_node->left instanceof Node\Expr\ArrayDimFetch and
	 $target_node->left->var instanceof Node\Expr\PropertyFetch){
        $left_prop_name = $target_node->left->var->name;
      }
      if($target_node->right instanceof Node\Expr\PropertyFetch){
        $right_prop_name = $target_node->right->name;
      }
      if($target_node->right instanceof Node\Expr\ArrayDimFetch and
         $target_node->right->var instanceof Node\Expr\PropertyFetch){
        $right_prop_name = $target_node->right->var->name;
      }

      // only left is property
      if($left_prop_name != NULL && $right_prop_name == NULL){
        $value_info = $this->GetValueInfoByAST($target_node->right);
        if($value_info['result'] != "NOTHING"){
          $this->SetConstraint(
            $value_info['result'], $left_prop_name,
            $value_info['type'], $value_info['value']
          );
        }
      }
      // only right is property
      elseif($left_prop_name == NULL && $right_prop_name != NULL){
        $value_info = $this->GetValueInfoByAST($target_node->left);
        if($value_info['result'] != "NOTHING"){
          $this->SetConstraint(
            $value_info['result'], $right_prop_name,
            $value_info['type'], $value_info['value']
          );
        }
      }
      // both left and right are prop.
      elseif($left_prop_name != NULL && $right_prop_name != NULL){

      }
      // both left and right are not prop.
      elseif($left_prop_name == NULL && $right_prop_name == NULL){

      }


    }
  }
  public function leaveNode(Node $node) {
    
  }
  public function afterTraverse(array $nodes) {

  }
    public function GetValueInfo(){
      return $this->constraint_info;
  }
}
?>
