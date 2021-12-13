<?php
class SeedTree {
  public $root;
  public $seed_idx = 0;

  function SetRoot($seed) {
    $this->root = $seed;
    $seed->structure_hash = $seed->GetStructureHash();
  }

  function AddSeed($parent, $child, $check_dup = False) {
    if ($check_dup == True) {
      $is_duplicated = $this->isDuplicateStructure($child);
      if (!$is_duplicated) {
        $child->structure_hash = $child->GetStructureHash();
        array_push($parent->child, $child);
        $child->parent = $parent;
      }
    }
    else {
      $child->structure_hash = $child->GetStructureHash();
      array_push($parent->child, $child);
      $child->parent = $parent;
    }
  }

  function RemoveSeed($seed) {
    $seed->parent->child = $seed->child;
    foreach ($seed->child as $seed_child) {
      $seed_child->parent = $seed->parent;
    }
    unset($seed);
  }

  function GetSeedCount() {
    $seed_count = 0;
    $seed_queue = array();
    array_push($seed_queue, $this->root);

    while ($seed = array_shift($seed_queue)) {
      $seed_count += 1;
      foreach ($seed->child as $child_seed) {
        array_push($seed_queue, $child_seed);
      }
    }

    return $seed_count;
  }

  function CherryPick($fianl_goal_depth) {
    $max_goal_depth = -INF; // in Exists
    $const_exp = exp(1);
    $const_max_try = 100;
    $const_sink_weight = 0.9;

    // Split seeds by depth
    $seed_dict = array();
    $seed_queue = array($this->root);
    while ($seed = array_shift($seed_queue)) {
      if (!array_key_exists($seed->goal_depth, $seed_dict)) {
        $goal_depth = $seed->goal_depth + 1;
        if ($max_goal_depth <= $goal_depth) {
          $max_goal_depth = $goal_depth;
        }
        $seed_dict[$goal_depth]['TRY_O'] = array();
        $seed_dict[$goal_depth]['TRY_X'] = array();
        $seed_dict[$goal_depth]['TRY_O_SINK_REACHED'] = array();
        $seed_dict[$goal_depth]['TRY_X_SINK_REACHED'] = array();
      }
      if ($seed->select_count == 0) {
        array_push($seed_dict[$goal_depth]['TRY_X'], $seed);
        if ($seed->sink_reached['reach'] == True) {
          array_push($seed_dict[$goal_depth]['TRY_X_SINK_REACHED'], $seed);
        }
      }
      else {
        array_push($seed_dict[$goal_depth]['TRY_O'], $seed);
        if ($seed->sink_reached['reach'] == True) {
          array_push($seed_dict[$goal_depth]['TRY_O_SINK_REACHED'], $seed);
        }
      }

      foreach ($seed->child as $child_seed) {
        array_push($seed_queue, $child_seed);
      }
    }

    // Select depth
    $depth_scores = array();
    $depth_sum = 0;
    foreach ($seed_dict as $goal_depth => $seeds) {
      $power = (5 * ($fianl_goal_depth - $goal_depth) / ($max_goal_depth));
      $depth_score = 1/(1+pow(exp(1), $power));
      array_push($depth_scores, $depth_score);
      $depth_sum += $depth_score;
    }
    $depth_probability = array();
    foreach ($depth_scores as $goal_depth => $depth_score) {
      array_push($depth_probability, $depth_score/$depth_sum);
    }
    $selected_depth_array = $this->RandomChoiceByProbability($seed_dict,
                                                             $depth_probability);

    // Select a seed among seeds that have the selected depth
    $seeds = array();
    $seed_probabilities = array();
    $selected_seeds = array();
    $count_try_o = count($selected_depth_array['TRY_O']);
    $count_try_x = count($selected_depth_array['TRY_X']);
    $count_try_o_sink_reached = count($selected_depth_array['TRY_O_SINK_REACHED']);
    $count_try_x_sink_reached = count($selected_depth_array['TRY_X_SINK_REACHED']);
    $count_sink_reached = $count_try_o_sink_reached + $count_try_x_sink_reached;
    if ($count_sink_reached > 0) {
      $try_o_prob_sum = 0;
      $sink_reached_try_o_prob_sum = 0;
      foreach ($selected_depth_array['TRY_O'] as $try_o_seed) {
        $power = $try_o_seed->select_count / ($const_exp * $const_max_try);
        $try_o_alpha = 2 / (1 + pow($const_exp, $power));
        if ($try_o_seed->sink_reached['reach']) {
          $try_o_prob = (($const_sink_weight) /
                        ($count_try_o_sink_reached + $count_try_x_sink_reached)) *
                        $try_o_alpha;
          $sink_reached_try_o_prob_sum += $try_o_prob;
        }
        else {
          $try_o_prob = ((1 - $const_sink_weight) /
                        (($count_try_o + $count_try_x) -
                        ($count_try_o_sink_reached + $count_try_x_sink_reached))) *
                        $try_o_alpha;
          $try_o_prob_sum += $try_o_prob;
        }
        array_push($seeds, $try_o_seed);
        array_push($seed_probabilities, $try_o_prob);
      }
      foreach ($selected_depth_array['TRY_X'] as $try_x_seed) {
        if ($try_x_seed->sink_reached['reach']) {
          $try_x_prob = ($const_sink_weight - $sink_reached_try_o_prob_sum) /
                        ($count_try_x_sink_reached);
        }
        else {
          $try_x_prob = (((1 - $const_sink_weight) - ($try_o_prob_sum)) /
                        (($count_try_o + $count_try_x) -
                        ($count_try_o_sink_reached + $count_try_x_sink_reached) -
                        ($count_try_o)));
        }
        array_push($seeds, $try_x_seed);
        array_push($seed_probabilities, $try_x_prob);
      }
    }
    else {
      $try_o_prob_sum = 0;
      foreach ($selected_depth_array['TRY_O'] as $try_o_seed) {
        $power = $try_o_seed->select_count / ($const_exp * $const_max_try);
        $try_o_alpha = 2 / (1 + pow($const_exp, $power));
        $try_o_prob = (1 / ($count_try_o + $count_try_x)) * $try_o_alpha;
        array_push($seeds, $try_o_seed);
        array_push($seed_probabilities, $try_o_prob);
        $try_o_prob_sum += $try_o_prob;
      }
      foreach ($selected_depth_array['TRY_X'] as $try_x_seed) {
        $try_x_prob = (1 - $try_o_prob_sum) / ($count_try_x);
        array_push($seeds, $try_x_seed);
        array_push($seed_probabilities, $try_x_prob);
      }
    }

    // Get Seed
    $picked_seed = $this->RandomChoiceByProbability($seeds, $seed_probabilities);
    return $picked_seed;
  }

