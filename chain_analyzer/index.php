<?php

@set_time_limit(0);
$pwd = dirname(__FILE__);
$ROOTDIR = realpath($pwd . "/../Files/fuzzing");
// $ROOTDIR = realpath($pwd . "/../test");

echo "<h1>Directory List</h1></hr>";

function r_scandir($root_dir, &$results = array()) {
    foreach(glob("$root_dir/*", GLOB_ONLYDIR) as $dir) {
        r_scandir($dir, $results);
        if (in_array('put-body.php', scandir($dir)) ||
            in_array('PUT_body.php', scandir($dir))) {
            $results[] = $dir;
        }
    }
    return $results;
}

$dir_info = array();
foreach(r_scandir($ROOTDIR) as $dir_path) {
    $dir_name = basename($dir_path);
    $chain_cnt = shell_exec("find $dir_path -name \"proc*.chain\" | wc -l");

    $parents = array_diff(explode('/', $dir_path, -1), explode('/', "$ROOTDIR/", -1));
    $parents[] = '.';

    $cur = &$dir_info;
    foreach ($parents as $parent) {
        if (!array_key_exists($parent, $cur)) {
            $cur[$parent] = array();
        }
        $cur = &$cur[$parent];
    }
    $cur[] = array('DIR_PATH'=>$dir_path,
                   'DIR_NAME'=>$dir_name,
                   'CHAIN_COUNT'=>$chain_cnt);
}

function traverse_dir_info($parent) {
    foreach($parent as $dir_name=>$dir_list) {
        if ($dir_name == '.') {
            echo "<ul>";
            foreach($dir_list as $dir) {
                $name = $dir['DIR_NAME'];
                $path = $dir['DIR_PATH'];
                $chain_count = $dir['CHAIN_COUNT'];
                echo "<li>";
                echo "<a href='sink_list.php?dir_path=$path'>";
                echo "$name</a> ";
                echo " - # of chains: $chain_count";
                echo "</li>";
            }
            echo "</ul>";
        }
        else {
            echo "<ul>";
            echo "<li>$dir_name</li>";
            traverse_dir_info($dir_list);
            echo "</ul>";
        }
    }
}
traverse_dir_info($dir_info);