/*
    1. \GuzzleHttp\Psr7\FnStream::__toString // call_user_func
    2. callRegisteredShutdown::callRegisteredShutdown
    3. call_user_func_array
*/

// core/vendor/guzzlehttp/psr7/src/FnStream.php
class FnStream implements StreamInterface {
    public function __toString() {
        return call_user_func($this->_fn___toString); // [*] next
                                                      // [!] need to set $this->_fn___toString to call callRegisteredShutdown::callRegisteredShutdown
    }
}

// core/src/pydio/Core/Controller/ShutdownScheduler.php
class ShutdownScheduler {
    public function callRegisteredShutdown($cliOutput = null) {
        session_write_close();
        ob_end_flush();
        flush();
        $index = 0;
        while (count($this->callbacks)) {
            $arguments = array_shift($this->callbacks);
            $callback = array_shift($arguments);
            try {
                if($cliOutput !== null){
                    $cliOutput->writeln("<comment>--> Applying Shutdown Hook: ". get_class($callback[0]) ."::".$callback[1]."</comment>");
                }
                call_user_func_array($callback, $arguments); // [*] sink
            } catch (PydioException $e) {
                Logger::error(__CLASS__, __FUNCTION__, array("context" => "Applying hook " . get_class($callback[0]) . "::" . $callback[1], "message" => $e->getMessage()));
            }
            $index++;
            if($index > 100000) {
                Logger::error(__CLASS__, __FUNCTION__, "Breaking ShutdownScheduler loop, seems too big (100000)");
                break;
            }
        }
    }
}