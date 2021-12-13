/*
    1. WC_Logger::__destruct // foreach
    2. Requests_Utility_FilteredIterator::current
    3. call_user_func
*/

// wp-content/plugins/woocommerce-2.6.0/includes/class-wc-logger.php
class WC_Logger {
    public function __destruct() {
        foreach ( $this->_handles as $handle ) { // [*] next (call current)
            if ( is_resource( $handle ) ) {
                fclose( $handle );
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