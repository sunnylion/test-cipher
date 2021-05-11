<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;


use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Jsq\EncryptionStreams\HashingStream;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\SignCleaningStream;
use Sunnylion\TestCipher\Stream\Base\Utils;
use Sunnylion\TestCipher\Stream\Exception\HashValidationException;

class SignValidationStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const CROPPED_MAC_SIZE = Utils::CROPPED_MAC_SIZE;

    private HashingStream $stream;
    private SignCleaningStream $signCleaningStream;

    public function __construct(StreamInterface $stream, string $macKey)
    {
        $this->signCleaningStream = new SignCleaningStream($stream);
        $this->stream = new HashingStream($this->signCleaningStream, $macKey);
    }

    /**
     * @throws HashValidationException
     */
    public function read($length): string
    {
        if ($length === 0 || $this->stream->eof()) {
            return '';
        }

        $read = $this->stream->read($length);
        if ($this->stream->eof()) {
            $this->validate($this->stream->getHash(), $this->signCleaningStream->getMac());
        }

        return $read;
    }

    /**
     * @throws HashValidationException
     */
    private function validate(string $hash, string $actualMacCropped): void
    {
        $expectedMacCropped = substr($hash, 0, self::CROPPED_MAC_SIZE);
        if ($expectedMacCropped !== $actualMacCropped) {
            throw new HashValidationException('Wrong sign');
        }
    }
}