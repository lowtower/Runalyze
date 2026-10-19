<?php

declare(strict_types=1);

namespace Runalyze\Tests\Util;

use PHPUnit\Framework\TestCase;
use Runalyze\Util\ServerParams;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ServerParamsTest extends TestCase
{
    private ServerParams $ServerParams;

    protected function setUp(): void
    {
        $this->ServerParams = new ServerParams();
    }

    public function testTransformStringToBytesReturnsNullForAnEmptyString(): void
    {
        $this->assertNull($this->ServerParams->transformStringToBytes(''));
    }

    public function testTransformStringToBytesReturnsPhpIntMaxForNegativeOne(): void
    {
        $this->assertSame(PHP_INT_MAX, $this->ServerParams->transformStringToBytes('-1'));
    }

    public function testTransformStringToBytesWithoutSuffixReturnsThePlainNumber(): void
    {
        $this->assertSame(512, $this->ServerParams->transformStringToBytes('512'));
    }

    public function testTransformStringToBytesWithKiloSuffix(): void
    {
        $this->assertSame(2 * 1024, $this->ServerParams->transformStringToBytes('2K'));
    }

    public function testTransformStringToBytesWithMegaSuffix(): void
    {
        $this->assertSame(8 * 1024 * 1024, $this->ServerParams->transformStringToBytes('8M'));
    }

    public function testTransformStringToBytesWithGigaSuffix(): void
    {
        $this->assertSame(1024 * 1024 * 1024, $this->ServerParams->transformStringToBytes('1G'));
    }

    public function testTransformStringToBytesIsCaseInsensitiveForTheSuffix(): void
    {
        $this->assertSame(4 * 1024, $this->ServerParams->transformStringToBytes('4k'));
    }

    public function testTransformStringToBytesAcceptsALeadingPlusSign(): void
    {
        $this->assertSame(16 * 1024, $this->ServerParams->transformStringToBytes('+16K'));
    }

    public function testGetPostMaxSizeInBytesReturnsANonNegativeIntOrNull(): void
    {
        $result = $this->ServerParams->getPostMaxSizeInBytes();

        $this->assertTrue($result === null || $result >= 0);
    }

    public function testGetUploadMaxFilesizeReturnsANonNegativeIntOrNull(): void
    {
        $result = $this->ServerParams->getUploadMaxFilesize();

        $this->assertTrue($result === null || $result >= 0);
    }

    public function testGetNormalizedIniPostMaxSizeReturnsAnUppercaseTrimmedString(): void
    {
        $value = $this->ServerParams->getNormalizedIniPostMaxSize();

        $this->assertSame(strtoupper(trim($value)), $value);
    }

    public function testGetNormalizedIniUploadMaxFilesizeReturnsAnUppercaseTrimmedString(): void
    {
        $value = $this->ServerParams->getNormalizedIniUploadMaxFilesize();

        $this->assertSame(strtoupper(trim($value)), $value);
    }
}
