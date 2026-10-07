<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\TrackDataAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Model\Trackdata\Entity as EntityModel;
use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;

class TrackdataTest extends \PHPUnit\Framework\TestCase
{
    protected Trackdata $Data;

    protected function setUp(): void
    {
        $this->Data = new Trackdata();
    }

    public function testEmptyEntity(): void
    {
        $this->assertTrue($this->Data->isEmpty());
        $this->assertNull($this->Data->getTime());
        $this->assertNull($this->Data->getDistance());
        $this->assertInstanceOf(PauseCollection::class, $this->Data->getPauses());
        $this->assertTrue($this->Data->getPauses()->isEmpty());
    }

    public function testLazyArraysForEmptyEntity(): void
    {
        $this->assertFalse($this->Data->hasPace());
        $this->assertFalse($this->Data->hasStrideLength());
        $this->assertFalse($this->Data->hasVerticalRatio());

        $this->assertNull($this->Data->getPace());
        $this->assertNull($this->Data->getGradient());
        $this->assertNull($this->Data->getGradeAdjustedPace());
        $this->assertNull($this->Data->getStrideLength());
        $this->assertNull($this->Data->getVerticalRatio());
    }

    public function testThatPaceIsAlwaysUpToDate(): void
    {
        $this->assertFalse($this->Data->hasPace());
        $this->assertNull($this->Data->getPace());

        $this->Data->setDistance([0.0, 1.0, 2.0, 3.0]);
        $this->Data->setTime([0, 300, 660, 900]);

        $this->assertTrue($this->Data->hasPace());
        $this->assertEquals([300, 300, 360, 240], $this->Data->getPace());
    }

    public function testCalculationOfLazyArrays(): void
    {
        $this->Data->setTime([0, 300, 600, 900]);
        $this->Data->setDistance([0.0, 1.0, 2.0, 3.0]);
        $this->Data->setCadence([90, 90, 90, 90]);
        $this->Data->setVerticalOscillation([110, 110, 110, 110]);

        $this->Data->setActivity(new Training());
        $this->Data->getActivity()
            ->setRoute(new Route());
        $this->Data->getActivity()
            ->getRoute()
            ->setElevationsCorrected([0, 50, 100, 50]);

        $this->assertEquals([111, 111, 111, 111], $this->Data->getStrideLength());
        $this->assertEquals([99, 99, 99, 99], $this->Data->getVerticalRatio());
        $this->assertEquals([300, 300, 300, 300], $this->Data->getPace());
    }

    public function testCalculationOfGradientAndGradeAdjustedPace(): void
    {
        $this->Data->setTime(range(0, 900, 15));
        $this->Data->setDistance(range(0.0, 3.0, 0.05));

        $this->Data->setActivity(new Training());
        $this->Data->getActivity()
            ->setRoute(new Route());
        $this->Data->getActivity()
            ->getRoute()
            ->setElevationsCorrected(
                array_fill(0, 10, 0) +
                        array_fill(10, 20, 50) +
                        array_fill(30, 20, 100) +
                        array_fill(50, 11, 50)
            );

        $gradeAdjustedPace = $this->Data->getGradeAdjustedPace();
        $gradient = $this->Data->getGradient();

        $this->assertLessThan(300, $gradeAdjustedPace[10]);
        $this->assertLessThan(300, $gradeAdjustedPace[30]);
        $this->assertGreaterThan(300, $gradeAdjustedPace[50]);

        $this->assertEqualsWithDelta(5.0, $gradient[10], 0.5);
        $this->assertEqualsWithDelta(5.0, $gradient[30], 0.5);
        $this->assertEqualsWithDelta(-5.0, $gradient[50], 0.5);

        $this->assertEquals(array_fill(0, 61, 300), $this->Data->getPace());
    }

    private function getPrivateProperty(object $object, string $name): mixed
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($name);

