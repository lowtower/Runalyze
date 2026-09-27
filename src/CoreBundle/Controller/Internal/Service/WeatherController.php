<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal\Service;

use DateTime;
use GuzzleHttp\Exception\RequestException;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Source\SourceInterface;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Service\WeatherForecast\Exception\TooOldForCurrentConditionsException;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\DarkSky;
use Runalyze\Service\WeatherForecast\Strategy\DatabaseCache;
use Runalyze\Service\WeatherForecast\Strategy\MeteostatNet;
use Runalyze\Service\WeatherForecast\Strategy\OpenWeatherMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @Route("/_internal/service/weather")
 */
class WeatherController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @Route("", name="internal-service-weather")
     * @throws AccessDeniedException
     */
    public function fetchWeatherDataAction(Request $request, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $location = $this->getLocationForRequest($request, $account);

        // #TSC: if no location is given, cancel.
        if (! $location->hasPosition()) {
            return new JsonResponse([
                'empty' => true,
                'location' => [
                    'name' => $location->getLocationName(),
                    'lat' => null,
                    'lng' => null,
                    'date' => $location->hasDateTime() ? $location->getDateTime()
                        ->format('c') : '',
                ],
                'source' => [
                    'id' => null,
                    'name' => '',
                ],
                'weatherid' => null,
                'temperature' => null,
                'wind_speed' => null,
                'wind_deg' => null,
                'humidity' => null,
                'pressure' => null,
            ]);
        }

        /** @var WeatherForecast $weatherForecastService */
        $weatherForecastService = $this->get('app.weather_forecast');
        $weather = $weatherForecastService->loadForecast($location) ?: new WeatherData();

        /** @var ?SourceInterface $source */
        $source = $weather->Source ? WeatherSourceProfile::get($weather->Source) : null;

        $response = [
            'empty' => $weather->isEmpty(),
            'location' => [
                'name' => $location->getLocationName(),
                'lat' => $location->getLatitude(),
                'lng' => $location->getLongitude(),
                'date' => $location->hasDateTime() ? $location->getDateTime()
                    ->format('c') : '',
            ],
            'source' => [
                'id' => $weather->Source,
                'name' => $source !== null ? $source->getAttribution($this->translator) : '',
            ],
            'weatherid' => $weather->InternalConditionId,
            'temperature' => $weather->Temperature,
            'wind_speed' => $weather->WindSpeed,
            'wind_deg' => $weather->WindDirection,
            'humidity' => $weather->Humidity,
            'pressure' => $weather->AirPressure,
        ];

        if ($weather->isEmpty()) {
            $response['sources'] = $this->buildSourceDiagnostics($weatherForecastService);
        }

        return new JsonResponse($response);
    }

    /**
     * @return array<int, array{source: string, reason: string, detail: ?string}>
     */
    private function buildSourceDiagnostics(WeatherForecast $weatherForecastService): array
    {
        $reasons = $weatherForecastService->getLastAttemptReasons();
        $exceptions = $weatherForecastService->getLastAttemptExceptions();

        $sourceKeys = [
            DatabaseCache::class => 'cache',
            DarkSky::class => 'darksky',
            OpenWeatherMap::class => 'openweathermap',
            MeteostatNet::class => 'meteostatnet',
        ];

        $diagnostics = [];

        foreach ($reasons as $strategyClass => $reason) {
            $sourceKey = $sourceKeys[$strategyClass] ?? $strategyClass;

            if ($sourceKey === 'cache') {
                continue;
            }

            $detail = null;

            if ($reason === 'no_data' && isset($exceptions[$strategyClass])) {
                $reason = $this->classifyException($exceptions[$strategyClass]);
                $detail = $exceptions[$strategyClass]->getMessage();
            }

            $diagnostics[] = [
                'source' => $sourceKey,
                'reason' => $reason,
                'detail' => $detail,
            ];
        }

        return $diagnostics;
    }

    private function classifyException(Throwable $exception): string
    {
        if ($exception instanceof TooOldForCurrentConditionsException) {
            return 'too_old';
        }

        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $statusCode = $exception->getResponse()
                ->getStatusCode();

            if (in_array($statusCode, [401, 403], true)) {
                return 'auth_failed';
            }

            if ($statusCode === 429) {
                return 'rate_limited';
            }

            return 'request_failed';
        }

        return 'request_failed';
    }

    private function getLocationForRequest(Request $request, Account $account): Location
    {
        $location = new Location();

        if ($request->query->has('geohash')) {
            $location->setGeohash($request->query->get('geohash'));
        } elseif ($request->query->has('latlng')) {
            $latLng = explode(',', $request->query->get('latlng'));

            if (count($latLng) === 2) {
                $location->setPosition((float) $latLng[0], (float) $latLng[1]);
            }
        }

        if ($request->query->has('city')) {
            $location->setLocationName($request->query->get('city'));
        } elseif (! $location->hasPosition()) {
            /** @var ConfigurationManager $configurationManager */
            $configurationManager = $this->get('app.configuration_manager');

            $location->setLocationName($configurationManager->getList($account)->get('activity-form.PLZ'));
        }

        if ($request->query->has('time') && is_numeric($request->query->get('time'))) {
            $location->setDateTime(new DateTime('@' . $request->query->get('time')));
        } elseif ($request->query->has('date')) {
            if (strtotime($request->query->get('date')) !== false) {
                $location->setDateTime(new DateTime($request->query->get('date')));
            }
        }

        // #TSC set also the timezone for fetch historical time based data
        $timezone = $account->getTimezone();
        $timezoneName = Timezone::getFullNameByEnum($timezone);
        $location->setTimezone($timezoneName);

        return $location;
    }
}
