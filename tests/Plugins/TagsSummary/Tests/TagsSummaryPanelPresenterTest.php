<?php

declare(strict_types=1);

namespace Runalyze\Plugins\TagsSummary\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\General;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Plugins\TagsSummary\TagsSummaryPanelPresenter;
use Runalyze\Plugins\TagsSummary\TagsSummaryPanelSelectionHandler;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class TagsSummaryPanelPresenterTest extends TestCase
{
    public function testPresentsTagCountsInUsageOrderForTheSelectedSportAndTimescale(): void
    {
        $account = $this->account(12);
        $installation = $this->installation($account);
        $sport = $this->sport(7, 'Running');

        $sports = $this->createStub(SportRepository::class);
        $sports->method('findAllFor')->willReturn([$sport]);

        $tags = $this->createMock(TagRepository::class);
        $tags->expects($this->once())
            ->method('findUsageForSportSince')
            ->with($account, 7, (new DateTimeImmutable('2026-11-19 12:00:00 UTC'))->modify('-3 months')->getTimestamp())
            ->willReturn([
                ['id' => 2, 'tag' => 'Intervals', 'usage_count' => 6],
                ['id' => 4, 'tag' => 'Easy', 'usage_count' => 3],
            ]);

        $storage = $this->createStub(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn([
            5 => ['sport' => '7', 'tagssummary_timescale' => '3'],
        ]);

        $result = $this->presenter($sports, $tags, $storage)->present($account, $installation);

        $this->assertSame(7, $result['selected_sport_id']);
        $this->assertSame(3, $result['selected_timescale']);
        $this->assertSame([
            ['id' => 2, 'name' => 'Intervals', 'count' => 6, 'usage_width' => 330],
            ['id' => 4, 'name' => 'Easy', 'count' => 3, 'usage_width' => 165],
        ], $result['tags']);
    }

    public function testReturnsEmptyTagsWithoutAConfiguredSport(): void
    {
        $account = $this->account(12);
        $sports = $this->createStub(SportRepository::class);
        $sports->method('findAllFor')->willReturn([]);
        $tags = $this->createMock(TagRepository::class);
        $tags->expects($this->never())->method('findUsageForSportSince');
        $storage = $this->createStub(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn([]);

        $result = $this->presenter($sports, $tags, $storage)->present($account, $this->installation($account));

        $this->assertSame([], $result['sports']);
        $this->assertNull($result['selected_sport_id']);
        $this->assertSame([], $result['tags']);
        $this->assertSame(1, $result['selected_timescale']);
    }

    public function testRejectsAnInstallationOwnedByAnotherAccount(): void
    {
        $account = $this->account(12);

        $this->expectException(InvalidArgumentException::class);

        $this->presenter()->present($account, $this->installation($this->account(13)));
    }

    public function testSelectionPersistsOnlyValidAccountSports(): void
    {
        $account = $this->account(12);
        $installation = $this->installation($account);
        $sports = $this->createStub(SportRepository::class);
        $sports->method('findAllFor')->willReturn([$this->sport(7, 'Running')]);

        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->once())->method('loadForPlugins')->with([5])->willReturn([]);
        $storage->expects($this->once())->method('insert')->with(5, 'sport', '7');
        $storage->expects($this->never())->method('update');

        new TagsSummaryPanelSelectionHandler($sports, $storage)
            ->handle($account, $installation, ['sport' => '7']);
    }

    public function testSelectionIgnoresUnknownSportAndUnsupportedTimescale(): void
    {
        $account = $this->account(12);
        $sports = $this->createStub(SportRepository::class);
        $sports->method('findAllFor')->willReturn([$this->sport(7, 'Running')]);

        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('loadForPlugins');
        $storage->expects($this->never())->method('insert');
        $storage->expects($this->never())->method('update');

        new TagsSummaryPanelSelectionHandler($sports, $storage)
            ->handle($account, $this->installation($account), [
                'sport' => '999',
                'tagssummary_timescale' => '99',
            ]);
    }

    public function testSelectionUpdatesExistingTimescale(): void
    {
        $account = $this->account(12);
        $sports = $this->createStub(SportRepository::class);
        $sports->method('findAllFor')->willReturn([]);

        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->once())->method('loadForPlugins')
            ->willReturn([5 => ['tagssummary_timescale' => '1']]);
        $storage->expects($this->once())->method('update')
            ->with(5, 'tagssummary_timescale', '24');

        new TagsSummaryPanelSelectionHandler($sports, $storage)
            ->handle($account, $this->installation($account), ['tagssummary_timescale' => '24']);
    }

    public function testSelectionRejectsAnInstallationOwnedByAnotherAccount(): void
    {
        $account = $this->account(12);
        $sports = $this->createMock(SportRepository::class);
        $sports->expects($this->never())->method('findAllFor');
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('insert');

        $this->expectException(InvalidArgumentException::class);

        new TagsSummaryPanelSelectionHandler($sports, $storage)
            ->handle($account, $this->installation($this->account(13)), ['sport' => '7']);
    }

    public function testDeclaresItsStableKeyAndTemplate(): void
    {
        $presenter = $this->presenter();

        $this->assertSame('RunalyzePluginPanel_TagsSummary', $presenter->getKey());
        $this->assertSame('@TagsSummary/panel.html.twig', $presenter->getTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }

    private function presenter(
        ?SportRepository $sports = null,
        ?TagRepository $tags = null,
        ?PluginConfigurationStorageInterface $storage = null
    ): TagsSummaryPanelPresenter {
        $sports ??= $this->createStub(SportRepository::class);
        $tags ??= $this->createStub(TagRepository::class);
        $storage ??= $this->createStub(PluginConfigurationStorageInterface::class);

        $general = $this->createStub(General::class);
        $general->method('getRunningSport')->willReturn(7);

        $list = $this->createStub(RunalyzeConfigurationList::class);
        $list->method('getGeneral')->willReturn($general);

        $configuration = $this->createStub(ConfigurationManager::class);
        $configuration->method('getList')->willReturn($list);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-11-19 12:00:00 UTC'));

        return new TagsSummaryPanelPresenter(
            $sports,
            $tags,
            $storage,
            $configuration,
            new TagsSummaryPanelSelectionHandler($sports, $storage),
            $clock
        );
    }

    private function account(int $id): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn($id);

        return $account;
    }

    private function installation(Account $account): Plugin
    {
        $installation = $this->createStub(Plugin::class);
        $installation->method('getId')->willReturn(5);
        $installation->method('getAccount')->willReturn($account);
        $installation->method('getKey')->willReturn('RunalyzePluginPanel_TagsSummary');
        $installation->method('isPanel')->willReturn(true);
        $installation->method('isInactive')->willReturn(false);

        return $installation;
    }

    private function sport(int $id, string $name): Sport
    {
        $sport = $this->createStub(Sport::class);
        $sport->method('getId')->willReturn($id);
        $sport->method('getName')->willReturn($name);

        return $sport;
    }
}
