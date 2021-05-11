<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Tests;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Sunnylion\TestCipher\Stream\DecryptingStream;

class DecryptingStreamTest extends AbstractStreamTest
{
    /**
     * @dataProvider mediaTypeDataProvider
     */
    public function testToString(string $mediaType)
    {
        $cipherParams = $this->getCipherParams($mediaType);
        $originalDataStream = $this->getOriginal($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());

        $decryptingStream = new DecryptingStream($encryptedDataStream, $cipherParams->getKey());

        $this->assertData((string)$originalDataStream, (string)$decryptingStream);
    }

    /**
     * @dataProvider mediaTypeDataProvider
     */
    public function testShouldReadStartingWithPosition(string $mediaType)
    {
        $cipherParams = $this->getCipherParams($mediaType);
        $originalDataStream = $this->getOriginal($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());

        $decryptingStream = new DecryptingStream($encryptedDataStream, $cipherParams->getKey());

        $decryptingStream->seek(155);
        $originalDataStream->seek(155);
        $this->assertEquals($decryptingStream->read(83), $originalDataStream->read(83));
    }


    public function testShouldTellAfterSeek()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());

        $decryptingStream = new DecryptingStream($encryptedDataStream, $cipherParams->getKey());

        $decryptingStream->seek(138);
        $this->assertEquals(138, $decryptingStream->tell());
    }

    public function testShouldTellAfterRead()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());

        $decryptingStream = new DecryptingStream($encryptedDataStream, $cipherParams->getKey());

        $decryptingStream->read(234);
        $this->assertEquals(234, $decryptingStream->tell());
    }

    public function testShouldSuccessWhenLastReadChunkMatchesEndOfUnderlyingStream()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $originalDataStream = $this->getOriginal($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $lastOriginalBlock = substr((string)$originalDataStream, -self::BLOCK_SIZE);


        $decryptingStream = new DecryptingStream($encryptedDataStream, $cipherParams->getKey());

        $decryptingStream->seek($originalDataStream->getSize() - 3 * self::BLOCK_SIZE);
        $decryptingStream->read(self::BLOCK_SIZE);
        $decryptingStream->read(self::BLOCK_SIZE);
        $lastDecryptedBlock = $decryptingStream->read(self::BLOCK_SIZE);

        $this->assertEquals($lastOriginalBlock, $lastDecryptedBlock);
    }

    public function testShouldDecryptIv()
    {
        $cipherParams = $this->getCipherParams(self::TYPE_AUDIO);
        $decryptingStream = new DecryptingStream(Utils::streamFor($cipherParams->getIv()), $cipherParams->getKey());

        $this->assertEquals('', (string)$decryptingStream);
    }

    public function testShouldNotBeWritable()
    {
        $cipherParams = $this->getCipherParams(self::TYPE_AUDIO);
        $decryptingStream = new DecryptingStream(Utils::streamFor(), $cipherParams->getKey());

        $this->assertFalse($decryptingStream->isWritable());
    }

    public function testShouldNotAcceptWrongKey()
    {
        $this->expectException(InvalidArgumentException::class);

        new DecryptingStream(Utils::streamFor(), 'aaaa');
    }
}