<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Doctrine\ORM\Query;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Model;
use Symfony\Component\Filesystem\Filesystem;

class GenerateJsonData
{
    /**
     * @var string
     */
    protected $Directory;

    /**
     * @param string $KernelRootDir
     */
    public function __construct(
        protected TrainingRepository $TrainingRepository,
        protected RaceresultRepository $RaceresultRepository,
        protected $KernelRootDir
    ) {
    }

    public function getPathToJsonFiles(): string
    {
        return $this->KernelRootDir . '/../var/tmp/' . $this->Directory;
    }

    public function createJsonFilesFor(Account $account, Sport $sport, int $year): void
    {
        $this->Directory = md5($account->getId() . strtotime('now'));

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getPathToJsonFiles(), 0755);

        $counter = 0;

        $query = $this->TrainingRepository->getQueryForJsonPosterData($account, $sport, $year);
        $result = $query->toIterable([], Query::HYDRATE_SCALAR);

        foreach ($result as $data) {
            $json = [
                'start' => date('Y-m-d H:i:s', $data['time']),
                'end' => date('Y-m-d H:i:s', $data['time'] + $data['s']),
                'length' => 1000.0 * (float) $data['distance'],
                'segments' => $this->getSegmentsFor($data['geohashes'], $data['distance']),
            ];
            $filesystem->dumpFile($this->getPathToJsonFiles() . '/' . date('Y-m-d-His', $data['time']) . '.json', json_encode($json));
            $counter++;
        }

        $this->listSpecialFiles($account, $sport, $year);
    }

    /**
     * @param string $geohashLine
     * @param float $distance
     */
    protected function getSegmentsFor($geohashLine, $distance): array
    {
        $segments = [];
        $segments[] = [];

        if ($geohashLine === null || $geohashLine == '') {
            return $segments;
        }

        $loop = new Model\Route\Loop(new Model\Route\Entity([
            Model\Route\Entity::GEOHASHES => $geohashLine,
        ]));
        $loop->setStepSize(5);

        $pauseLimit = 50 * 5 * $distance / $loop->num();
        $currentSegment = 0;

        while ($loop->nextStep()) {
            if ($loop->geohash() !== '7zzzzzzzzzzz') {
                $coordinate = $loop->coordinate();
                $segments[$currentSegment][] = [
                    'lat' => (float) $coordinate->getLatitude(),
                    'lng' => (float) $coordinate->getLongitude(),
                ];

                if ($loop->calculatedStepDistance() > $pauseLimit) {
                    $segments[] = [];
                    $currentSegment++;
                }
            }
        }

        return $segments;
    }

    protected function listSpecialFiles(Account $account, Sport $sport, int $year)
    {
        $races = $this->RaceresultRepository->findForSportAndYear($account, $sport, $year);

        if ($races !== []) {
            $argument = '';

            foreach ($races as $race) {
                $argument .= ' --special ' . date('Y-m-d-His', $race['time']) . '.json';
            }

            $filesystem = new Filesystem();
            $filesystem->dumpFile($this->getPathToJsonFiles() . '/special.params', $argument);
        }
    }

    public function deleteGeneratedFiles(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getPathToJsonFiles());
    }
}
