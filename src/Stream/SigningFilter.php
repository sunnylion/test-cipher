<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream;


use Sunnylion\TestCipher\Stream\Base\Filter;
use Sunnylion\TestCipher\Stream\Base\Utils;
use function substr;

class SigningFilter implements Filter
{
    private const CROPPED_MAC_SIZE = Utils::CROPPED_MAC_SIZE;

    private string $macKey;

    public function __construct(string $macKey)
    {
        $this->macKey = $macKey;
    }


    public function filter(string $data): string
    {
        $mac = Utils::sha256HashHmac($data, $this->macKey);
        $macCropped = substr($mac, 0, self::CROPPED_MAC_SIZE);

        return $data . $macCropped;
    }
}