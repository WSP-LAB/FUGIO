/*
    1. \Zend_Form_Element::__toString
    2. \Zend_Form_Element::render
    3. \Zend_Form_Decorator_Form::render ($view->$helper)
    4. \Zend_Cache_Frontend_Function::call
    5. user_func_array
*/

// library/Zend/Form/Element.php
class Zend_Form_Element implements Zend_Validate_Interface {
    public function __toString() {
        try {
            $return = $this->render(); // [*] next
            return $return;
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }
    
    public function render(Zend_View_Interface $view = null) {
        if ($this->_isPartialRendering) {
            return '';
        }

        if (null !== $view) {
            $this->setView($view);
        }

        $content = '';
        foreach ($this->getDecorators() as $decorator) {
            $decorator->setElement($this);
            $content = $decorator->render($content); // [*] next
        }
        return $content;
    }
}

// library/Zend/Form/Decorator/Form.php
class Zend_Form_Decorator_Form extends Zend_Form_Decorator_Abstract {
    public function render($content) {
        $form    = $this->getElement();
        $view    = $form->getView();
        if (null === $view) {
            return $content;
        }

        $helper        = $this->getHelper();
        $attribs       = $this->getOptions();
        $name          = $form->getFullyQualifiedName();
        $attribs['id'] = $form->getId();
        return $view->$helper($name, $attribs, $content); // [*] next
                                                          // [!] need to set $view->$helper to call Zend_Cache_Frontend_Function::call
    }
}

// library/Zend/Cache/Frontend/Function.php
class Zend_Cache_Frontend_Function extends Zend_Cache_Core {
    public function call($callback, array $parameters = array(), $tags = array(), $specificLifetime = false, $priority = 8) {
        if (!is_callable($callback, true, $name)) {
            Zend_Cache::throwException('Invalid callback');
        }

        $cacheBool1 = $this->_specificOptions['cache_by_default'];
        $cacheBool2 = in_array($name, $this->_specificOptions['cached_functions']);
        $cacheBool3 = in_array($name, $this->_specificOptions['non_cached_functions']);
        $cache = (($cacheBool1 || $cacheBool2) && (!$cacheBool3));
        if (!$cache) {
            // Caching of this callback is disabled
            return call_user_func_array($callback, $parameters); // [*] sink
        }

        $id = $this->_makeId($callback, $parameters);
        if ( ($rs = $this->load($id)) && isset($rs[0], $rs[1])) {
            // A cache is available
            $output = $rs[0];
            $return = $rs[1];
        } else {
            // A cache is not available (or not valid for this frontend)
            ob_start();
            ob_implicit_flush(false);
            $return = call_user_func_array($callback, $parameters); // [*] sink
            $output = ob_get_clean();
            $data = array($output, $return);
            $this->save($data, $id, $tags, $specificLifetime, $priority);
        }

        echo $output;
        return $return;
    }
}