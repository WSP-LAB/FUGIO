/*
    1. Smarty_Internal_Template::__destruct
    2. SoapClient::__call (There is a type confusion bug triggering RCE - Bug #69085)
*/

// libs/sysplugins/smarty_internal_template.php
class Smarty_Internal_Template extends Smarty_Internal_TemplateBase {
    public function __destruct() {
        if ($this->smarty->cache_locking && isset($this->cached) && $this->cached->is_locked) {
            $this->cached->handler->releaseLock($this->smarty, $this->cached); // [!] need to set $this->cached->handler to SoapClient
        }
    }
}