<?php

namespace Runalyze\Tests\Parser\Activity\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\ContinuousData;
use Runalyze\Parser\Activity\Common\Data\Merge\ContinuousDataMerger;

class ContinuousDataMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContinuousData
     */
    protected $FirstData;

    /**
     * @var ContinuousData
     */
    protected $SecondData;

    protected function setUp(): void
    {
        $this->FirstData = new ContinuousData();
        $this->SecondData = new ContinuousData();
    }

    public function testThatMergeWorksWithEmptyObjects(): void
    {
        (new ContinuousDataMerger($this->FirstData, $this->SecondData))->merge();

        foreach ($this->FirstData->getPropertyNamesOfArrays() as $property) {
            $this->assertEmpty(
                $this->FirstData->{$property},
                sprintf('Property "%s" should remain empty after merging two empty objects.', $property)
            );
        }
    }

    public function testWithDifferentArraySizes(): void
    {
        $this->FirstData->Time = [1, 2, 3];
        $this->SecondData->Distance = [1, 2, 3, 4];

        $this->expectException(\RuntimeException::class);

        (new ContinuousDataMerger($this->FirstData, $this->SecondData))->merge(true);
    }

    public function testWithEmptySecondObject(): void
    {
        $this->FirstData->Time = [1, 2, 3];

        (new ContinuousDataMerger($this->FirstData, $this->SecondData))->merge(true);

        $this->assertEquals([1, 2, 3], $this->FirstData->Time);
    }

    public function testWithEmptyFirstObject(): void
    {
        $this->SecondData->Time = [1, 2, 3];

        (new ContinuousDataMerger($this->FirstData, $this->SecondData))->merge(true);

        $this->assertEquals([1, 2, 3], $this->FirstData->Time);
        $this->assertFalse($this->FirstData->IsAltitudeDataBarometric);
    }

    public function testThatBarometricAltitudeFromFirstObjectIsPreferred(): void
    {
        $this->FirstData->Altitude = [120, 125, 120];
        $this->FirstData->IsAltitudeDataBarometric = true;

        $this->SecondData->Altitude = [121, 124, 122];

        (new ContinuousDataMerger($this->FirstData, $this->SecondData))->merge(true);

        $this->assertEquals([120, 125, 120], $this->FirstData->Altitude);
        $this->assertTrue($this->FirstData->IsAltitudeDataBarometric);
    }

    public function testThatBarometricAltitudeFromSecondObjectIsPreferred(): void
    {
        $this->FirstData->Altitude = [120, 125, 120];
        $this->FirstData->IsAltitudeDataBarometric = false;

        $this->SecondData->Altitude = [121, 124, 122];
        $this->SecondData->IsAltitudeDataBarometric = true;

        (new ContinuousDataMerger($this->FirstData, $this->SecondData))->merge(true);

        $this->assertEquals([121, 124, 122], $this->FirstData->Altitude);
        $this->assertTrue($this->FirstData->IsAltitudeDataBarometric);
    }
}
