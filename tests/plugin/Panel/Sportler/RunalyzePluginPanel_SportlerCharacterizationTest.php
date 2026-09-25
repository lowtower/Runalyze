<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Activity\Weight;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\WeightUnit;

/**
 * Characterization test for RunalyzePluginPanel_Sportler
 */
#[Group('plugin')]
class RunalyzePluginPanel_SportlerCharacterizationTest extends TestCase
{
    private RunalyzePluginPanel_Sportler $plugin;

    private int $originalWeightDecimals;

    protected function setUp(): void
    {
        $this->plugin = (new \ReflectionClass(RunalyzePluginPanel_Sportler::class))
            ->newInstanceWithoutConstructor();

        $this->originalWeightDecimals = Weight::$DefaultDecimals;
        Weight::$DefaultDecimals = 1;
        Configuration::General()->weightUnit()->set(WeightUnit::KG);
    }

    protected function tearDown(): void
    {
        Weight::$DefaultDecimals = $this->originalWeightDecimals;
    }

    public function testBuildBodyValuesCodeIncludesAllValuesWhenAllArePresent(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 68.4,
            'pulse_rest' => 48,
            'pulse_max' => 185,
            'fat' => 14.5,
            'water' => 58.2,
            'muscles' => 41.3,
        ]);

        $this->assertStringContainsString('68.4', $Code);
        $this->assertStringContainsString('48', $Code);
        $this->assertStringContainsString('185', $Code);
        $this->assertStringContainsString('14.5', $Code);
        $this->assertStringContainsString('58.2', $Code);
        $this->assertStringContainsString('41.3', $Code);

        $this->assertStringContainsString('Weight', $Code);
        $this->assertStringContainsString('Resting&nbsp;HR', $Code);
        $this->assertStringContainsString('Maximal&nbsp;HR', $Code);
        $this->assertStringContainsString('Fat', $Code);
        $this->assertStringContainsString('Water', $Code);
        $this->assertStringContainsString('Muscles', $Code);
    }

    public function testBuildBodyValuesCodeUsesW33FloatingBlockForThreeFirstValues(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 68.4,
            'pulse_rest' => 48,
            'pulse_max' => 185,
            'fat' => 0.0,
            'water' => 0.0,
            'muscles' => 0.0,
        ]);

        $this->assertStringContainsString('boxed-value-outer w33', $Code);
    }

    public function testBuildBodyValuesCodeOmitsWarningBoxWhenMaximalHeartRateIsKnown(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 68.4,
            'pulse_rest' => 48,
            'pulse_max' => 185,
            'fat' => 0.0,
            'water' => 0.0,
            'muscles' => 0.0,
        ]);

        $this->assertStringNotContainsString('Enter maximal HR', $Code);
        $this->assertStringNotContainsString('colored-orange', $Code);
    }

    public function testBuildBodyValuesCodeShowsWarningBoxWhenMaximalHeartRateIsZero(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 68.4,
            'pulse_rest' => 48,
            'pulse_max' => 0,
            'fat' => 0.0,
            'water' => 0.0,
            'muscles' => 0.0,
        ]);

        $this->assertStringContainsString('Enter maximal HR', $Code);
        $this->assertStringContainsString('colored-orange', $Code);
        $this->assertStringContainsString('Otherwise&nbsp;calculations&nbsp;will&nbsp;be&nbsp;wrong', $Code);
    }

    public function testBuildBodyValuesCodeOmitsFatWaterMusclesWhenAllAreZero(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 68.4,
            'pulse_rest' => 48,
            'pulse_max' => 185,
            'fat' => 0.0,
            'water' => 0.0,
            'muscles' => 0.0,
        ]);

        $this->assertStringNotContainsString('Fat', $Code);
        $this->assertStringNotContainsString('Water', $Code);
        $this->assertStringNotContainsString('Muscles', $Code);
    }

    public function testBuildBodyValuesCodeShowsOnlyFatWhenOnlyFatIsPresent(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 68.4,
            'pulse_rest' => 48,
            'pulse_max' => 185,
            'fat' => 14.5,
            'water' => 0.0,
            'muscles' => 0.0,
        ]);

        $this->assertStringContainsString('Fat', $Code);
        $this->assertStringNotContainsString('Water', $Code);
        $this->assertStringNotContainsString('Muscles', $Code);
    }

    public function testBuildBodyValuesCodeShowsDashForZeroWeight(): void
    {
        $Code = $this->callProtectedMethod([
            'weight' => 0.0,
            'pulse_rest' => 48,
            'pulse_max' => 185,
            'fat' => 0.0,
            'water' => 0.0,
            'muscles' => 0.0,
        ]);

        $this->assertMatchesRegularExpression('/<div class="boxed-value">-\s*<div/', $Code);
    }

    public function testDefaultUserDataProvidesAllExpectedKeysAsZero(): void
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_Sportler::class, 'defaultUserData');
        $defaults = $method->invoke($this->plugin);

        $this->assertSame(
            [
                'weight' => 0.0,
                'pulse_rest' => 0,
                'pulse_max' => 0,
                'fat' => 0.0,
                'water' => 0.0,
                'muscles' => 0.0,
            ],
            $defaults
        );
    }

    /**
     * @param array<string, float>|array<string, int> $userData
     */
    private function callProtectedMethod(array $userData): string
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_Sportler::class, 'buildBodyValuesCode');

        return $method->invoke($this->plugin, $userData);
    }
}
