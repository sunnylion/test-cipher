<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream\Base;


use Error;
use Psr\Http\Message\StreamInterface;

class Utils
{
    public const BLOCK_SIZE = 16;
    public const KEY_SIZE = 32;
    public const CROPPED_MAC_SIZE = 10;

    public static function sha256HashHmac(string $data, string $macKey): string
    {
        $result = hash_hmac('sha256', $data, $macKey, true);
        if ($result === false) {
            throw new Error('hash_hmac error');
        }

        return $result;
    }

    public static function aes256cbcDecrypt(string $data, string $key, string $iv, bool $zeroPadding = false): string
    {
        $result = openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | ((int)$zeroPadding * OPENSSL_ZERO_PADDING), $iv);
        if ($result === false) {
            throw new Error('openssl_decrypt error');
        }

        return $result;
    }

    public static function aes256cbcEncrypt(string $data, string $key, string $iv, bool $zeroPadding = false): string
    {
        $result = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | ((int)$zeroPadding * OPENSSL_ZERO_PADDING), $iv);
        if ($result === false) {
            throw new Error('openssl_encrypt error');
        }

        return $result;
    }

    public static function readCompletely(StreamInterface $stream, int $length): string
    {
        if ($length === 0 || $stream->eof()) {
            return '';
        }
        $readData = '';
        while (strlen($readData) !== $length && !$stream->eof()) {
            $readData .= $stream->read($length - strlen($readData));
        }

        return $readData;
    }
}