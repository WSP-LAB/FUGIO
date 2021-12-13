/*
    1. \Swift_ByteStream_TemporaryFileByteStream::__destruct
    2. unlink
*/

// vendor/swiftmailer/swiftmailer/lib/classes/Swift/ByteStream/TemporaryFileByteStream.php
class Swift_ByteStream_TemporaryFileByteStream extends Swift_ByteStream_FileByteStream {
    public function __destruct() {
        if (file_exists($this->getPath())) {
            @unlink($this->getPath()); // [*] sink
        }
    }
}
