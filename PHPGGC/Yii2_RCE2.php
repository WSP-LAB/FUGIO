/*
    1. \yii\db\BatchQueryResult::__destruct
    2. \yii\db\BatchQueryResult::reset
    3. \yii\web\DbSession::close
    4. \yii\web\DbSession(\yii\web\MultiFieldSession)::composeFields // call_user_func
    5. \yii\caching\ExpressionDependency::evaluateDependency
    6. eval
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

// vendor/yiisoft/yii2/web/DbSession.php
namespace yii\web;
class DbSession extends MultiFieldSession {
    public function close() {
        if ($this->getIsActive()) {
            // prepare writeCallback fields before session closes
            $this->fields = $this->composeFields(); // [*] next
            YII_DEBUG ? session_write_close() : @session_write_close();
        }
    }
}

// vendor/yiisoft/yii2/web/MultiFieldSession.php
namespace yii\web;
abstract class MultiFieldSession extends Session {
    protected function composeFields($id = null, $data = null) {
        $fields = $this->writeCallback ? call_user_func($this->writeCallback, $this) : []; // [*] next
                                                                                           // [!] need to set $this->writeCallback to call \yii\caching\ExpressionDependency::evaluateDependency
        if ($id !== null) {
            $fields['id'] = $id;
        }
        if ($data !== null) {
            $fields['data'] = $data;
        }
        return $fields;
    }
}

// vendor/yiisoft/yii2/caching/Dependency.php
namespace yii\caching;
abstract class Dependency extends \yii\base\BaseObject {
    public function evaluateDependency($cache) {
        if ($this->reusable) {
            $hash = $this->generateReusableHash();
            if (!array_key_exists($hash, self::$_reusableData)) {
                self::$_reusableData[$hash] = $this->generateDependencyData($cache);
            }
            $this->data = self::$_reusableData[$hash];
        } else {
            $this->data = $this->generateDependencyData($cache); // [*] next
        }
    }
}

// vendor/yiisoft/yii2/caching/ExpressionDependency.php
namespace yii\caching;
class ExpressionDependency extends Dependency {
    protected function generateDependencyData($cache) {
        return eval("return {$this->expression};"); // [*] sink
    }
}