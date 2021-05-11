<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;

use Sunnylion\TestCipher\Stream\Base\Filter;
use Sunnylion\TestCipher\Stream\Base\Utils;
use Sunnylion\TestCipher\Stream\Exception\HashValidationException;

class SignValidationFilter implements Filter
{
    private const CROPPED_MAC_SIZE = Utils::CROPPED_MAC_SIZE;

    private string $macKey;

    public function __construct(string $macKey)
    {
        $this->macKey = $macKey;
    }


    /**
     * @param string $data
     * @return string
     * @throws HashValidationException
     */
    public function filter(string $data): string
    {
        $actualMacCropped = substr($data, -self::CROPPED_MAC_SIZE);
        $file = substr($data, 0, -self::CROPPED_MAC_SIZE);

        $expectedMac = Utils::sha256HashHmac($file, $this->macKey);
        $expectedMacCropped = substr($expectedMac, 0, self::CROPPED_MAC_SIZE);

        if ($actualMacCropped !== $expectedMacCropped) {
            throw new HashValidationException('Mac\'s do not match');
        }

        return substr($data, 0, -self::CROPPED_MAC_SIZE);
    }
}