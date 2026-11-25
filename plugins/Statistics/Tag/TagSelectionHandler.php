<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Tag;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInstallation;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;

final readonly class TagSelectionHandler
{
    private const string SETTING = 'tag';

    public function __construct(
        private TagRepository $tagRepository,
        private DoctrinePluginConfigurationStorage $configurationStorage
    ) {}

    /**
     * @return array{tags: array<int, string>, selectedTagId: ?int}
     */
    public function getSelection(Account $account, Plugin $installation): array
    {
        $pluginId = StatisticsInstallation::requireId($account, $installation, TagStatisticsPresenter::KEY);
        $tags = [];
        foreach ($this->tagRepository->findAllFor($account) as $tag) {
            $tags[$tag->getId()] = $tag->getTag();
        }

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId]);
        $selectedTagId = (int) ($configuration[$pluginId][self::SETTING] ?? '');

        return [
            'tags' => $tags,
            'selectedTagId' => isset($tags[$selectedTagId]) ? $selectedTagId : null,
        ];
    }

    public function select(Account $account, Plugin $installation, mixed $requestedTag): void
    {
        StatisticsInstallation::requireId($account, $installation, TagStatisticsPresenter::KEY);

        if (! is_string($requestedTag) || ! ctype_digit($requestedTag)) {
            return;
        }

        $tagId = (int) $requestedTag;
        $tag = $this->tagRepository->findOneBy([
            'id' => $tagId,
            'account' => $account,
        ]);

        if ($tag instanceof Tag) {
            $this->configurationStorage->saveValue($installation, self::SETTING, (string) $tagId);
        }
    }
}
