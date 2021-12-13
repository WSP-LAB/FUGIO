/*
    1. PHPExcel_Shared_XMLWriter::__destruct // unlink
    2. PHPExcel_RichText::__toString
    3. PHPExcel_RichText::getPlainText // foreach
    4. Requests_Utility_FilteredIterator::current
    5. call_user_func
*/

// wp-content/themes/twentytwenty/PHPExcel-1.8.2/Classes/PHPExcel/Shared/XMLWriter.php
class PHPExcel_Shared_XMLWriter extends XMLWriter {
    public function __destruct() {
        // Unlink temporary files
        if ($this->tempFileName != '') {
            @unlink($this->tempFileName); // [*] next (call __toString)
        }
    }
}

// wp-content/themes/twentytwenty/PHPExcel-1.8.2/Classes/PHPExcel/RichText.php
class PHPExcel_RichText implements PHPExcel_IComparable {
    public function __toString() {
        return $this->getPlainText(); // [*] next
    }
    
    public function getPlainText() {
        // Return value
        $returnValue = '';

        // Loop through all PHPExcel_RichText_ITextElement
        foreach ($this->richTextElements as $text) { // [*] next (call current)
            $returnValue .= $text->getText();
        }

        // Return
        return $returnValue;
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
