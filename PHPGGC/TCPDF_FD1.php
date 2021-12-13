/*
    1. TCPDF::__destruct
    2. TCPDF::_destroy
    3. unlink
*/

// tcpdf.php
class TCPDF {
    public function __destruct() {
        // cleanup
        $this->_destroy(true); // [*] next
    }
    
    public function _destroy($destroyall=false, $preserve_objcopy=false) {
        // restore internal encoding
        if (isset($this->internal_encoding) AND !empty($this->internal_encoding)) {
            mb_internal_encoding($this->internal_encoding);
        }
        if (isset(self::$cleaned_ids[$this->file_id])) {
            $destroyall = false;
        }
        if ($destroyall AND !$preserve_objcopy) {
            self::$cleaned_ids[$this->file_id] = true;
            // remove all temporary files
            if ($handle = opendir(K_PATH_CACHE)) {
                while ( false !== ( $file_name = readdir( $handle ) ) ) {
                    if (strpos($file_name, '__tcpdf_'.$this->file_id.'_') === 0) {
                        unlink(K_PATH_CACHE.$file_name); // [*] sink
                    }
                }
                closedir($handle);
            }
            if (isset($this->imagekeys)) {
                foreach($this->imagekeys as $file) {
                    unlink($file); // [*] sink
                }
            }
        }
        ...
    }
}