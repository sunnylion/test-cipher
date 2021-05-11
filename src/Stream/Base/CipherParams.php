<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream\Base;


use Error;
use InvalidArgumentException;

class CipherParams
{
    private string $iv;
    private string $cipherKey;
    private string $macKey;

    public function __construct(string $mediaKey, string $appInfo)
    {
        if (strlen($mediaKey) !== 32) {
            throw new InvalidArgumentException('Wrong media key');
        }

        $mediaKeyExpanded = hash_hkdf('sha256', $mediaKey, 112, $appInfo);
        if ($mediaKeyExpanded === false) {
            throw new Error('hash_hkdf error');
        }

        $this->iv = substr($mediaKeyExpanded, 0, 16);
        $this->cipherKey = substr($mediaKeyExpanded, 16, 32);
        $this->macKey = substr($mediaKeyExpanded, 48, 32);
    }

    public function getIv(): string
    {
        return $this->iv;
    }

    public function getKey(): string
    {
        return $this->cipherKey;
    }

    public function getMacKey(): string
    {
        return $this->macKey;
    }
}