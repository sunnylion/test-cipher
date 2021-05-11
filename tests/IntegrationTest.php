<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Tests;


use GuzzleHttp\Psr7\Utils;
use Sunnylion\TestCipher\Stream\DecryptingStream;
use Sunnylion\TestCipher\Stream\EncryptingStream;
use Sunnylion\TestCipher\Stream\SidecarCreatingStream;
use Sunnylion\TestCipher\Stream\SidecarValidationStream;
use Sunnylion\TestCipher\Stream\SigningStream;
use Sunnylion\TestCipher\Stream\SignValidationStream;
use Sunnylion\TestCipher\Stream\WhatsAppEncryptingStream;

class IntegrationTest extends AbstractStreamTest
{
    public function testWhatsAppEncrypting()
    {
        $originalFileStream = Utils::streamFor(file_get_contents($this->getSamplePath('VIDEO.original')));
        $encryptedFileStream = Utils::streamFor(file_get_contents($this->getSamplePath('VIDEO.encrypted')));
        $cipherParams = $this->getCipherParams(self::TYPE_VIDEO);

        $whatsAppEncryptingStream = new WhatsAppEncryptingStream($originalFileStream, $cipherParams);

        $this->assertData((string)$encryptedFileStream, (string)$whatsAppEncryptingStream);
    }

    public function testSignValidation()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $dataStream = $this->getEncryptedWithLeadingIvWithMac($mediaType, $cipherParams->getIv());

        $signValidationStream = new SignValidationStream($dataStream, $cipherParams->getMacKey());

        $expectedStream = $this->getEncryptedWithLeadingIvWithoutMac($mediaType, $cipherParams->getIv());
        $this->assertData((string)$expectedStream, (string)$signValidationStream);
    }

    public function testSigning()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $dataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());

        $signingStream = new SigningStream($dataStream, $cipherParams->getMacKey());

        $expected = $this->getEncryptedWithLeadingIvWithMac($mediaType, $cipherParams->getIv());
        $this->assertData((string)$expected, (string)$signingStream);
    }

    public function testSidecarCreating()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $dataStream = $this->getEncryptedWithLeadingIvWithMac($mediaType, $cipherParams->getIv());
        $expectedSidecarStream = $this->getSidecar($mediaType);

        $actualSidecarStream = Utils::streamFor();
        $sidecarCreatingStream = new SidecarCreatingStream($dataStream, $cipherParams->getMacKey(), 64 * 1024, $actualSidecarStream);
        $sidecarCreatingStream->getContents();

        $this->assertEquals((string)$expectedSidecarStream, (string)$actualSidecarStream);
    }

    public function testSidecarValidation()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $dataStream = $this->getEncryptedWithLeadingIvWithMac($mediaType, $cipherParams->getIv());
        $sidecarStream = $this->getSidecar($mediaType);

        $sidecarValidationStream = new SidecarValidationStream($dataStream, $cipherParams->getMacKey(), 64 * 1024, $sidecarStream);
        $sidecarValidationStream->getContents();

        $this->assertTrue(true);
    }

    public function testDecrypting()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $originalDataStream = $this->getOriginal($mediaType);

        $decryptingStream = new DecryptingStream($encryptedDataStream, $cipherParams->getKey());

        $this->assertData((string)$originalDataStream, (string)$decryptingStream);
    }

    public function testEncrypting()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $originalDataStream = $this->getOriginal($mediaType);

        $encryptingStream = new EncryptingStream($originalDataStream, $cipherParams->getKey(), $cipherParams->getIv());

        $this->assertData((string)$encryptedDataStream, (string)$encryptingStream);
    }

    public function testDecryptingStartingWithGivenPosition()
    {
        $mediaType = self::TYPE_VIDEO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $originalDataStream = $this->getOriginal($mediaType);
        $sidecarStream = $this->getSidecar($mediaType);


        $sidecarValidationStream = new SidecarValidationStream($encryptedDataStream, $cipherParams->getMacKey(), 64 * 1024, $sidecarStream);
        $decryptingStream = new DecryptingStream($sidecarValidationStream, $cipherParams->getKey());
        $decryptingStream->seek(64 * 1024, SEEK_CUR);
        $originalDataStream->seek(64 * 1024, SEEK_CUR);
        $sidecarStream->seek(10, SEEK_CUR);

        $this->assertData(
            $originalDataStream->read(64 * 1024),
            $decryptingStream->read(64 * 1024),
        );
    }
}