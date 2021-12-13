/*
    1. \Dompdf\Adapter\CPDF::__destruct // foreach
    2. Requests_Utility_FilteredIterator::current
    3. call_user_func
*/

// wp-content/themes/twentytwenty/dompdf/vendor/dompdf/dompdf/src/Adapter/CPDF.php
namespace Dompdf\Adapter;
class CPDF implements Canvas {
    public function __destruct() {
        foreach ($this->_image_cache as $img) { // [*] next (call current)
            // The file might be already deleted by 3rd party tmp cleaner,
            // the file might not have been created at all
            // (if image outputting commands failed)
            // or because the destructor was called twice accidentally.
            if (!file_exists($img)) {
                continue;
            }

            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[__destruct unlink ' . $img . ']';
            }
            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($img);
            }
        }
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