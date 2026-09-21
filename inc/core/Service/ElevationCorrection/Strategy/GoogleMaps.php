<?php

namespace Runalyze\Service\ElevationCorrection\Strategy;

use Runalyze\Service\ElevationCorrection\Exception\InvalidResponseException;
use Runalyze\Service\ElevationCorrection\Exception\OverQueryLimitException;

/**
 * @see https://developers.google.com/maps/documentation/elevation/?hl=de&csw=1
 * @see http://maps.googleapis.com/maps/api/elevation/json?locations=0,0&sensor=false
 */
class GoogleMaps extends AbstractStrategyFromExternalAPI
{
    use GuessUnknownValuesTrait;

    public function isPossible(): bool
    {
        return true;
    }

    public function loadAltitudeData(array $latitudes, array $longitudes): ?array
    {
        $altitudes = parent::loadAltitudeData($latitudes, $longitudes);

        $this->guessUnknown($altitudes, -3492);

        return $altitudes;
    }

    /**
     * @return int[]
     */
    protected function fetchElevationFor(array $latitudes, array $longitudes): array
    {
        $response = $this->tryToLoadJsonFromUrl($this->getUrlFor($latitudes, $longitudes));

        $altitudes = [];
        $responseLength = count($response['results']);

        for ($i = 0; $i < $responseLength; $i++) {
            $altitudes[] = (int) round($response['results'][$i]['elevation']);
        }

        return $altitudes;
    }

    protected function checkApiResult(array $json): bool
    {
        if (isset($json['error_message'])) {
            $this->logger->warning(sprintf('GoogleMaps request failed: %s', $json['error_message']));
        }

        if (isset($json['status']) && $json['status'] == 'OVER_QUERY_LIMIT') {
            throw new OverQueryLimitException($json['error_message'] ?? 'GoogleMaps: query limit exceeded.');
        }

        if (
            isset($json['status'])
            && in_array($json['status'], ['REQUEST_DENIED', 'INVALID_REQUEST'], true)
        ) {
            throw new InvalidResponseException(
                $json['error_message'] ?? sprintf('GoogleMaps request denied (status: %s).', $json['status'])
            );
        }

        if (! isset($json['results']) || ! isset($json['results'][0]['elevation'])) {
            throw new InvalidResponseException('GoogleMaps returned malformed code.');
        }

        return true;
    }

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     */
    protected function getUrlFor(array $latitudes, array $longitudes): string
    {
        $numberOfCoordinates = count($latitudes);
        $coordinates = [];

        for ($i = 0; $i < $numberOfCoordinates; $i++) {
            $coordinates[] = $latitudes[$i] . ',' . $longitudes[$i];
        }

        return sprintf(
            'http://maps.googleapis.com/maps/api/elevation/json?locations=%s',
            substr(implode('|', $coordinates), 0, -1)
        );
    }
}