  function RandomChoiceByProbability($arrays, $weights) {
    $longest_point = 0;
    foreach ($weights as $weight) {
      $float_to_str = explode(".", $weight);
      if (count($float_to_str) == 1) {
        $under_point = 0;
      }
      else {
        $under_point = strlen($float_to_str[1]);
      }

      if ($under_point >= $longest_point) {
        $longest_point = $under_point;
      }
    }

    // Convert float weights to int weights
    $weights_sum = 0;
    $converted_weights = array();
    $convert_aux_value = pow(10, $longest_point);
    foreach ($weights as $weight) {
      array_push($converted_weights, $weight * $convert_aux_value);
      $weights_sum += $weight * $convert_aux_value;
    }

    $rand_num = rand(1, $weights_sum);
    $index = 0;
    $inc_value = 0;

    foreach ($arrays as $array) {
      $inc_value += $converted_weights[$index];
      if ($inc_value >= $rand_num) {
        return $array;
      }
      $index += 1;
    }
  }

  function isDuplicateStructure($new_seed) {
    $new_seed_structure_hash = $new_seed->GetStructureHash();

    $seed_queue = array();
    array_push($seed_queue, $this->root);

    $seed_list = array();
    while ($seed = array_shift($seed_queue)) {
      if ($seed->structure_hash == $new_seed_structure_hash and
          $seed->goal_depth == $new_seed->goal_depth) {
        return True;
      }
      foreach ($seed->child as $child_seed) {
        array_push($seed_queue, $child_seed);
      }
    }

    return False;
  }

  function IsNewPath($copied_seed) {
    $seed_queue = array();
    array_push($seed_queue, $this->root);

    $seed_list = array();
    while ($seed = array_shift($seed_queue)) {
      if ($seed->path_hash == $copied_seed->path_hash and
          $seed->goal_depth == $copied_seed->goal_depth and
          $seed->depth == $copied_seed->depth) {
        return False;
      }
      foreach ($seed->child as $child_seed) {
        array_push($seed_queue, $child_seed);
      }
    }

    return True;
  }
}