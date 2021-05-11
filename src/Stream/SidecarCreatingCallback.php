<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;


use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\Callback;
use Sunnylion\TestCipher\Stream\Base\Utils;
use function substr;

class SidecarCreatingCallback implements Callback
{
    private const CROPPED_MAC_SIZE = Utils::CROPPED_MAC_SIZE;
    private const BLOCK_SIZE = Utils::BLOCK_SIZE;

    private string $macKey;
    private StreamInterface $sidecarStream;

    public function __construct(string $macKey, StreamInterface $sidecarStream)
    {
        $this->macKey = $macKey;
        $this->sidecarStream = $sidecarStream;
    }

    public function call(string $data): void
    {
        if (strlen($data) < self::BLOCK_SIZE) {
            throw new InvalidArgumentException('Data size must be at least ' . self::BLOCK_SIZE);
        }
        $mac = Utils::sha256HashHmac($data, $this->macKey);
        $macCropped = substr($mac, 0, self::CROPPED_MAC_SIZE);
        $this->sidecarStream->write($macCropped);
    }
}