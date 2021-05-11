<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Tests;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\Callback;
use Sunnylion\TestCipher\Stream\Base\CallbackStream;

class CallbackStreamTest extends AbstractStreamTest
{
    public function testShouldStringify()
    {
        $dataStream = Utils::streamFor('abcdefgh');
        $callbackResultStream = Utils::streamFor();
        $filteringStream = new CallbackStream($dataStream, $this->getCallback($callbackResultStream), 2, 1);
        $filteringStream->getContents();

        $this->assertEquals('abcdefgh', (string)$filteringStream);
    }

    public function testShouldTest()
    {
        $dataStream = Utils::streamFor('abcdefgh');
        $callbackResultStream = Utils::streamFor();
        $filteringStream = new CallbackStream($dataStream, $this->getCallback($callbackResultStream), 1, 3);
        $filteringStream->getContents();

        $this->assertEquals('adg', (string)$callbackResultStream);
    }


    public function testShouldCallBackForEachChunk()
    {
        $dataStream = Utils::streamFor('abcdefgh');
        $callbackResultStream = Utils::streamFor();
        $filteringStream = new CallbackStream($dataStream, $this->getCallback($callbackResultStream), 4, 3);
        $filteringStream->getContents();

        $this->assertEquals('abcddefggh', (string)$callbackResultStream);
    }

    public function testShouldReadAfterSeek()
    {
        $dataStream = Utils::streamFor('abcdefgh');
        $callbackResultStream = Utils::streamFor();

        $filteringStream = new CallbackStream($dataStream, $this->getCallback($callbackResultStream), 3, 2);
        $filteringStream->seek(2);

        $this->assertEquals('cd', $filteringStream->read(2));
    }

    public function testShouldCallBackWhenReadingLastByteOfChunk()
    {
        $dataStream = Utils::streamFor('abcdefgh');
        $callbackResultStream = Utils::streamFor();

        $filteringStream = new CallbackStream($dataStream, $this->getCallback($callbackResultStream), 3, 2);
        $filteringStream->seek(1);


        $filteringStream->read(2);
        $afterReadingTwo = (string)$callbackResultStream;
        $filteringStream->read(1);
        $afterReadingThree = (string)$callbackResultStream;

        $this->assertTrue($afterReadingTwo === '' && $afterReadingThree === 'bcd');
    }

    protected function getCallback(StreamInterface $stream)
    {
        $callback = $this->getMockBuilder(Callback::class)
            ->getMock();

        $callback->method('call')
            ->willReturnCallback(function (string $data) use ($stream) {
                $stream->write($data);
            });

        return $callback;
    }
}