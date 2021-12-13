<?php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class IncludeParseVisitor extends NodeVisitorAbstract
{
	public function beforeTraverse(array $nodes) {
        $this->inc_files = array();
        $this->first_namespace = False;
        $this->stop_traverse = False;
        $this->file_type = "Body";
    }

	public function enterNode(Node $node) {

        if($this->stop_traverse){
            return $node;
        }

        if($node instanceof Node\Expr\Include_){
			$include_file = $node->expr->value;
			$include_file_info = pathinfo($include_file);
			$file_dir = $include_file_info['dirname'];
            $file_name = $include_file_info['basename'];
            $inst_include_file = $file_dir . "/inst-" . $file_name;
            
            $node->expr->value = $inst_include_file;
            array_push($this->inc_files, $include_file);
            
            return $node;
            
		}
        if($node instanceof Node\Stmt\Namespace_){
            if($this->first_namespace == False){
                $this->first_namespace = True;
            }
            else{
                $this->stop_traverse = True;
                $this->file_type = "Class";
            }
        }
        if($node instanceof Node\Stmt\Function_){
            $this->stop_traverse = True;
            $this->file_type = "Function";
        }
    
    }
	public function leaveNode(Node $node) {
    
    }
	public function afterTraverse(array $nodes) {
        $output = array();
        $output['inc_files'] = $this->inc_files;
        $output['ast_nodes'] = $nodes;
        $output['file_type'] = $this->file_type;

        return $output;

    }
}
?>
