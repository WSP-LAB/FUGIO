/*
    1. \CodeIgniter\Cache\Handlers\RedisHandler::__destruct
    2. \CodeIgniter\Session\Handlers\MemcachedHandler::close
    3. \CodeIgniter\Model::delete
    4. \CodeIgniter\Model::trigger // $this->{$callback}($data)
    5. \CodeIgniter\Model::validate
    6. \CodeIgniter\Validation\Validation::run
    7. \CodeIgniter\Validation\Validation::processRules
    8. $set->$rule($value, $error)
*/

// vendor/codeigniter4/framework/system/Cache/Handlers/RedisHandler.php
namespace CodeIgniter\Cache\Handlers;
class RedisHandler implements CacheInterface {
    public function __destruct() {
        if ($this->redis) {
            $this->redis->close(); // [*] next
        }
    }
}

// vendor/codeigniter4/framework/system/Session/Handlers/MemcachedHandler.php
namespace CodeIgniter\Session\Handlers;
class MemcachedHandler extends BaseHandler implements \SessionHandlerInterface {
    public function close(): bool {
        if (isset($this->memcached)) {
            isset($this->lockKey) && $this->memcached->delete($this->lockKey); // [*] next

            ...
    }
}

// vendor/codeigniter4/framework/system/Model.php
namespace CodeIgniter;
class Model {
    public function delete($id = null, bool $purge = false) {
        if (! empty($id) && is_numeric($id)) {
            $id = [$id];
        }

        $builder = $this->builder();
        if (! empty($id)) {
            $builder = $builder->whereIn($this->primaryKey, $id);
        }

        $this->trigger('beforeDelete', ['id' => $id, 'purge' => $purge]); // [*] next

        ...
    }
    
    protected function trigger(string $event, array $data) {
        // Ensure it's a valid event
        if (! isset($this->{$event}) || empty($this->{$event})) {
            return $data;
        }

        foreach ($this->{$event} as $callback) {
            if (! method_exists($this, $callback)) {
                throw DataException::forInvalidMethodTriggered($callback);
        }

        $data = $this->{$callback}($data); // [*] next
                                           // [!] need to set $this->{$callback}($data) to call \CodeIgniter\Model::validate
        }

        return $data;
    }
    
    public function validate($data): bool {
        if ($this->skipValidation === true || empty($this->validationRules) || empty($data)) {
            return true;
        }

        // Query Builder works with objects as well as arrays,
        // but validation requires array, so cast away.
        if (is_object($data)) {
            $data = (array) $data;
        }

        $rules = $this->validationRules;

        // ValidationRules can be either a string, which is the group name,
        // or an array of rules.
        if (is_string($rules)) {
            $rules = $this->validation->loadRuleGroup($rules);
        }

        $rules = $this->cleanValidationRules
                ? $this->cleanValidationRules($rules, $data)
                : $rules;

        // If no data existed that needs validation
        // our job is done here.
        if (empty($rules)) {
            return true;
        }

        // Replace any placeholders (i.e. {id}) in the rules with
        // the value found in $data, if exists.
        $rules = $this->fillPlaceholders($rules, $data);

        $this->validation->setRules($rules, $this->validationMessages);
        $valid = $this->validation->run($data, null, $this->DBGroup); // [*] next

        return (bool) $valid;
    }
}

// vendor/codeigniter4/framework/system/Validation/Validation.php
namespace CodeIgniter\Validation;
class Validation implements ValidationInterface {
    public function run(array $data = null, string $group = null, string $db_group = null): bool {
        $data = $data ?? $this->data;

        // i.e. is_unique
        $data['DBGroup'] = $db_group;

        $this->loadRuleSets();

        $this->loadRuleGroup($group);

        // If no rules exist, we return false to ensure
        // the developer didn't forget to set the rules.
        if (empty($this->rules)) {
            return false;
        }

        // Need this for searching arrays in validation.
        helper('array');

        // Run through each rule. If we have any field set for
        // this rule, then we need to run them through!
        foreach ($this->rules as $rField => $rSetup) {
            // Blast $rSetup apart, unless it's already an array.
            $rules = $rSetup['rules'] ?? $rSetup;

            if (is_string($rules)) {
                $rules = $this->splitRules($rules);
            }

            $value = dot_array_search($rField, $data);

            $this->processRules($rField, $rSetup['label'] ?? $rField, $value ?? null, $rules, $data); // [*] next
        }

        return ! empty($this->getErrors()) ? false : true;
    }
    
    protected function processRules(string $field, string $label = null, $value, $rules = null, array $data): bool {
        // If the if_exist rule is defined...
        if (in_array('if_exist', $rules)) {
            // and the current field does not exists in the input data
            // we can return true. Ignoring all other rules to this field.
            if (! array_key_exists($field, $data)) {
                return true;
            }
            // Otherwise remove the if_exist rule and continue the process
            $rules = array_diff($rules, ['if_exist']);
        }

        if (in_array('permit_empty', $rules)) {
            if (! in_array('required', $rules) && (is_array($value) ? empty($value) : (trim($value) === ''))) {
                return true;
            }

            $rules = array_diff($rules, ['permit_empty']);
        }

        foreach ($rules as $rule) {
            $callable = is_callable($rule);
            $passed   = false;

            // Rules can contain parameters: max_length[5]
            $param = false;
            if (! $callable && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
                $rule  = $match[1];
                $param = $match[2];
            }

            // Placeholder for custom errors from the rules.
            $error = null;

            // If it's a callable, call and and get out of here.
            if ($callable) {
                $passed = $param === false ? $rule($value) : $rule($value, $param, $data);
            }
            else {
                $found = false;

                // Check in our rulesets
                foreach ($this->ruleSetInstances as $set) {
                    if (! method_exists($set, $rule)) {
                        continue;
                    }
                    
                    $found = true;

                    $passed = $param === false ? $set->$rule($value, $error) : $set->$rule($value, $param, $data, $error); // [*] sink
                    break;
                }

                ...
    }
}