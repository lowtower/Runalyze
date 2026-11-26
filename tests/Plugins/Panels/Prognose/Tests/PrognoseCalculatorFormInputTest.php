<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorFormInput;

#[Group('plugin')]
final class PrognoseCalculatorFormInputTest extends TestCase
{
    public function testProvidesDefaultValuesForInitialMetricRequest(): void
    {
        $input = new PrognoseCalculatorFormInput()->prepare(
            [],
            [],
            [5, 10, 21.1],
            48.5,
            78,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC)
        );

        $this->assertSame('vo2max', $input['model']);
        $this->assertSame('5, 10, 21.1', $input['distances']);
        $this->assertEqualsWithDelta(48.5, $input['vo2max'], PHP_FLOAT_EPSILON);
        $this->assertTrue($input['endurance']);
        $this->assertSame(78, $input['endurance-value']);
        $this->assertSame('5.0', $input['best-result-km']);
        $this->assertSame('0:26:00', $input['best-result-time']);
        $this->assertSame('10.0', $input['second-best-result-km']);
        $this->assertSame('1:00:00', $input['second-best-result-time']);
    }

    public function testPrefillsBestResultsAndConvertsDefaultDistancesToMiles(): void
    {
        $input = new PrognoseCalculatorFormInput()->prepare(
            [],
            [
                [
                    'distance' => 5.0,
                    's' => 1200,
                    'time' => 1740000000,
                ],
                [
                    'distance' => 10.0,
                    's' => 2550,
                    'time' => 1740000001,
                ],
            ],
            [5, 10],
            50.0,
            75,
            new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL)
        );

        $this->assertSame('3.11, 6.21', $input['distances']);
        $this->assertEqualsWithDelta(5.0, $input['best-result-km'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(10.0, $input['second-best-result-km'], PHP_FLOAT_EPSILON);
        $this->assertStringContainsString('20:00', (string) $input['best-result-time']);
        $this->assertStringContainsString('42:30', (string) $input['second-best-result-time']);
    }

    public function testNormalizesSubmittedImperialReferenceDistances(): void
    {
        $input = new PrognoseCalculatorFormInput()->prepare(
            [
                'model' => 'robert-bock',
                'distances' => '3.1, 6.2',
                'best-result-km' => '3,1',
                'second-best-result-km' => '6,2',
                'best-result-time' => '20:00',
                'second-best-result-time' => '42:30',
            ],
            [],
            [5, 10],
            50.0,
            80,
            new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL)
        );

        $this->assertSame('robert-bock', $input['model']);
        $this->assertSame('3.1, 6.2', $input['distances']);
        $this->assertEqualsWithDelta(4.99, $input['best-result-km'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(9.98, $input['second-best-result-km'], PHP_FLOAT_EPSILON);
        $this->assertArrayNotHasKey('endurance', $input);
    }

    public function testIgnoresNonScalarFieldsAndFallsBackFromInvalidDistances(): void
    {
        $input = new PrognoseCalculatorFormInput()->prepare(
            [
                'model' => [
                    'unexpected' => 'value',
                ],
                'distances' => '5, invalid',
                'best-result-km' => ['invalid'],
                'endurance' => ['invalid'],
            ],
            [],
            [5, 10],
            50.0,
            80,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC)
        );

        $this->assertSame('vo2max', $input['model']);
        $this->assertSame('5, 10', $input['distances']);
        $this->assertEqualsWithDelta(0.0, $input['best-result-km'], PHP_FLOAT_EPSILON);
        $this->assertArrayNotHasKey('endurance', $input);
    }

    public function testRejectsNonFiniteAndNonPositiveSubmittedDistances(): void
    {
        $service = new PrognoseCalculatorFormInput();
        $unit = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

        foreach (['-5', '0', '1e999', '1, 0'] as $invalidDistances) {
            $input = $service->prepare(
                [
                    'distances' => $invalidDistances,
                ],
                [],
                [5, 10],
                50.0,
                80,
                $unit
            );

            $this->assertSame('5, 10', $input['distances']);
        }
    }
}
