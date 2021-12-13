/*
    1. \GuzzleHttp\Cookie\FileCookieJar::__destruct
    2. \GuzzleHttp\Cookie\FileCookieJar::save // file_put_contents
    3. \GuzzleHttp\Cookie\SetCookie::__toString // foreach
    4. Requests_Utility_FilteredIterator::current
    5. call_user_func
*/

// wp-content/themes/twentytwenty/Guzzle-6.0.0/vendor/guzzlehttp/guzzle/src/Cookie/FileCookieJar.php
namespace GuzzleHttp\Cookie;
class FileCookieJar extends CookieJar {
    public function __destruct() {
        $this->save($this->filename); // [*] next
    }
    
    public function save($filename) {
        $json = [];
        foreach ($this as $cookie) {
            /** @var SetCookie $cookie */
            if ($cookie->getExpires() && !$cookie->getDiscard()) {
                $json[] = $cookie->toArray();
            }
        }

        if (false === file_put_contents($filename, json_encode($json))) { // [*] next (call __toString)
            throw new \RuntimeException("Unable to save file {$filename}");
        }
    }
}

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