<?php
if(getenv("FUZZ_CMD") === "FuzzerInit"){
    $GLOBALS['Feedback_cls']->init_output = array(
        "CONDITIONS" => $GLOBALS['Feedback_cls']->ifConstraints
    );
    exit();
}

// $entry_magic_method = "__destruct";
$GLOBALS['Feedback_cls']->BranchPath = array();
$userInput = base64_decode($argv[1]);
// $userInput = $GLOBALS['Feedback_cls']->mutate();
// echo "[#] User Input: " . $userInput . "\n";
$fuzzed_class = unserialize($userInput);
switch ($entry_magic_method){
    case "__destruct":
        unset($fuzzed_class);
        break;
    case "__construct": // TODO
        break;
    case "__call":
        $fuzzed_class->non_existed_method();
        break;
    case "__callStatic":
        $fuzzed_class::non_existed_method();
        break;
    case "__get":
        $fuzzed_class->non_existed_property;
        break;
    case "__set":
        $fuzzed_class->non_existed_property = NULL;
        break;
    case "__isset":
        isset($fuzzed_class->non_existed_property);
        break;
    case "__unset":
        unset($fuzzed_class->non_existed_property);
        break;
    case "__sleep":
        serialize($fuzzed_class);
        break;
    case "__toString":
        echo $fuzzed_class;
        break;
    case "__invoke":
        $fuzzed_class(NULL);
        break;
    case "__set_state":
        var_export($fuzzed_class);
        break;
    case "__clone":
        clone $fuzzed_class;
        break;
    case "__debugInfo":
        var_dump($fuzzed_class);
        break;
    default: // __wakeup
        break;
}
/*
$last_path = end($GLOBALS['Feedback_cls']->BranchPath);
if($last_path['type'] == "IF-PRE" or
    $last_path['type'] == "ELIF-PRE"){
    foreach($GLOBALS['Feedback_cls']->goalPath as $goalPath){
        if($last_path['hash'] == $goalPath['hash']){
            foreach($GLOBALS['Feedback_cls']->ifConstraints as $ifConstraint){
                if($ifConstraint['hash'] == $last_path['hash']){
                    echo "[!] We need to pass this cond\n";
                    var_dump($ifConstraint);
                }
            }
        }
    }
    // if we do not need pass this cond.
    // waive this cond?
}
*/
// var_dump($GLOBALS['Feedback_cls']->goalPath);
// var_dump($GLOBALS['Feedback_cls']->BranchPath);

/*
echo "[#] Branch passed count\n";
echo count($GLOBALS['Feedback_cls']->BranchPath);
echo "/";
echo count($GLOBALS['Feedback_cls']->goalPath);
echo "\n";
var_dump($GLOBALS['Feedback_cls']->BranchPath);
*/

?>
