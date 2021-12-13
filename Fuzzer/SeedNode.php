<?php
class SeedNode {
  public $parent;
  public $child = array();
  public $class_template;
  public $input_tree;
  public $structure_hash;

  public $fuzz_result;
  public $revise_message = array();
  public $seed_idx;

  public $goal_depth = 0;
  public $depth = 0;
  public $select_count = 0;

  public $sink_reached = array("reach" => False, "sink_branch" => NULL);
  public $sink_strategy_tried = array();
  public $need_validating_argv_idxs = array();
  public $validated_argv_idxs = array();

  public $path_hash;
  public $array_hinting = array();
  public $array_object = array();

  function GetStructureHash() {
    $new_tree = new ArrayObject($this->input_tree);
    $copied_tree = $new_tree->getArrayCopy();

    $tree_queue = array();
    $tree_queue[] = &$copied_tree['$this'];
    while ($tree_node = &$tree_queue[0]) {
      array_shift($tree_queue);
      // Name, Type, Value, File, Visibility, Info (Data, Candidates), Deps;
      if ($tree_node['type'] == "Object") {
        unset($tree_node['value']);
        unset($tree_node['preserve']); // Object doens't need preserve.

        foreach ($tree_node['deps'] as &$child_node) {
          $tree_queue[] = &$child_node;
        }
      }
      else {
        if (array_key_exists('preserve', $tree_node)) {
          if ($tree_node['preserve'] == "ONLY_TYPE") {
            unset($tree_node['value']);
          }
          elseif ($tree_node['preserve'] == "TYPE_VALUE") {
            // Do nothing. It is unique.
          }
        }
        else {
          unset($tree_node['type']);
          unset($tree_node['value']);
        }
      }
    }

    // Reference clear
    unset($child_node);
    unset($tree_node);
    unset($tree_queue);

    $arr_hinting_str = "";
    foreach ($this->array_hinting as $hinting_data) {
      $arr_hinting_str .= $hinting_data['hash'];
    }

    $tree_string = var_export($copied_tree, true);
    $tree_hash = md5($tree_string);
    return $tree_hash;
  }

  function GetMergedTreeList($new_template, $add_flag = False) {
    $new_tree_list = array();

    $deterministic_info = array();
    $deterministic_info['exist'] = False;
    $deterministic_info['order'] = 0;
    $deterministic_info['name'] = NULL;

    $change_this = True;
    // If new node has the same $this value
    if ($this->input_tree['$this']['value'] != $new_template['$this']['value']) {
      $change_this = False;
    }

    $node_queue = array();
    array_push($node_queue, $this->input_tree['$this']);
    while ($node = array_shift($node_queue)) {
      $deterministic_info['order'] += 1;
      if ($node['type'] == "Object" and
          $add_flag == False and // Forcely add on any props.
          property_exists($node['info'], 'candidates')) {
        $cand_order = 0;
        foreach ($node['info']->candidates as $node_cands) {
          if ($node_cands->deterministic === True) { // Deterministic?
            $new_tree = new ArrayObject($this->input_tree);
            $new_tree_list = array($new_tree->getArrayCopy());
            $deterministic_info['exist'] = True;
            break;
          }
          $cand_order += 1;
        }
      }
      if ($deterministic_info['exist'] == True) {
        break;
      }
      $new_tree = new ArrayObject($this->input_tree);
      array_push($new_tree_list, $new_tree->getArrayCopy());
      foreach ($node['deps'] as $child_node) {
        array_push($node_queue, $child_node);
      }
    }

    $change_count = 0;
    foreach ($new_tree_list as &$new_tree) {
      $traverse_count = 0;
      $node_queue = array(&$new_tree['$this']);
      while ($node = &$node_queue[0]) {
        array_shift($node_queue);
        if ($deterministic_info['exist'] == True) {
          $deterministic_info['order'] -= 1;
          if ($deterministic_info['order'] == 0) {
            $node['info']->candidates[$cand_order]->deterministic = "Used";
            if ($new_template != NULL) {
              $node['type'] = $new_template['$this']['type'];
              $node['info'] = $new_template['$this']['info'];
              $node['value'] = $new_template['$this']['value'];
              if ($add_flag == FALSE) {
                $node['file'] = $new_template['$this']['file'];
              }
              $node['deps'] = $node['deps'] + $new_template['$this']['deps'];
            }
            break;
          }
        }
        else {
          if ($traverse_count >= $change_count) {
            $change_count += 1;
            if ($change_this == False and $node['name'] == '$this') {
              foreach ($node['deps'] as &$child_node) {
                $node_queue[] = &$child_node;
              }
              continue;
            }

            if ($new_template != NULL) {
              $node['type'] = $new_template['$this']['type'];
              // $node['info'] = $new_template['$this']['info'];
              $node['value'] = $new_template['$this']['value'];
              if ($add_flag == FALSE) {
                $node['file'] = $new_template['$this']['file'];
                // $node['visibility'] = $new_template['$this']['visibility'];
              }
              // $node['deps'] = $node['deps'] + $new_template['$this']['deps'];
              foreach ($new_template['$this']['deps'] as $name=>$info) {
                if (array_key_exists($name, $node['deps'])) {
                  if (rand(0, 100) >= 50) continue;
                }
                $node['deps'][$name] = $info;
              }
            }
            else { // If we met func_call gadget, do not merge anything.
              break;
            }
            break;
          }
          else {
            $traverse_count += 1;
          }
        }
        foreach ($node['deps'] as &$child_node) {
          $node_queue[] = &$child_node;
        }
      }
      unset($child_node);
      unset($node_queue);
      unset($node);
    }

    unset($new_tree);
    unset($node_queue);

    // Useless first array
    if ($change_this == False and $deterministic_info['exist'] == False) {
      array_shift($new_tree_list);
    }

    return $new_tree_list;
  }
}