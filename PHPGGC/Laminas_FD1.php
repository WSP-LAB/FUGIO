/*
    1. \Laminas\Http\Response\Stream::__destruct
    2. unlink
*/

// vendor/laminas/laminas-http/src/Response/Stream.php
class Stream extends Response {
    public function __destruct() {
        if (is_resource($this->stream)) {
            $this->stream = null; //Could be listened by others
        }
        if ($this->cleanup) {
            ErrorHandler::start(E_WARNING);
            unlink($this->streamName); // [*] sink
            ErrorHandler::stop();
        }   
}