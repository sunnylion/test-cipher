<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream\Base;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class SignCleaningStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const CROPPED_MAC_SIZE = Utils::CROPPED_MAC_SIZE;

    private StreamInterface $stream;

    private string $mac;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function read($length): string
    {
        if ($length === 0 || $this->stream->eof()) {
            return '';
        }

        if ($this->mac === '') {
            $this->initMac();
        }

        $read = $this->mac . $this->stream->read($length);
        $this->mac = substr($read, -self::CROPPED_MAC_SIZE);

        return substr($read, 0, -self::CROPPED_MAC_SIZE);
    }

    public function getMac(): ?string
    {
        if ($this->stream->eof()) {
            return $this->mac;
        }

        return null;
    }

    public function getSize(): ?int
    {
        $size = $this->stream->getSize();
        if ($size !== null) {
            return $size - self::CROPPED_MAC_SIZE;
        }

        return null;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
        try {
            $this->initMac();
        } catch (RuntimeException $e) {
            throw new RuntimeException('Wrong position');
        }
    }

    public function initMac(): void
    {
        $this->mac = $this->stream->read(self::CROPPED_MAC_SIZE);
        if (strlen($this->mac) !== self::CROPPED_MAC_SIZE) {
            throw new RuntimeException('Too short input');
        }
    }

    public function tell(): int
    {
        return max(0, $this->stream->tell() - self::CROPPED_MAC_SIZE);
    }
}