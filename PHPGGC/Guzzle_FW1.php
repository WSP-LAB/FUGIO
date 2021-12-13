/*
    1. \GuzzleHttp\Cookie\FileCookieJar::__destruct
    2. \GuzzleHttp\Cookie\FileCookieJar::save
    3. file_put_contents
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

        if (false === file_put_contents($filename, json_encode($json))) { // [!] sink
            throw new \RuntimeException("Unable to save file {$filename}");
        }
    }
}

// ======== REF ======== 
// wp-content/themes/twentytwenty/Guzzle-6.0.0/vendor/guzzlehttp/guzzle/src/Cookie/SetCookie.php
namespace GuzzleHttp\Cookie;
class SetCookie {
    public function getExpires() {
        return $this->data['Expires'];
    }
    
    public function getDiscard() {
        return $this->data['Discard'];
    }
    
    public function toArray() {
        return $this->data;
    }
}