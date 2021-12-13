/*
    1. Smarty_Internal_Template::__destruct
    2. Smarty_Internal_CacheResource_File::releaseLock
    3. unlink
*/

// libs/sysplugins/smarty_internal_template.php
class Smarty_Internal_Template extends Smarty_Internal_TemplateBase {
    public function __destruct() {
        if ($this->smarty->cache_locking && isset($this->cached) && $this->cached->is_locked) {
            $this->cached->handler->releaseLock($this->smarty, $this->cached); // [*] next
        }
    }
}

// libs/sysplugins/smarty_internal_cacheresource_file.php
class Smarty_Internal_CacheResource_File extends Smarty_CacheResource {
    public function releaseLock(Smarty $smarty, Smarty_Template_Cached $cached) {
        $cached->is_locked = false;
        @unlink($cached->lock_id); // [*] sink
    }
}

// ======== REF ======== 
// libs/Smarty.class.php
class Smarty extends Smarty_Internal_TemplateBase {
    public $cache_locking;
}

// libs/sysplugins/smarty_template_cached.php
class Smarty_Template_Cached extends Smarty_Template_Resource_Base {
    public $is_locked;
    public $handler;
}