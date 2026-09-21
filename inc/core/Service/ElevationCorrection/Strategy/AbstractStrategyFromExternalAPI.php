<?php

namespace Runalyze\Service\ElevationCorrection\Strategy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Runalyze\Service\ElevationCorrection\Exception\InvalidResponseException;
use Runalyze\Service\ElevationCorrection\Exception\NoResponseException;
use Runalyze\Service\ElevationCorrection\Exception\StrategyException;

abstract class AbstractStrategyFromExternalAPI extends AbstractStrategy implements LoggerAwareInterface, LastExceptionAwareInterface
{
    use LoggerAwareTrait;

    protected int $PointsPerCall = 20;

    protected ?\Throwable $LastException = null;

    public function __construct(
        protected Client $HttpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?: new NullLogger();
    }

    public function getLastException(): ?\Throwable
    {
        return $this->LastException;
    }

    public function loadAltitudeData(array $latitudes, array $longitudes): ?array
    {
        $this->LastException = null;

        try {
            $altitudes = $this->collectGroupWiseAltitudes($latitudes, $longitudes);
        } catch (StrategyException $strategyException) {
            $this->LastException = $strategyException;

            return null;
        }

        return $altitudes;
    }

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     *
     * @return int[] [m]
     */
    protected function collectGroupWiseAltitudes(array $latitudes, array $longitudes): array
    {
        $numberOfPoints = count($latitudes);
        $pointsToGroup = (int) ($this->PointsToGroup * ceil($numberOfPoints / 1000));
        if ($pointsToGroup <= 0) {
            throw new StrategyException('Invalid grouping configuration: PointsToGroup must be greater than zero.');
        }

        $latitudesForRequest = [];
        $longitudesForRequest = [];
        $altitudes = [];

        for ($i = 0; $i < $numberOfPoints; $i++) {
            if ($i % $pointsToGroup === 0) {
                $latitudesForRequest[] = $latitudes[$i];
                $longitudesForRequest[] = $longitudes[$i];
            }

            if (($i + 1) % ($this->PointsPerCall * $pointsToGroup) === 0 || $i === $numberOfPoints - 1) {
                $result = $this->fetchElevationFor($latitudesForRequest, $longitudesForRequest);
                $points = count($result);

                for ($d = 0; $d < $points; $d++) {
                    for ($j = 0; $j < $pointsToGroup; $j++) {
                        $altitudes[] = $result[$d];
                    }
                }

                $latitudesForRequest = [];
                $longitudesForRequest = [];
            }
        }

        if (count($altitudes) > $numberOfPoints) {
            return array_slice($altitudes, 0, $numberOfPoints);
        }

        return $altitudes;
    }

    protected function tryToLoadJsonFromUrl(string|UriInterface $url)
    {
        try {
            $response = $this->HttpClient->get($url);
            $result = json_decode($response->getBody()->getContents(), true);

            if (is_array($result) && $this->checkApiResult($result)) {
                return $result;
            }
        } catch (RequestException $requestException) {
            $this->logger->warning('Elevation request failed.', [
                'exception' => $requestException,
            ]);

            throw new NoResponseException($requestException->getMessage(), $requestException->getCode(), $requestException);
        }

        throw new InvalidResponseException();
    }

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     *
     * @return int[] [m]
     */
    abstract protected function fetchElevationFor(array $latitudes, array $longitudes): array;

    abstract protected function checkApiResult(array $json): bool;
}
