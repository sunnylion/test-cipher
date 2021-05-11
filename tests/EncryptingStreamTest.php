<?php
declare(strict_types=1);

namespace Sunnylion\TestCipher\Tests;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Sunnylion\TestCipher\Stream\EncryptingStream;

class EncryptingStreamTest extends AbstractStreamTest
{
    /**
     * @dataProvider mediaTypeDataProvider
     */
    public function testShouldStringify(string $mediaType)
    {
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $originalDataStream = $this->getOriginal($mediaType);

        $encryptingStream = new EncryptingStream($originalDataStream, $cipherParams->getKey(), $cipherParams->getIv());

        $this->assertData((string)$encryptedDataStream, (string)$encryptingStream);
    }

    public function testShouldReadProperly()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $originalDataStream = $this->getOriginal($mediaType);

        $encryptingStream = new EncryptingStream($originalDataStream, $cipherParams->getKey(), $cipherParams->getIv());

        $actualData = $encryptingStream->read(19) . $encryptingStream->read(11);

        $expectedData = $encryptedDataStream->read(11 + 19);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testShouldTellAfterReading()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $originalDataStream = $this->getOriginal($mediaType);

        $encryptingStream = new EncryptingStream($originalDataStream, $cipherParams->getKey(), $cipherParams->getIv());

        $encryptingStream->read(19);

        $this->assertEquals(19, $encryptingStream->tell());
    }

    public function testShouldReadAfterSeekingForZero()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptedDataStream = $this->getEncryptedWithLeadingIv($mediaType, $cipherParams->getIv());
        $originalDataStream = $this->getOriginal($mediaType);

        $encryptingStream = new EncryptingStream($originalDataStream, $cipherParams->getKey(), $cipherParams->getIv());
        $encryptingStream->read(19);
        $encryptingStream->seek(0);

        $actualData = $encryptingStream->read(37);
        $expectedData = $encryptedDataStream->read(37);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testShouldNotSeekUsingSeekCur()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptingStream = new EncryptingStream(Utils::streamFor(), $cipherParams->getKey(), $cipherParams->getIv());

        $this->expectException(InvalidArgumentException::class);

        $encryptingStream->seek(12, SEEK_CUR);
    }

    public function testShouldNotSeekUsingSeekEnd()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptingStream = new EncryptingStream(Utils::streamFor(), $cipherParams->getKey(), $cipherParams->getIv());

        $this->expectException(InvalidArgumentException::class);

        $encryptingStream->seek(12, SEEK_END);
    }

    public function testShouldNotSeekForNotZero()
    {
        $mediaType = self::TYPE_AUDIO;
        $cipherParams = $this->getCipherParams($mediaType);
        $encryptingStream = new EncryptingStream(Utils::streamFor(), $cipherParams->getKey(), $cipherParams->getIv());

        $this->expectException(InvalidArgumentException::class);

        $encryptingStream->seek(15, SEEK_SET);
    }

    public function testShouldGetSizeIfSizeOfSourceDataModBlockSizeIsZero()
    {
        $cipherParams = $this->getCipherParams(self::TYPE_AUDIO);
        $blockLengthString = substr(md5('a'), 0, self::BLOCK_SIZE);
        $sourceStream = Utils::streamFor($blockLengthString);
        $encryptingStream = new EncryptingStream($sourceStream, $cipherParams->getKey(), $cipherParams->getIv());

        $this->assertEquals(3 * self::BLOCK_SIZE, $encryptingStream->getSize());
    }

    public function testShouldGetSizeIfSizeOfSourceDataModBlockSizeIsNotZero()
    {
        $cipherParams = $this->getCipherParams(self::TYPE_AUDIO);
        $blockLengthPlusOneString = substr(md5('a'), 0, self::BLOCK_SIZE + 1);
        $sourceStream = Utils::streamFor($blockLengthPlusOneString);
        $encryptingStream = new EncryptingStream($sourceStream, $cipherParams->getKey(), $cipherParams->getIv());

        $this->assertEquals(2 * self::BLOCK_SIZE, $encryptingStream->getSize());
    }
}