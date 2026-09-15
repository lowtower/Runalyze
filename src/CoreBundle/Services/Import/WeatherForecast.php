<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\DatabaseCacheInterface;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\DatabaseCache;
use Runalyze\Service\WeatherForecast\Strategy\MeteostatNet;
use Runalyze\Service\WeatherForecast\Strategy\OpenWeatherMap;
use Runalyze\Service\WeatherForecast\Strategy\StrategyCollection;
use Runalyze\Service\WeatherForecast\Strategy\StrategyInterface;

class WeatherForecast
{
    protected StrategyCollection $StrategyCollection;

    public function __construct(
        DatabaseCache $cache,
        # DarkSky $darkSky,
        OpenWeatherMap $openWeatherMap,
        // #TSC - new weather source
        MeteostatNet $meteostatNet,
        protected DatabaseCacheInterface $DatabaseCache
    ) {
        $this->StrategyCollection = new StrategyCollection();
        $this->StrategyCollection->add($cache);
        // NOTE: DarkSky (forecast.io) intentionally NOT added to the
        // strategy collection anymore. Apple acquired Dark Sky in March
        // 2020 and permanently shut down its public API on 31 March 2023
        // (see https://support.apple.com/en-in/102594) - every request to
        // api.darksky.net has returned an error (HTTP 410 Gone, or a
        // network/DNS failure) for years now, regardless of how valid the
        // configured API key is or how correct this codebase's own request
        // logic is. Keeping it in the fallback chain only wastes time on a
        // request that can never succeed, before falling through to
        // OpenWeatherMap/MeteostatNet - removing it here is a pure
        // performance/cleanliness improvement with no behavioral downside,
        // since it could never contribute real weather data anyway.
        # $this->StrategyCollection->add($darkSky);
        $this->StrategyCollection->add($openWeatherMap);
        $this->StrategyCollection->add($meteostatNet);
    }

    public function loadForecast(Location $location): ?WeatherData
    {
        $result = $this->StrategyCollection->tryToLoadForecast($location);

        if ($result instanceof WeatherData) {
            $this->cacheWeatherData($result, $location, $this->StrategyCollection->getLastSuccessfulStrategy());
        }

        return $result;
    }

    /**
     * @return array<string, string> strategy class name => 'not_possible'|'no_data'
     */
    public function getLastAttemptReasons(): array
    {
        return $this->StrategyCollection->getLastAttemptReasons();
    }

    /**
     * @return array<string, \Throwable> strategy class name => exception
     */
    public function getLastAttemptExceptions(): array
    {
        return $this->StrategyCollection->getLastAttemptExceptions();
    }

    protected function cacheWeatherData(WeatherData $data, Location $location, StrategyInterface $strategy)
    {
        if ($strategy->isCachable() && ! $data->hasNoUsableData()) {
            $this->DatabaseCache->cacheWeatherData($data, $location);
        }
    }
}
