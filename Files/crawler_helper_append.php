<?php
if(array_key_exists('point_crawl_helper',$_COOKIE) and
  $_COOKIE['point_crawl_helper'] == True){
  $crawl_helper_result = array();
  $crawl_helper_result['isset'] = array();
  $isset_lower_array = array();
  foreach(isset_log() as $isset_key => $isset_value){
    if(!in_array(strtolower($isset_value), $isset_lower_array) ){
      array_push($crawl_helper_result['isset'], $isset_value);
      array_push($isset_lower_array, strtolower($isset_value));
    }
  }
  $crawl_helper_result['array_key_exists'] = $GLOBALS['array_key_list_r353t'];
  echo "CRAWL_HELPER_DATA_START_R353T";
  echo json_encode($crawl_helper_result);
  echo "CRAWL_HELPER_DATA_END_R353T";
}
?>