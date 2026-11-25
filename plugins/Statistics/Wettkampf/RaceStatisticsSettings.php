<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Wettkampf;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;

final readonly class RaceStatisticsSettings
{
    public function __construct(
        private DoctrinePluginConfigurationStorage $storage,
        private RaceresultRepository $races
    ) {}

    /**
     * @return array{mainDistance: float, distances: list<float>, funIds: list<int>}
     */
    public function forInstallation(Account $account, Plugin $installation): array
    {
        $id = $this->installationId($account, $installation);
        $values = $this->storage->loadForPlugins([$id])[$id] ?? [];

        return [
            'mainDistance' => (float) ($values['main_distance'] ?? '10'),
            'distances' => array_values(array_unique(array_map(
                static fn (string $distance): float => (float) $distance,
                array_filter(explode(',', $values['pb_distances'] ?? '1, 3, 5, 10, 21.1, 42.2'), is_numeric(...))
            ))),
            'funIds' => PluginConfRepository::parseActivityIds($values['fun_ids'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Account $account, Plugin $installation, array $data, float $factor): void
    {
        $this->installationId($account, $installation);
        $values = [];
        $main = $data['main_distance'] ?? null;
        if (is_string($main) && is_numeric($main) && is_finite((float) $main) && (float) $main > 0) {
            $values['main_distance'] = (string) round((float) $main * $factor, 2);
        }

        $distances = $data['pb_distances'] ?? null;
        if (is_string($distances)) {
            $kilometers = [];
            foreach (explode(',', $distances) as $distance) {
                $distance = trim($distance);
                if (is_numeric($distance) && is_finite((float) $distance) && (float) $distance > 0) {
                    $kilometers[] = round((float) $distance * $factor, 2);
                }
            }

            $values['pb_distances'] = implode(', ', array_unique($kilometers));
        }

        $this->storage->saveValues($installation, $values);
    }

    public function updateFunRace(Account $account, Plugin $installation, mixed $selection): void
    {
        $this->installationId($account, $installation);
        if (! is_string($selection) || preg_match('/\A(fun|nofun)-([1-9][0-9]*)\z/', $selection, $parts) !== 1) {
            return;
        }

        $activityId = filter_var($parts[2], FILTER_VALIDATE_INT);
        if ($activityId === false || ! $this->races->hasRaceForActivity($account, $activityId)) {
            return;
        }

        $this->storage->changeValue($installation, 'fun_ids', static function (string $value) use ($parts, $activityId): string {
            $ids = array_fill_keys(PluginConfRepository::parseActivityIds($value), true);
            if ($parts[1] === 'fun') {
                $ids[$activityId] = true;
            } else {
                unset($ids[$activityId]);
            }

            return implode(', ', array_keys($ids));
        });
    }

    private function installationId(Account $account, Plugin $installation): int
    {
        $id = $installation->getId();
        if ($id === null || $account->getId() === null || $installation->getAccount()->getId() !== $account->getId()
            || $installation->getKey() !== RaceStatisticsPresenter::KEY || ! $installation->isStatistic()
        ) {
            throw new InvalidArgumentException('The race statistics installation is not available for this account.');
        }

        return $id;
    }
}
