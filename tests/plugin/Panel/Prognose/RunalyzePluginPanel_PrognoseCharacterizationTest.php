<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginPanel_Prognose.
 */
#[Group('plugin')]
#[Group('requiresDoctrine')]
class RunalyzePluginPanel_PrognoseCharacterizationTest extends TestCase
{
    public $configuration;

    private RunalyzePluginPanel_Prognose $plugin;

    private \ArrayObject $topResult;

    protected function setUp(): void
    {
        $this->topResult = new \ArrayObject();

        $this->plugin = new class($this->topResult) extends RunalyzePluginPanel_Prognose {
            public function __construct(
                private readonly \ArrayObject $topResultHolder
            ) {
            }

            public function getTopResult(mixed ...$arguments): array
            {
                return $this->topResultHolder->getArrayCopy();
            }
        };
    }

    public function testThereAreNotEnoughCompetitionsIsTrueWhenNoPersonalBestsWereFound(): void
    {
        $this->setProtectedProperty('NumberOfPBs', 0);

        $this->assertTrue($this->callProtectedMethod('thereAreNotEnoughCompetitions'));
    }

    public function testThereAreNotEnoughCompetitionsIsFalseWhenAtLeastOnePersonalBestWasFound(): void
    {
        $this->setProtectedProperty('NumberOfPBs', 1);

        $this->assertFalse($this->callProtectedMethod('thereAreNotEnoughCompetitions'));
    }

    #[DataProvider('provideDistanceConfigurations')]
    public function testGetDistancesFiltersOutNonNumericValues(array $configuredDistances, array $expectedDistances): void
    {
        $this->setConfigurationValue('distances', $configuredDistances);

        $distances = $this->plugin->getDistances();

        $this->assertSame($expectedDistances, array_values($distances));
    }

    /**
     * @return array<string, int[][]|float[][]|string[][]|null[][]|never[][]>
     */
    public static function provideDistanceConfigurations(): array
    {
        return [
            'all numeric' => [[1, 3, 5, 10, 21.1, 42.2], [1, 3, 5, 10, 21.1, 42.2]],
            'contains empty string' => [[1, '', 5], [1, 5]],
            'contains null' => [[1, null, 5], [1, 5]],
            'empty configuration' => [[], []],
        ];
    }

    public function testPrepareForPrognosisUsesBockForCppModelWhenTwoTopResultsExist(): void
    {
        $this->setConfigurationValue('model', 'cpp');
        $this->topResult->exchangeArray([
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
            [
                'distance' => 10.0,
                's' => 2550.0,
            ],
        ]);

        $this->callProtectedMethod('prepareForPrognosis');
        $prognosis = $this->getProtectedProperty('Prognosis');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Bock::class, $prognosis);
        $this->assertTrue($prognosis->areValuesValid());
    }

    public function testPrepareForPrognosisLeavesBockInvalidForCppModelWhenFewerThanTwoTopResultsExist(): void
    {
        $this->setConfigurationValue('model', 'cpp');
        $this->topResult->exchangeArray([
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
        ]);

        $this->callProtectedMethod('prepareForPrognosis');
        $prognosis = $this->getProtectedProperty('Prognosis');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Bock::class, $prognosis);
        $this->assertFalse($prognosis->areValuesValid());
    }

    public function testPrepareForPrognosisUsesSteffnyForSteffnyModel(): void
    {
        $this->setConfigurationValue('model', 'steffny');
        $this->topResult->exchangeArray([
            [
                'distance' => 10.0,
                's' => 2550.0,
            ],
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
        ]);

        $this->callProtectedMethod('prepareForPrognosis');
        $prognosis = $this->getProtectedProperty('Prognosis');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Steffny::class, $prognosis);
    }

    public function testPrepareForPrognosisUsesCameronForCameronModel(): void
    {
        $this->setConfigurationValue('model', 'cameron');
        $this->topResult->exchangeArray([
            [
                'distance' => 10.0,
                's' => 2550.0,
            ],
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
        ]);

        $this->callProtectedMethod('prepareForPrognosis');
        $prognosis = $this->getProtectedProperty('Prognosis');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\Cameron::class, $prognosis);
    }

    public function testPrepareForPrognosisUsesVO2maxForVo2maxModel(): void
    {
        $this->setConfigurationValue('model', 'vo2max');
        $this->setConfigurationValue('use_be', true);

        $this->callProtectedMethod('prepareForPrognosis');
        $prognosis = $this->getProtectedProperty('Prognosis');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\VO2max::class, $prognosis);
    }

    public function testPrepareForPrognosisFallsBackToVO2maxForUnknownModel(): void
    {
        $this->setConfigurationValue('model', 'some_unknown_model');
        $this->setConfigurationValue('use_be', true);

        $this->callProtectedMethod('prepareForPrognosis');
        $prognosis = $this->getProtectedProperty('Prognosis');

        $this->assertInstanceOf(\Runalyze\Sports\Running\Prognosis\VO2max::class, $prognosis);
    }

    private function setConfigurationValue(string $key, mixed $value): void
    {
        $this->configuration ??= new PluginConfiguration(0);

        $valueObject = new PluginConfigurationValueSelect($key);
        $valueObject->setValue($value);

        $this->configuration->addValue($valueObject);

        // Prevent Plugin::Configuration() from hitting a real database via catchValuesFromDatabaseIfNotDoneYet().
        $catchedValuesProperty = new \ReflectionProperty(PluginConfiguration::class, 'CatchedValues');
        $catchedValuesProperty->setValue($this->configuration, true);

        $setConfigurationMethod = new \ReflectionMethod(RunalyzePluginPanel_Prognose::class, 'setConfiguration');
        $setConfigurationMethod->invoke($this->plugin, $this->configuration);
    }

    private function setProtectedProperty(string $name, mixed $value): void
    {
        $property = new \ReflectionProperty(RunalyzePluginPanel_Prognose::class, $name);
        $property->setValue($this->plugin, $value);
    }

    private function getProtectedProperty(string $name): mixed
    {
        $property = new \ReflectionProperty(RunalyzePluginPanel_Prognose::class, $name);

        return $property->isInitialized($this->plugin) ? $property->getValue($this->plugin) : null;
    }

    private function callProtectedMethod(string $name, mixed ...$arguments): mixed
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_Prognose::class, $name);

        return $method->invoke($this->plugin, ...$arguments);
    }
}
