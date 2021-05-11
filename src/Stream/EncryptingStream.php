<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream;


use GuzzleHttp\Psr7\StreamDecoratorTrait;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Sunnylion\TestCipher\Stream\Base\Utils;

class EncryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const BLOCK_SIZE = Utils::BLOCK_SIZE;
    private const KEY_SIZE = Utils::KEY_SIZE;

    private StreamInterface $stream;
    private string $startIv;
    private string $key;

    private string $currentIv;
    private int $position = 0;
    private string $buffer = '';


    public function __construct(StreamInterface $stream, string $key, string $iv)
    {
        if (strlen($key) !== self::KEY_SIZE) {
            throw new InvalidArgumentException('Wrong key size');
        }

        if (strlen($iv) !== self::BLOCK_SIZE) {
            throw new InvalidArgumentException('Wrong iv size');
        }

        $this->stream = $stream;
        $this->key = $key;
        $this->startIv = $iv;
    }

    public function read($length): string
    {
        if (!is_int($length)) {
            throw new RuntimeException('Length must have integer type');
        }

        if ($this->eof() || $length === 0) {
            return '';
        }

        if ($this->position === 0) {
            $this->buffer = $this->startIv;
            $this->currentIv = $this->startIv;
        }

        if ($length > strlen($this->buffer) && !$this->stream->eof()) {
            $minLengthToLoad = $length - strlen($this->buffer);
            $blocksNumberToLoad = ($minLengthToLoad - $minLengthToLoad % self::BLOCK_SIZE) / self::BLOCK_SIZE + 1;
            $this->encryptChunk($blocksNumberToLoad);
        }

        $encryptedData = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, strlen($encryptedData));
        $this->position += strlen($encryptedData);

        return $encryptedData;
    }


    private function encryptChunk(int $blocksAmount): void
    {
        $length = $blocksAmount * self::BLOCK_SIZE;
        $chunk = Utils::readCompletely($this->stream, $length);

        $zeroPadding = !$this->stream->eof();
        $encrypted = Utils::aes256cbcEncrypt($chunk, $this->key, $this->currentIv, $zeroPadding);
        $this->currentIv = substr($encrypted, -self::BLOCK_SIZE);

        $this->buffer .= $encrypted;
    }

    public function eof(): bool
    {
        return $this->stream->eof() && empty($this->buffer);
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($offset !== 0 || $whence !== SEEK_SET) {
            throw new InvalidArgumentException('Only setting to 0 is allowed');
        }

        $this->stream->seek(0, SEEK_SET);
        $this->buffer = '';
        $this->position = 0;
        $this->currentIv = '';
    }


    public function tell(): int
    {
        return $this->position;
    }

    public function getSize(): ?int
    {
        $streamSize = $this->stream->getSize();
        if ($streamSize === null) {
            return null;
        }

        return ($streamSize % self::BLOCK_SIZE === 0)
            ? ($streamSize + 2 * self::BLOCK_SIZE)
            : $streamSize - $streamSize % self::BLOCK_SIZE + self::BLOCK_SIZE;
    }
}