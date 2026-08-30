<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GeneratePoster
{
    public $Python3Path;

    /**
     * @var array
     */
    protected $Parameter = [];

    /**
     * @var string
     */
    protected $KernelRootDir;

    /**
     * @var string
     */
    protected $Python3path;

    /**
     * @var TrainingRepository
     */
    protected $TrainingRepository;

    /**
     * @var string
     */
    protected $Filename;

    /**
     * @param string $kernelRootDir
     * @param string $python3Path
     */
    public function __construct($kernelRootDir, $python3Path, TrainingRepository $trainingRepository)
    {
        $this->KernelRootDir = $kernelRootDir;
        $this->Python3Path = $python3Path;
        $this->TrainingRepository = $trainingRepository;
    }

    protected function pathToRepository(): string
    {
        return $this->KernelRootDir . '/../vendor/runalyze/gpxtrackposter/';
    }

    protected function pathToSvgDirectory(): string
    {
        return $this->KernelRootDir . '/../var/poster/';
    }

    /**
     * @param string $athlete
     * @param string $year
     */
    protected function generateRandomFileName($athlete, $year)
    {
        $this->Filename = md5($athlete . $year . strtotime('now')) . '.svg';
    }

    /**
     * @return string path to generated file
     */
    public function generate(): string
    {
        $command = array_merge(
            [$this->Python3Path, 'create_poster.py'],
            $this->Parameter
        );
        $builder = new Process($command);
        $builder->setWorkingDirectory($this->pathToRepository())
            ->run();

        if (! $builder->isSuccessful()) {
            throw new ProcessFailedException($builder);
        }

        return $this->pathToSvgDirectory() . $this->Filename;
    }

    public function buildCommand(string $type, string $jsonDir, int $year, Account $account, Sport $sport, ?string $title, $backgroundColor, $trackColor, $textColor, $raceColor)
    {
        $this->generateRandomFileName($account->getUsername(), (string) $year);

        $this->Parameter[] = '--json-dir ' . $jsonDir;
        $this->Parameter[] = '--athlete ' . $account->getUsername();
        $this->Parameter[] = '--year ' . $year;
        $this->Parameter[] = '--output ' . $this->pathToSvgDirectory() . $this->Filename;
        $this->Parameter[] = '--type ' . $type;
        $this->Parameter[] = '--title ' . escapeshellarg($title);
        $this->Parameter[] = '--background-color  ' . escapeshellarg($backgroundColor);
        $this->Parameter[] = '--track-color ' . escapeshellarg($trackColor);
        $this->Parameter[] = '--text-color ' . escapeshellarg($textColor);
        $this->Parameter[] = '--special-color ' . escapeshellarg($raceColor);

        $this->addStatsParameter($account, $sport, $year);

        if ((new Filesystem())->exists($jsonDir . '/special.params')) {
            $this->Parameter[] = file_get_contents($jsonDir . '/special.params');
        }
    }

    private function addStatsParameter(Account $account, Sport $sport, int $year)
    {
        $stats = $this->TrainingRepository->getStatsForPoster($account, $sport, $year)
            ->getArrayResult();
        $data = $stats[0];

        $this->Parameter[] = '--stat-num ' . (int) $data['num'];
        $this->Parameter[] = '--stat-total ' . (float) $data['total_distance'];
        $this->Parameter[] = '--stat-min ' . (float) $data['min_distance'];
        $this->Parameter[] = '--stat-max ' . (float) $data['max_distance'];
    }

    /**
     * @return array<int, string>
     */
    public function availablePosterTypes(): array
    {
        return ['grid', 'calendar', 'circular', 'heatmap'];
    }

    public function deleteSvg()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->pathToSvgDirectory() . $this->Filename);
    }
}
