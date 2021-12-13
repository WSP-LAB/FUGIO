/*
    1. \yii\db\BatchQueryResult::__destruct
    2. \yii\db\BatchQueryResult::reset
    3. \yii\db\Connection::close // '' . $this->dsn
    4. \yii\db\ColumnSchemaBuilder::__toString
    5. \yii\db\ColumnSchemaBuilder::getTypeCategory // $this->categoryMap[$this->type]
    6. \yii\caching\ArrayCache(\yii\caching\Cache)::offsetGet
    7. \yii\caching\ArrayCache(\yii\caching\Cache)::get
    8. call_user_func
*/

// vendor/yiisoft/yii2/db/BatchQueryResult.php
namespace yii\db;
class BatchQueryResult extends BaseObject implements \Iterator {
    public function __destruct() {
        // make sure cursor is closed
        $this->reset(); // [*] next
    }

    public function reset() {
        if ($this->_dataReader !== null) {
            $this->_dataReader->close(); // [*] next
        }
        $this->_dataReader = null;
        $this->_batch = null;
        $this->_value = null;
        $this->_key = null;
    }
}

// vendor/yiisoft/yii2/db/Connection.php
namespace yii\db;
class Connection extends Component {
    public function close() {
        if ($this->_master) {
            if ($this->pdo === $this->_master->pdo) {
                $this->pdo = null;
            }

            $this->_master->close();
            $this->_master = false;
        }

        if ($this->pdo !== null) {
            Yii::debug('Closing DB connection: ' . $this->dsn, __METHOD__); // [*] next (call __toString)
            $this->pdo = null;
        }

        ...
    }
}

// vendor/yiisoft/yii2/db/ColumnSchemaBuilder.php
namespace yii\db;
class ColumnSchemaBuilder extends BaseObject {
    public function __toString() {
        switch ($this->getTypeCategory()) { // [*] next
            case self::CATEGORY_PK:
                $format = '{type}{check}{comment}{append}';
                break;
            default:
                $format = '{type}{length}{notnull}{unique}{default}{check}{comment}{append}';
        }

        return $this->buildCompleteString($format);
    }
    
    protected function getTypeCategory() {
        return isset($this->categoryMap[$this->type]) ? $this->categoryMap[$this->type] : null; // [*] next
    }
}

// vendor/yiisoft/yii2/caching/ArrayCache.php
namespace yii\caching;
class ArrayCache extends Cache {
}

// vendor/yiisoft/yii2/caching/Cache.php
namespace yii\caching;
abstract class Cache extends Component implements CacheInterface {
    public function offsetGet($key) {
        return $this->get($key);
    }
    
    public function get($key) {
        $key = $this->buildKey($key);
        $value = $this->getValue($key);
        if ($value === false || $this->serializer === false) {
            return $value;
        } elseif ($this->serializer === null) {
            $value = unserialize($value);
        } else {
            $value = call_user_func($this->serializer[1], $value); // [*] sink
        }
        if (is_array($value) && !($value[1] instanceof Dependency && $value[1]->isChanged($this))) {
            return $value[0];
        }

        return false;
    }
}

// vendor/yiisoft/yii2/caching/CacheInterface.php
namespace yii\caching; 
interface CacheInterface extends \ArrayAccess {}