/*
    1. \Slim\Http\Response::__toString
    2. \Slim\Http\Response(\Slim\Http\Message)::getHeaders // $this->headers->all()
    3. \Slim\App::__call // call_user_func_array
    4. \Slim\App::__call // $this->container->has($method)
    5. \Slim\App::__call
    6. call_user_func_array
*/

// vendor/slim/slim/Slim/Http/Response.php
namespace Slim\Http;
class Response extends Message implements ResponseInterface {
    public function __toString() {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= Response::EOL;
        foreach ($this->getHeaders() as $name => $values) { // [*] next
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . Response::EOL;
        }
        $output .= Response::EOL;
        $output .= (string)$this->getBody();

        return $output;
    }
}

// vendor/slim/slim/Slim/Http/Message.php
namespace Slim\Http;
abstract class Message implements MessageInterface {
    public function getHeaders() {
        return $this->headers->all(); // [*] next
                                      // [!] need to set $this->headers to \Slim\App
    }
}

// vendor/slim/slim/Slim/App.php
namespace Slim;
class App {
    public function __call($method, $args) {              // [!] $method: 'all', $args: 
        if ($this->container->has($method)) {
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args); // [*] next
                                                          // [!] need to set arguments to call \Slim\App::__call
            }
        }

        throw new \BadMethodCallException("Method $method is not a valid method");
    }
}

// vendor/slim/slim/Slim/App.php
namespace Slim;
class App {
    public function __call($method, $args) {              // [!] $method: {parameter}, $args: 
        if ($this->container->has($method)) {             // [*] next
                                                          // [!] need to set $this->container to \Slim\App
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }
        }

        throw new \BadMethodCallException("Method $method is not a valid method");
    }
}

// vendor/slim/slim/Slim/App.php
namespace Slim;
class App {
    public function __call($method, $args) {              // [!] $method: has, $args: {parameter}
        if ($this->container->has($method)) {
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args); // [*] sink
            }
        }

        throw new \BadMethodCallException("Method $method is not a valid method");
    }
}

// ======== REF ======== 
// vendor/slim/slim/Slim/Container.php
namespace Slim;
use Pimple\Container as PimpleContainer;
class Container extends PimpleContainer implements ContainerInterface
    public function has($id) {
        return $this->offsetExists($id);
    }
    
    public function get($id) {
        if (!$this->offsetExists($id)) {
            throw new ContainerValueNotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }
        try {
            return $this->offsetGet($id);
        } catch (\InvalidArgumentException $exception) {
            if ($this->exceptionThrownByContainer($exception)) {
                throw new SlimContainerException(
                    sprintf('Container error while retrieving "%s"', $id),
                    null,
                    $exception
                );
            } else {
                throw $exception;
            }
        }
    }
}

// vendor/pimple/pimple/src/Pimple/Container.php
namespace Pimple;
class Container implements \ArrayAccess {
    public function offsetExists($id) {
        return isset($this->keys[$id]);
    }
    
    public function offsetGet($id) {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (
            isset($this->raw[$id])
            || !\is_object($this->values[$id])
            || isset($this->protected[$this->values[$id]])
            || !\method_exists($this->values[$id], '__invoke')
        ) {
            return $this->values[$id];
        }

        if (isset($this->factories[$this->values[$id]])) {
            return $this->values[$id]($this);
        }

        $raw = $this->values[$id];
        $val = $this->values[$id] = $raw($this);
        $this->raw[$id] = $raw;

        $this->frozen[$id] = true;

        return $val;
    }
}
