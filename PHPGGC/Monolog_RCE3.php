/*
    1. \Monolog\Handler\BufferHandler(\Monolog\Handler\AbstractHandler)::__destruct
    2. \Monolog\Handler\BufferHandler::close
    3. \Monolog\Handler\BufferHandler::flush
    4. \Monolog\Handler\NativeMailerHandler(\Monolog\Handler\MailHandler)::handleBatch
    5. \Monolog\Handler\NativeMailerHandler(\Monolog\Handler\AbstractHandler)::processRecord
    6. call_user_func
*/

// vendor/monolog/monolog/src/Monolog/Handler/BufferHandler.php
namespace Monolog\Handler;
class BufferHandler extends AbstractHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php
namespace Monolog\Handler;
abstract class AbstractHandler implements HandlerInterface {
    public function __destruct()
    {
        try {
            $this->close(); // [*] next
        } catch (\Exception $e) {
            // do nothing
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/BufferHandler.php
namespace Monolog\Handler;
class BufferHandler extends AbstractHandler {
    public function close() {
        $this->flush(); // [*] next
    }
    
    public function flush() {
        if ($this->bufferSize === 0) {
            return;
        }

        $this->handler->handleBatch($this->buffer); // [*] next
        $this->bufferSize = 0;
        $this->buffer = array();
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/NativeMailerHandler.php
namespace Monolog\Handler;
class NativeMailerHandler extends MailHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/MailHandler.php
namespace Monolog\Handler;
abstract class MailHandler extends AbstractProcessingHandler {
    public function handleBatch(array $records) {
        $messages = array();

        foreach ($records as $record) {
            if ($record['level'] < $this->level) {
                continue;
            }
            $messages[] = $this->processRecord($record); // [*] next
        }

        if (!empty($messages)) {
            $this->send((string) $this->getFormatter()->formatBatch($messages), $messages);
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractProcessingHandler.php
namespace Monolog\Handler;
abstract class AbstractProcessingHandler extends AbstractHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php
namespace Monolog\Handler;
abstract class AbstractHandler implements HandlerInterface {
    protected function processRecord(array $record) {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record); // [*] sink
            }
        }

        return $record;
    }
}