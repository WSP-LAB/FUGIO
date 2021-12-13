/*
    1. \GuzzleHttp\Psr7\FnStream::__destruct
    2. call_user_func
*/

// wp-content/themes/twentytwenty/Guzzle-6.0.0/vendor/guzzlehttp/psr7/src/FnStream.php
namespace GuzzleHttp\Psr7;
class FnStream implements StreamInterface {
    public function __destruct() {
        if (isset($this->_fn_close)) {
            call_user_func($this->_fn_close); // [*] sink
        }
    }
}