/*
    1. \Monolog\Handler\SyslogUdpHandler(\Monolog\Handler\AbstractHandler)::__destruct
    2. \Monolog\Handler\SyslogUdpHandler::close
    3. \Monolog\Handler\BufferHandler::close
    4. \Monolog\Handler\BufferHandler::flush
    5. \Monolog\Handler\BufferHandler(\Monolog\Handler\AbstractHandler)::handleBatch
    6. \Monolog\Handler\BufferHandler::handle
    7. call_user_func
*/

// vendor/monolog/monolog/src/Monolog/Handler/SyslogUdpHandler.php
namespace Monolog\Handler;
class SyslogUdpHandler extends AbstractSyslogHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractSyslogHandler.php
namespace Monolog\Handler;
abstract class AbstractSyslogHandler extends AbstractProcessingHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractProcessingHandler.php
namespace Monolog\Handler;
abstract class AbstractProcessingHandler extends AbstractHandler {
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php
namespace Monolog\Handler;
abstract class AbstractHandler implements HandlerInterface {
    public function __destruct() {
        try {
            $this->close(); // [*] next
        } catch (\Exception $e) {
            // do nothing
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/SyslogUdpHandler.php
namespace Monolog\Handler;
class SyslogUdpHandler extends AbstractSyslogHandler {
    public function close(){
        $this->socket->close(); // [*] next
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

        $this->handler->handleBatch($this->buffer);
        $this->bufferSize = 0;
        $this->buffer = array();
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php
namespace Monolog\Handler;
abstract class AbstractHandler implements HandlerInterface {
    public function handleBatch(array $records) {
        foreach ($records as $record) {
            $this->handle($record); // [*] next
        }
    }
}

// vendor/monolog/monolog/src/Monolog/Handler/BufferHandler.php
namespace Monolog\Handler;
class BufferHandler extends AbstractHandler {
    public function handle(array $record) {
        if ($record['level'] < $this->level) {
            return false;
        }

        if ($this->bufferLimit > 0 && $this->bufferSize === $this->bufferLimit) {
            if ($this->flushOnOverflow) {
                $this->flush();
            } else {
                array_shift($this->buffer);
                $this->bufferSize--;
            }
        }

        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record); // [*] sink
            }
        }

        $this->buffer[] = $record;
        $this->bufferSize++;

        return false === $this->bubble;
    }
}