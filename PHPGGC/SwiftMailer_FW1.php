/*
    1. \Swift_Message(\Swift_Mime_SimpleMessage)::__toString
    2. \Swift_Message::toString
    3. \Swift_Message::doSign
    4. \Swift_Message(\Swift_Mime_SimpleMimeEntity)::_bodyToByteStream
    5. \Swift_Signers_DomainKeySigner::write
    6. \Swift_KeyCache_SimpleKeyCacheInputStream::write
    7. \Swift_ByteStream_FileByteStream(\Swift_ByteStream_AbstractFilterableInputStream)::write
    8. \Swift_ByteStream_FileByteStream(\Swift_ByteStream_AbstractFilterableInputStream)::_doWrite
    9. \Swift_ByteStream_FileByteStream::_commit
    10. fwrite
*/

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Message.php
class Swift_Message extends Swift_Mime_SimpleMessage {
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Mime/SimpleMessage.php
class Swift_Mime_SimpleMessage extends Swift_Mime_MimePart implements Swift_Mime_Message {
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
            $this->_bodyToByteStream($signer); // [*] next
            $signer->endBody();

            $signer->addSignature($this->getHeaders());
        }
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Mime/MimePart.php
class Swift_Mime_MimePart extends Swift_Mime_SimpleMimeEntity {
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Mime/SimpleMimeEntity.php
class Swift_Mime_SimpleMimeEntity implements Swift_Mime_MimeEntity {
    protected function _bodyToByteStream(Swift_InputByteStream $is) {
        if (empty($this->_immediateChildren)) {
            if (isset($this->_body)) {
                if ($this->_cache->hasKey($this->_cacheKey, 'body')) {
                    $this->_cache->exportToByteStream($this->_cacheKey, 'body', $is);
                } else {
                    $cacheIs = $this->_cache->getInputByteStream($this->_cacheKey, 'body');
                    if ($cacheIs) {
                        $is->bind($cacheIs);
                    }

                    $is->write("\r\n");

                    if ($this->_body instanceof Swift_OutputByteStream) {
                        $this->_body->setReadPointer(0);

                        $this->_encoder->encodeByteStream($this->_body, $is, 0, $this->getMaxLineLength());
                    } else {
                        $is->write($this->_encoder->encodeString($this->getBody(), 0, $this->getMaxLineLength())); // [*] next
                    }

                    if ($cacheIs) {
                        $is->unbind($cacheIs);
                    }
                }
            }
        }

        if (!empty($this->_immediateChildren)) {
            foreach ($this->_immediateChildren as $child) {
                $is->write("\r\n\r\n--" . $this->getBoundary() . "\r\n");
                $child->toByteStream($is);
            }
            $is->write("\r\n\r\n--" . $this->getBoundary() . "--\r\n");
        }
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/Signers/DomainKeySigner.php
class Swift_Signers_DomainKeySigner implements Swift_Signers_HeaderSigner {
    public function write($bytes) {
        $this->_canonicalizeBody($bytes);
        foreach ($this->_bound as $is) {
            $is->write($bytes); // [*] next
        }

        return $this;
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/KeyCache/SimpleKeyCacheInputStream.php
class Swift_KeyCache_SimpleKeyCacheInputStream implements Swift_KeyCache_KeyCacheInputStream {
    public function write($bytes, Swift_InputByteStream $is = null) {
        $this->_keyCache->setString(
            $this->_nsKey, $this->_itemKey, $bytes, Swift_KeyCache::MODE_APPEND
            );
        if (isset($is)) {
            $is->write($bytes);
        }
        if (isset($this->_writeThrough)) {
            $this->_writeThrough->write($bytes); // [*] next
        }
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/FileByteStream.php
class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream implements Swift_FileStream {
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/AbstractFilterableInputStream.php
abstract class Swift_ByteStream_AbstractFilterableInputStream implements Swift_InputByteStream, Swift_Filterable {
    public function write($bytes) {
        $this->_writeBuffer .= $bytes;
        foreach ($this->_filters as $filter) {
            if ($filter->shouldBuffer($this->_writeBuffer)) {
                return;
            }
        }
        $this->_doWrite($this->_writeBuffer); // [*] next

        return ++$this->_sequence;
    }
    
    private function _doWrite($bytes) {
        $this->_commit($this->_filter($bytes)); // [*] next

        foreach ($this->_mirrors as $stream) {
            $stream->write($bytes);
        }

        $this->_writeBuffer = '';
    }
}

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/FileByteStream.php
class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream implements Swift_FileStream {
    protected function _commit($bytes) {
        fwrite($this->_getWriteHandle(), $bytes); // [*] sink
        $this->_resetReadHandle();
    }

    private function _getWriteHandle()
    {
        if (!isset($this->_writer)) {
            if (!$this->_writer = fopen($this->_path, $this->_mode)) { // [!] need to set arguments to get file handler
                throw new Swift_IoException(
                    'Unable to open file for writing [' . $this->_path . ']'
                );
            }
        }

        return $this->_writer;
    }
}