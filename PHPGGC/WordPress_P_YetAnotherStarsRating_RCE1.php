/*
    // Payload has to be in the COOKIE yasr_visitor_vote_cookie in a page containing the shortcode of the plugin allowing visitor ratings
    1. shortcode_visitor_votes_callback // foreach
    2. Requests_Utility_FilteredIterator::current
    3. call_user_func
*/

// wp-content/plugins/yet-another-stars-rating/lib/yasr-shortcode-functions.php
function shortcode_visitor_votes_callback ($atts) {
    // ...
    if (isset($_COOKIE[$yasr_cookiename])) {

        $cookie_data = stripslashes($_COOKIE[$yasr_cookiename]);
        $cookie_data = unserialize($cookie_data);

        foreach ($cookie_data as $value) { // [*] next (call current)
        // ...
}

// wp-includes/Requests/Utility/FilteredIterator.php
class Requests_Utility_FilteredIterator extends ArrayIterator {
    public function current() {
        $value = parent::current();
        $value = call_user_func($this->callback, $value); // [*] sink
        return $value;
    }
}