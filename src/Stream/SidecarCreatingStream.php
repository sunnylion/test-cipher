<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\CallbackStream;
use Sunnylion\TestCipher\Stream\Base\Utils;

class SidecarCreatingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    public function __construct(StreamInterface $stream, string $macKey, int $blockSize, StreamInterface $sidecarStream)
    {
        $this->stream = new CallbackStream(
            $stream,
            new SidecarCreatingCallback($macKey, $sidecarStream),
            $blockSize + Utils::BLOCK_SIZE,
            $blockSize,
        );
    }
}