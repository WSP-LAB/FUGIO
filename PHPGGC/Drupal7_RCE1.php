/*
    // Stage 1: Inject arbitrary data into any caching table
    1. SchemaCache::__destruct
    2. SchemaCache::set
    3. cache_set
    4. DrupalDatabaseCache::set
    
    // Stage 2: Use the injected cached data (trigger by making a equest to the URL: BASEURL/?q=system/ajax)
    5. ajax_form_callback
    6. drupal_process_form
    7. form_builder // $process($element, $form_state, $form_state['complete form'])
    8. drupal_process_attached
    9. call_user_func_array
    
    (ref: https://blog.ripstech.com/2019/complex-drupal-pop-chain/)
*/

// includes/bootstrap.inc
abstract class DrupalCacheArray implements ArrayAccess {
    public function __destruct() {
        $data = array();
        foreach ($this->keysToPersist as $offset => $persist) {
            if ($persist) {
                $data[$offset] = $this->storage[$offset];
            }
        }
        if (!empty($data)) {
            $this->set($this->cid, $data, $this->bin); // [*] next
        }
    }
    protected function set($cid, $data, $bin, $lock = TRUE) {
        // Lock cache writes to help avoid stampedes.
        // To implement locking for cache misses, override __construct().
        $lock_name = $cid . ':' . $bin;
        if (!$lock || lock_acquire($lock_name)) {
            if ($cached = cache_get($cid, $bin)) {
                $data = $cached->data + $data;
            }
            cache_set($cid, $data, $bin); // [*] next
            if ($lock) {
                lock_release($lock_name);
            }
        }
    }
}

// includes/cache.inc
function cache_set($cid, $data, $bin = 'cache', $expire = CACHE_PERMANENT) {
    return _cache_get_object($bin)->set($cid, $data, $expire); // [*] next
}

// includes/cache.inc
class DrupalDatabaseCache implements DrupalCacheInterface {
    function set($cid, $data, $expire = CACHE_PERMANENT) {
        $fields = array(
            'serialized' => 0,
            'created' => REQUEST_TIME,
            'expire' => $expire,
        );
        if (!is_string($data)) {
            $fields['data'] = serialize($data);
            $fields['serialized'] = 1;
        }
        else {
            $fields['data'] = $data;
            $fields['serialized'] = 0;
        }
    
        try {
            db_merge($this->bin) // [!] need to set $this->bin to db table name ("cache_xxx")
            ->key(array('cid' => $cid))
            ->fields($fields)
            ->execute(); // [!] now we able to inject arbitrary data into any caching table
        }
        catch (Exception $e) {
            // The database may not be available, so we'll ignore cache_set requests.
        }
    }
}

// includes/ajax.inc
function ajax_form_callback() {
    list($form, $form_state) = ajax_get_form(); // [!] get a cached entry from the db datable
    drupal_process_form($form['#form_id'], $form, $form_state); // [*] next
    
    // We need to return the part of the form (or some other content) that needs
    // to be re-rendered so the browser can update the page with changed content.
    // Since this is the generic menu callback used by many Ajax elements, it is
    // up to the #ajax['callback'] function of the element (may or may not be a
    // button) that triggered the Ajax request to determine what needs to be
    // rendered.
    if (!empty($form_state['triggering_element'])) {
        $callback = $form_state['triggering_element']['#ajax']['callback'];
    }
    if (!empty($callback) && function_exists($callback)) {
        return $callback($form, $form_state);
    }
}

// includes/form.inc
function drupal_process_form($form_id, &$form, &$form_state) {
    $form_state['values'] = array();
    
    // With $_GET, these forms are always submitted if requested.
    if ($form_state['method'] == 'get' && !empty($form_state['always_process'])) {
        if (!isset($form_state['input']['form_build_id'])) {
            $form_state['input']['form_build_id'] = $form['#build_id'];
        }
        if (!isset($form_state['input']['form_id'])) {
            $form_state['input']['form_id'] = $form_id;
        }
        if (!isset($form_state['input']['form_token']) && isset($form['#token'])) {
            $form_state['input']['form_token'] = drupal_get_token($form['#token']);
        }
    }
    
    // form_builder() finishes building the form by calling element #process
    // functions and mapping user input, if any, to #value properties, and also
    // storing the values in $form_state['values']. We need to retain the
    // unprocessed $form in case it needs to be cached.
    $unprocessed_form = $form;
    $form = form_builder($form_id, $form, $form_state); // [*] next
    
    ...
}

