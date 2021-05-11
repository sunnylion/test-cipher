<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream\Base;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class CallbackStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;
    private Callback $chunkCallback;
    private int $chunkSize;
    private int $chunkShift;

    private int $position = 0;
    private string $chunkWindow = '';

    public function __construct(StreamInterface $stream, Callback $dataFilter, int $chunkSize, int $chunkShift)
    {
        if ($chunkSize <= 0 || $chunkShift <= 0) {
            throw new InvalidArgumentException('Size and shift must be more than zero');
        }

        $this->stream = $stream;
        $this->chunkCallback = $dataFilter;
        $this->chunkSize = $chunkSize;
        $this->chunkShift = $chunkShift;
    }


    public function read($length): string
    {
        if ($length === 0 || $this->eof()) {
            return '';
        }

        $bytesLoaded = Utils::readCompletely($this->stream, $length);
        $this->processLoaded($bytesLoaded);
        $this->position += strlen($bytesLoaded);

        return $bytesLoaded;
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->position = 0;
        $this->chunkWindow = '';
        $this->stream->seek($offset, $whence);
    }

    private function processLoaded(string $bytesLoaded): void
    {
        $queue = $bytesLoaded;
        $windowStart = $this->position - $this->chunkSize;
        $windowWidth = $this->chunkSize;
        $window = $this->chunkWindow;

        while ($queue !== '') {
            if ($windowStart < 0) {
                $bytesAddedToWindow = substr($queue, 0, $windowWidth - strlen($window));
            } else {
                $bytesAddedToWindow = substr($queue, 0, $this->chunkShift - $windowStart % $this->chunkShift);
            }
            $windowShift = strlen($bytesAddedToWindow);
            $nextWindowStart = $windowStart + $windowShift;
            $queue = substr($queue, $windowShift);

            $nextWindow = $window . $bytesAddedToWindow;
            if ($windowStart < 0 && $nextWindowStart >= 0) {
                $nextWindow = substr($nextWindow, $nextWindowStart);
            } elseif ($windowStart >= 0 && $nextWindowStart >= 0) {
                $nextWindow = substr($nextWindow, $windowShift);
            }

            $chunk = '';
            if (($nextWindowStart >= 0) && ($nextWindowStart % $this->chunkShift === 0)) {
                $chunk = $nextWindow;
            } elseif ($this->stream->eof()) {
                $lastChunkStart = ($nextWindowStart < 0) ? 0 : ($nextWindowStart - $nextWindowStart % $this->chunkShift + $this->chunkShift);
                $chunk = substr($nextWindow, $lastChunkStart - $nextWindowStart) ?: '';
            }

            if ($chunk !== '') {
                $this->chunkCallback->call($chunk);
            }

            $windowStart = $nextWindowStart;
            $window = $nextWindow;
        }

        $this->chunkWindow = $window;
    }
}