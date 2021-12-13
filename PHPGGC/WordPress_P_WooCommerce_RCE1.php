/*
    1. WC_Log_Handler_File::__destruct // foreach
    2. Requests_Utility_FilteredIterator::current
    3. call_user_func
*/

// wp-content/plugins/woocommerce-3.4.0/includes/log-handlers/class-wc-log-handler-file.php
class WC_Log_Handler_File extends WC_Log_Handler {
    public function __destruct() {
        foreach ( $this->handles as $handle ) { // [*] next (call current)
            if ( is_resource( $handle ) ) {
                fclose( $handle ); // @codingStandardsIgnoreLine.
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