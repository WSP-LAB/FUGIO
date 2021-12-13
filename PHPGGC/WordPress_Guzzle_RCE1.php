/*
    1. \GuzzleHttp\Cookie\SetCookie::__toString // foreach
    2. Requests_Utility_FilteredIterator::current
    3. call_user_func
*/

// wp-content/themes/twentytwenty/Guzzle-6.0.0/vendor/guzzlehttp/guzzle/src/Cookie/SetCookie.php
namespace GuzzleHttp\Cookie;
class SetCookie {
    public function __toString() {
        $str = $this->data['Name'] . '=' . $this->data['Value'] . '; ';
        foreach ($this->data as $k => $v) { // [*] next (call current)
            if ($k != 'Name' && $k != 'Value' && $v !== null && $v !== false) {
                if ($k == 'Expires') {
                    $str .= 'Expires=' . gmdate('D, d M Y H:i:s \G\M\T', $v) . '; ';
                } else {
                    $str .= ($v === true ? $k : "{$k}={$v}") . '; ';
                }
            }
        }

        return rtrim($str, '; ');
    }
}

// wp-includes/Requests/Utility/FilteredIterator.php
class Requests_Utility_FilteredIterator extends ArrayIterator {
    public function current() {
        $value = parent::current();
        $value = call_user_func($this->callback, $value); // [*] sink
        return $value;
    }
}