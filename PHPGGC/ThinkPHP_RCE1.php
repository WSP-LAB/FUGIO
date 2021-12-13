/*
    1. \think\process\pipes\Windows::__destruct
    2. \think\process\pipes\Windows::removeFiles (file_exists)
    3. \think\model\Pivot(\think\model\concern\Conversion)::__toString
    4. \think\model\Pivot(\think\model\concern\Conversion)::toJson
    5. \think\model\Pivot(\think\model\concern\Conversion)::toArray
    6. \think\model\Pivot(\think\model\concern\Attribute)::getAttr
    7. \think\model\Pivot(\think\model\concern\Attribute)::getData
    8. $this->data[$name]
*/

// vendor/topthink/framework/library/think/process/pipes/Windows.php
namespace think\process\pipes;
class Windows extends Pipes {
    public function __destruct() {
        $this->close();
        $this->removeFiles(); // [*] next
    }
    
    private function removeFiles() {
        foreach ($this->files as $filename) {
            if (file_exists($filename)) {  // [*] next (call __toString)
                                           // [!] need to set $filename to \think\model\Pivot
                @unlink($filename);
            }
        }
        $this->files = [];
    }
}

// vendor/topthink/framework/library/think/model/Pivot.php
namespace think\model;
class Pivot extends Model {
}

// vendor/topthink/framework/library/think/Model.php
namespace think;
abstract class Model implements \JsonSerializable, \ArrayAccess {
    use model\concern\Attribute;
    use model\concern\Conversion;
}

// vendor/topthink/framework/library/think/model/concern/Conversion.php
namespace think\model\concern;
trait Conversion {
    public function __toString() {
        return $this->toJson(); // [*] next
    }
    
    public function toJson($options = JSON_UNESCAPED_UNICODE) {
        return json_encode($this->toArray(), $options); // [*] next
    }
    
    public function toArray() {
        ...
        
        if (!empty($this->append)) {
            foreach ($this->append as $key => $name) {
                if (is_array($name)) {
                    $relation = $this->getRelation($key);

                    if (!$relation) {
                        $relation = $this->getAttr($key); // [*] next
                        if ($relation) {
                            $relation->visible($name);
                        }
                    }

                    $item[$key] = $relation ? $relation->append($name)->toArray() : [];
                } elseif (strpos($name, '.')) {
                    list($key, $attr) = explode('.', $name);
                    $relation = $this->getRelation($key);

                    if (!$relation) {
                        $relation = $this->getAttr($key);
                        if ($relation) {
                            $relation->visible([$attr]);
                        }
                    }

                    $item[$key] = $relation ? $relation->append([$attr])->toArray() : [];
                } else {
                    $item[$name] = $this->getAttr($name, $item);
                }
            }
        }

        return $item;
    }
}


// vendor/topthink/framework/library/think/model/concern/Attribute.php
namespace think\model\concern;
trait Attribute {
    public function getAttr($name, &$item = null) {
        try {
            $notFound = false;
            $value    = $this->getData($name); // [*] next
        } catch (InvalidArgumentException $e) {
            $notFound = true;
            $value    = null;
        }

        ...
    }
    
    public function getData($name = null) {
        if (is_null($name)) {
            return $this->data;
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name]; // [*] sink
        } elseif (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        }
        throw new InvalidArgumentException('property not exists:' . static::class . '->' . $name);
    }
    
    
}