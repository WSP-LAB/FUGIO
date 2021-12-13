<?php
class DebugUtils {
  function PrintTemplateInfo($input_seed, $chain_info) {
    $queue = array();
    array_push($queue, array("node" => $input_seed->input_tree['$this'], "parent" => ''));

    echo "================================== SEED#(" . $input_seed->seed_idx .
         ") =================================\n";
    echo "[#] Parent seed: ";
    if (!empty($input_seed->parent)) {
      echo $input_seed->parent-> seed_idx . "\n";
    }
    else {
      echo "\n";
    }
    echo "[#] Select count: " . $input_seed->select_count . "\n";
    echo "[#] Seed Depth: " . $input_seed->depth;
    if ($input_seed->depth > 0) {
         echo " - " . $chain_info[$input_seed->depth]->real_class . "::" .
         $chain_info[$input_seed->depth]->method . "\n";
    }
    else {
      echo "\n";
    }
    echo "[#] Goal depth: " . $input_seed->goal_depth .
         " - " . $chain_info[$input_seed->goal_depth]->class . "::" .
         $chain_info[$input_seed->goal_depth]->method;
    if (empty($chain_info[$input_seed->goal_depth]->sink)) {
      echo "\n";
    }
    else {
      echo " (Sink - " . $chain_info[$input_seed->goal_depth]->sink . ")\n";
    }
    echo "=============================================================================\n";
    echo "[#]" .
         str_pad("Name", 35, " ", STR_PAD_BOTH) . "|" .
         str_pad("Visibility", 12, " ", STR_PAD_BOTH) . "|" .
         str_pad("Type", 13, " ", STR_PAD_BOTH) . "|" .
         str_pad("Value", 14, " ", STR_PAD_BOTH) . "\n";
    echo "-----------------------------------------------------------------------------\n";
    while ($node = array_shift($queue)) {
      echo "[#]";
      echo str_pad($node['parent'] . $node['node']['name'], 35, " ", STR_PAD_BOTH) . "|";
      if ($node['node']['visibility'] == NULL) {
        echo str_pad("public", 12, " ", STR_PAD_BOTH) . "|";
      }
      else {
        echo str_pad($node['node']['visibility'], 12, " ", STR_PAD_BOTH) . "|";
      }
      echo str_pad($node['node']['type'], 13, " ", STR_PAD_BOTH) . "|";
      if ($node['node']['type'] == "Array" or
          $node['node']['type'] == "Oracle-array") {
        echo '  ' . "Array (Skip)\n";
      }
      elseif ($node['node']['type'] == "ArrayObject") {
        echo '  ' . "Array [object] - ";
        for ($i = 0; $i < count($node['node']['value']); $i++) {
          if (is_array($node['node']['value'][$i]['arr_value'])) {
            if (array_key_exists('type', $node['node']['value'][$i]['arr_value'])) {
              if ($node['node']['value'][$i]['arr_value']['type'] == "Object") {
                echo $node['node']['value'][$i]['arr_value']['value'] . "\n";
              }
            }
          }
        }
      }
      elseif ($node['node']['type'] == "DirPath") {
        echo '  ';
        $path = explode("/", $node['node']['value']);
        echo $path[count($path) - 3] . "/" . $path[count($path) - 2];
        echo "\n";
      }
      elseif ($node['node']['type'] == "FilePath") {
        echo '  ';
        $path = explode("/", $node['node']['value']);
        echo $path[count($path) - 2] . "/" . $path[count($path) - 1];
        echo "\n";
      }
      elseif ($node['node']['type'] == "String" and strlen($node['node']['value']) > 50) {
        echo '  ' . substr($node['node']['value'], 0, 50) . "...\n";
      }
      else {
        echo '  ' . $node['node']['value'] . "\n";
      }
      if (empty($node['node']['deps']) == FALSE) {
        foreach ($node['node']['deps'] as $name => $child) {
          if ($node['parent'] == '') {
            $parent = $node['node']['name'] . "->";
          }
          else {
            $parent = $node['parent'] . $node['node']['name'] . "->";
          }
          array_push($queue, array('node' => $child,
                                   'parent' => $parent));
        }
      }
    }
    echo "=============================================================================\n";
  }

