/*
    1. \Zend_Log::__destruct
    2. \Zend_Log_Writer_Mail::shutdown
    3. \Zend_Layout::render
    4. \Zend_Filter_Inflector::filter ($ruleFilter->filter)
    5. \Zend_Filter_Callback::filter
    6. call_user_func_array
*/

// library/Zend/Log.php
class Zend_Log {
    public function __destruct() {
        /** @var Zend_Log_Writer_Abstract $writer */
        foreach($this->_writers as $writer) {
            $writer->shutdown(); // [*] next
        }
    }
}

// library/Zend/Log/Writer/Mail.php
class Zend_Log_Writer_Mail extends Zend_Log_Writer_Abstract {
    public function shutdown() {
        // If there are events to mail, use them as message body.  Otherwise,
        // there is no mail to be sent.
        if (empty($this->_eventsToMail)) {
            return;
        }

        if ($this->_subjectPrependText !== null) {
            // Tack on the summary of entries per-priority to the subject
            // line and set it on the Zend_Mail object.
            $numEntries = $this->_getFormattedNumEntriesPerPriority();
            $this->_mail->setSubject(
                "{$this->_subjectPrependText} ({$numEntries})");
        }


        // Always provide events to mail as plaintext.
        $this->_mail->setBodyText(implode('', $this->_eventsToMail));

        // If a Zend_Layout instance is being used, set its "events"
        // value to the lines formatted for use with the layout.
        if ($this->_layout) {
            // Set the required "messages" value for the layout.  Here we
            // are assuming that the layout is for use with HTML.
            $this->_layout->events =
                implode('', $this->_layoutEventsToMail);

            // If an exception occurs during rendering, convert it to a notice
            // so we can avoid an exception thrown without a stack frame.
            try {
                $this->_mail->setBodyHtml($this->_layout->render()); // [*] next
            } catch (Exception $e) {
                trigger_error(
                    "exception occurred when rendering layout; " .
                        "unable to set html body for message; " .
                        "message = {$e->getMessage()}; " .
                        "code = {$e->getCode()}; " .
                        "exception class = " . get_class($e),
                    E_USER_NOTICE);
            }
        }
        ...
    }
}

// library/Zend/Layout.php
class Zend_Layout {
    public function render($name = null) {
        if (null === $name) {
            $name = $this->getLayout();
        }

        if ($this->inflectorEnabled() && (null !== ($inflector = $this->getInflector())))
        {
            $name = $this->_inflector->filter(array('script' => $name)); // [*] next
        }

        $view = $this->getView();

        if (null !== ($path = $this->getViewScriptPath())) {
            if (method_exists($view, 'addScriptPath')) {
                $view->addScriptPath($path);
            } else {
                $view->setScriptPath($path);
            }
        } elseif (null !== ($path = $this->getViewBasePath())) {
            $view->addBasePath($path, $this->_viewBasePrefix);
        }

        return $view->render($name);
    }
}

// library/Zend/Filter/Inflector.php
class Zend_Filter_Inflector implements Zend_Filter_Interface {
    public function filter($source) {
        // clean source
        foreach ( (array) $source as $sourceName => $sourceValue) {
            $source[ltrim($sourceName, ':')] = $sourceValue;
        }

        $pregQuotedTargetReplacementIdentifier = preg_quote($this->_targetReplacementIdentifier, '#');
        $processedParts = array();

        foreach ($this->_rules as $ruleName => $ruleValue) {
            if (isset($source[$ruleName])) {
                if (is_string($ruleValue)) {
                    // overriding the set rule
                    $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $source[$ruleName]);
                } elseif (is_array($ruleValue)) {
                    $processedPart = $source[$ruleName];
                    foreach ($ruleValue as $ruleFilter) {
                        $processedPart = $ruleFilter->filter($processedPart); // [*] next
                                                                              // [!] need to set to call \Zend\Filter\FilterChain::filter
                    }
        ...
    }
}

// library/Zend/Filter/Callback.php
class Zend_Filter_Callback implements Zend_Filter_Interface {
    public function filter($value)
    {
        $options = array();

        if ($this->_options !== null) {
            if (!is_array($this->_options)) {
                $options = array($this->_options);
            } else {
                $options = $this->_options;
            }
        }

        array_unshift($options, $value);

        return call_user_func_array($this->_callback, $options); // [*] sink
    }
}