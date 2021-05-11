<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Sunnylion\TestCipher\Stream\Base\Utils;

class DecryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const BLOCK_SIZE = Utils::BLOCK_SIZE;
    private const KEY_SIZE = Utils::KEY_SIZE;

    private StreamInterface $stream;
    private string $key;

    private int $position = 0;
    private string $iv = '';
    private string $buffer = '';
    private string $preloadedNextBlock = '';

    public function __construct(StreamInterface $stream, string $key)
    {
        if (strlen($key) !== self::KEY_SIZE) {
            throw new InvalidArgumentException('Wrong key size');
        }
        $this->stream = $stream;
        $this->key = $key;
    }

    public function read($length): string
    {
        if ($this->stream->eof() || $length === 0) {
            return '';
        }

        if ($this->iv === '') {
            $this->iv = Utils::readCompletely($this->stream, self::BLOCK_SIZE);
            if (strlen($this->iv) !== self::BLOCK_SIZE) {
                throw new RuntimeException('Too short data');
            }
        }

        if ($length > strlen($this->buffer) && !$this->stream->eof()) {
            $this->decryptNextChunk($length - strlen($this->buffer));
        }

        $decrypted = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, strlen($decrypted));
        $this->position += strlen($decrypted);

        return $decrypted;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->stream->seek($offset, $whence);

        $position = $this->stream->tell();
        $blockStart = $position - $position % self::BLOCK_SIZE;
        $this->stream->seek($blockStart);
        $this->iv = Utils::readCompletely($this->stream, self::BLOCK_SIZE);
        if (strlen($this->iv) < self::BLOCK_SIZE) {
            throw new RuntimeException('Wrong position');
        }
        $this->buffer = '';
        $this->preloadedNextBlock = '';
        $this->position = $blockStart;

        $shift = $position - $blockStart;
        $blockData = $this->read($shift);
        if (strlen($blockData) < $shift) {
            throw new RuntimeException('Wrong position');
        }
    }

    private function decryptNextChunk(int $minLoadedLength): void
    {
        $loadedLength = (int)ceil(($minLoadedLength - strlen($this->buffer)) / self::BLOCK_SIZE) * self::BLOCK_SIZE;

        $chunk = $this->preloadedNextBlock;
        $chunk .= Utils::readCompletely($this->stream, $loadedLength - strlen($chunk));
        if ($chunk === '') {
            return;
        }
        if (strlen($chunk) % self::BLOCK_SIZE !== 0) {
            throw new RuntimeException('Encrypted data contains not integer number of blocks');
        }

        $this->preloadedNextBlock = $this->stream->read(self::BLOCK_SIZE);

        $zeroPadding = $this->preloadedNextBlock !== '';
        $this->buffer .= Utils::aes256cbcDecrypt($chunk, $this->key, $this->iv, $zeroPadding);
        $this->iv = substr($chunk, -self::BLOCK_SIZE);
    }

    public function getSize(): ?int
    {
        return null; //The result cannot be gotten accurately for the method used.
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->stream->eof() && $this->preloadedNextBlock === '' && $this->buffer === '';
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function getMetadata($key = null)
    {
        return null;
    }

}