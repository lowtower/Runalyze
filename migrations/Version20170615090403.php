<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ClimbScoreCalculator;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\FlatOrHillyAnalyzer;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Service\ElevationCorrection\StepwiseElevationProfileFixer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170615090403 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly EntityManager $EntityManager
    ) {
        parent::__construct($connection, $logger);
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManager $em */
        $em = $this->EntityManager;
        $em->getConnection()
            ->getConfiguration()
            ->setSQLLogger();

        $repo = $em->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Training::class);
        $numLocked = $this->getNumberOfLockedRoutes($em);

        $flatOrHillyAnalyzer = new FlatOrHillyAnalyzer();
        $climbScoreCalculator = new ClimbScoreCalculator();

        while ($numLocked > 0) {
            $activities = $repo->createQueryBuilder('t')
                ->select('partial t.{id, climbScore, percentageHilly, route}')
                ->addSelect('partial r.{id, lock, distance, elevationsOriginal, elevationsCorrected}')
                ->addSelect('partial tr.{activity, distance}')
                ->join('t.route', 'r')
                ->join('t.trackdata', 'tr')
                ->where('r.lock = 1')
                ->setMaxResults(100)
                ->getQuery();

            $batchSize = 100;
            $i = 0;
            $iterableResult = $activities->toIterable();

            foreach ($iterableResult as $row) {
                /** @var Training $activity */
                $activity = $row;

                $this->correctSizeOfCorrectedElevationsIfRequired($activity);

                $oldElevations = $activity->getRoute()
                    ->getElevationsCorrected();

                if ($activity->getRoute()->getElevationsCorrected() !== null && $activity->getTrackdata()->getDistance() !== null) {
                    $activity->getRoute()
                        ->setElevationsCorrected((new StepwiseElevationProfileFixer(
                            5,
                            StepwiseElevationProfileFixer::METHOD_VARIABLE_GROUP_SIZE
                        ))->fixStepwiseElevations(
                            $activity->getRoute()
                                ->getElevationsCorrected(),
                            $activity->getTrackdata()
                                ->getDistance()
                        ));
                }

                try {
                    $flatOrHillyAnalyzer->calculatePercentageHillyFor($activity);
                    $climbScoreCalculator->calculateFor($activity);
                } catch (\InvalidArgumentException $e) {
                    $this->write(sprintf(
                        '    <comment>Warning: Activity #%u failed. (%s)</comment>',
                        $activity->getId(),
                        $e->getMessage()
                    ));
                }

                $activity->getRoute()
                    ->setElevationsCorrected($oldElevations);
                $activity->getRoute()
                    ->setLock(false);

                $em->persist($activity);

                if (($i % $batchSize) === 0) {
                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();
                }

                ++$i;
            }

            $em->flush();

            $numLocked -= 100;
        }
    }

    protected function getNumberOfLockedRoutes(EntityManager $em): int
    {
        return (int) $em->createQueryBuilder()
            ->select('count(r.id)')
            ->where('r.lock = 1')
            ->from(\Runalyze\Bundle\CoreBundle\Entity\Route::class, 'r')
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function correctSizeOfCorrectedElevationsIfRequired(Training $activity)
    {
        if (
            $activity->hasRoute() && $activity->getRoute()
                ->getElevationsCorrected() !== null &&
            $activity->hasTrackdata() && $activity->getTrackdata()
                ->getDistance() !== null
        ) {
            $numDistance = count($activity->getTrackdata()->getDistance());
            $numElevations = count($activity->getRoute()->getElevationsCorrected());

            if ($numElevations > $numDistance) {
                $activity->getRoute()
                    ->setElevationsCorrected(array_slice($activity->getRoute()->getElevationsCorrected(), 0, $numDistance));
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
