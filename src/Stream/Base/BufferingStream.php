<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream\Base;


use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils as GuzzleUtils;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class BufferingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $underlyingStream;
    private StreamInterface $bufferingStream;
    private Filter $filter;

    public function __construct(StreamInterface $stream, Filter $filter)
    {
        $this->underlyingStream = $stream;
        $this->filter = $filter;
    }

    public function __get($name)
    {
        if ($name === 'stream') {
            return $this->getBufferingStream();
        }

        throw new RuntimeException("Cannot find {$name} property");
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new RuntimeException('This stream is not writable');
    }

    public function close(): void
    {
        $this->underlyingStream->close();
        $this->bufferingStream->close();
    }

    public function detach()
    {
        $this->close();

        return null;
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    private function getBufferingStream(): StreamInterface
    {
        if (empty($this->bufferingStream)) {
            $res = GuzzleUtils::tryFopen('php://memory', 'r+');
            $filteredData = $this->filter((string)$this->underlyingStream);
            fseek($res, 0);
            fwrite($res, $filteredData);
            $this->bufferingStream = new Stream($res);
        }

        return $this->bufferingStream;
    }

    private function filter(string $data): string
    {
        return $this->filter->filter($data);
    }
}