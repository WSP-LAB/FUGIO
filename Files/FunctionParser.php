<?php
$func_info = array();
$func_names = explode("|", $argv[1]);

foreach($func_names as $func_name){
  $ref_func = new ReflectionFunction($func_name);
  foreach($ref_func->getParameters() as $param){
      $idx = $param->getPosition();
      $func_info[$func_name][$idx]['name'] = $param->getName();
      $func_info[$func_name][$idx]['option'] = $param->isOptional();
  }
}

echo json_encode($func_info);
?>
