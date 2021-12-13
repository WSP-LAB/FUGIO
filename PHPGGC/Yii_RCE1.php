/*
    1. CDbCriteria::__wakeup // foreach
    2. CMapIterator::current // $this->_d[$this->_key]
    3. CFileCache::offsetGet(CCache::offsetGet)
    4. CFileCache::get(CCache::get)
    5. call_user_func
*/

// vendor/yiisoft/yii/framework/db/schema/CDbCriteria.php
class CDbCriteria extends CComponent {
    public function __wakeup() {
        $map=array();
        $params=array();
        foreach($this->params as $name=>$value) { // [*] next (call current)
            if(strpos($name,self::PARAM_PREFIX)===0) {
                $newName=self::PARAM_PREFIX.self::$paramCount++;
                $map[$name]=$newName;
            }
            else {
                $newName=$name;
            }
            $params[$newName]=$value;
        }
        ...
    }
}

// vendor/yiisoft/yii/framework/collections/CMapIterator.php
class CMapIterator implements Iterator {
    public function current() {
        return $this->_d[$this->_key]; // [*] next (call offsetGet)
    }
}

// vendor/yiisoft/yii/framework/caching/CFileCache.php
class CFileCache extends CCache {
}

// vendor/yiisoft/yii/framework/caching/CCache.php
abstract class CCache extends CApplicationComponent implements ICache, ArrayAccess {
    public function offsetGet($id) {
        return $this->get($id); // [*] next
    }
    
    public function get($id) {
        $value = $this->getValue($this->generateUniqueKey($id));
        if($value===false || $this->serializer===false)
            return $value;
        if($this->serializer===null)
            $value=unserialize($value);
        else
            $value=call_user_func($this->serializer[1], $value); // [*] sink
        if(is_array($value) && (!$value[1] instanceof ICacheDependency || !$value[1]->getHasChanged())) {
            Yii::trace('Serving "'.$id.'" from cache','system.caching.'.get_class($this));
            return $value[0];
        }
        else
            return false;
    }
}