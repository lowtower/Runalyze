<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Activity;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Activity\DataSeriesRemover;

class DataSeriesRemoverTest extends TestCase
{
    private EntityManager&MockObject $EntityManagerMock;

    private DataSeriesRemover $Remover;

    protected function setUp(): void
    {
        $this->EntityManagerMock = $this->createMock(EntityManager::class);
        $this->Remover = new DataSeriesRemover($this->EntityManagerMock);
    }

    #[DataProvider('provideNonArrayFormData')]
    public function testHandleRequestDoesNothingForNonArrayInput(mixed $formData): void
    {
        $activity = $this->createMock(Training::class);
        $activity->expects($this->never())
            ->method('hasTrackdata');
        $activity->expects($this->never())
            ->method('hasRoute');
        $activity->expects($this->never())
            ->method('hasHrv');

        $this->EntityManagerMock->expects($this->never())
            ->method('remove');

        $this->Remover->handleRequest($formData, $activity);
    }

    /**
     * @return array<string, null[]|string[]|int[]|true[]|\stdClass[]>
     */
    public static function provideNonArrayFormData(): array
    {
        return [
            'null' => [null],
            'string' => ['time'],
            'int' => [42],
            'bool' => [true],
            'object' => [new \stdClass()],
        ];
    }

    /**
     * Robustheitstest: array_flip() akzeptiert ausschließlich int/string-Werte.
     * Enthält das Formular-Array (z. B. durch einen fehlerhaften Client-Request)
     * andere Typen wie Arrays oder Objekte, würde ein ungeschütztes array_flip()
     * eine TypeError werfen. Dank des Guard-Clauses in handleRequest()
     * (die zusätzliche array_filter()-Prüfung) wird dieser Fall bereits vorher
     * abgefangen: die Methode kehrt sauber zurück, statt eine Exception zu
     * werfen - und es dürfen keinerlei Seiteneffekte auftreten.
     */
    public function testHandleRequestReturnsSilentlyForInvalidArrayValues(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->expects($this->never())
            ->method('hasTrackdata');
        $activity->expects($this->never())
            ->method('hasRoute');
        $activity->expects($this->never())
            ->method('hasHrv');

        $this->EntityManagerMock->expects($this->never())
            ->method('remove');

        $this->Remover->handleRequest([[
            'nested' => 'array',
        ]], $activity);
    }

    /**
     * Ergänzender Grenzfall: ein gemischtes Array, in dem nur EIN einziger
     * Wert ungültig ist (Rest valide Strings), muss ebenfalls komplett
     * abgelehnt werden - array_filter() darf nicht nur den ersten oder
     * letzten Wert prüfen, sondern muss über das gesamte Array laufen.
     */
    public function testHandleRequestReturnsSilentlyWhenOneValueAmongManyIsInvalid(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->expects($this->never())
            ->method('hasTrackdata');
        $activity->expects($this->never())
            ->method('hasRoute');
        $activity->expects($this->never())
            ->method('hasHrv');

        $this->Remover->handleRequest(
            [DataSeriesRemover::KEY_TRACKDATA_TIME, new \stdClass(), DataSeriesRemover::KEY_ROUTE_GEOHASHES],
            $activity
        );
    }

    /**
     * Gegenprobe: ein Array, das ausschließlich aus gültigen Strings besteht,
     * muss weiterhin ganz normal verarbeitet werden - die neue Guard-Clause
     * darf valide Requests nicht fälschlich blockieren.
     */
    public function testHandleRequestProcessesValidArrayOfStrings(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);

        $activity->expects($this->once())
            ->method('hasTrackdata');