        return $property->getValue($object);
    }

    #[DataProvider('provideSimpleFieldAccessors')]
    public function testSimpleFieldDefaultsToNull(string $hasMethod, string $getMethod, string $setMethod): void
    {
        $this->assertFalse($this->Data->{$hasMethod}());
        $this->assertNull($this->Data->{$getMethod}());
    }

    #[DataProvider('provideSimpleFieldAccessors')]
    public function testSimpleFieldSetGetHasRoundTrip(string $hasMethod, string $getMethod, string $setMethod): void
    {
        $values = [1, 2, 3];

        $this->Data->{$setMethod}($values);

        $this->assertTrue($this->Data->{$hasMethod}());
        $this->assertSame($values, $this->Data->{$getMethod}());
    }

    #[DataProvider('provideSimpleFieldAccessors')]
    public function testSimpleFieldCanBeResetBackToNull(string $hasMethod, string $getMethod, string $setMethod): void
    {
        $this->Data->{$setMethod}([1, 2, 3]);
        $this->Data->{$setMethod}(null);

        $this->assertFalse($this->Data->{$hasMethod}());
        $this->assertNull($this->Data->{$getMethod}());
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideSimpleFieldAccessors(): array
    {
        return [
            'heartrate' => ['hasHeartrate', 'getHeartrate', 'setHeartrate'],
            'power' => ['hasPower', 'getPower', 'setPower'],
            'temperature' => ['hasTemperature', 'getTemperature', 'setTemperature'],
            'groundcontact' => ['hasGroundcontact', 'getGroundcontact', 'setGroundcontact'],
            'groundcontactBalance' => ['hasGroundcontactBalance', 'getGroundcontactBalance', 'setGroundcontactBalance'],
            'smo20' => ['hasSmo20', 'getSmo20', 'setSmo20'],
            'smo21' => ['hasSmo21', 'getSmo21', 'setSmo21'],
            'thb0' => ['hasThb0', 'getThb0', 'setThb0'],
            'thb1' => ['hasThb1', 'getThb1', 'setThb1'],
            'impactGsLeft' => ['hasImpactGsLeft', 'getImpactGsLeft', 'setImpactGsLeft'],
            'impactGsRight' => ['hasImpactGsRight', 'getImpactGsRight', 'setImpactGsRight'],
            'brakingGsLeft' => ['hasBrakingGsLeft', 'getBrakingGsLeft', 'setBrakingGsLeft'],
            'brakingGsRight' => ['hasBrakingGsRight', 'getBrakingGsRight', 'setBrakingGsRight'],
            'footstrikeTypeLeft' => ['hasFootstrikeTypeLeft', 'getFootstrikeTypeLeft', 'setFootstrikeTypeLeft'],
            'footstrikeTypeRight' => ['hasFootstrikeTypeRight', 'getFootstrikeTypeRight', 'setFootstrikeTypeRight'],
            'pronationExcursionLeft' => ['hasPronationExcursionLeft', 'getPronationExcursionLeft', 'setPronationExcursionLeft'],
            'pronationExcursionRight' => ['hasPronationExcursionRight', 'getPronationExcursionRight', 'setPronationExcursionRight'],
            'performanceCondition' => ['hasPerformanceCondition', 'getPerformanceCondition', 'setPerformanceCondition'],
            'respirationRate' => ['hasRespirationRate', 'getRespirationRate', 'setRespirationRate'],
        ];
    }

    public function testSetCadenceInvalidatesCachedStrideLength(): void
    {
        $reflection = new \ReflectionObject($this->Data);
        $property = $reflection->getProperty('strideLength');
        $property->setValue($this->Data, [100.0, 100.0]);

        $this->Data->setCadence([90, 90]);

        $this->assertNull($this->getPrivateProperty($this->Data, 'strideLength'));
    }

    public function testSetVerticalOscillationInvalidatesCachedVerticalRatio(): void
    {
        $reflection = new \ReflectionObject($this->Data);
        $property = $reflection->getProperty('verticalRatio');
        $property->setValue($this->Data, [50.0, 50.0]);

        $this->Data->setVerticalOscillation([110, 110]);

        $this->assertNull($this->getPrivateProperty($this->Data, 'verticalRatio'));
    }

    public function testSetDistanceInvalidatesCachedPaceAndGradient(): void
    {
        $reflection = new \ReflectionObject($this->Data);
        $paceProperty = $reflection->getProperty('pace');
        $paceProperty->setValue($this->Data, [300, 300]);

        $gradientProperty = $reflection->getProperty('gradient');
        $gradientProperty->setValue($this->Data, [1.0, 2.0]);

        $this->Data->setDistance([0.0, 1.0]);

        $this->assertNull($this->getPrivateProperty($this->Data, 'pace'));
        $this->assertNull($this->getPrivateProperty($this->Data, 'gradient'));
    }

    public function testSetTimeInvalidatesCachedPaceOnly(): void
    {
        $reflection = new \ReflectionObject($this->Data);
        $paceProperty = $reflection->getProperty('pace');
        $paceProperty->setValue($this->Data, [300, 300]);

        $gradientProperty = $reflection->getProperty('gradient');
        $gradientProperty->setValue($this->Data, [1.0, 2.0]);

        $this->Data->setTime([0, 300]);

        $this->assertNull($this->getPrivateProperty($this->Data, 'pace'));
        $this->assertSame([1.0, 2.0], $this->getPrivateProperty($this->Data, 'gradient'));
    }

    public function testGetTotalDurationReturnsZeroWhenTimeIsNotSet(): void
    {
        $this->assertSame(0, $this->Data->getTotalDuration());
    }

    public function testGetTotalDurationReturnsLastTimeValue(): void
    {
        $this->Data->setTime([0, 300, 900]);

        $this->assertSame(900, $this->Data->getTotalDuration());
    }

    public function testGetTotalDistanceReturnsZeroWhenDistanceIsNotSet(): void
    {
        $this->assertSame(0.0, $this->Data->getTotalDistance());
    }

    public function testGetTotalDistanceReturnsLastDistanceValue(): void
    {
        $this->Data->setDistance([0.0, 1.5, 3.0]);

        $this->assertSame(3.0, $this->Data->getTotalDistance());
    }

    public function testHasTimeReturnsFalseForExplicitlyEmptyArray(): void
    {
        $this->Data->setTime([]);

        $this->assertFalse($this->Data->hasTime());
    }

    public function testHasTimeStillReturnsTrueForANonEmptyArray(): void
    {
        $this->Data->setTime([0, 300, 900]);

        $this->assertTrue($this->Data->hasTime());
    }

    public function testGetTotalDurationReturnsZeroWhenTimeIsSetToAnEmptyArray(): void
    {
        $this->Data->setTime([]);

        $this->assertSame(0, $this->Data->getTotalDuration());
    }

    public function testIsEmptyNowTreatsAnExplicitlyEmptyFieldAsEmpty(): void
    {
        $this->Data->setHeartrate([]);

        $this->assertTrue($this->Data->isEmpty());
    }

    public function testIsEmptyStillReturnsFalseWhenAFieldHasActualData(): void
    {
        $this->Data->setHeartrate([120, 130]);

        $this->assertFalse($this->Data->isEmpty());
    }

    public function testIsEmptyStillReturnsFalseWhenOnlyPausesAreNonEmpty(): void
    {
        $pauses = $this->createStub(PauseCollection::class);
        $pauses->method('isEmpty')
            ->willReturn(false);
        $this->Data->setPauses($pauses);

        $this->assertFalse($this->Data->isEmpty());
    }

    public function testIsEmptyReturnsTrueForACompletelyFreshEntity(): void
    {
        $this->assertTrue($this->Data->isEmpty());
    }

    public function testIsEmptyIsFalseWhenAnyFieldHasNonEmptyData(): void
    {
        $this->Data->setHeartrate([120, 130]);

        $this->assertFalse($this->Data->isEmpty());
    }

    public function testIsEmptyIsFalseWhenPausesAreNotEmpty(): void
    {
        $pauses = $this->createStub(PauseCollection::class);
        $pauses->method('isEmpty')
            ->willReturn(false);

        $this->Data->setPauses($pauses);

        $this->assertFalse($this->Data->isEmpty());
    }

    public function testSetAndGetPauses(): void
    {
        $pauses = new PauseCollection();

        $this->Data->setPauses($pauses);

        $this->assertSame($pauses, $this->Data->getPauses());
    }

    public function testGetActivityDefaultsToNull(): void
    {
        $this->assertNull($this->Data->getActivity());
    }

    public function testSetAndGetActivity(): void
    {
        $activity = new Training();

        $this->Data->setActivity($activity);

        $this->assertSame($activity, $this->Data->getActivity());
    }

    public function testGetAccountDefaultsToNull(): void
    {
        $this->assertNull($this->Data->getAccount());
    }

    public function testSetAndGetAccount(): void
    {
        $account = $this->createStub(Account::class);

        $this->Data->setAccount($account);

        $this->assertSame($account, $this->Data->getAccount());
    }

    public function testLockDefaultsToFalse(): void
    {
        $this->assertFalse($this->Data->getLock());
        $this->assertFalse($this->Data->isLocked());
    }

    public function testSetLockUpdatesBothGetLockAndIsLocked(): void
    {
        $this->Data->setLock(true);

        $this->assertTrue($this->Data->getLock());
        $this->assertTrue($this->Data->isLocked());
    }

    public function testGetAdapterReturnsATrackDataAdapterInstance(): void
    {
        $this->assertInstanceOf(TrackDataAdapter::class, $this->Data->getAdapter());
    }

    public function testGetAdapterReturnsTheSameInstanceOnRepeatedCalls(): void
    {
        $first = $this->Data->getAdapter();
        $second = $this->Data->getAdapter();

        $this->assertSame($first, $second);
    }

    public function testGetLegacyModelThrowsWhenNoActivityIsSet(): void
    {
        $this->expectException(\Error::class);

        $this->Data->getLegacyModel();
    }

    public function testGetLegacyModelReturnsCorrectTypeWhenActivityIsSet(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getId')
            ->willReturn(123);

        $this->Data->setActivity($activity);

        $result = $this->Data->getLegacyModel();

        $this->assertInstanceOf(EntityModel::class, $result);
    }
}
