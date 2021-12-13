/*
    1. \Swift_Message(\Swift_Mime_SimpleMessage)::__toString
    2. \Swift_Message::toString
    3. \Swift_Message::doSign
    4. \Swift_Message(\Swift_Mime_SimpleMimeEntity)::bodyToByteStream
    5. \Swift_Signers_DomainKeySigner::write
    6. \Swift_KeyCache_SimpleKeyCacheInputStream::write
    7. \Swift_ByteStream_FileByteStream(\Swift_ByteStream_AbstractFilterableInputStream)::write
    8. \Swift_ByteStream_FileByteStream(\Swift_ByteStream_AbstractFilterableInputStream)::doWrite
    9. \Swift_ByteStream_FileByteStream::doCommit
    10. fwrite
*/

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Message.php
class Swift_Message extends Swift_Mime_SimpleMessage {
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Mime/SimpleMessage.php
class Swift_Mime_SimpleMessage extends Swift_Mime_MimePart {
    public function __toString() {
        return $this->toString(); // [*] next
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Message.php
class Swift_Message extends Swift_Mime_SimpleMessage {
    public function toString() {
        if (empty($this->headerSigners) && empty($this->bodySigners)) {
            return parent::toString();
        }

        $this->saveMessage();

        $this->doSign(); // [*] next

        $string = parent::toString();

        $this->restoreMessage();

        return $string;
    }
    
    protected function doSign() {
        foreach ($this->bodySigners as $signer) {
            $altered = $signer->getAlteredHeaders();
            $this->saveHeaders($altered);
            $signer->signMessage($this);
        }

        foreach ($this->headerSigners as $signer) {
            $altered = $signer->getAlteredHeaders();
            $this->saveHeaders($altered);
            $signer->reset();

            $signer->setHeaders($this->getHeaders());

            $signer->startBody();
            $this->bodyToByteStream($signer); // [*] next
            $signer->endBody();

            $signer->addSignature($this->getHeaders());
        }
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Mime/MimePart.php
class Swift_Mime_MimePart extends Swift_Mime_SimpleMimeEntity {
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Mime/SimpleMimeEntity.php
class Swift_Mime_SimpleMimeEntity implements Swift_Mime_CharsetObserver, Swift_Mime_EncodingObserver {
    protected function bodyToByteStream(Swift_InputByteStream $is) {
        if (empty($this->immediateChildren)) {
            if (isset($this->body)) {
                if ($this->cache->hasKey($this->cacheKey, 'body')) {
                    $this->cache->exportToByteStream($this->cacheKey, 'body', $is);
                } else {
                    $cacheIs = $this->cache->getInputByteStream($this->cacheKey, 'body');
                    if ($cacheIs) {
                        $is->bind($cacheIs);
                    }

                    $is->write("\r\n");

                    if ($this->body instanceof Swift_OutputByteStream) {
                        $this->body->setReadPointer(0);

                        $this->encoder->encodeByteStream($this->body, $is, 0, $this->getMaxLineLength());
                    } else {
                        $is->write($this->encoder->encodeString($this->getBody(), 0, $this->getMaxLineLength())); // [*] next
                    }

                    if ($cacheIs) {
                        $is->unbind($cacheIs);
                    }
                }
            }
        }

        if (!empty($this->immediateChildren)) {
            foreach ($this->immediateChildren as $child) {
                $is->write("\r\n\r\n--".$this->getBoundary()."\r\n");
                $child->toByteStream($is);
            }
            $is->write("\r\n\r\n--".$this->getBoundary()."--\r\n");
        }
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Signers/DomainKeySigner.php
class Swift_Signers_DomainKeySigner implements Swift_Signers_HeaderSigner {
    public function write($bytes) {
        $this->canonicalizeBody($bytes);
        foreach ($this->bound as $is) {
            $is->write($bytes); // [*] next
        }

        return $this;
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/KeyCache/SimpleKeyCacheInputStream.php
class Swift_KeyCache_SimpleKeyCacheInputStream implements Swift_KeyCache_KeyCacheInputStream {
    public function write($bytes, Swift_InputByteStream $is = null) {
        $this->keyCache->setString(
            $this->nsKey, $this->itemKey, $bytes, Swift_KeyCache::MODE_APPEND
            );
        if (isset($is)) {
            $is->write($bytes);
        }
        if (isset($this->writeThrough)) {
            $this->writeThrough->write($bytes); // [*] next
        }
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/FileByteStream.php
class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream implements Swift_FileStream {
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/AbstractFilterableInputStream.php
abstract class Swift_ByteStream_AbstractFilterableInputStream implements Swift_InputByteStream, Swift_Filterable {
    public function write($bytes) {
        $this->writeBuffer .= $bytes;
        foreach ($this->filters as $filter) {
            if ($filter->shouldBuffer($this->writeBuffer)) {
                return;
            }
        }
        $this->doWrite($this->writeBuffer); // [*] next

        return ++$this->sequence;
    }
    
    private function doWrite($bytes) {
        $this->doCommit($this->filter($bytes)); // [*] next

        foreach ($this->mirrors as $stream) {
            $stream->write($bytes);
        }

        $this->writeBuffer = '';
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/FileByteStream.php
class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream implements Swift_FileStream {
    protected function doCommit($bytes) {
        fwrite($this->getWriteHandle(), $bytes); // [*] sink
        $this->resetReadHandle();
    }
    
    private function getWriteHandle() {
        if (!isset($this->writer)) {
            if (!$this->writer = fopen($this->path, $this->mode)) { // [!] need to set arguments to get file handler
                throw new Swift_IoException(
                    'Unable to open file for writing ['.$this->path.']'
                );
            }
        }

        return $this->writer;
    }
}