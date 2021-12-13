/*
    1. \Zend_Log::__destruct
    2. \Zend_Log_Writer_Mail::shutdown
    3. \Zend_Layout::render
    4. \Zend_Filter_PregReplace::filter
    5. preg_replace
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

        ...
    }
}

// library/Zend/Filter/PregReplace.php
class Zend_Filter_PregReplace implements Zend_Filter_Interface {
    public function filter($value) {
        if ($this->_matchPattern == null) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception(get_class($this) . ' does not have a valid MatchPattern set.');
        }

        return preg_replace($this->_matchPattern, $this->_replacement, $value); // [*] sink
    }
}