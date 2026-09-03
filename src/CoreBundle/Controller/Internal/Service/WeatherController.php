<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal\Service;

use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Source\SourceInterface;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Service\WeatherForecast\Location;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/_internal/service/weather")
 */
class WeatherController extends AbstractController
{
    use AppServiceSubscriberTrait;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("", name="internal-service-weather")
     * @Security("has_role('ROLE_USER')")
     */
    public function fetchWeatherDataAction(Request $request, Account $account)
    {
        $location = $this->getLocationForRequest($request, $account);

        // #TSC: if no location is given, cancel
        if (! $location->hasPosition()) {
            return null;
        }

        $weather = $this->get('app.weather_forecast')
            ->loadForecast($location) ?: new WeatherData();

        /** @var SourceInterface|null $source */
        $source = $weather->Source ? WeatherSourceProfile::get($weather->Source) : null;

        return new JsonResponse([
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
        ]);
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
            $location->setLocationName($this->get('app.configuration_manager')->getList($account)->get('activity-form.PLZ'));
        }

        if ($request->query->has('time')) {
            $location->setDateTime(new \DateTime('@' . $request->query->get('time')));
        } elseif ($request->query->has('date')) {
            $location->setDateTime(new \DateTime($request->query->get('date')));
        }

        // #TSC set also the timezone for fetch historical time based data
        $timezone = (int) $account->getTimezone();
        $timezoneName = Timezone::getFullNameByEnum($timezone);
        $location->setTimezone($timezoneName);

        return $location;
    }
}
