<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Tests;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Sunnylion\TestCipher\Stream\Base\CipherParams;

abstract class AbstractStreamTest extends TestCase
{
    protected const TYPE_AUDIO = 'AUDIO';
    protected const TYPE_IMAGE = 'IMAGE';
    protected const TYPE_VIDEO = 'VIDEO';

    protected const MAC_SIZE = 10;
    protected const BLOCK_SIZE = 16;

    public static function sampleProvider(): array
    {
        return [
            ['VIDEO.key', 'VIDEO.original', 'VIDEO.encrypted', 'WhatsApp Video Keys'],
            ['AUDIO.key', 'AUDIO.original', 'AUDIO.encrypted', 'WhatsApp Audio Keys'],
            ['IMAGE.key', 'IMAGE.original', 'IMAGE.encrypted', 'WhatsApp Image Keys'],
        ];
    }

    protected function getSamplePath(string $file): string
    {
        return SAMPLES_DIR . '/' . $file;
    }

    protected function assertData($expected, $actual)
    {
        $this->assertEquals(md5($expected), md5($actual));
    }

    protected function getCipherParams(string $name): CipherParams
    {
        $path = $this->getSamplePath($name . '.key');
        $mediaKey = file_get_contents($path);
        $appInfo = $this->getAppInfo($name);

        return new CipherParams($mediaKey, $appInfo);
    }

    protected function getEncryptedWithLeadingIv(string $name, string $iv): StreamInterface
    {
        return Utils::streamFor($iv . $this->getEncryptedWithoutLeadingIv($name));
    }

    protected function getEncryptedWithLeadingIvWithMac(string $name, string $iv): StreamInterface
    {
        $path = $this->getSamplePath($name . '.encrypted');

        return Utils::streamFor($iv . file_get_contents($path));
    }

    protected function getEncryptedWithLeadingIvWithoutMac(string $name, string $iv): StreamInterface
    {
        $path = $this->getSamplePath($name . '.encrypted');

        return Utils::streamFor($iv . substr(file_get_contents($path), 0, -self::MAC_SIZE));
    }

    protected function getEncryptedWithoutLeadingIv(string $name): StreamInterface
    {
        $path = $this->getSamplePath($name . '.encrypted');

        return Utils::streamFor(substr(file_get_contents($path), 0, -self::MAC_SIZE));
    }

    protected function getOriginal(string $name): StreamInterface
    {
        $path = $this->getSamplePath($name . '.original');

        return Utils::streamFor(file_get_contents($path));
    }

    protected function getSidecar(string $name): StreamInterface
    {
        $path = $this->getSamplePath($name . '.sidecar');

        return Utils::streamFor(file_get_contents($path));
    }

    protected function getAppInfo(string $name): string
    {
        return [
            self::TYPE_VIDEO => 'WhatsApp Video Keys',
            self::TYPE_AUDIO => 'WhatsApp Audio Keys',
            self::TYPE_IMAGE => 'WhatsApp Image Keys',
        ][$name];
    }

    public function mediaTypeDataProvider(): array
    {
        return [
            ['VIDEO'],
            ['IMAGE'],
            ['AUDIO'],
        ];
    }
}
