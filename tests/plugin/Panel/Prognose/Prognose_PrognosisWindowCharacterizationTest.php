<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Characterization test for Prognose_PrognosisWindow.
 */
#[Group('plugin')]
final class Prognose_PrognosisWindowCharacterizationTest extends TestCase
{
    private Prognose_PrognosisWindow $window;

    private array $originalPost;

    protected function setUp(): void
    {
        $this->window = new \ReflectionClass(Prognose_PrognosisWindow::class)
            ->newInstanceWithoutConstructor();

        $this->originalPost = $_POST;
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
    }

    #[DataProvider('provideMetricDistanceValues')]
    public function testDistanceValuesToKmReturnsValuesUnchangedForMetricSystem(array $values): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);

        $result = $this->callProtectedMethod('distanceValuesToKm', $values);

        $this->assertSame($values, $result);
    }

    /**
     * @return \Iterator<string, array<int, array<string>>>
     */
    public static function provideMetricDistanceValues(): \Iterator
    {
        yield 'numeric strings' => [['1', '3.1', '6.2']];
        yield 'empty array' => [[]];
    }

    public function testDistanceValuesToKmConvertsFromMilesForImperialSystem(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::IMPERIAL);

        $result = $this->callProtectedMethod('distanceValuesToKm', ['1', '3.1', '6.2']);

        $this->assertSame([1.61, 4.99, 9.98], $result);
    }

    #[DataProvider('provideMetricDistanceValues')]
    public function testDistanceValuesToMilesReturnsValuesUnchangedForMetricSystem(array $values): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);

        $result = $this->callProtectedMethod('distanceValuesToMiles', $values);

        $this->assertSame($values, $result);
    }

    public function testDistanceValuesToMilesConvertsToMilesForImperialSystem(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::IMPERIAL);

        $result = $this->callProtectedMethod('distanceValuesToMiles', [1, 3, 5, 10, 21.1, 42.2]);

        $this->assertSame([0.62, 1.86, 3.11, 6.21, 13.11, 26.22], $result);
    }

    public function testReadPostDataParsesAndTrimsCommaSeparatedDistances(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);
        $_POST = [
            'model' => 'vo2max',
            'distances' => ' 1, 3, 5 , 10',
            'vo2max' => '50',
            'endurance-value' => '80',
        ];

        $this->callProtectedMethod('readPostData');

        $this->assertSame(['1', '3', '5', '10'], $this->getProtectedProperty('Distances'));
    }

    public function testReadPostDataUsesVO2maxForVo2maxModel(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);
        $_POST = [
            'model' => 'vo2max',
            'distances' => '5',
            'vo2max' => '50',
            'endurance' => true,
            'endurance-value' => '80',
        ];

        $this->callProtectedMethod('readPostData');

        $this->assertInstanceOf(
            \Runalyze\Sports\Running\Prognosis\VO2max::class,
            $this->getProtectedProperty('PrognosisObject')
        );
    }

    public function testReadPostDataUsesVO2maxAsDefaultForUnknownModel(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);
        $_POST = [
            'model' => 'some_unknown_model',
            'distances' => '5',
            'vo2max' => '50',
            'endurance-value' => '80',
        ];

        $this->callProtectedMethod('readPostData');

        $this->assertInstanceOf(
            \Runalyze\Sports\Running\Prognosis\VO2max::class,
            $this->getProtectedProperty('PrognosisObject')
        );
    }

    public function testReadPostDataUsesBockForRobertBockModelWithValidResults(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);
        $_POST = [
            'model' => 'robert-bock',
            'distances' => '5',
            'best-result-km' => '5.0',
            'best-result-time' => '20:00',
            'second-best-result-km' => '10.0',
            'second-best-result-time' => '42:30',
        ];

        $this->callProtectedMethod('readPostData');
        $prognosis = $this->getProtectedProperty('PrognosisObject');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Bock::class, $prognosis);
        $this->assertTrue($prognosis->areValuesValid());
    }

    public function testReadPostDataUsesSteffnyForHerbertSteffnyModel(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);
        $_POST = [
            'model' => 'herbert-steffny',
            'distances' => '5',
            'best-result-km' => '10.0',
            'best-result-time' => '42:30',
        ];

        $this->callProtectedMethod('readPostData');
        $prognosis = $this->getProtectedProperty('PrognosisObject');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Steffny::class, $prognosis);
        $this->assertTrue($prognosis->areValuesValid());
        $this->assertEqualsWithDelta(2550.0, $prognosis->getSeconds(10.0), 0.01);
    }

    public function testReadPostDataUsesCameronForDavidCameronModel(): void
    {
        $this->setUnitSystem(DistanceUnitSystem::METRIC);
        $_POST = [
            'model' => 'david-cameron',
            'distances' => '5',
            'best-result-km' => '10.0',
            'best-result-time' => '42:30',
        ];

        $this->callProtectedMethod('readPostData');
        $prognosis = $this->getProtectedProperty('PrognosisObject');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Cameron::class, $prognosis);
        $this->assertTrue($prognosis->areValuesValid());
        $this->assertEqualsWithDelta(2550.0, $prognosis->getSeconds(10.0), 0.01);
    }

    private function setUnitSystem(string $system): void
    {
        $this->setProtectedProperty('UnitSystem', new DistanceUnitSystem($system));
    }

    private function setProtectedProperty(string $name, mixed $value): void
    {
        $property = new \ReflectionProperty(Prognose_PrognosisWindow::class, $name);
        $property->setValue($this->window, $value);
    }

    private function getProtectedProperty(string $name): mixed
    {
        $property = new \ReflectionProperty(Prognose_PrognosisWindow::class, $name);

        return $property->isInitialized($this->window) ? $property->getValue($this->window) : null;
    }

    private function callProtectedMethod(string $name, mixed ...$arguments): mixed
    {
        $method = new \ReflectionMethod(Prognose_PrognosisWindow::class, $name);

        return $method->invoke($this->window, ...$arguments);
    }
}
