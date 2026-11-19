<?php

declare(strict_types=1);

namespace Runalyze\Plugins\TagsSummary;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class TagsSummaryPanelPresenter implements ModernPanelInterface, ModernPanelRequestHandlerInterface
{
    private const string PLUGIN_KEY = 'RunalyzePluginPanel_TagsSummary';

    public const array TIMESCALES = [1, 3, 6, 12, 24];

    public function __construct(
        private SportRepository $sportRepository,
        private TagRepository $tagRepository,
        private PluginConfigurationStorageInterface $configurationStorage,
        private ConfigurationManager $configurationManager,
        private TagsSummaryPanelSelectionHandler $selectionHandler,
        private ClockInterface $clock
    ) {}

    public function getKey(): string
    {
        return self::PLUGIN_KEY;
    }

    public function getTemplate(): string
    {
        return '@TagsSummary/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function handleRequest(Account $account, Plugin $installation, array $queryParameters): void
    {
        $this->selectionHandler->handle($account, $installation, $queryParameters);
    }

    /**
     * @return array{
     *     sports: list<array{id: int, name: string}>,
     *     selected_sport_id: ?int,
     *     timescales: list<int>,
     *     selected_timescale: int,
     *     tags: list<array{id: int, name: string, count: int, usage_width: int}>
     * }
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function present(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation);

        $pluginId = $installation->getId();
        assert($pluginId !== null);
        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

        $sports = [];
        foreach ($this->sportRepository->findAllFor($account) as $sport) {
            $id = $sport->getId();
            if ($id !== null) {
                $sports[] = ['id' => $id, 'name' => $sport->getName()];
            }
        }

        $sportIds = array_column($sports, 'id');
        $storedSportId = (int) ($configuration['sport'] ?? 0);
        $runningSportId = $this->configurationManager->getList($account)->getGeneral()->getRunningSport();
        $selectedSportId = in_array($storedSportId, $sportIds, true) ? $storedSportId
            : (in_array($runningSportId, $sportIds, true) ? $runningSportId : ($sportIds[0] ?? null));

        $storedTimescale = (int) ($configuration['tagssummary_timescale'] ?? 1);
        $selectedTimescale = in_array($storedTimescale, self::TIMESCALES, true) ? $storedTimescale : 1;

        $tags = [];
        if ($selectedSportId !== null) {
            $since = $this->clock->now()->modify('-' . $selectedTimescale . ' months')->getTimestamp();
            $rows = $this->tagRepository->findUsageForSportSince($account, $selectedSportId, $since);
            $maxCount = $rows[0]['usage_count'] ?? 0;

            foreach ($rows as $row) {
                $tags[] = [
                    'id' => $row['id'],
                    'name' => $row['tag'],
                    'count' => $row['usage_count'],
                    'usage_width' => $maxCount > 0 ? (int) round(330 * $row['usage_count'] / $maxCount) : 0,
                ];
            }
        }

        return [
            'sports' => $sports,
            'selected_sport_id' => $selectedSportId,
            'timescales' => self::TIMESCALES,
            'selected_timescale' => $selectedTimescale,
            'tags' => $tags,
        ];
    }

    /**
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public static function validateInstallation(Account $account, Plugin $installation): void
    {
        $accountId = $account->getId();

        if (
            $accountId === null
            || $installation->getId() === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== self::PLUGIN_KEY
            || ! $installation->isPanel()
            || $installation->isInactive()
        ) {
            throw new InvalidArgumentException('The TagsSummary panel installation is not available for this account.');
        }
    }
}
