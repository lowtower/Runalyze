<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Tag;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class TagStatisticsPresenter implements StatisticsInterface, StatisticsConfigurationInterface, StatisticsRequestHandlerInterface
{
    public const string KEY = 'RunalyzePluginStat_Tag';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TagRepository $tagRepository,
        private ConfigurationManager $configurationManager,
        private TagSelectionHandler $selectionHandler
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Tag analysis');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('How often have you tagged your activities with tag x?');
    }

    public function getTemplate(): string
    {
        return '@Tag/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Tag/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        return $this->selectionHandler->getSelection($account, $installation) + [
            'fieldLabel' => __('Tag to analyze'),
            'chooseTag' => __('Please choose a tag.'),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->selectionHandler->select($account, $installation, $data['tag'] ?? null);
    }

    public function handleRequest(Account $account, Plugin $installation, InputBag $query): void
    {
        if ($query->has('dat')) {
            $this->selectionHandler->select($account, $installation, $query->all()['dat']);
        }
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $selection = $this->selectionHandler->getSelection($account, $installation);
        $mainSport = $this->configurationManager->getList($account)->getGeneral()->getMainSport();
        $filter = $this->filterFactory->fromQuery($account, $query, defaultSport: $mainSport, defaultYear: -1);
        $sport = $filter['sport'];
        $tagId = $selection['selectedTagId'];
        $counts = $tagId === null ? [] : $this->tagRepository->findMonthlyUsageFor(
            $account,
            $tagId,
            $sport > 0 ? $sport : null,
            $filter['from'],
            $filter['until']
        );

        return $selection + [
            'pluginId' => $installation->getId(),
            'filter' => $filter,
            'title' => $sport > 0 ? $this->getName() . ': ' . $filter['sports'][$sport] : $this->getName(),
            'chooseTag' => __('Please choose a tag.'),
            'tagLabel' => __('Tag'),
            'emptyMessage' => __('No activities found.'),
            'counts' => $counts,
        ];
    }
}
