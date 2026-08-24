<?php

namespace Runalyze\Service\ElevationCorrection\Strategy;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Runalyze\Service\ElevationCorrection\Exception\InvalidResponseException;
use Runalyze\Service\ElevationCorrection\Exception\OverQueryLimitException;
use Runalyze\Service\ElevationCorrection\Exception\StrategyException;

/**
 * Elevation corrector strategy: ws.geonames.org
 *
 * We use SRTM3 data from GeoNames.
 * ASTER data (resolution of 30m) are available but are not processed (i.e. contain holes).
 * GTOPO30 data have a resolution of only 1 km.
 *
 * @see http://www.geonames.org/export/web-services.html#srtm3
 * @see http://www.geonames.org/export/credits.html
 * @see http://www.geonames.org/export/webservice-exception.html
 */
class Geonames extends AbstractStrategyFromExternalAPI
{
    use GuessUnknownValuesTrait;

    /**
     * @var string
     */
    protected $GeonamesUsername;

    /**
     * @param string $geonamesUsername
     */
    public function __construct($geonamesUsername, Client $client, ?LoggerInterface $logger = null)
    {
        $this->GeonamesUsername = $geonamesUsername;

        parent::__construct($client, $logger);
    }

    public function isPossible(): bool
    {
        return (string) $this->GeonamesUsername !== '';
    }

    public function loadAltitudeData(array $latitudes, array $longitudes)
    {
        $altitudes = parent::loadAltitudeData($latitudes, $longitudes);

        $this->guessUnknown($altitudes, -32768);

        return $altitudes;
    }

    /**
     * @return int[]
     */
    protected function fetchElevationFor(array $latitudes, array $longitudes): array
    {
        $response = $this->tryToLoadJsonFromUrl($this->getUrlFor($latitudes, $longitudes));

        $elevationData = [];
        $responseLength = count($response['geonames']);

        for ($i = 0; $i < $responseLength; $i++) {
            $elevationData[] = (int) $response['geonames'][$i]['srtm3'];
        }

        return $elevationData;
    }

    protected function checkApiResult(array $json): bool
    {
        if (isset($json['status']) && isset($json['status']['value'])) {
            if (isset($json['status']['message'])) {
                $this->logger->warning(sprintf('Geonames request failed: %s', $json['status']['message']));
            }

            if ((int) $json['status']['value'] === 10) {
                throw new StrategyException('Invalid user id for geonames.');
            }

            throw new OverQueryLimitException('Geonames request failed: limit of credits reached.');
        }

        if (! isset($json['geonames']) || ! is_array($json['geonames'])) {
            throw new InvalidResponseException('Geonames returned malformed code.');
        }

        return true;
    }

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     */
    protected function getUrlFor(array $latitudes, array $longitudes): string
    {
        return sprintf(
            'http://api.geonames.org/srtm3JSON?lats=%s&lngs=%s&username=%s',
            implode(',', $latitudes),
            implode(',', $longitudes),
            $this->GeonamesUsername
        );
    }
}
