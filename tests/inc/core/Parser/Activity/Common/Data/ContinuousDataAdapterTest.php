<?php

namespace Runalyze\Tests\Parser\Activity\Data;

use Runalyze\Parser\Activity\Common\Data\ContinuousData;
use Runalyze\Parser\Activity\Common\Data\ContinuousDataAdapter;

class ContinuousDataAdapterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContinuousData
     */
    protected $Data;

    /**
     * @var ContinuousDataAdapter
     */
    protected $Adapter;

    protected function setUp(): void
    {
        $this->Data = new ContinuousData();
        $this->Adapter = new ContinuousDataAdapter($this->Data);
    }

    public function testDistanceCalculationForEmptyData(): void
    {
        $this->Adapter->calculateDistancesIfRequired();

        $this->assertEmpty($this->Data->Distance);
    }

    public function testDistanceCalculationDoesNotOverwrite(): void
    {
        $this->Data->Distance = [0.0, 1.0, 2.0];
        $this->Data->Latitude = [49.77, 49.78, 49.79];
        $this->Data->Longitude = [7.78, 7.76, 7.75];

        $this->Adapter->calculateDistancesIfRequired();

        $this->assertEquals([0.0, 1.0, 2.0], $this->Data->Distance);
    }

    public function testSimpleDistanceCalculation(): void
    {
        $this->Data->Latitude = [49.77, 49.78, 49.79];
        $this->Data->Longitude = [7.78, 7.76, 7.75];

        $this->Adapter->calculateDistancesIfRequired();

        $this->assertEqualsWithDelta(0.0, $this->Data->Distance[0], 0.001);
        $this->assertEqualsWithDelta(1.816, $this->Data->Distance[1], 0.001);
        $this->assertEqualsWithDelta(3.140, $this->Data->Distance[2], 0.001);
    }

    public function testClearingEmptyArraysIfAllArraysAreAlreadyEmpty(): void
    {
        $this->assertEmpty($this->Data->Distance);
        $this->assertEmpty($this->Data->HeartRate);
        $this->assertEmpty($this->Data->Altitude);

        $this->Adapter->clearEmptyArrays();

        $this->assertEmpty($this->Data->Distance);
        $this->assertEmpty($this->Data->HeartRate);
        $this->assertEmpty($this->Data->Altitude);
    }

    public function testClearingEmptyArrays(): void
    {
        $this->Data->Distance = [0.0, 1.0, 2.0, 3.0];
        $this->Data->HeartRate = [null, 120, 140, null];
        $this->Data->Altitude = [null, null, null, null];

        $this->Adapter->clearEmptyArrays();

        $this->assertNotEmpty($this->Data->Distance);
        $this->assertNotEmpty($this->Data->HeartRate);
        $this->assertEmpty($this->Data->Altitude);
    }

    public function testFilteringUnwantedZeros(): void
    {
        $this->Data->HeartRate = [0, 120, 140, 0];

        $this->Adapter->filterUnwantedZeros();

        $this->assertEquals([null, 120, 140, null], $this->Data->HeartRate);
    }

    public function testClearingEmptyArraysAfterFilteringUnwantedZeros(): void
    {
        $this->Data->HeartRate = [0, 0, 0, 0];

        $this->Adapter->filterUnwantedZeros();
        $this->Adapter->clearEmptyArrays();

        $this->assertEmpty($this->Data->HeartRate);
    }

    public function testNullValuesInMiddleAndEndAreReplacedByLastKnownValue(): void
    {
        $values = [null, null, 100, 101, null, null, 104, null];
        $this->Adapter->correctArraySurroundedNulls($values);

        $this->assertSame([100, 100, 100, 101, 101, 101, 104, 104], $values);
    }

    public function testArrayContainingOnlyNullValuesIsNotModified(): void
    {
        $values = [null, null, null];
        $this->Adapter->correctArraySurroundedNulls($values);

        $this->assertSame([null, null, null], $values);
    }

    public function testArrayWithoutNullValuesRemainsUnchanged(): void
    {
        $values = [100, 101, 102];
        $this->Adapter->correctArraySurroundedNulls($values);

        $this->assertSame([100, 101, 102], $values);
    }

    public function testLeadingNullValuesAreReplacedByFirstKnownValue(): void
    {
        $values = [null, null, 100, 101];
        $this->Adapter->correctArraySurroundedNulls($values);

        $this->assertSame([100, 100, 100, 101], $values);
    }

    public function testMidNullValuesAreReplacedByPreviousValue(): void
    {
        $values = [150, 151, null, null, 154];
        $this->Adapter->correctArraySurroundedNulls($values);

        $this->assertSame([150, 151, 151, 151, 154], $values);
    }
}
