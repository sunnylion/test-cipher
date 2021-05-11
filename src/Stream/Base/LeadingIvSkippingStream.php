<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream\Base;


use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class LeadingIvSkippingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const BLOCK_SIZE = Utils::BLOCK_SIZE;

    private StreamInterface $stream;

    private bool $isRead = false;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function read($length): string
    {
        if (!$this->isRead) {
            $this->isRead = true;
            $this->rewind();
        }

        return $this->stream->read($length);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($offset !== 0 || $whence !== SEEK_SET) {
            throw new RuntimeException('Only setting to 0 is allowed');
        }
        $this->stream->seek($offset, $whence);
        $this->stream->read(self::BLOCK_SIZE);
    }

    public function tell(): int
    {
        return max(0, $this->stream->tell() - self::BLOCK_SIZE);
    }

    public function getSize(): ?int
    {
        $size = $this->stream->getSize();
        if ($size === null) {
            return null;
        }

        return $size - self::BLOCK_SIZE;
    }
}