<?php
class FuzzManager {
  public $max_gadget_dpeth = 0;
  public $seed_pool = array();
  public $banned_seed;
  public $chain_info;
  public $inst_put_file;

  function __construct($file_put_head, $file_put, $file_chain,
                       $rabbitmq_ip, $rabbitmq_port,
                       $rabbitmq_id, $rabbitmq_password,
                       $rabbitmq_channel = NULL) {
    $this->max_gadget_depth = 0;
    $this->banned_seed = array();

    $this->file_put_head = $file_put_head;
    $this->file_put = $file_put;
    $this->file_chain = $file_chain;
    $this->chain_info = $this->LoadChainInfo($this->file_chain);
    $this->cand_methods = $this->chain_info->var_list->method_candidates;
    $this->cand_props = $this->chain_info->var_list->prop_candidates;
    $this->cand_foreach = $this->chain_info->var_list->foreach_candidates;

    $this->rabbitmq_settings = array(
      "ip" => $rabbitmq_ip,
      "port" => intval($rabbitmq_port),
      "id" => $rabbitmq_id,
      "password" => $rabbitmq_password,
      "channel" => $rabbitmq_channel
    );
    // gadget_info => $this->chain_info->var_list->gadget_info;
    // chain_info => $this->chain_info->chain

    $this->file_inst = $this->Instrumentation();
    $this->RunSlave();
  }

  function LoadChainInfo($file_chain) {
    return json_decode(file_get_contents($file_chain));
  }

  function Instrumentation() {
    $inst_file = dirname($this->file_chain) .
                  "/inst_PUT.php";

    if (!file_exists($inst_file)) {
      shell_exec(
        "php " .
        __DIR__ .
        "/Instrumentor.php " .
        $this->file_put_head .
        " " .
        $this->file_put
      );
    }
    return $inst_file;
  }

  function RunSlave() {
    $channel_name = str_replace("/", "_", realpath($this->file_chain));
    $channel_name = str_replace(".", "_", $channel_name);
    $this->rabbitmq_settings['channel'] = $channel_name;

    $fuzz_slave = new FuzzSlave($this->file_chain, $this->chain_info,
                                $this->cand_methods, $this->cand_props,
                                $this->cand_foreach, $this->file_inst,
                                $this->rabbitmq_settings);

    $this->seed_pool[$this->file_chain] = new SeedTree();
    $fuzz_slave->RunFuzz($this->seed_pool[$this->file_chain]);

    /*
    seed_pool was called by ref to RunFuzz(). Because, each fuzz slave share
    their seed_pool tree to other slave.
    */
  }
}