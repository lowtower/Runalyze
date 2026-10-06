<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType;

class TinyIntTypeTest extends \PHPUnit\Framework\TestCase
{
    protected TinyIntType $Type;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected \PHPUnit\Framework\MockObject\Stub $PlatformMock;

    protected function setUp(): void
    {
        /** @var TinyIntType $type */
        $type = TinyIntType::getType(TinyIntType::TINYINT);
        $this->Type = $type;
        $this->PlatformMock = $this->createStub(AbstractPlatform::class);
    }

    public function testNull(): void
    {
        $this->assertNull($this->Type->convertToPHPValue(null, $this->PlatformMock));
    }

    public function testSimpleData(): void
    {
        $this->assertEquals(17, $this->Type->convertToPHPValue('17', $this->PlatformMock));
    }
}
