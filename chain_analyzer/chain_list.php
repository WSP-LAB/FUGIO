<?php
$dir_path = $_GET['dir_path'];
$sink = $_GET['sink'];
$proc_id = $_GET['proc_id'];

echo "<h1> $dir_path </h1>";

$all_chain_list = array();
$uniq_chain_list = array();
$total_chain_counts = 0;
foreach(glob("{$dir_path}/{$proc_id}_*.chain") as $file_path) {
    $total_chain_counts += 1;
    $file_name = basename($file_path);

    $data = file_get_contents($file_path);
    $gadget_list = json_decode($data, true)['chain'];
    if(!is_array($gadget_list) || count($gadget_list) == 0) {
        continue;
    }
    $magic_method = $gadget_list[0]['method'];
    $idx = explode('_', $file_name)[1];

    $chain = "";
    foreach($gadget_list as $gadget) {
        if ($gadget == end($gadget_list)) {
            $chain .= $gadget['real_class'] . "::" . $gadget['sink'];
        }
        else {
            $chain .= $gadget['real_class'] . "::" . $gadget['method'] . " - ";
        }
    }

    if(!array_key_exists($magic_method, $all_chain_list)) {
        $all_chain_list[$magic_method] = array();
    }

    $all_chain_list[$magic_method][] = array(
        'PATH'=>$file_path,
        'NAME'=>$file_name,
        'IDX'=>$idx,
        'LENGTH'=>count($gadget_list),
        'MAGIC_METHOD'=>$magic_method,
        'CHAIN'=>$chain
    );

    if (!in_array($chain, $uniq_chain_list)) {
        $uniq_chain_list[] = $chain;
    }
}

echo "<h3> $sink - $proc_id ($total_chain_counts/" . count($uniq_chain_list) . ") </h3>";
echo "<ul>";
foreach($all_chain_list as $magic_method=>$chain_list) {
    echo '<li><a href="#' . $magic_method . '">';
    echo "$magic_method</a>";
    echo " (" . count($chain_list) . ")";
    echo "</li>";
}
echo "</ul>";
echo '<hr align="left" width="80%">';

echo "<ul>";
foreach($all_chain_list as $magic_method=>$chain_list) {
    echo '<li id="' . $magic_method . '">';
    echo '<b>' . $magic_method;
    echo " (". count($chain_list) . ")";
    echo "</b></li>";
    echo "<ul>";

    $key1 = array_map(function($element) {
        return $element['IDX'];
    }, $chain_list);
    $key2 = array_map(function($element) {
        return $element['LENGTH'];
    }, $chain_list);
    $key3 = array_map(function($element) {
        return $element['CHAIN'];
    }, $chain_list);
    array_multisort($key2, SORT_NUMERIC, SORT_ASC,
                    $key3, SORT_ASC,
                    $key1, SORT_NUMERIC, SORT_ASC, $chain_list);

    $old_chain = "";
    foreach($chain_list as $chain) {
        $file_name = $chain['NAME'];
        $file_path = $chain['PATH'];
        $chain_len = $chain['LENGTH'];
        $magic_method = $chain['MAGIC_METHOD'];
        $new_chain = $chain['CHAIN'];

        if ($old_chain != $new_chain) {
            if ($chain != $chain_list[0]) {
                echo "</ol>";
            }
            echo "<li>";
            // foreach($new_chain as $c) {
            //     echo "$c";
            //     if ($c != end($new_chain)) {
            //         echo " - ";
            //     }
            // }
            echo $new_chain;
            echo "</li>";
            echo "<ol>";
            $old_chain = $new_chain;
        }

        echo "<li>";
        echo "<a href='chain_analyzer.php?file_path=$file_path'>";
        echo "$file_name" . "</a> ";
        echo "(Lenth: " . $chain_len . ", ";
        echo "Magic method: " . $magic_method . ")";
        echo "</li>";
    }
    echo "</ol>";
    echo "</ul>";
    echo "<br>";
}
echo "</ul>";