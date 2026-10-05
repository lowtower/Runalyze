<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxCorrectionFactorCalculation;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class RaceresultRepositoryTest extends AbstractRepositoryTestCase
{
    protected RaceresultRepository $RaceresultRepository;

    protected TrainingRepository $TrainingRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->RaceresultRepository = $this->getEntityManager()
            ->getRepository(Raceresult::class);
        $this->TrainingRepository = $this->getEntityManager()
            ->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase(): void
    {
        $this->assertNull($this->RaceresultRepository->findForActivity(1));
        $this->assertNull($this->RaceresultRepository->findForAccount(1, 1));
        $this->assertEquals(1.0, $this->RaceresultRepository->getEffectiveVO2maxCorrectionFactor(
            $this->Account,
            $this->getDefaultAccountsRunningSport()
                ->getId()
        ));
    }

    public function testSingleRace(): void
    {
        $raceActivity = $this->getActivityForDefaultAccount(mktime(3, 14, 15, 9, 26, 2016), 2400, 10.0);
        $race = $this->insertRace('Awesome pirace', $raceActivity);

        $this->assertNull($this->RaceresultRepository->findForAccount($race->getActivity()->getId(), $this->Account->getId() + 1));
        $this->assertEquals(
            $this->RaceresultRepository->findForActivity($race->getActivity()->getId()),
            $this->RaceresultRepository->findForAccount($race->getActivity()->getId(), $this->Account->getId())
        );
        $this->assertEquals(2400, $this->RaceresultRepository->findForAccount($race->getActivity()->getId(), $this->Account->getId())->getOfficialTime());
        $this->assertEmpty($this->RaceresultRepository->findForSportAndYear($this->Account, $this->getDefaultAccountsRunningSport(), 2017));
        $this->assertEmpty($this->RaceresultRepository->findForSportAndYear($this->Account, $this->getDefaultAccountsCyclingSport(), 2016));
        $this->assertEmpty($this->RaceresultRepository->findForSportAndYear(new Account(), $this->getDefaultAccountsRunningSport(), 2016));

        $races = $this->RaceresultRepository->findForSportAndYear($this->Account, $this->getDefaultAccountsRunningSport(), 2016);

        $this->assertEquals(1, count($races));
        $this->assertEquals(mktime(3, 14, 15, 9, 26, 2016), $races[0]['time']);
        $this->assertEquals('10', $races[0][0]['officialDistance']);
        $this->assertEquals('Awesome pirace', $races[0][0]['name']);
        $this->assertFalse($this->getContainer()->get('app.recalculation_manager')->isTaskScheduled($this->Account, VO2maxCorrectionFactorCalculation::class));
    }

    public function testFindingVO2maxCorrectionFactorForSingleRace(): void
    {
        $activity = $this->getActivityForDefaultAccount(time(), 2400, 10.0);
        $activity->setPulseAvg(160)
            ->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $expectedFactor = $activity->getVO2maxByTime() / $activity->getVO2max();

        $this->assertEqualsWithDelta($expectedFactor, $this->RaceresultRepository->getEffectiveVO2maxCorrectionFactor(
            $this->Account,
            $this->getDefaultAccountsRunningSport()
                ->getId()
        ), 0.01);
        $this->assertTrue($this->getContainer()->get('app.recalculation_manager')->isTaskScheduled($this->Account, VO2maxCorrectionFactorCalculation::class));
    }

    public function testFindingVO2maxCorrectionFactorForMultipleRaces(): void
    {
        $activity = $this->getActivityForDefaultAccount(time(), 2400, 10.0);
        $activity->setPulseAvg(160)
            ->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $activity = $this->getActivityForDefaultAccount(time(), 2100, 10.0);
        $activity->setPulseAvg(140)
            ->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $activity = $this->getActivityForDefaultAccount(time(), 1200, 5.0);
        $activity->setPulseAvg(180)
            ->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $expectedFactor = $activity->getVO2maxByTime() / $activity->getVO2max();

        $activity = $this->getActivityForDefaultAccount(time(), 2400, 5.0);
        $activity->setPulseAvg(100)
            ->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $this->assertEqualsWithDelta($expectedFactor, $this->RaceresultRepository->getEffectiveVO2maxCorrectionFactor(
            $this->Account,
            $this->getDefaultAccountsRunningSport()
                ->getId()
        ), 0.01);
    }

    protected function insertRace(string $name, Training $activity): Raceresult
    {
        $this->TrainingRepository->save($activity);

        $race = new Raceresult();
        $race->fillFromActivity($activity);
        $race->setName($name);

        $this->RaceresultRepository->save($race);

        return $race;
    }
}
