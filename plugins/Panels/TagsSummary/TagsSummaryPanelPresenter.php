<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\TagsSummary;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class TagsSummaryPanelPresenter implements PanelInterface, PanelRequestHandlerInterface, PanelConfigurationInterface
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

    public function getName(): string
    {
        return __('Tags summary');
    }

    public function getDescription(): string
    {
        return __('Summarises your tags for the selected sport.');
    }

    public function getConfigurationTemplate(): string
    {
        return '@TagsSummary/configuration.html.twig';
    }

    /**
     * @return array{sports: list<array{id: int, name: string}>, selected_sport_id: ?int, timescales: list<int>, selected_timescale: int, active: int}
     *
     * @throws InvalidArgumentException if the installation does not belong to the account
     */
    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation, true);

        $pluginId = $installation->getId();
        assert($pluginId !== null);
        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

        $sports = [];

        foreach ($this->sportRepository->findAllFor($account) as $sport) {
            $id = $sport->getId();

            if ($id !== null) {
                $sports[] = [
                    'id' => $id,
                    'name' => $sport->getName(),
                ];
            }
        }

        $sportIds = array_column($sports, 'id');
        $configuredId = (int) ($configuration['sport'] ?? 0);
        $runningSportId = $this->configurationManager->getList($account)->getGeneral()->getRunningSport();
        $selectedSportId = in_array($configuredId, $sportIds, true) ? $configuredId
            : (in_array($runningSportId, $sportIds, true) ? $runningSportId : ($sportIds[0] ?? null));

        $selectedTimescale = (int) ($configuration['tagssummary_timescale'] ?? 1);

        return [
            'sports' => $sports,
            'selected_sport_id' => $selectedSportId,
            'timescales' => self::TIMESCALES,
            'selected_timescale' => in_array($selectedTimescale, self::TIMESCALES, true) ? $selectedTimescale : 1,
            'active' => $installation->getActive(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException if the installation does not belong to the account
     */
    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->selectionHandler->handle($account, $installation, $data, true);
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
                $sports[] = [
                    'id' => $id,
                    'name' => $sport->getName(),
                ];
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
    public static function validateInstallation(Account $account, Plugin $installation, bool $allowInactive = false): void
    {
        $accountId = $account->getId();

        if (
            $accountId === null
            || $installation->getId() === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== self::PLUGIN_KEY
            || ! $installation->isPanel()
            || (! $allowInactive && $installation->isInactive())
        ) {
            throw new InvalidArgumentException('The TagsSummary panel installation is not available for this account.');
        }
    }
}