function form_builder($form_id, &$element, &$form_state) {
    // Initialize as unprocessed.
    $element['#processed'] = FALSE;
    
    // Use element defaults.
    if (isset($element['#type']) && empty($element['#defaults_loaded']) && ($info = element_info($element['#type']))) {
        // Overlay $info onto $element, retaining preexisting keys in $element.
        $element += $info;
        $element['#defaults_loaded'] = TRUE;
    }
    // Assign basic defaults common for all form elements.
    $element += array(
        '#required' => FALSE,
        '#attributes' => array(),
        '#title_display' => 'before',
    );
    
    // Special handling if we're on the top level form element.
    if (isset($element['#type']) && $element['#type'] == 'form') {
        if (!empty($element['#https']) && variable_get('https', FALSE) &&
            !url_is_external($element['#action'])) {
            global $base_root;
            
            // Not an external URL so ensure that it is secure.
            $element['#action'] = str_replace('http://', 'https://', $base_root) . $element['#action'];
        }
    
        // Store a reference to the complete form in $form_state prior to building
        // the form. This allows advanced #process and #after_build callbacks to
        // perform changes elsewhere in the form.
        $form_state['complete form'] = &$element;
        
        // Set a flag if we have a correct form submission. This is always TRUE for
        // programmed forms coming from drupal_form_submit(), or if the form_id coming
        // from the POST data is set and matches the current form_id.
        if ($form_state['programmed'] || (!empty($form_state['input']) && (isset($form_state['input']['form_id']) && ($form_state['input']['form_id'] == $form_id)))) {
            $form_state['process_input'] = TRUE;
        }
        else {
            $form_state['process_input'] = FALSE;
        }
    
        // All form elements should have an #array_parents property.
        $element['#array_parents'] = array();
        }
        
        if (!isset($element['#id'])) {
            $element['#id'] = drupal_html_id('edit-' . implode('-', $element['#parents']));
        }
        // Handle input elements.
        if (!empty($element['#input'])) {
            _form_builder_handle_input_element($form_id, $element, $form_state);
        }
        // Allow for elements to expand to multiple elements, e.g., radios,
        // checkboxes and files.
        if (isset($element['#process']) && !$element['#processed']) {
            foreach ($element['#process'] as $process) {
                $element = $process($element, $form_state, $form_state['complete form']); // [*] next
                                                                                          // [!] need to set to call drupal_process_attached
            }
            $element['#processed'] = TRUE;
        }
    ... 
}

// includes/common.inc
function drupal_process_attached($elements, $group = JS_DEFAULT, $dependency_check = FALSE, $every_page = NULL) {
    // Add defaults to the special attached structures that should be processed differently.
    $elements['#attached'] += array(
        'library' => array(),
        'js' => array(),
        'css' => array(),
    );
    
    // Add the libraries first.
    $success = TRUE;
    foreach ($elements['#attached']['library'] as $library) {
        if (drupal_add_library($library[0], $library[1], $every_page) === FALSE) {
            $success = FALSE;
            // Exit if the dependency is missing.
            if ($dependency_check) {
                return $success;
            }
        }
    }
    unset($elements['#attached']['library']);
    
    // Add both the JavaScript and the CSS.
    // The parameters for drupal_add_js() and drupal_add_css() require special
    // handling.
    foreach (array('js', 'css') as $type) {
        foreach ($elements['#attached'][$type] as $data => $options) {
            // If the value is not an array, it's a filename and passed as first
            // (and only) argument.
            if (!is_array($options)) {
                $data = $options;
                $options = NULL;
            }
            // In some cases, the first parameter ($data) is an array. Arrays can't be
            // passed as keys in PHP, so we have to get $data from the value array.
            if (is_numeric($data)) {
                $data = $options['data'];
                unset($options['data']);
            }
            // Apply the default group if it isn't explicitly given.
            if (!isset($options['group'])) {
                $options['group'] = $group;
            }
            // Set the every_page flag if one was passed.
            if (isset($every_page)) {
                $options['every_page'] = $every_page;
            }
            call_user_func('drupal_add_' . $type, $data, $options);
        }
        unset($elements['#attached'][$type]);
    }
    
    // Add additional types of attachments specified in the render() structure.
    // Libraries, JavaScript and CSS have been added already, as they require
    // special handling.
    foreach ($elements['#attached'] as $callback => $options) {
        if (function_exists($callback)) {
            foreach ($elements['#attached'][$callback] as $args) {
                call_user_func_array($callback, $args); // [*] sink
            }
        }
    }
    
    return $success;
}

