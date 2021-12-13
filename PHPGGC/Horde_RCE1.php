/*
    1. Horde_Kolab_Server_Decorator_Clean::__destruct
    2. Horde_Kolab_Server_Decorator_Clean::cleanup
    3. Horde_Kolab_Server_Decorator_Clean::delete
    4. Horde_Prefs_Identity::delete
    5. Horde_Prefs_Identity::save
    6. Horde_Prefs::setValue // call_user_func
    7. Horde_Config::readXMLConfig
    8. eval
*/

// Kolab/Server/Decorator/Clean.php
class Horde_Kolab_Server_Decorator_Clean implements Horde_Kolab_Server_Interface {
    public function __destruct() {
        try {
            $this->cleanup(); // [*] next
        } catch (Horde_Kolab_Server_Exception $e) {
        }
    }
    
    public function cleanup() {
        foreach ($this->_added as $guid) {
            $this->delete($guid); // [*] next
        }
    }
    
    public function delete($guid) {
        $this->_server->delete($guid); // [*] next
        if (in_array($guid, $this->_added)) {
            $this->_added = array_diff($this->_added, array($guid));
        }
    }
}

// Prefs/Identity.php
class Horde_Prefs_Identity implements ArrayAccess, Countable, IteratorAggregate {
    public function delete($identity) {
        $deleted = array_splice($this->_identities, $identity, 1);

        if (!empty($deleted)) {
            foreach (array_keys($this->_identities) as $id) {
                if ($this->setDefault($id)) {
                    break;
                }
            }
            $this->save(); // [*] next
        }

        return reset($deleted);
    }
    
    public function save() {
        $this->_prefs->setValue($this->_prefnames['identities'], serialize($this->_identities)); // [*] next
        $this->_prefs->setValue($this->_prefnames['default_identity'], $this->_default);
    }
}

// Prefs.php
class Horde_Prefs implements ArrayAccess {
    public function setValue($pref, $val, array $opts = array()) {
        /* Exit early if preference doesn't exist or is locked. */
        if (!($scope = $this->_getScope($pref)) ||
            (empty($opts['force']) &&
             $this->_scopes[$scope]->isLocked($pref))) {
            return false;
        }

        // Check to see if the value exceeds the allowable storage limit.
        if ($this->_opts['sizecallback'] &&
            call_user_func($this->_opts['sizecallback'], $pref, strlen($val))) { // [*] next
                                                                                 // [!] need to set $this->_opts['sizecallback'] to call Horde_Config::readXMLConfig)
            return false;
        }

        $this->_scopes[$scope]->set($pref, $val);
        if (!empty($opts['nosave'])) {
            $this->_scopes[$scope]->setDirty($pref, false);
        }

        foreach ($this->_storage as $storage) {
            $storage->onChange($scope, $pref);
        }

        if ($this->_opts['logger']) {
            $this->_opts['logger']->log(__CLASS__ . ': Storing preference value (' . $pref . ')', 'DEBUG');
        }

        return true;
    }
}

// Config.php
class Horde_Config {
    public function readXMLConfig($custom_conf = null) {
        if (!is_null($this->_xmlConfigTree) && !$custom_conf) {
            return $this->_xmlConfigTree;
        }

        $path = $GLOBALS['registry']->get('fileroot', $this->_app) . '/config';

        if ($custom_conf) {
            $this->_currentConfig = $custom_conf;
        } else {
            /* Fetch the current conf.php contents. */
            @eval($this->getPHPConfig()); // [*] sink
            if (isset($conf)) {
                $this->_currentConfig = $conf;
            }
        }

        /* Load the DOM object. */
        $dom = new DOMDocument();
        $dom->loadXML(file_get_contents($path . '/conf.xml'));

        /* Create config file hash. */
        $this->_configHash = hash_file('sha1', $path . '/conf.xml');
        
        /* Check if there is a CVS/Git version tag and store it. */
        /* TODO: Remove for Horde 6. */
        $node = $dom->firstChild;
        while (!empty($node)) {
            if (($node->nodeType == XML_COMMENT_NODE) &&
                ($vers_tag = $this->getVersion($node->nodeValue))) {
                $this->_versionTag = $vers_tag . "\n";
                break;
            }
            $node = $node->nextSibling;
        }

        /* Parse the config file. */
        $this->_xmlConfigTree = array();
        $root = $dom->documentElement;
        if ($root->hasChildNodes()) {
            $this->_parseLevel($this->_xmlConfigTree, $root->childNodes, '');
        }

        /* Parse additional config files. */
        foreach (glob($path . '/conf.d/*.xml') as $additional) {
            $dom = new DOMDocument();
            $dom->load($additional);
            $root = $dom->documentElement;
            if ($root->hasChildNodes()) {
                $tree = array();
                $this->_parseLevel($tree, $root->childNodes, '');
                $this->_xmlConfigTree = array_replace_recursive($this->_xmlConfigTree, $tree);
            }
        }

        return $this->_xmlConfigTree;
    }
    
    public function getPHPConfig() {
        if (!is_null($this->_oldConfig)) {
            return $this->_oldConfig;
        }

        $path = $GLOBALS['registry']->get('fileroot', $this->_app) . '/config';
        if (file_exists($path . '/conf.php')) {
            $this->_oldConfig = file_get_contents($path . '/conf.php');
            if (!empty($this->_oldConfig)) {
                $this->_oldConfig = preg_replace('/<\?php\n?/', '', $this->_oldConfig);
                $pos = strpos($this->_oldConfig, $this->_configBegin);
                if ($pos !== false) {
                    $this->_preConfig = substr($this->_oldConfig, 0, $pos);
                    $this->_oldConfig = substr($this->_oldConfig, $pos);
                }
                $pos = strpos($this->_oldConfig, $this->_configEnd);
                if ($pos !== false) {
                    $this->_postConfig = substr($this->_oldConfig, $pos + strlen($this->_configEnd));
                    $this->_oldConfig = substr($this->_oldConfig, 0, $pos);
                }
            }
        } else {
            $this->_oldConfig = '';
        }

        return $this->_oldConfig;
    }
}