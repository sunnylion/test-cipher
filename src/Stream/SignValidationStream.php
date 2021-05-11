<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;


use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\BufferingStream;

class SignValidationStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;

    public function __construct(StreamInterface $stream, string $macKey)
    {
        $this->stream = new BufferingStream(
            $stream,
            new SignValidationFilter($macKey)
        );
    }
}