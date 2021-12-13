/*
    1. \Zend_Http_Response_Stream::__destruct
    2. unlink
*/

// library/Zend/Http/Response/Stream.php
class Zend_Http_Response_Stream extends Zend_Http_Response {
    public function __destruct() {
        if(is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = null;
        }
        if($this->_cleanup) {
            @unlink($this->stream_name); // [*] sink
        }
    }
}