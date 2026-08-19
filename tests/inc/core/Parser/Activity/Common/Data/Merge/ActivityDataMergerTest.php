<?php

namespace Runalyze\Tests\Parser\Activity\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\ActivityData;
use Runalyze\Parser\Activity\Common\Data\Merge\ActivityDataMerger;

class ActivityDataMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ActivityData
     */
    protected $FirstData;

    /**
     * @var ActivityData
     */
    protected $SecondData;

    protected function setUp(): void
    {
        $this->FirstData = new ActivityData();
        $this->SecondData = new ActivityData();
    }

    /**
     * @group review-later
     * TODO: This test needs a deeper look later. It currently fails because the $activity might be null.
     */
    public function testThatMergeWorksWithEmptyObjects()
    {
        $merger = new ActivityDataMerger($this->FirstData, $this->SecondData);
        $result = $merger->merge();

        $this->assertInstanceOf(ActivityData::class, $result, 'merge() should return an instance of ActivityData');
    }

    public function testWithEmptySecondObject()
    {
        $this->FirstData->Duration = 321;

        (new ActivityDataMerger($this->FirstData, $this->SecondData))->merge();

        $this->assertEquals(321, $this->FirstData->Duration);
    }

    public function testWithEmptyFirstObject()
    {
        $this->FirstData->Duration = 321;

        (new ActivityDataMerger($this->FirstData, $this->SecondData))->merge();

        $this->assertEquals(321, $this->FirstData->Duration);
    }
}
