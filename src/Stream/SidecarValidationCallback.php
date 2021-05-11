<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;


use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\Callback;
use Sunnylion\TestCipher\Stream\Base\Utils;
use Sunnylion\TestCipher\Stream\Exception\HashValidationException;

class SidecarValidationCallback implements Callback
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

    /**
     * @param string $data
     * @throws HashValidationException
     */
    public function call(string $data): void
    {
        if (strlen($data) < self::BLOCK_SIZE) {
            throw new InvalidArgumentException('Data size must be at least ' . self::BLOCK_SIZE);
        }
        $actualMac = Utils::sha256HashHmac($data, $this->macKey);
        $actualMacCropped = substr($actualMac, 0, self::CROPPED_MAC_SIZE);
        $expectedMacCropped = $this->sidecarStream->read(self::CROPPED_MAC_SIZE);

        if ($expectedMacCropped !== $actualMacCropped) {
            throw new HashValidationException('Mac\'s do not match');
        }
    }
}