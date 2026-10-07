<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Wetter.
 */
#[Group('plugin')]
class RunalyzePluginStat_WetterCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Wetter $plugin;

    protected function setUp(): void
    {
        $this->plugin = (new \ReflectionClass(RunalyzePluginStat_Wetter::class))
            ->newInstanceWithoutConstructor();
    }

    public function testBuildExtremeTrainingsHtmlShowsAllFourCategoriesWithData(): void
    {
        $html = $this->callBuildExtremeTrainingsHtml(
            ['30°C on 01.01.2024'],
            ['5°C on 02.01.2024'],
            ['40 km/h on 03.01.2024'],
            ['95% on 04.01.2024']
        );

        $this->assertStringContainsString('<strong>Hottest activities:</strong> 30°C on 01.01.2024<br>', $html);
        $this->assertStringContainsString('<strong>Coldest activities:</strong> 5°C on 02.01.2024<br>', $html);
        $this->assertStringContainsString('<strong>Most windy activities:</strong> 40 km/h on 03.01.2024<br>', $html);
        $this->assertStringContainsString('<strong>Highest humidity activities:</strong> 95% on 04.01.2024<br>', $html);
    }

    public function testBuildExtremeTrainingsHtmlShowsColdDataWhenHotIsEmpty(): void
    {
        $html = $this->callBuildExtremeTrainingsHtml([], ['5°C on 02.01.2024'], [], []);

        $this->assertStringContainsString('<strong>Hottest activities:</strong> none<br>', $html);
        $this->assertStringContainsString('<strong>Coldest activities:</strong> 5°C on 02.01.2024<br>', $html);
        $this->assertStringNotContainsString('none:</strong>', $html);
    }

    public function testBuildExtremeTrainingsHtmlShowsNoneForEmptyCold(): void
    {
        $html = $this->callBuildExtremeTrainingsHtml(['30°C on 01.01.2024'], [], [], []);

        $this->assertStringContainsString('<strong>Coldest activities:</strong> none<br>', $html);
    }

    public function testBuildExtremeTrainingsHtmlShowsNoneForAllCategoriesWhenAllEmpty(): void
    {
        $html = $this->callBuildExtremeTrainingsHtml([], [], [], []);

        $this->assertSame(
            '<p>'
            . '<strong>Hottest activities:</strong> none<br>'
            . '<strong>Coldest activities:</strong> none<br>'
            . '<strong>Most windy activities:</strong> none<br>'
            . '<strong>Highest humidity activities:</strong> none<br>'
            . '</p>',
            $html
        );
    }

    public function testBuildExtremeTrainingsHtmlJoinsMultipleEntriesWithComma(): void
    {
        $html = $this->callBuildExtremeTrainingsHtml(['A', 'B'], [], [], []);

        $this->assertStringContainsString('<strong>Hottest activities:</strong> A, B<br>', $html);
    }

    public function testKnowsEquipmentTypeIsFalseWhenIdIsZero(): void
    {
        $this->setEquipmentTypeId(0);
        $this->setAllTypes([
            0 => 'none',
        ]);

        $this->assertFalse($this->callKnowsEquipmentType());
    }

    public function testKnowsEquipmentTypeIsFalseWhenIdIsNotInAllTypes(): void
    {
        $this->setEquipmentTypeId(5);
        $this->setAllTypes([
            0 => 'none',
            1 => 'Shoes',
        ]);

        $this->assertFalse($this->callKnowsEquipmentType());
    }

    public function testKnowsEquipmentTypeIsTrueWhenIdIsPositiveAndKnown(): void
    {
        $this->setEquipmentTypeId(1);
        $this->setAllTypes([
            0 => 'none',
            1 => 'Shoes',
        ]);

        $this->assertTrue($this->callKnowsEquipmentType());
    }

    public function testGetEquipmentTypeSubmenuLinksReturnsEmptyArrayWhenOnlyDefaultTypeExists(): void
    {
        $this->setAllTypes([
            0 => 'none',
        ]);
        $this->setEquipmentTypeId(0);

        $links = $this->callGetEquipmentTypeSubmenuLinks();

        $this->assertSame([], $links);
    }

    public function testGetEquipmentTypeSubmenuLinksReturnsEmptyArrayWhenNoTypesAtAll(): void
    {
        $this->setAllTypes([]);
        $this->setEquipmentTypeId(0);

        $links = $this->callGetEquipmentTypeSubmenuLinks();

        $this->assertSame([], $links);
    }

    public function testGetEquipmentTypeSubmenuLinksProducesBalancedMarkupWhenMultipleTypesExist(): void
    {
        $this->setAllTypes([
            0 => 'none',
            1 => 'Shoes A',
            2 => 'Shoes B',
        ]);
        $this->setEquipmentTypeId(1);

        $links = $this->callGetEquipmentTypeSubmenuLinks();
        $html = implode('', $links);

        $this->assertStringStartsWith('<li class="with-submenu"><span class="link">Shoes A</span><ul class="submenu">', $html);
        $this->assertStringEndsWith('</ul></li>', $html);
        $this->assertSame(1, substr_count($html, '<ul class="submenu">'));
        $this->assertSame(1, substr_count($html, '</ul>'));
    }

    public function testGetEquipmentTypeSubmenuLinksMarksActiveTypeWithActiveClass(): void
    {
        $this->setAllTypes([
            0 => 'none',
            1 => 'Shoes A',
            2 => 'Shoes B',
        ]);
        $this->setEquipmentTypeId(2);

        $links = $this->callGetEquipmentTypeSubmenuLinks();
        $html = implode('', $links);

        $this->assertStringContainsString('<li class="active">[LINK:Shoes B:0:0:2]</li>', $html);
        $this->assertStringNotContainsString('<li class="active">[LINK:Shoes A', $html);
    }

    #[DataProvider('provideTimerOrderingCases')]
    public function testGetTimerForOrderingInQuery(int $year, bool $addTableName, string $expected): void
    {
        $this->setYear($year);

        $this->assertSame($expected, $this->callGetTimerForOrderingInQuery($addTableName));
    }

    /**
     * @return array<string, int[]|bool[]|string[]>
     */
    public static function provideTimerOrderingCases(): array
    {
        return [
            'all years, no table name' => [-1, false, 'MONTH(FROM_UNIXTIME(`time`))'],
            'all years, with table name' => [-1, true, 'MONTH(FROM_UNIXTIME(`runalyze_training`.`time`))'],
            'last 6 months delegates to parent DATE_FORMAT' => [6, false, 'DATE_FORMAT(FROM_UNIXTIME(`time`), "%Y-%m")'],
            'last 12 months delegates to parent DATE_FORMAT' => [12, false, 'DATE_FORMAT(FROM_UNIXTIME(`time`), "%Y-%m")'],
            'specific year delegates to parent MONTH' => [2024, false, 'MONTH(FROM_UNIXTIME(`time`))'],
            'specific year with table name delegates to parent MONTH' => [2024, true, 'MONTH(FROM_UNIXTIME(`runalyze_training`.`time`))'],
        ];
    }

    private function setEquipmentTypeId(int $id): void
    {
        $property = new \ReflectionProperty(RunalyzePluginStat_Wetter::class, 'EquipmentTypeId');
        $property->setValue($this->plugin, $id);
    }

    /**
     * @param string[] $types
     */
    private function setAllTypes(array $types): void
    {
        $property = new \ReflectionProperty(RunalyzePluginStat_Wetter::class, 'AllTypes');
        $property->setValue($this->plugin, $types);
    }

    private function setYear(int $year): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'year');
        $property->setValue($this->plugin, $year);
    }

    /**
     * @param string[] $hot
     * @param string[] $cold
     * @param string[] $windiest
     * @param string[] $maxhumidity
     */
    private function callBuildExtremeTrainingsHtml(array $hot, array $cold, array $windiest, array $maxhumidity): string
    {
        $closure = \Closure::bind(
            fn (array $hot, array $cold, array $windiest, array $maxhumidity): string => $this->buildExtremeTrainingsHtml($hot, $cold, $windiest, $maxhumidity),
            $this->plugin,
            RunalyzePluginStat_Wetter::class
        );

        return $closure($hot, $cold, $windiest, $maxhumidity);
    }

    private function callGetEquipmentTypeSubmenuLinks(): array
    {
        $testPlugin = new class() extends RunalyzePluginStat_Wetter {
            public function __construct()
            {
                // Intentionally empty: replaces disableOriginalConstructor()
            }

            public function getInnerLink(string $name, int $sport = 0, int $year = 0, string $dat = ''): string
            {
                return "[LINK:{$name}:{$sport}:{$year}:{$dat}]";
            }
        };

        $allTypesProperty = new \ReflectionProperty(RunalyzePluginStat_Wetter::class, 'AllTypes');
        $allTypesProperty->setValue($testPlugin, $allTypesProperty->getValue($this->plugin));

        $equipmentTypeIdProperty = new \ReflectionProperty(RunalyzePluginStat_Wetter::class, 'EquipmentTypeId');
        $equipmentTypeIdProperty->setValue($testPlugin, $equipmentTypeIdProperty->getValue($this->plugin));

        $closure = \Closure::bind(
            fn (): array => $this->getEquipmentTypeSubmenuLinks(),
            $testPlugin,
            RunalyzePluginStat_Wetter::class
        );

        return $closure();
    }

    private function callKnowsEquipmentType(): bool
    {
        $closure = \Closure::bind(
            fn (): bool => $this->knowsEquipmentType(),
            $this->plugin,
            RunalyzePluginStat_Wetter::class
        );

        return $closure();
    }

    private function callGetTimerForOrderingInQuery(bool $addTableName): string
    {
        $closure = \Closure::bind(
            fn (bool $addTableName): string => $this->getTimerForOrderingInQuery($addTableName),
            $this->plugin,
            RunalyzePluginStat_Wetter::class
        );

        return $closure($addTableName);
    }
}
