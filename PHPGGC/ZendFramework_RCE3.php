/*
    1. \Zend\Log\Logger::__destruct
    2. \Zend\Log\Writer\Mail::shutdown
    3. \Zend\Log\Writer\Mail::getFormattedNumEntriesPerPriority ("{$priority}={$numEntries}")
    4. \Zend\Tag\Cloud::__toString
    5. \Zend\Tag\Cloud::render
    6. \Zend\Tag\Cloud\Decorator\HtmlCloud::render
    7. \Zend\Tag\Cloud\Decorator\HtmlCloud(\Zend\Tag\Cloud\Decorator\AbstractDecorator)::wrapTag
    8. \Zend\Escaper\Escaper::escapeHtmlAttr (preg_replace_callback)
    9. \Zend\Filter\FilterChain::filter
    10. call_user_func
*/

// vendor/zendframework/zendframework/library/Zend/Log/Logger.php
namespace Zend\Log;
class Logger implements LoggerInterface {
    public function __destruct() {
        foreach ($this->writers as $writer) {
            try {
                $writer->shutdown(); // [*] next
            } catch (\Exception $e) {}
        }
    }
}

// vendor/zendframework/zendframework/library/Zend/Log/Writer/Mail.php
namespace Zend\Log\Writer;
class Mail extends AbstractWriter {
    public function shutdown() {
        // If there are events to mail, use them as message body.  Otherwise,
        // there is no mail to be sent.
        if (empty($this->eventsToMail)) {
            return;
        }

        if ($this->subjectPrependText !== null) {
            // Tack on the summary of entries per-priority to the subject
            // line and set it on the Zend\Mail object.
            $numEntries = $this->getFormattedNumEntriesPerPriority(); // [*] next
            $this->mail->setSubject("{$this->subjectPrependText} ({$numEntries})");
        }

        ...
    }
    
    protected function getFormattedNumEntriesPerPriority() {
        $strings = array();

        foreach ($this->numEntriesPerPriority as $priority => $numEntries) {
            $strings[] = "{$priority}={$numEntries}"; // [*] next (call __toString)
        }

        return implode(', ', $strings);
    }
}

// vendor/zendframework/zendframework/library/Zend/Tag/Cloud.php
namespace Zend\Tag;
class Cloud {
    public function __toString() {
        try {
            $result = $this->render(); // [*] next
            return $result;
        } catch (\Exception $e) {
            $message = "Exception caught by tag cloud: " . $e->getMessage()
                     . "\nStack Trace:\n" . $e->getTraceAsString();
            trigger_error($message, E_USER_WARNING);
            return '';
        }
    }
    
    public function render() {
        $tags = $this->getItemList();

        if (count($tags) === 0) {
            return '';
        }

        $tagsResult  = $this->getTagDecorator()->render($tags); // [*] next
        $cloudResult = $this->getCloudDecorator()->render($tagsResult);

        return $cloudResult;
    }
}

// vendor/zendframework/zendframework/library/Zend/Tag/Cloud/Decorator/HtmlCloud.php
namespace Zend\Tag\Cloud\Decorator;
class HtmlCloud extends AbstractCloud {
    public function render($tags) {
        if (!is_array($tags)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'HtmlCloud::render() expects an array argument; received "%s"',
                (is_object($tags) ? get_class($tags) : gettype($tags))
            ));
        }
        $cloudHTML = implode($this->getSeparator(), $tags);
        $cloudHTML = $this->wrapTag($cloudHTML); // [*] next
        return $cloudHTML;
    }
}

// vendor/zendframework/zendframework/library/Zend/Tag/Cloud/Decorator/AbstractCloud.php
namespace Zend\Tag\Cloud\Decorator;
abstract class AbstractCloud extends AbstractDecorator {
}

// vendor/zendframework/zendframework/library/Zend/Tag/Cloud/Decorator/AbstractDecorator.php
namespace Zend\Tag\Cloud\Decorator;
abstract class AbstractDecorator implements Decorator {
    protected function wrapTag($html) {
        $escaper = $this->getEscaper();
        foreach ($this->getHTMLTags() as $key => $data) {
            if (is_array($data)) {
                $attributes = '';
                $htmlTag    = $key;
                $this->validateElementName($htmlTag);

                foreach ($data as $param => $value) {
                    $this->validateAttributeName($param);
                    $attributes .= ' ' . $param . '="' . $escaper->escapeHtmlAttr($value) . '"'; // [*] next
                }
            } else {
                $attributes = '';
                $htmlTag    = $data;
                $this->validateElementName($htmlTag);
            }

            $html = sprintf('<%1$s%3$s>%2$s</%1$s>', $htmlTag, $html, $attributes);
        }
        return $html;
    }
}

// vendor/zendframework/zendframework/library/Zend/Escaper/Escaper.php
namespace Zend\Escaper;
class Escaper {
    public function escapeHtmlAttr($string) {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }

        $result = preg_replace_callback('/[^a-z0-9,\.\-_]/iSu', $this->htmlAttrMatcher, $string); // [*] next
                                                                                                  // [!] need to set to call \Zend\Filter\FilterChain::filter
        return $this->fromUtf8($result);
    }
}

// vendor/zendframework/zendframework/library/Zend/Filter/FilterChain.php
namespace Zend\Filter;
class FilterChain extends AbstractFilter implements Countable {
    public function filter($value) {
        $chain = clone $this->filters;

        $valueFiltered = $value;
        foreach ($chain as $filter) {
            $valueFiltered = call_user_func($filter, $valueFiltered); // [*] sink
        }

        return $valueFiltered;
    }
}