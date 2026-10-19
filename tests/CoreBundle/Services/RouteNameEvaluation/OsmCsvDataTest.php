<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Runalyze\Service\RouteNameEvaluation\OsmCsvData;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class OsmCsvDataTest extends \PHPUnit\Framework\TestCase
{
    private function buildRow(
        string $type = 'node',
        string $name = 'TestName',
        string $nameDe = '',
        string $place = '',
        string $trafficSign = '',
        string $tourism = '',
        string $landuse = '',
        string $natural = '',
        string $elevation = '',
        string $hiking = '',
        string $sacScale = '',
        string $amenity = ''
    ): string {
        return implode(OsmCsvData::SEPERATOR, [
            $type, $name, $nameDe, $place, $trafficSign, $tourism, $landuse, $natural, $elevation, $hiking, $sacScale, $amenity,
        ]);
    }

    public function testConstructorThrowsForTooFewFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OsmCsvData('node|OnlyTwoFields');
    }

    public function testConstructorThrowsForTooManyFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OsmCsvData(implode(OsmCsvData::SEPERATOR, array_fill(0, 13, 'x')));
    }

    public function testConstructorThrowsInvalidArgumentExceptionWhenNoCategoryTagMatchesAnyRecognizedField(): void
    {
        $row = $this->buildRow(name: 'NoCategoryName');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No recognized category tag/');

        new OsmCsvData($row);
    }

    public function testNameWithDashesIsCleaned(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(name: 'Grubistein-Vor-gipfel', natural: 'peak'));

        $this->assertSame('Grubistein Vor gipfel', $osmCsvData->name);
    }

    public function testNameDeFallsBackToNullWhenEmpty(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(nameDe: '', natural: 'peak'));

        $this->assertNull($osmCsvData->nameDe);
    }

    public function testNameDeIsSetAndCleanedWhenPresent(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(nameDe: 'Schuh-Info', natural: 'peak'));

        $this->assertSame('Schuh Info', $osmCsvData->nameDe);
    }

    public function testCategoryPeakForNaturalPeak(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'peak'));

        $this->assertSame(OsmCsvData::$CAT_PEAK, $osmCsvData->category);
    }

    public function testCategoryPeakForNaturalSaddle(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'saddle'));

        $this->assertSame(OsmCsvData::$CAT_PEAK, $osmCsvData->category);
    }

    public function testCategoryPlaceForPlaceField(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(place: 'town'));

        $this->assertSame(OsmCsvData::$CAT_PLACE, $osmCsvData->category);
    }

    public function testCategoryPlaceForTrafficSign(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(trafficSign: 'city_limit'));

        $this->assertSame(OsmCsvData::$CAT_PLACE, $osmCsvData->category);
    }

    public function testCategoryPlaceForLanduseForest(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(landuse: 'forest'));

        $this->assertSame(OsmCsvData::$CAT_PLACE, $osmCsvData->category);
    }

    public function testCategoryPlaceForNaturalWood(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'wood'));

        $this->assertSame(OsmCsvData::$CAT_PLACE, $osmCsvData->category);
    }

    public function testCategoryPlaceForNaturalWater(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'water'));

        $this->assertSame(OsmCsvData::$CAT_PLACE, $osmCsvData->category);
    }

    public function testCategoryHutForTourismAlpineHut(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(tourism: 'alpine_hut'));

        $this->assertSame(OsmCsvData::$CAT_HUT, $osmCsvData->category);
    }

    public function testCategoryHutForTourismWildernessHut(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(tourism: 'wilderness_hut'));

        $this->assertSame(OsmCsvData::$CAT_HUT, $osmCsvData->category);
    }

    public function testCategoryHutForAmenityRestaurant(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(amenity: 'restaurant'));

        $this->assertSame(OsmCsvData::$CAT_HUT, $osmCsvData->category);
    }

    public function testCategoryWayForHikingYes(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(hiking: 'yes'));

        $this->assertSame(OsmCsvData::$CAT_WAY, $osmCsvData->category);
    }

    public function testCategoryWayForSacScaleWhenHikingIsAbsent(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(sacScale: 'mountain_hiking'));

        $this->assertSame(OsmCsvData::$CAT_WAY, $osmCsvData->category);
    }

    public function testCategoryInfoForTourismInformation(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(tourism: 'information'));

        $this->assertSame(OsmCsvData::$CAT_INFO, $osmCsvData->category);
    }

    public function testCategoryWayForOverpassElementTypeWay(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(type: 'way'));

        $this->assertSame(OsmCsvData::$CAT_WAY, $osmCsvData->category);
    }

    public function testTypeRelationDoesNotTriggerWayCategoryOnItsOwn(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(type: 'relation', natural: 'water'));

        $this->assertSame(OsmCsvData::$CAT_PLACE, $osmCsvData->category);
    }

    public function testNaturalTakesPriorityOverPlaceAndPlaceIsNeverActuallyRead(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(place: 'town', natural: 'peak'));

        $this->assertSame(OsmCsvData::$CAT_PEAK, $osmCsvData->category);
        $this->assertNull($osmCsvData->place);
    }

    public function testTourismTakesPriorityOverLanduseAndLanduseIsNeverActuallyRead(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(tourism: 'information', landuse: 'forest'));

        $this->assertSame(OsmCsvData::$CAT_INFO, $osmCsvData->category);
        $this->assertNull($osmCsvData->landuse);
    }

    public function testHikingTakesPriorityOverSacScaleAndSacScaleIsNeverActuallyRead(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(hiking: 'yes', sacScale: 'mountain_hiking'));

        $this->assertSame(OsmCsvData::$CAT_WAY, $osmCsvData->category);
    }

    public function testElevationIsNullWhenEmpty(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'peak', elevation: ''));

        $this->assertNull($osmCsvData->elevation);
    }

    public function testElevationIsStoredAsRawStringWhenPresent(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'peak', elevation: '1985'));

        $this->assertSame('1985', $osmCsvData->elevation);
    }

    public function testElevationOfLiteralZeroIsStoredAsNullNotZero(): void
    {
        $osmCsvData = new OsmCsvData($this->buildRow(natural: 'peak', elevation: '0'));

        $this->assertNull($osmCsvData->elevation);
    }

    public function testOrderRanksHigherElevationPeakBeforeLowerElevationPeak(): void
    {
        $higher = new OsmCsvData($this->buildRow(natural: 'peak', elevation: '1985'));
        $lower = new OsmCsvData($this->buildRow(natural: 'peak', elevation: '1453'));

        $this->assertLessThan(0, $higher->order <=> $lower->order);
    }

    public function testOrderPlacesPeakBeforeAnyNonPeakCategory(): void
    {
        $peak = new OsmCsvData($this->buildRow(natural: 'peak'));
        $place = new OsmCsvData($this->buildRow(place: 'town'));

        $this->assertLessThan(0, $peak->order <=> $place->order);
    }

    public function testOrderRanksKnownPlaceHierarchyCityBeforeTown(): void
    {
        $city = new OsmCsvData($this->buildRow(place: 'city'));
        $town = new OsmCsvData($this->buildRow(place: 'town'));

        $this->assertLessThan(0, $city->order <=> $town->order);
        $this->assertStringContainsString('pl1.', $city->order);
        $this->assertStringContainsString('pl5.', $town->order);
    }

    public function testOrderRanksUnknownPlaceValueLastAmongPlaces(): void
    {
        $known = new OsmCsvData($this->buildRow(place: 'hamlet'));
        $unknown = new OsmCsvData($this->buildRow(place: 'region'));

        $this->assertLessThan(0, $known->order <=> $unknown->order);
        $this->assertStringContainsString('pl7.', $known->order);
        $this->assertStringContainsString('pl9.', $unknown->order);
    }

    public function testOrderMarksWaterEntriesDistinctlyFromOtherPlaces(): void
    {
        $water = new OsmCsvData($this->buildRow(natural: 'water'));

        $this->assertStringContainsString('.w1.', $water->order);
    }

    public function testOrderMarksInfoCategoryDistinctly(): void
    {
        $info = new OsmCsvData($this->buildRow(tourism: 'information'));

        $this->assertStringEndsWith('in1', $info->order);
    }
}