        $this->Remover->handleRequest([DataSeriesRemover::KEY_TRACKDATA_TIME], $activity);
    }

    public function testHandleRequestSkipsTrackDataWhenActivityHasNone(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);

        $activity->expects($this->never())
            ->method('getTrackdata');

        $this->Remover->handleRequest(['time'], $activity);
    }

    public function testHandleRequestSkipsRouteWhenActivityHasNone(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);

        $activity->expects($this->never())
            ->method('getRoute');

        $this->Remover->handleRequest(['geohashes'], $activity);
    }

    #[DataProvider('provideSimpleTrackDataFields')]
    public function testSimpleTrackDataFieldIsRemovedWhenSelectedAndPresent(
        string $formKey,
        string $hasMethod,
        string $setMethod
    ): void {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method($hasMethod)
            ->willReturn(true);
        $trackData->expects($this->once())
            ->method($setMethod)
            ->with(null);
        $trackData->method('isEmpty')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(true);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getTrackdata')
            ->willReturn($trackData);

        $this->Remover->handleRequest([$formKey], $activity);
    }

    #[DataProvider('provideSimpleTrackDataFields')]
    public function testSimpleTrackDataFieldIsNotTouchedWhenNotPresent(
        string $formKey,
        string $hasMethod,
        string $setMethod
    ): void {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method($hasMethod)
            ->willReturn(false);
        $trackData->expects($this->never())
            ->method($setMethod);
        $trackData->method('isEmpty')
            ->willReturn(true);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(true);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getTrackdata')
            ->willReturn($trackData);

        $this->Remover->handleRequest([$formKey], $activity);
    }

    #[DataProvider('provideSimpleTrackDataFields')]
    public function testSimpleTrackDataFieldIsNotTouchedWhenNotSelected(
        string $formKey,
        string $hasMethod,
        string $setMethod
    ): void {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method($hasMethod)
            ->willReturn(true);
        $trackData->expects($this->never())
            ->method($setMethod);
        $trackData->method('isEmpty')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(true);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getTrackdata')
            ->willReturn($trackData);

        $this->Remover->handleRequest(['__unrelated_key__'], $activity);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideSimpleTrackDataFields(): array
    {
        return [
            'time' => [DataSeriesRemover::KEY_TRACKDATA_TIME, 'hasTime', 'setTime'],
            'distance' => [DataSeriesRemover::KEY_TRACKDATA_DISTANCE, 'hasDistance', 'setDistance'],
            'heartRate' => [DataSeriesRemover::KEY_TRACKDATA_HEART_RATE, 'hasHeartrate', 'setHeartrate'],
            'cadence' => [DataSeriesRemover::KEY_TRACKDATA_CADENCE, 'hasCadence', 'setCadence'],
            'power' => [DataSeriesRemover::KEY_TRACKDATA_POWER, 'hasPower', 'setPower'],
            'smo2' => [DataSeriesRemover::KEY_TRACKDATA_MUSCLE_OXYGENATION, 'hasSmo20', 'setSmo20'],
            'smo2_2' => [DataSeriesRemover::KEY_TRACKDATA_MUSCLE_OXYGENATION_2, 'hasSmo21', 'setSmo21'],
            'thb' => [DataSeriesRemover::KEY_TRACKDATA_TOTAL_HAEMOGLOBIN, 'hasThb0', 'setThb0'],
            'thb_2' => [DataSeriesRemover::KEY_TRACKDATA_TOTAL_HAEMOGLOBIN_2, 'hasThb1', 'setThb1'],
            'temperature' => [DataSeriesRemover::KEY_TRACKDATA_TEMPERATURE, 'hasTemperature', 'setTemperature'],
            'performance_condition' => [DataSeriesRemover::KEY_TRACKDATA_PERFOMANCE_CONDITION, 'hasPerformanceCondition', 'setPerformanceCondition'],
            'respiration_rate' => [DataSeriesRemover::KEY_TRACKDATA_RESPIRATION_RATE, 'hasRespirationRate', 'setRespirationRate'],
        ];
    }

    #[DataProvider('provideCascadingTrackDataFields')]
    public function testCascadingTrackDataFieldClearsBothTrackDataAndActivity(
        string $formKey,
        string $hasMethod,
        string $trackDataSetMethod,
        string $activitySetMethod
    ): void {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method($hasMethod)
            ->willReturn(true);
        $trackData->expects($this->once())
            ->method($trackDataSetMethod)
            ->with(null);
        $trackData->method('isEmpty')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(true);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getTrackdata')
            ->willReturn($trackData);
        $activity->expects($this->once())
            ->method($activitySetMethod)
            ->with(null);

        $this->Remover->handleRequest([$formKey], $activity);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideCascadingTrackDataFields(): array
    {
        return [
            'verticalOscillation' => [
                DataSeriesRemover::KEY_TRACKDATA_VERTICAL_OSCILLATION,
                'hasVerticalOscillation', 'setVerticalOscillation', 'setVerticalOscillation',
            ],
            'groundContactTime' => [
                DataSeriesRemover::KEY_TRACKDATA_GROUND_CONTACT_TIME,
                'hasGroundcontact', 'setGroundcontact', 'setGroundcontact',
            ],
            'groundContactBalance' => [
                DataSeriesRemover::KEY_TRACKDATA_GROUND_CONTACT_BALANCE,
                'hasGroundcontactBalance', 'setGroundcontactBalance', 'setGroundcontactBalance',
            ],
            'impact_gs_left' => [
                DataSeriesRemover::KEY_TRACKDATA_IMPACT_GS_LEFT,
                'hasImpactGsLeft', 'setImpactGsLeft', 'setAvgImpactGsLeft',
            ],
            'impact_gs_right' => [
                DataSeriesRemover::KEY_TRACKDATA_IMPACT_GS_RIGHT,
                'hasImpactGsRight', 'setImpactGsRight', 'setAvgImpactGsRight',
            ],
            'braking_gs_left' => [
                DataSeriesRemover::KEY_TRACKDATA_BRAKING_GS_LEFT,
                'hasBrakingGsLeft', 'setBrakingGsLeft', 'setAvgBrakingGsLeft',
            ],
            'braking_gs_right' => [
                DataSeriesRemover::KEY_TRACKDATA_BRAKING_GS_RIGHT,
                'hasBrakingGsRight', 'setBrakingGsRight', 'setAvgBrakingGsRight',
            ],
            'footstrike_type_left' => [
                DataSeriesRemover::KEY_TRACKDATA_FOOTSTRIKE_TYPE_LEFT,
                'hasFootstrikeTypeLeft', 'setFootstrikeTypeLeft', 'setAvgFootstrikeTypeLeft',
            ],
            'footstrike_type_right' => [
                DataSeriesRemover::KEY_TRACKDATA_FOOTSTRIKE_TYPE_RIGHT,
                'hasFootstrikeTypeRight', 'setFootstrikeTypeRight', 'setAvgFootstrikeTypeRight',
            ],
            'pronation_excursion_left' => [
                DataSeriesRemover::KEY_TRACKDATA_PRONATION_EXCURSION_LEFT,
                'hasPronationExcursionLeft', 'setPronationExcursionLeft', 'setAvgPronationExcursionLeft',
            ],
            'pronation_excursion_right' => [
                DataSeriesRemover::KEY_TRACKDATA_PRONATION_EXCURSION_RIGHT,
                'hasPronationExcursionRight', 'setPronationExcursionRight', 'setAvgPronationExcursionRight',
            ],
        ];
    }

    public function testTrackDataIsRemovedFromEntityManagerWhenEmpty(): void
    {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method('hasTime')
            ->willReturn(true);
        $trackData->method('setTime')
            ->with(null);
        $trackData->method('isEmpty')
            ->willReturn(true);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(true);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getTrackdata')
            ->willReturn($trackData);
        $activity->expects($this->once())
            ->method('setTrackdata')
            ->with();

        $this->EntityManagerMock->expects($this->once())
            ->method('remove')
            ->with($trackData);

        $this->Remover->handleRequest([DataSeriesRemover::KEY_TRACKDATA_TIME], $activity);
    }

    public function testTrackDataIsNotRemovedWhenNotEmpty(): void
    {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method('hasTime')
            ->willReturn(true);
        $trackData->method('isEmpty')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(true);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getTrackdata')
            ->willReturn($trackData);
        $activity->expects($this->never())
            ->method('setTrackdata');

        $this->EntityManagerMock->expects($this->never())
            ->method('remove');

        $this->Remover->handleRequest([DataSeriesRemover::KEY_TRACKDATA_TIME], $activity);
    }

    #[DataProvider('provideRouteFields')]
    public function testRouteFieldIsRemovedWhenSelectedAndPresent(
        string $formKey,
        string $hasMethod,
        string $setMethod
    ): void {
        $route = $this->createMock(Route::class);
        $route->method($hasMethod)
            ->willReturn(true);
        $route->expects($this->once())
            ->method($setMethod)
            ->with(null);
        $route->method('isEmpty')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getRoute')
            ->willReturn($route);

        $this->Remover->handleRequest([$formKey], $activity);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideRouteFields(): array
    {
        return [
            'geohashes' => [DataSeriesRemover::KEY_ROUTE_GEOHASHES, 'hasGeohashes', 'setGeohashes'],
            'elevationOriginal' => [DataSeriesRemover::KEY_ROUTE_ELEVATION_ORIGINAL, 'hasOriginalElevations', 'setElevationsOriginal'],
            'elevationCorrected' => [DataSeriesRemover::KEY_ROUTE_ELEVATION_CORRECTED, 'hasCorrectedElevations', 'setElevationsCorrected'],
        ];
    }

    public function testRouteIsRemovedAndActivityElevationClearedWhenEmpty(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('hasGeohashes')
            ->willReturn(true);
        $route->method('isEmpty')
            ->willReturn(true);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->expects($this->once())
            ->method('setRoute')
            ->with();
        $activity->expects($this->once())
            ->method('setElevation')
            ->with(null);

        $this->EntityManagerMock->expects($this->once())
            ->method('remove')
            ->with($route);

        $this->Remover->handleRequest([DataSeriesRemover::KEY_ROUTE_GEOHASHES], $activity);
    }

    public function testRouteIsNotRemovedWhenNotEmpty(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('hasGeohashes')
            ->willReturn(true);
        $route->method('isEmpty')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->expects($this->never())
            ->method('setRoute');
        $activity->expects($this->never())
            ->method('setElevation');

        $this->EntityManagerMock->expects($this->never())
            ->method('remove');

        $this->Remover->handleRequest([DataSeriesRemover::KEY_ROUTE_GEOHASHES], $activity);
    }

    public function testHrvIsRemovedWhenSelectedAndPresent(): void
    {
        $hrv = $this->createMock(Hrv::class);

        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(true);
        $activity->method('getHrv')
            ->willReturn($hrv);
        $activity->expects($this->once())
            ->method('setHrv')
            ->with();

        $this->EntityManagerMock->expects($this->once())
            ->method('remove')
            ->with($hrv);

        $this->Remover->handleRequest([DataSeriesRemover::KEY_HRV], $activity);
    }

    public function testHrvIsNotRemovedWhenNotSelected(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(true);
        $activity->expects($this->never())
            ->method('getHrv');
        $activity->expects($this->never())
            ->method('setHrv');

        $this->EntityManagerMock->expects($this->never())
            ->method('remove');

        $this->Remover->handleRequest(['__unrelated_key__'], $activity);
    }

    public function testHrvIsNotRemovedWhenActivityHasNone(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('hasTrackdata')
            ->willReturn(false);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->method('hasHrv')
            ->willReturn(false);
        $activity->expects($this->never())
            ->method('getHrv');

        $this->Remover->handleRequest([DataSeriesRemover::KEY_HRV], $activity);
    }

    public function testGetChoicesForActivityReturnsEmptyArrayWithoutAnyData(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('getTrackdata')
            ->willReturn(null);
        $activity->method('getRoute')
            ->willReturn(null);
        $activity->method('getHrv')
            ->willReturn(null);

        $choices = DataSeriesRemover::getChoicesForActivity($activity);

        $this->assertSame([], $choices);
    }

    public function testGetChoicesForActivityReturnsAllAvailableOptions(): void
    {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method('hasTime')
            ->willReturn(true);
        $trackData->method('hasDistance')
            ->willReturn(true);
        $trackData->method('hasHeartrate')
            ->willReturn(false);
        $trackData->method('hasCadence')
            ->willReturn(false);
        $trackData->method('hasPower')
            ->willReturn(false);
        $trackData->method('hasVerticalOscillation')
            ->willReturn(false);
        $trackData->method('hasGroundcontact')
            ->willReturn(false);
        $trackData->method('hasGroundcontactBalance')
            ->willReturn(false);
        $trackData->method('hasSmo20')
            ->willReturn(false);
        $trackData->method('hasSmo21')
            ->willReturn(false);
        $trackData->method('hasThb0')
            ->willReturn(false);
        $trackData->method('hasThb1')
            ->willReturn(false);
        $trackData->method('hasTemperature')
            ->willReturn(false);
        $trackData->method('hasPerformanceCondition')
            ->willReturn(false);
        $trackData->method('hasRespirationRate')
            ->willReturn(false);
        $trackData->method('hasImpactGsLeft')
            ->willReturn(true);
        $trackData->method('hasImpactGsRight')
            ->willReturn(false);
        $trackData->method('hasBrakingGsLeft')
            ->willReturn(false);
        $trackData->method('hasBrakingGsRight')
            ->willReturn(false);
        $trackData->method('hasFootstrikeTypeLeft')
            ->willReturn(false);
        $trackData->method('hasFootstrikeTypeRight')
            ->willReturn(false);
        $trackData->method('hasPronationExcursionLeft')
            ->willReturn(false);
        $trackData->method('hasPronationExcursionRight')
            ->willReturn(false);

        $route = $this->createMock(Route::class);
        $route->method('hasGeohashes')
            ->willReturn(true);
        $route->method('hasOriginalElevations')
            ->willReturn(false);
        $route->method('hasCorrectedElevations')
            ->willReturn(false);

        $hrv = $this->createMock(Hrv::class);

        $activity = $this->createMock(Training::class);
        $activity->method('getTrackdata')
            ->willReturn($trackData);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getHrv')
            ->willReturn($hrv);

        $choices = DataSeriesRemover::getChoicesForActivity($activity);

        $this->assertSame([
            'Time' => DataSeriesRemover::KEY_TRACKDATA_TIME,
            'Distance' => DataSeriesRemover::KEY_TRACKDATA_DISTANCE,
            'Impact Gs (left)' => DataSeriesRemover::KEY_TRACKDATA_IMPACT_GS_LEFT,
            'GPS path' => DataSeriesRemover::KEY_ROUTE_GEOHASHES,
            'HRV' => DataSeriesRemover::KEY_HRV,
        ], $choices);
    }
}
