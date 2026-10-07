<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Wettkampf.
 */
#[Group('plugin')]
class RunalyzePluginStat_WettkampfCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Wettkampf $plugin;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginStat_Wettkampf::class)
            ->newInstanceWithoutConstructor();
    }

    public function testKnowsAgeGradeIsFalseByDefault(): void
    {
        $this->assertFalse($this->callKnowsAgeGrade());
    }

    public function testKnowsAgeGradeIsFalseWhenExplicitlyNull(): void
    {
        $this->setAgeGradeLookup(null);

        $this->assertFalse($this->callKnowsAgeGrade());
    }

    public function testKnowsAgeGradeIsTrueWhenLookupIsSet(): void
    {
        $lookup = new \ReflectionClass(\Runalyze\AgeGrade\Lookup::class)
            ->newInstanceWithoutConstructor();
        $this->setAgeGradeLookup($lookup);

        $this->assertTrue($this->callKnowsAgeGrade());
    }

    #[DataProvider('provideFunIdScenarios')]
    public function testIsFunCompetition(array $funIds, int $id, bool $expected): void
    {
        $this->setConfigurationValue('fun_ids', $funIds);

        $this->assertSame($expected, $this->plugin->isFunCompetition($id));
    }

    /**
     * @return array<string, int[]|bool[]|never[][]|string[][]>
     */
    public static function provideFunIdScenarios(): array
    {
        return [
            'empty fun_ids' => [[], 5, false],
            'id present as string (loose comparison bridges int vs string)' => [['5'], 5, true],
            'id not present' => [['5', '7'], 6, false],
            'id present among multiple' => [['5', '7', '9'], 7, true],
        ];
    }

    public function testApplyFunTagToIdsAddsIdWhenTaggedAsFun(): void
    {
        $result = $this->callApplyFunTagToIds('fun-123', []);

        $this->assertSame(['123'], $result);
    }

    public function testApplyFunTagToIdsRemovesIdWhenTaggedAsNofun(): void
    {
        $result = $this->callApplyFunTagToIds('nofun-123', ['123', '456']);

        $this->assertSame([
            1 => '456',
        ], $result);
    }

    public function testApplyFunTagToIdsLeavesListUnchangedWhenNofunIdNotPresent(): void
    {
        $result = $this->callApplyFunTagToIds('nofun-999', ['123', '456']);

        $this->assertSame(['123', '456'], $result);
    }

    public function testApplyFunTagToIdsIgnoresNonNumericId(): void
    {
        $result = $this->callApplyFunTagToIds('fun-abc', ['123']);

        $this->assertSame(['123'], $result);
    }

    public function testApplyFunTagToIdsIgnoresUnknownTag(): void
    {
        $result = $this->callApplyFunTagToIds('whatever-123', ['456']);

        $this->assertSame(['456'], $result);
    }

    public function testApplyFunTagToIdsLeavesListUnchangedWhenDatHasNoSeparator(): void
    {
        $result = $this->callApplyFunTagToIds('xyz', ['123']);

        $this->assertSame(['123'], $result);
    }

    public function testApplyFunTagToIdsLeavesListUnchangedForBareTagWithoutId(): void
    {
        $result = $this->callApplyFunTagToIds('fun', ['123']);

        $this->assertSame(['123'], $result);
    }

    /**
     * @param string[] $funIds
     */
    private function callApplyFunTagToIds(string $dat, array $funIds): array
    {
        $closure = \Closure::bind(
            fn (string $dat, array $funIds): array => $this->applyFunTagToIds($dat, $funIds),
            $this->plugin,
            RunalyzePluginStat_Wettkampf::class
        );

        return $closure($dat, $funIds);
    }

    private function callKnowsAgeGrade(): bool
    {
        $closure = \Closure::bind(
            fn (): bool => $this->knowsAgeGrade(),
            $this->plugin,
            RunalyzePluginStat_Wettkampf::class
        );

        return $closure();
    }

    private function setAgeGradeLookup(?\Runalyze\AgeGrade\Lookup $lookup): void
    {
        $property = new \ReflectionProperty(RunalyzePluginStat_Wettkampf::class, 'AgeGradeLookup');
        $property->setValue($this->plugin, $lookup);
    }

    private function setConfigurationValue(string $key, mixed $value): void
    {
        $valueObject = new PluginConfigurationValueSelect($key);
        $valueObject->setValue($value);

        $configuration = new PluginConfiguration(0);
        $configuration->addValue($valueObject);

        $catchedValuesProperty = new \ReflectionProperty(PluginConfiguration::class, 'CatchedValues');
        $catchedValuesProperty->setValue($configuration, true);

        $setConfigurationMethod = new \ReflectionMethod(RunalyzePluginStat_Wettkampf::class, 'setConfiguration');
        $setConfigurationMethod->invoke($this->plugin, $configuration);
    }
}
