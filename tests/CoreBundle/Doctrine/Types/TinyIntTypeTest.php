<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType;

#[\PHPUnit\Framework\Attributes\Group('default')]
class TinyIntTypeTest extends TestCase
{
    protected TinyIntType $Type;

    protected AbstractPlatform&Stub $PlatformMock;

    protected function setUp(): void
    {
        $type = TinyIntType::getType(TinyIntType::TINYINT);
        $this->assertInstanceOf(TinyIntType::class, $type);

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

    public static function mysqlFamilyPlatforms(): iterable
    {
        yield 'MySQL' => [new MySQLPlatform()];
        yield 'MariaDB' => [new MariaDBPlatform()];
    }

    #[DataProvider('mysqlFamilyPlatforms')]
    public function testSqlDeclarationIsTinyIntOnMysqlAndMariaDb(AbstractPlatform $platform): void
    {
        $this->assertSame('TINYINT', $this->Type->getSQLDeclaration([], $platform));
    }

    #[DataProvider('mysqlFamilyPlatforms')]
    public function testSqlDeclarationKeepsUnsignedOnMysqlAndMariaDb(AbstractPlatform $platform): void
    {
        $this->assertSame('TINYINT UNSIGNED', $this->Type->getSQLDeclaration([
            'unsigned' => true,
        ], $platform));
    }

    public function testSqlDeclarationIsNotChangedOnOtherPlatforms(): void
    {
        $declaration = $this->Type->getSQLDeclaration([], new SQLitePlatform());

        $this->assertStringNotContainsString('TINYINT', $declaration);
        $this->assertStringContainsString('SMALLINT', $declaration);
    }
}
