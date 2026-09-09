<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType;

class TinyIntTypeTest extends \PHPUnit\Framework\TestCase
{
    protected TinyIntType $Type;

    protected AbstractPlatform $PlatformMock;

    protected function setUp(): void
    {
        /** @var TinyIntType $type */
        $type = TinyIntType::getType(TinyIntType::TINYINT);
        $this->Type = $type;
        $this->PlatformMock = $this->getMockForAbstractClass(AbstractPlatform::class);
    }

    public function testNull()
    {
        $this->assertNull($this->Type->convertToPHPValue(null, $this->PlatformMock));
    }

    public function testSimpleData()
    {
        $this->assertEquals(17, $this->Type->convertToPHPValue('17', $this->PlatformMock));
    }
}
