<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;


use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Jsq\EncryptionStreams\HashingStream;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\Utils;

class SigningStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const CROPPED_MAC_SIZE = Utils::CROPPED_MAC_SIZE;

    private HashingStream $stream;
    private string $macKey;

    private string $macBuffer = '';
    private int $position = 0;

    public function __construct(StreamInterface $stream, string $macKey)
    {
        $this->stream = new HashingStream($stream, $macKey);
        $this->macKey = $macKey;
    }

    public function read($length): string
    {
        if ($length === 0) {
            return '';
        }
        if ($this->stream->eof() && $this->macBuffer === '') {
            return '';
        }

        $read = '';
        if (!$this->stream->eof()) {
            $read = $this->stream->read($length);
        }

        if ($this->stream->eof() && $this->macBuffer === '') {
            $mac = (string)$this->stream->getHash();
            $this->macBuffer = substr($mac, 0, self::CROPPED_MAC_SIZE);
        }

        if ($this->stream->eof()) {
            $read .= substr($this->macBuffer, 0, $length - strlen($read));
            $this->macBuffer = substr($this->macBuffer, $length - strlen($read)) ?: '';
        }

        $this->position += strlen($read);

        return $read;
    }

    public function eof(): bool
    {
        return $this->stream->eof() && $this->macBuffer === '';
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->stream->seek($offset, $whence); //only rewind
        $this->macBuffer = '';
        $this->position = 0;
    }

    public function getSize(): ?int
    {
        $size = $this->stream->getSize();
        if ($size !== null) {
            return $size + self::CROPPED_MAC_SIZE;
        }

        return null;
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function tell(): int
    {
        return $this->position;
    }
}