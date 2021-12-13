/*
    1. \GuzzleHttp\Psr7\FnStream::__destruct // call_user_func
    2. \GuzzleHttp\HandlerStack::resolve
    3. $fn[0]($prev)
*/

// wp-content/themes/twentytwenty/Guzzle-6.0.0/vendor/guzzlehttp/psr7/src/FnStream.php
namespace GuzzleHttp\Psr7;
class FnStream implements StreamInterface {
    public function __destruct() {
        if (isset($this->_fn_close)) {
            call_user_func($this->_fn_close); // [*] next
                                              // [!] need to set $this->_fn_close to call \GuzzleHttp\HandlerStack::resolve
        }
    }
}

// wp-content/themes/twentytwenty/Guzzle-6.0.0/vendor/guzzlehttp/guzzle/src/HandlerStack.php
namespace GuzzleHttp;
class HandlerStack {
    public function resolve() {
        if (!($prev = $this->handler)) {
            throw new \LogicException('No handler has been specified');
        }

        foreach (array_reverse($this->stack) as $fn) {
            $prev = $fn[0]($prev); // [*] sink
        }

        return $prev;
    }
}