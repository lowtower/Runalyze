<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Trainingspartner.
 */
#[Group('plugin')]
class RunalyzePluginStat_TrainingspartnerCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Trainingspartner $plugin;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginStat_Trainingspartner::class)
            ->newInstanceWithoutConstructor();
    }

    public function testRenderPartnerRowsClosesPreviousRowWhenTransitioningToCountOne(): void
    {
        $partners = [
            'Alice' => 5,
            'Bob' => 3,
            'Carol' => 1,
            'Dave' => 1,
        ];

        $html = $this->callRenderPartnerRows($partners);

        $this->assertSame(
            '<tr><td class="b">5x</td><td>' . $this->searchLinkFor('Alice') . '</td></tr>'
            . '<tr><td class="b">3x</td><td>' . $this->searchLinkFor('Bob') . '</td></tr>'
            . '<tr><td class="b">1x</td><td>' . $this->searchLinkFor('Carol') . ', ' . $this->searchLinkFor('Dave') . '</td></tr>',
            $html
        );
    }

    public function testRenderPartnerRowsClosesEveryRowWhenAllCountsAreDistinct(): void
    {
        $partners = [
            'Alice' => 5,
            'Bob' => 4,
            'Carol' => 3,
            'Dave' => 2,
            'Erin' => 1,
        ];

        $html = $this->callRenderPartnerRows($partners);

        $this->assertSame(
            '<tr><td class="b">5x</td><td>' . $this->searchLinkFor('Alice') . '</td></tr>'
            . '<tr><td class="b">4x</td><td>' . $this->searchLinkFor('Bob') . '</td></tr>'
            . '<tr><td class="b">3x</td><td>' . $this->searchLinkFor('Carol') . '</td></tr>'
            . '<tr><td class="b">2x</td><td>' . $this->searchLinkFor('Dave') . '</td></tr>'
            . '<tr><td class="b">1x</td><td>' . $this->searchLinkFor('Erin') . '</td></tr>',
            $html
        );
    }

    public function testRenderPartnerRowsGroupsAllPartnersWithCountOneOnASingleRow(): void
    {
        $partners = [
            'Alice' => 1,
            'Bob' => 1,
            'Carol' => 1,
        ];

        $html = $this->callRenderPartnerRows($partners);

        $this->assertSame(
            '<tr><td class="b">1x</td><td>'
            . $this->searchLinkFor('Alice') . ', ' . $this->searchLinkFor('Bob') . ', ' . $this->searchLinkFor('Carol')
            . '</td></tr>',
            $html
        );
    }

    public function testRenderPartnerRowsReturnsEmptyStringForEmptyPartners(): void
    {
        $this->assertSame('', $this->callRenderPartnerRows([]));
    }

    public function testRenderPartnerRowsHandlesSinglePartner(): void
    {
        $html = $this->callRenderPartnerRows([
            'Alice' => 2,
        ]);

        $this->assertSame(
            '<tr><td class="b">2x</td><td>' . $this->searchLinkFor('Alice') . '</td></tr>',
            $html
        );
    }

    public function testRenderPartnerRowsProducesEqualNumberOfOpeningAndClosingRowTags(): void
    {
        $partners = [
            'Alice' => 5,
            'Bob' => 3,
            'Carol' => 3,
            'Dave' => 1,
            'Erin' => 1,
            'Frank' => 1,
        ];

        $html = $this->callRenderPartnerRows($partners);

        $openCount = substr_count($html, '<tr>');
        $closeCount = substr_count($html, '</tr>');

        $this->assertSame($openCount, $closeCount);
        $this->assertGreaterThan(0, $openCount);
    }

    private function searchLinkFor(string $name): string
    {
        return '<a data-size="big" class="window" href="my/search?get=true&partner=' . $name . '&opt[partner]=like">' . $name . '</a>';
    }

    /**
     * @param array<string, int> $partners
     */
    private function callRenderPartnerRows(array $partners): string
    {
        $closure = \Closure::bind(
            fn (array $partners): string => $this->renderPartnerRows($partners),
            $this->plugin,
            RunalyzePluginStat_Trainingspartner::class
        );

        return $closure($partners);
    }
}
