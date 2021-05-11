<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream;


use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\CipherParams;
use Sunnylion\TestCipher\Stream\Base\LeadingIvSkippingStream;

class WhatsAppEncryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;

    public function __construct(StreamInterface $stream, CipherParams $cipherParams)
    {
        $encryptingStream = new EncryptingStream($stream, $cipherParams->getKey(), $cipherParams->getIv());
        $signingStream = new SigningStream($encryptingStream, $cipherParams->getMacKey());
        $ivSkippingStream = new LeadingIvSkippingStream($signingStream);

        $this->stream = $ivSkippingStream;
    }
}