// ======== REF ======== 
// includes/cache.inc
function _cache_get_object($bin) {
    // We do not use drupal_static() here because we do not want to change the
    // storage of a cache bin mid-request.
    static $cache_objects;
    if (!isset($cache_objects[$bin])) {
        $class = variable_get('cache_class_' . $bin);
        if (!isset($class)) {
            $class = variable_get('cache_default_class', 'DrupalDatabaseCache');
        }
        $cache_objects[$bin] = new $class($bin);
    }
    return $cache_objects[$bin];
}

// includes/ajax.inc
function ajax_get_form() {
    $form_state = form_state_defaults();
    
    $form_build_id = $_POST['form_build_id'];
    
    // Get the form from the cache.
    $form = form_get_cache($form_build_id, $form_state);
    if (!$form) {
        // If $form cannot be loaded from the cache, the form_build_id in $_POST
        // must be invalid, which means that someone performed a POST request onto
        // system/ajax without actually viewing the concerned form in the browser.
        // This is likely a hacking attempt as it never happens under normal
        // circumstances, so we just do nothing.
        watchdog('ajax', 'Invalid form POST data.', array(), WATCHDOG_WARNING);
        drupal_exit();
    }
    
    // Since some of the submit handlers are run, redirects need to be disabled.
    $form_state['no_redirect'] = TRUE;
    
    // When a form is rebuilt after Ajax processing, its #build_id and #action
    // should not change.
    // @see drupal_rebuild_form()
    $form_state['rebuild_info']['copy']['#build_id'] = TRUE;
    $form_state['rebuild_info']['copy']['#action'] = TRUE;
    
    // The form needs to be processed; prepare for that by setting a few internal
    // variables.
    $form_state['input'] = $_POST;
    $form_id = $form['#form_id'];
    
    return array($form, $form_state, $form_id, $form_build_id);
}

// includes/form.inc
function form_get_cache($form_build_id, &$form_state) {
    if ($cached = cache_get('form_' . $form_build_id, 'cache_form')) {
        $form = $cached->data;
        
        global $user;
        if ((isset($form['#cache_token']) && drupal_valid_token($form['#cache_token'])) || (!isset($form['#cache_token']) && !$user->uid)) {
            if ($cached = cache_get('form_state_' . $form_build_id, 'cache_form')) {
                // Re-populate $form_state for subsequent rebuilds.
                $form_state = $cached->data + $form_state;
                
                // If the original form is contained in include files, load the files.
                // @see form_load_include()
                $form_state['build_info'] += array('files' => array());
                foreach ($form_state['build_info']['files'] as $file) {
                    if (is_array($file)) {
                        $file += array('type' => 'inc', 'name' => $file['module']);
                        module_load_include($file['type'], $file['module'], $file['name']);
                    }
                    elseif (file_exists($file)) {
                        require_once DRUPAL_ROOT . '/' . $file;
                    }
                }
            }
        return $form;
        }
    }
}

// includes/cache.inc
function cache_get($cid, $bin = 'cache') {
    return _cache_get_object($bin)->get($cid);
}

// includes/cache.inc
class DrupalDatabaseCache implements DrupalCacheInterface {
    function get($cid) {
        $cids = array($cid);
        $cache = $this->getMultiple($cids);
        return reset($cache);
    }
    
    function getMultiple(&$cids) {
        try {
            // Garbage collection necessary when enforcing a minimum cache lifetime.
            $this->garbageCollection($this->bin);
            
            // When serving cached pages, the overhead of using db_select() was found
            // to add around 30% overhead to the request. Since $this->bin is a
            // variable, this means the call to db_query() here uses a concatenated
            // string. This is highly discouraged under any other circumstances, and
            // is used here only due to the performance overhead we would incur
            // otherwise. When serving an uncached page, the overhead of using
            // db_select() is a much smaller proportion of the request.
            $result = db_query('SELECT cid, data, created, expire, serialized FROM {' . db_escape_table($this->bin) . '} WHERE cid IN (:cids)', array(':cids' => $cids)); // 
            $cache = array();
            foreach ($result as $item) {
                $item = $this->prepareItem($item);
                if ($item) {
                    $cache[$item->cid] = $item;
                }
            }
            $cids = array_diff($cids, array_keys($cache));
            return $cache;
        }
        catch (Exception $e) {
            // If the database is never going to be available, cache requests should
            // return FALSE in order to allow exception handling to occur.
            return array();
        }
    }
}