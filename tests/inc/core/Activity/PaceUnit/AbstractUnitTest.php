<?php

namespace Runalyze\Activity\PaceUnit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
class AbstractUnitTest extends TestCase
{
    #[DataProvider('provideTimeFormatValues')]
    public function testIsDecimalFormatIsInverseOfIsTimeFormat(bool $isTimeFormat, bool $expectedIsDecimalFormat): void
    {
        $Unit = $this->createUnitWithTimeFormat($isTimeFormat);

        $this->assertSame($expectedIsDecimalFormat, $Unit->isDecimalFormat());
    }

    /**
     * @return array<string, bool[]>
     */
    public static function provideTimeFormatValues(): array
    {
        return [
            'time format is true, decimal format must be false' => [true, false],
            'time format is false, decimal format must be true' => [false, true],
        ];
    }

    private function createUnitWithTimeFormat(bool $isTimeFormat): AbstractUnit
    {
        /** @var AbstractUnit&\PHPUnit\Framework\MockObject\MockObject $Unit */
        $Unit = $this->getMockForAbstractClass(AbstractUnit::class);
        $Unit->method('isTimeFormat')
            ->willReturn($isTimeFormat);

        return $Unit;
    }
}
