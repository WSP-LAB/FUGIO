<?php
$dir_path = $_GET['dir_path'];

echo "<h1> $dir_path </h1>";

$sink_list = array();
$total_chain_counts = 0;
$total_proc = 0;

$proc_sink = array();
foreach(glob("$dir_path/proc*.chain") as $file_path) {
    $total_chain_counts += 1;
    $file_name = basename($file_path);
    $chain_info = explode('_', $file_name);

    $proc_id = $chain_info[0];
    $chain_idx = $chain_info[1];
    $chain_length = $chain_info[2];

    if (!array_key_exists($proc_id, $proc_sink)) {
        $data = file_get_contents($file_path);
        $chain_list = json_decode($data, true)['chain'];
        if(!is_array($chain_list) || count($chain_list) == 0) {
            continue;
        }
        $sink = end($chain_list)['sink'];
        if (end($chain_list)['class'] == end($chain_list)['real_class']) {
            $sink_method = end($chain_list)['class'];
            $sink_method .= "::";
            $sink_method .= end($chain_list)['method'];
        }
        else {
            $sink_method = end($chain_list)['class'];
            $sink_method .= "(" . end($chain_list)['real_class']. ")::";
            $sink_method .= end($chain_list)['method'];
        }
        $proc_sink[$proc_id] = $sink;
    }
    $sink = $proc_sink[$proc_id];

    if (!array_key_exists($sink, $sink_list)) {
        $sink_list[$sink] = array();
    }
    if (!array_key_exists($proc_id, $sink_list[$sink])) {
        $sink_list[$sink][$proc_id] = array();
        $total_proc += 1;
    }
    $sink_list[$sink][$proc_id][] = array('IDX'=>$chain_idx,
                                          'LEN'=>$chain_length,
                                          'METHOD'=>$sink_method);
}

echo '<ul style="list-style-type:square;"><li><b>Overview</b></li>';
echo '<ul style="list-style-type:disc;">';
echo "<li><b># of covered sinks: " . count($sink_list) . "</b></li>";
echo "<li><b># of unique sinks: $total_proc</b></li>";
echo "<li><b># of generated chains: $total_chain_counts</b></li>";
echo "</ul><br>";

echo "<li><b>Sink list</b></li><ul>";
$sink_chain_cnt = array();
foreach($sink_list as $sink=>$proc_list) {
    $total_cnt = 0;
    foreach($proc_list as $proc=>$proc_info) {
        $total_cnt += count($proc_info) ;
    }
    $sink_chain_cnt[$sink] = $total_cnt;
    echo '<li><a href="#' . $sink . '">';
    echo "$sink</a>";
    echo " (" . count($sink_list[$sink]) . ", " . $total_cnt . ")";
    echo "</li>";
}
echo "</ul></ul>";
echo '<hr align="left" width="80%">';

echo "<ul>";
foreach($sink_list as $sink=>$proc_list) {
    echo '<li id="' . $sink . '">';
    echo '<b>' . $sink;
    echo " (". count($sink_list[$sink]) . ", " . $sink_chain_cnt[$sink] . ")";
    echo "</b></li>";

    echo "<ol>";
    $key = array_map(function($key) {
        return substr($key, 4);
    }, array_keys($proc_list));
    array_multisort($key, SORT_NUMERIC, SORT_ASC, $proc_list);

    foreach($proc_list as $proc=>$proc_info) {
        echo "<li><a href='chain_list.php?dir_path=$dir_path&sink=$sink&proc_id=$proc'>";
        echo "$proc</a>";
        echo " (" . count($proc_info) . ")";
        echo " - " . $proc_info[0]['METHOD'] . "</li>";
    }
    echo "</ol><br>";
}
echo "</ul>";