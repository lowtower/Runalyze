<?php

namespace Runalyze\Tests\Profile\Mapping;

use Runalyze\Profile\Mapping\FitSdkExercise;

class FitSdkExerciseTest extends \PHPUnit\Framework\TestCase
{
    protected $underTest;

    protected function setUp(): void
    {
        $this->underTest = new FitSdkExercise();
    }

    public function testFullMap1(): void
    {
        $this->assertEquals('curl, bench_press', $this->underTest->getFullMapping('7,65534,0', null));
    }

    public function testFullMap2(): void
    {
        $this->assertEquals('triceps_extension, ez_bar_pullover', $this->underTest->getFullMapping('30,21,65534', '65535,8,65535'));
    }

    public function testFullMapAllMultiple(): void
    {
        $this->assertEquals('jumping_jacks', $this->underTest->getFullMapping('2,2,2', '12,12,12'));
    }

    public function testFullMapTwoMultiple(): void
    {
        $this->assertEquals('triceps_extension, ez_bar_pullover', $this->underTest->getFullMapping('30,21,30', '65535,8,65535'));
    }

    public function testFullMapWrongSub(): void
    {
        $this->assertEquals('triceps_extension, pull_up', $this->underTest->getFullMapping('30,21,65534', '65535'));
    }

    public function testCategory(): void
    {
        $this->assertEquals('flye', $this->underTest->getMapping(9, null));
        $this->assertEquals('flye', $this->underTest->getMapping(9));
    }

    public function testFailedCategory(): void
    {
        $this->assertEquals(null, $this->underTest->getMapping(666, null));
    }

    public function testUnknownCategory(): void
    {
        $this->assertEquals(null, $this->underTest->getMapping(65534));
    }

    public function testCategoryWithSub1(): void
    {
        $this->assertEquals('single_leg_decline_push_up', $this->underTest->getMapping(1, 11));
    }

    public function testCategoryWithSub30(): void
    {
        $this->assertEquals('weighted_dip', $this->underTest->getMapping(30, 40));
    }

    public function testCategoryWithSubNotExists(): void
    {
        // use only exercise
        $this->assertEquals('calf_raise', $this->underTest->getMapping(1, 99));
    }

    public function testCategoryWithSubUnused(): void
    {
        // use only exercise
        $this->assertEquals('sit_up', $this->underTest->getMapping(27, 65535));
    }
}
