<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;

#[\PHPUnit\Framework\Attributes\Group('default')]
class SwimdataTest extends TestCase
{
    private Swimdata $swimdata;

    protected function setUp(): void
    {
        $this->swimdata = new Swimdata();
    }

    public function testStrokeDefaultsToNull(): void
    {
        $this->assertNull($this->swimdata->getStroke());
        $this->assertFalse($this->swimdata->hasStrokes());
    }

    public function testStroketypeDefaultsToNull(): void
    {
        $this->assertNull($this->swimdata->getStroketype());
        $this->assertFalse($this->swimdata->hasStrokeTypes());
    }

    public function testPoolLengthDefaultsToZero(): void
    {
        $this->assertSame(0, $this->swimdata->getPoolLength());
    }

    public function testGetAccountDefaultsToNull(): void
    {
        $this->assertNull($this->swimdata->getAccount());
    }

    public function testGetActivityDefaultsToNull(): void
    {
        $this->assertNull($this->swimdata->getActivity());
    }

    public function testSetStrokeGetStroke(): void
    {
        $this->swimdata->setStroke([1, 2, 1, 2]);

        $this->assertSame([1, 2, 1, 2], $this->swimdata->getStroke());
        $this->assertTrue($this->swimdata->hasStrokes());
    }

    public function testSetStroketypeGetStroketype(): void
    {
        $this->swimdata->setStroketype([1, 1, 2, 2]);

        $this->assertSame([1, 1, 2, 2], $this->swimdata->getStroketype());
        $this->assertTrue($this->swimdata->hasStrokeTypes());
    }

    public function testSetPoolLengthGetPoolLength(): void
    {
        $this->swimdata->setPoolLength(50);

        $this->assertSame(50, $this->swimdata->getPoolLength());
    }

    public function testSetAccountGetAccount(): void
    {
        $account = new Account();
        $this->swimdata->setAccount($account);

        $this->assertSame($account, $this->swimdata->getAccount());
    }

    public function testSetActivityGetActivity(): void
    {
        $activity = new Training();
        $this->swimdata->setActivity($activity);

        $this->assertSame($activity, $this->swimdata->getActivity());
    }

    public function testIsEmptyIsTrueForFreshEntity(): void
    {
        $this->assertTrue($this->swimdata->isEmpty());
    }

    public function testIsEmptyIsFalseWhenStrokeIsSet(): void
    {
        $this->swimdata->setStroke([1, 2]);

        $this->assertFalse($this->swimdata->isEmpty());
    }

    public function testIsEmptyIsFalseWhenStroketypeIsSet(): void
    {
        $this->swimdata->setStroketype([1, 2]);

        $this->assertFalse($this->swimdata->isEmpty());
    }

    public function testIsEmptyIsTrueWhenStrokeIsSetToAnEmptyArray(): void
    {
        $this->swimdata->setStroke([]);

        $this->assertTrue($this->swimdata->isEmpty());
    }
}
