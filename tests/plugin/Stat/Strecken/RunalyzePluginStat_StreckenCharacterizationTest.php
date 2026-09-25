<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Strecken.
 */
#[Group('plugin')]
class RunalyzePluginStat_StreckenCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Strecken $plugin;

    protected function setUp(): void
    {
        $this->plugin = (new \ReflectionClass(RunalyzePluginStat_Strecken::class))
            ->newInstanceWithoutConstructor();
    }

    public function testRenderLonelyCitiesRowsGroupsCitiesWithSameVisitCountOnOneRow(): void
    {
        $cities = [
            'CityA' => 5,
            'CityB' => 3,
            'CityC' => 2,
            'CityD' => 1,
            'CityE' => 1,
        ];

        $html = $this->callRenderLonelyCitiesRows($cities);

        $this->assertSame(
            '<tr><td class="b">1x</td><td>'
            . $this->searchLinkFor('CityE') . ', ' . $this->searchLinkFor('CityD') . '</td></tr>'
            . '<tr><td class="b">2x</td><td>' . $this->searchLinkFor('CityC') . '</td></tr>'
            . '<tr><td class="b">3x</td><td>' . $this->searchLinkFor('CityB') . '</td></tr>',
            $html
        );
    }

    public function testRenderLonelyCitiesRowsExcludesAllCitiesSharingTheFirstOverThresholdCount(): void
    {
        $cities = [
            'CityA' => 5,
            'CityB' => 5,
            'CityC' => 3,
            'CityD' => 2,
            'CityE' => 1,
            'CityF' => 1,
        ];

        $html = $this->callRenderLonelyCitiesRows($cities);

        $this->assertStringNotContainsString('CityA', $html);
        $this->assertStringNotContainsString('CityB', $html);
        $this->assertStringContainsString('CityC', $html);
        $this->assertSame(
            '<tr><td class="b">1x</td><td>' . $this->searchLinkFor('CityF') . ', ' . $this->searchLinkFor('CityE') . '</td></tr>'
            . '<tr><td class="b">2x</td><td>' . $this->searchLinkFor('CityD') . '</td></tr>'
            . '<tr><td class="b">3x</td><td>' . $this->searchLinkFor('CityC') . '</td></tr>',
            $html
        );
    }

    public function testRenderLonelyCitiesRowsDoesNotEmitStrayClosingTagWhenSmallestCountIsNotOne(): void
    {
        $cities = [
            'CityA' => 5,
            'CityB' => 3,
            'CityC' => 2,
            'CityD' => 2,
        ];

        $html = $this->callRenderLonelyCitiesRows($cities);

        $this->assertStringStartsWith('<tr>', $html);
        $this->assertSame(
            '<tr><td class="b">2x</td><td>' . $this->searchLinkFor('CityD') . ', ' . $this->searchLinkFor('CityC') . '</td></tr>'
            . '<tr><td class="b">3x</td><td>' . $this->searchLinkFor('CityB') . '</td></tr>',
            $html
        );
    }

    public function testRenderLonelyCitiesRowsClosesLastRowWhenLoopNeverBreaks(): void
    {
        $cities = [
            'CityA' => 3,
            'CityB' => 1,
        ];

        $html = $this->callRenderLonelyCitiesRows($cities);

        $this->assertStringEndsWith('</td></tr>', $html);
        $this->assertSame(
            '<tr><td class="b">1x</td><td>' . $this->searchLinkFor('CityB') . '</td></tr>'
            . '<tr><td class="b">3x</td><td>' . $this->searchLinkFor('CityA') . '</td></tr>',
            $html
        );
    }

    public function testRenderLonelyCitiesRowsReturnsEmptyStringForEmptyCities(): void
    {
        $this->assertSame('', $this->callRenderLonelyCitiesRows([]));
    }

    public function testRenderLonelyCitiesRowsReturnsEmptyStringWhenOnlyCityIsOverThreshold(): void
    {
        $this->assertSame('', $this->callRenderLonelyCitiesRows([
            'CityA' => 10,
        ]));
    }

    public function testFetchCitiesQueryUsesRouteOnlyQueryWhenNoSportOrYearFilterIsActive(): void
    {
        $this->setSportId(-1);
        $this->setYear(-1);

        $query = $this->callFetchCitiesQuery();

        $this->assertStringContainsString('SELECT `cities` FROM', $query);
        $this->assertStringNotContainsString('training', $query);
    }

    public function testFetchCitiesQueryJoinsTrainingWhenSportFilterIsActive(): void
    {
        $this->setSportId(1);
        $this->setYear(-1);

        $query = $this->callFetchCitiesQuery();

        $this->assertStringContainsString('RIGHT JOIN', $query);
        $this->assertStringContainsString('training', $query);
    }

    public function testFetchCitiesQueryJoinsTrainingWhenYearFilterIsActive(): void
    {
        $this->setSportId(-1);
        $this->setYear(2024);

        $query = $this->callFetchCitiesQuery();

        $this->assertStringContainsString('RIGHT JOIN', $query);
    }

    private function searchLinkFor(string $city): string
    {
        return '<a data-size="big" class="window" href="my/search?get=true&route=' . $city . '&opt[route]=like">' . $city . '</a>';
    }

    private function setSportId(int $sportid): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'sportid');
        $property->setValue($this->plugin, $sportid);
    }

    private function setYear(int $year): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'year');
        $property->setValue($this->plugin, $year);
    }

    /**
     * @param array<string, int> $cities
     */
    private function callRenderLonelyCitiesRows(array $cities): string
    {
        $closure = \Closure::bind(
            fn (array $cities): string => $this->renderLonelyCitiesRows($cities),
            $this->plugin,
            RunalyzePluginStat_Strecken::class
        );

        return $closure($cities);
    }

    private function callFetchCitiesQuery(): string
    {
        $closure = \Closure::bind(
            fn (): string => $this->fetchCitiesQuery(),
            $this->plugin,
            RunalyzePluginStat_Strecken::class
        );

        return $closure();
    }
}
