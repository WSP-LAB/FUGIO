/*
    1. Archive_Tar::__destruct
    2. drupal_unlink
    3. unlink
*/

// modules/system/system.tar.inc
class Archive_Tar {
    function __destruct() {
        $this->_close();
        // ----- Look for a local copy to delete
        if ($this->_temp_tarname != '')
            @drupal_unlink($this->_temp_tarname); // [*] next
        // $this->_PEAR();
    }
}

// includes/file.inc
function drupal_unlink($uri, $context = NULL) {
    $scheme = file_uri_scheme($uri);
    if ((!$scheme || !file_stream_wrapper_valid_scheme($scheme)) && (substr(PHP_OS, 0, 3) == 'WIN')) {
        chmod($uri, 0600);
    }
    if ($context) {
        return unlink($uri, $context);
    }
    else {
        return unlink($uri); // [*] sink
    }
}
