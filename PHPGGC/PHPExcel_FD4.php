/*
    1. PHPExcel_Shared_XMLWriter::__destruct
    2. unlink
*/

// wp-content/themes/twentytwenty/PHPExcel-1.8.1/Classes/PHPExcel/Shared/XMLWriter.php
class PHPExcel_Shared_XMLWriter extends XMLWriter {
    public function __destruct() {
        // Unlink temporary files
        if ($this->_tempFileName != '') {
            @unlink($this->_tempFileName); // [*] sink
        }
    }
}