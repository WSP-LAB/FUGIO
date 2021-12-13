/*
    1. PHPExcel_CachedObjectStorage_DiscISAM::__destruct
    2. unlink
*/

// wp-content/themes/twentytwenty/PHPExcel-1.8.2/Classes/PHPExcel/CachedObjectStorage/DiscISAM.php
class PHPExcel_CachedObjectStorage_DiscISAM extends PHPExcel_CachedObjectStorage_CacheBase implements PHPExcel_CachedObjectStorage_ICache {
    public function __destruct() {
        if (!is_null($this->fileHandle)) {
            fclose($this->fileHandle);
            unlink($this->fileName); // [*] sink
        }
        $this->fileHandle = null;
    }
}