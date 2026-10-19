<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Training;

final class TrainingTest extends \PHPUnit\Framework\TestCase
{
    protected Training $Activity;

    protected function setUp(): void
    {
        $this->Activity = new Training();
    }

    public function testCloningSplits(): void
    {
        $oldSplits = $this->Activity->getSplits();
        $this->Activity->setSplitsToClone();

        $this->assertNotSame($this->Activity->getSplits(), $oldSplits);
    }

    public function testSetWindDegNormalizesExactly360ToZero(): void
    {
        $training = new Training();

        $training->setWindDeg(360);

        $this->assertSame(0, $training->getWindDeg());
    }

    public function testSetWindDegLeavesValuesInsideValidRangeUnchanged(): void
    {
        $training = new Training();

        $training->setWindDeg(0);
        $this->assertSame(0, $training->getWindDeg());

        $training->setWindDeg(180);
        $this->assertSame(180, $training->getWindDeg());

        $training->setWindDeg(359);
        $this->assertSame(359, $training->getWindDeg());
    }

    public function testSetWindDegNormalizesValuesAboveOneFullRotation(): void
    {
        $training = new Training();

        $training->setWindDeg(720);
        $this->assertSame(0, $training->getWindDeg());

        $training->setWindDeg(365);
        $this->assertSame(5, $training->getWindDeg());
    }

    public function testSetWindDegNormalizesNegativeValuesIntoPositiveRange(): void
    {
        $training = new Training();

        $training->setWindDeg(-10);
        $this->assertSame(350, $training->getWindDeg());
    }

    public function testSetWindDegNormalizesValuesBelowOneFullNegativeRotation(): void
    {
        $training = new Training();

        $training->setWindDeg(-370);
        $this->assertSame(350, $training->getWindDeg());
    }

    public function testSetWindDegNormalizesExactlyNegative360ToZero(): void
    {
        $training = new Training();

        $training->setWindDeg(-360);

        $this->assertSame(0, $training->getWindDeg());
    }

    public function testSetWindDegAcceptsNull(): void
    {
        $training = new Training();

        $training->setWindDeg(null);

        $this->assertNull($training->getWindDeg());
    }

    public function testSetWindDegReturnsSelfForFluentChaining(): void
    {
        $training = new Training();

        $result = $training->setWindDeg(90);

        $this->assertSame($training, $result);
    }
}
