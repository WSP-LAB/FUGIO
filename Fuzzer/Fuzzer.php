<?php
if (function_exists('uopz_allow_exit')) {
  uopz_allow_exit(true);
}

define('MAKE_AUX_PROP', '50'); // 50 %
define('MAKE_AUX_SUB_PROP', '50'); // 50%
define('MUTATE_PROP', '100'); // 100%
define('MAX_STR_LENGTH', '150');
define('SEED_DEPTH_WEIGHT', '500'); // Deprecated
define('SINK_REACH_WEIGHT', '10000'); // Deprecated
define('MEMORY_LIMIT', '512M');
define('ARR_OBJ_PROP', '70');
define('FIRST_INDEX_OBJ_PROP', '95'); // Object is first index at ArrayObject Type
define('SEED_BASIC_VALUE', 1);
// $SEED_VALUE = SEED_BASIC_VALUE;
$SEED_VALUE = intval(explode(" ", microtime())[0] * 1000000) * SEED_BASIC_VALUE;
srand($SEED_VALUE);
echo "[#] SEED_VALUE: ". $SEED_VALUE ."\n";

$DEBUG_FLAG = FALSE;

require_once __DIR__ . '/Files/Sink_Info.php';
require_once __DIR__ . '/SeedNode.php';
require_once __DIR__ . '/SeedTree.php';
require_once __DIR__ . '/PayloadCreator.php';
require_once __DIR__ . '/Executor.php';
require_once __DIR__ . '/DebugUtils.php';
require_once __DIR__ . '/FuzzSlave.php';
require_once __DIR__ . '/FuzzManager.php';

$file_put_head = $argv[1];
$file_put = $argv[2];
$file_chain = $argv[3];
$rabbitmq_ip = $argv[4];
if (array_key_exists(5, $argv)) {
  $FUZZING_TIMEOUT = (int) $argv[5];
}
else {
  $FUZZING_TIMEOUT = INF;
}
$rabbitmq_port = 5672;
$rabbitmq_id = "fugio";
$rabbitmq_password = "fugio_password";
$rabbitmq_channel = "";
$fuzz_manager = new FuzzManager($file_put_head, $file_put, $file_chain,
                                $rabbitmq_ip, $rabbitmq_port,
                                $rabbitmq_id, $rabbitmq_password,
                                $rabbitmq_channel);