  function PrintFuzzingStat($selected_seed, $copied_seed,
                            $start_timestamp, $start_time, $total_exec_count,
                            $total_seed_count, $probably_exploitble_count,
                            $oracle_exec_count, $chain_info,
                            $display_clear = False) {

    // Seed count logging
    /*
    $payload_name = pathinfo($copied_seed->fuzz_result['file_output'])['filename'];
    $file_names = explode("_", $payload_name);
    array_shift($file_names);
    $file_name = implode("_", $file_names);
    $log_duration = ((time() + 3600 * 9) - ($start_timestamp) + 1);
    $log_file = "/tmp/point_log/" . $start_timestamp . "_" . $file_name;
    $log_text = $total_seed_count . ":" . $log_duration . "\n";
    $fp = fopen($log_file, 'w');
    fwrite($fp, $log_text);
    fclose($fp);
    */

    if ($display_clear) {
      echo "\x1b\x5b\x33\x4a\x1b\x5b\x48\x1b\x5b\x32\x4a"; // Display clear
    }

    $time_diff = (time() + 3600 * 9) - ($start_timestamp) + 1;
    $duration_day = floor(($time_diff) / 86400);
    $duration_time = ($time_diff % 86400) - 3600;
    $duration = $duration_day != 0 ?
                $duration_day . "d " .
                date("H:i:s", $duration_time) . " ago..." :
                date("H:i:s", $duration_time) . " ago...";

    echo "=================================== START ";
    echo "===================================\n";
    echo "[+] Selected seed: ". $selected_seed->seed_idx . "\n";
    echo "[#] Payload path: " . $copied_seed->fuzz_result['file_output'] . "\n";

    $this->PrintTemplateInfo($copied_seed, $chain_info);

    echo "[#] Start time: ". $start_time . " (" . $duration . ")\n";
    echo "[#] Total Execution Count: " . $total_exec_count . " (" .
         number_format(round($total_exec_count / ($time_diff), 2), 2) .
         " execution / 1 sec)\n";
    echo "  [+] Probably Exploitable Count: " . $probably_exploitble_count . "\n";
    echo "  [+] Trying Oracle Count: " . $oracle_exec_count . "\n";
    echo "[#] Total Seed Count: " . $total_seed_count . "\n";
    echo "[#] Chain info\n";
    for ($i = 0; $i < count($chain_info); $i++) {
      $file_name = $chain_info[$i]->file;
      $extended_file = $chain_info[$i]->real_file;
      $class_name = $chain_info[$i]->class;
      $extended_class = $chain_info[$i]->real_class;
      if ($file_name == $extended_file and $class_name == $extended_class) {
        $extended_file = NULL;
        $extended_class = NULL;
      }

      $method_name = $chain_info[$i]->method;
      if (!empty($chain_info[$i]->sink)) {
        $method_name = $method_name .
        " (Sink - " . $chain_info[$i]->sink . ")";
      }
      echo " * Gadget#" . $i . " " . $class_name . "::" . $method_name . "\n";
      echo "   - File: " . $file_name . "\n";
      if ($extended_file != NULL and $extended_class != NULL) {
        echo "   - Extended class: " . $extended_class . "\n";
        echo "   - Extended file: " . $extended_file . "\n";
      }
    }
    echo "=========================================";
    echo "====================================\n";
  }

  function PrintAllSeed($seed_pool, $chain_info) {
    $queue = array();
    array_push($queue, $seed_pool->root);

    while ($seed = array_shift($queue)) {
      $this->PrintTemplateInfo($seed, $chain_info);
      foreach ($seed->child as $seed_child) {
        array_push($queue, $seed_child);
      }
    }
  }

  function PrintSelectedLog($selected_log) {
    echo "=========================================";
    echo "====================================\n";
    echo str_pad("Selected Seed Log", 80, " ", STR_PAD_BOTH) . "\n";
    echo "=========================================";
    echo "====================================\n";
    echo str_pad("Index", 10, " ", STR_PAD_BOTH) . "|" .
         str_pad("Selected Count", 20, " ", STR_PAD_BOTH) . "|" .
         str_pad("Depth", 20, " ", STR_PAD_BOTH) . "|" .
         str_pad("GoalDepth", 20, " ", STR_PAD_BOTH) . "\n";
    echo "=========================================";
    echo "====================================\n";

    $strike_through = "\033[9m";
    $revert_com = "\033[0m"; // No color
    $color_red = "\033[31m";
    $color_yellow = "\033[33m";

    foreach ($selected_log as $seed_idx => $seed_summary) {
      $prefix = $revert_com;
      if ($seed_summary['sink_reached']) {
        $prefix = $color_red; // Red
      }
      if ($seed_summary['removed']) {
        $prefix = $color_yellow;
        $prefix .= $strike_through;
      }
      $prefix .= " ";

      echo $prefix .
           str_pad($seed_idx, 9, " ", STR_PAD_BOTH) . "|" .
           str_pad($seed_summary['count'], 20, " ", STR_PAD_BOTH) . "|" .
           str_pad($seed_summary['depth'], 20, " ", STR_PAD_BOTH) . "|" .
           str_pad($seed_summary['goal_depth'], 20, " ", STR_PAD_BOTH) .
           $revert_com. "\n";
    }

    echo "=========================================";
    echo "====================================\n";
  }
}