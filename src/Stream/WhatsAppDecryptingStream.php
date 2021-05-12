<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Stream;


use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\CipherParams;

class WhatsAppDecryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;

    public function __construct(StreamInterface $stream, CipherParams $cipherParams)
    {
        $leadingIvAddingStream = new AppendStream([Utils::streamFor($cipherParams->getIv()), $stream]);
        $signValidationStream = new SignValidationStream($leadingIvAddingStream, $cipherParams->getMacKey());
        $decryptingStream = new DecryptingStream($signValidationStream, $cipherParams->getKey());

        $this->stream = $decryptingStream;
    }
}