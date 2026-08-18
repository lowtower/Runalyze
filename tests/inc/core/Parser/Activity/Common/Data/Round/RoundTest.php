<?php

namespace Runalyze\Tests\Parser\Activity\Common\Data\Round;

use Runalyze\Parser\Activity\Common\Data\Round\Round;

class RoundTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleRound()
    {
        $round = new Round(1.0, 265);

        $this->assertEquals(1.0, $round->getDistance());
        $this->assertEquals(265, $round->getDuration());
        $this->assertTrue($round->isActive());
    }

    public function testRestingRound()
    {
        $round = new Round(1.0, 265, false);

        $this->assertFalse($round->isActive());
    }

    public function testSetter()
    {
        $round = new Round(1.0, 105, false);
        $round->setDistance(0.5);
        $round->setDuration(104);
        $round->setActive();

        $this->assertEquals(0.5, $round->getDistance());
        $this->assertEquals(104, $round->getDuration());
        $this->assertTrue($round->isActive());
    }

    /**
     * @dataProvider getProviderForComparison
     */
    public function testComparison(bool $expectedComparison, float $distanceFirst, int $durationFirst, bool $isActiveFirst, float $distanceSecond, int $durationSecond, bool $isActiveSecond)
    {
        $this->assertEquals(
            $expectedComparison,
            (new Round($distanceFirst, $durationFirst, $isActiveFirst))
                ->isEqualTo(new Round($distanceSecond, $durationSecond, $isActiveSecond))
        );
    }

    /**
     * @return array<int, bool[]|float[]|int[]>
     */
    public function getProviderForComparison(): array
    {
        return [
            [true, 1.0, 265, false, 1.0, 265, false],
            [false, 1.0, 265, true, 1.0, 265, false],
            [false, 1.0, 265, false, 1.01, 265, false],
            [false, 1.0, 264, false, 1.0, 265, false]
        ];
    }
}
