/*
    1. EVF_Log_Handler_File::__destruct // foreach
    2. Requests_Utility_FilteredIterator::current
    3. call_user_func
*/

// wp-content/plugins/everest-forms-1.6.6/includes/log-handlers/class-evf-log-handler-file.php
class EVF_Log_Handler_File extends EVF_Log_Handler {
    public function __destruct() {
        foreach ( $this->handles as $handle ) { // [*] next (call current)
            if ( is_resource( $handle ) ) {
                fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
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