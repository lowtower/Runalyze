<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Strecken;

use League\Geotools\Geohash\Geohash;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Model\Route\Entity as LegacyRoute;
use Runalyze\Parameter\Application\ActivityRouteBreak;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @phpstan-type Position array{float, float}
 */
final readonly class RouteNetworkPresenter
{
    private const int ROUTES_PER_INTERVAL = 250;

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Account $account, InputBag $query): array
    {
        $filter = $this->filterFactory->fromQuery($account, new InputBag([
            'sport' => $query->getString('sport'),
            'jahr' => $query->getString('y'),
        ]));
        $interval = filter_var($query->getString('posint', '1'), FILTER_VALIDATE_INT);
        $interval = $interval === false ? 1 : min(5, max(1, $interval));

        $limit = self::ROUTES_PER_INTERVAL * $interval;
        $samples = $this->trainingRepository->iterateRecentMappedRoutesFor(
            $account,
            $filter['sport'] > 0 ? $filter['sport'] : null,
            $filter['from'],
            $filter['until'],
            $limit
        );

        $configuration = $this->configurationManager->getList($account);
        $precision = min(20, max(1, (int) $configuration->get('activity-view.GMAP_PATH_PRECISION')));
        $breakDistance = $configuration->get('activity-view.GMAP_PATH_BREAK') === ActivityRouteBreak::NO_BREAK
            ? INF
            : 0.05 * $precision;
        $routes = [];
        $pointCount = 0;
        $bounds = [90.0, 180.0, -90.0, -180.0];
        foreach ($samples as $sample) {
            $segments = $this->segments($sample['geohashes'] ?? [], $interval * $precision, $breakDistance);
            if ($segments === []) {
                continue;
            }

            foreach ($segments as $segment) {
                $pointCount += count($segment);
                foreach ($segment as [$lat, $lng]) {
                    $bounds = [min($bounds[0], $lat), min($bounds[1], $lng), max($bounds[2], $lat), max($bounds[3], $lng)];
                }
            }

            $routes[] = [
                'id' => $sample['id'],
                'name' => mb_strlen($sample['name']) > 50 ? mb_substr($sample['name'], 0, 50) . '...' : $sample['name'],
                'date' => gmdate('d.m.Y H:i', $sample['time']),
                'distance' => $sample['distance'],
                'sportIcon' => $sample['sportIcon'],
                'segments' => $segments,
            ];
        }

        return [
            'filter' => $filter,
            'interval' => $interval,
            'routes' => $routes,
            'bounds' => [[$bounds[0], $bounds[1]], [$bounds[2], $bounds[3]]],
            'pointCount' => $pointCount,
            'limit' => $limit,
            'layer' => $configuration->getActivityView()->getMapLayer(),
            'scroll' => $configuration->get('activity-view.TRAINING_MAP_ZOOM_ON_SCROLL') === 'true',
        ];
    }

    /**
     * @param list<string> $hashes
     * @return list<list<Position>>
     */
    private function segments(array $hashes, int $step, float $breakDistance): array
    {
        $segments = [];
        $segment = [];
        $previous = null;
        $decoder = new Geohash();
        $last = count($hashes) - 1;
        foreach ($hashes as $index => $hash) {
            if (($index % $step !== 0 && $index !== $last)
                || $hash === '7zzzzzzzzzzz' || ! preg_match('/^[0-9bcdefghjkmnpqrstuvwxyz]{12}$/D', $hash)
            ) {
                continue;
            }

            /** @var Geohash $decoded */
            $decoded = $decoder->decode($hash);
            $coordinate = $decoded->getCoordinate();
            $position = [(float) $coordinate->getLatitude(), (float) $coordinate->getLongitude()];
            if ($previous !== null && LegacyRoute::gpsDistance($previous[0], $previous[1], $position[0], $position[1]) > $breakDistance) {
                $segments[] = $segment;
                $segment = [];
            }

            $segment[] = $position;
            $previous = $position;
        }

        if ($segment !== []) {
            $segments[] = $segment;
        }

        return $segments;
    }
}
