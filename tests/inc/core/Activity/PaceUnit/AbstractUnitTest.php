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
        return new class($isTimeFormat) extends AbstractUnit {
            public function __construct(
                private readonly bool $isTimeFormat
            ) {
            }

            public function unit(): int
            {
                return 0;
            }

            public function appendix(): string
            {
                return '';
            }

            public function isTimeFormat(): bool
            {
                return $this->isTimeFormat;
            }

            public function factorForUnit(): float
            {
                return 0.0;
            }

            public function dividendForUnit(): float
            {
                return 0.0;
            }

            public function rawValue(float $secondsPerKm): float
            {
                return 0.0;
            }

            public function format(float $secondsPerKm): string
            {
                return '';
            }

            public function compare(float $firstPaceInSecondsPerKm, float $secondPaceInSecondsPerKm): float
            {
                return 0.0;
            }
        };
    }
}
