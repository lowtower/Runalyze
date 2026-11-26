<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\VO2max;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\MarathonShapeCalculation;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxShapeCalculation;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapeDailyAggregator;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapeDataProvider;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class TrainingRepositoryTest extends AbstractRepositoryTestCase
{
    protected TrainingRepository $TrainingRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->TrainingRepository = $this->getEntityManager()
            ->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase(): void
    {
        $this->assertFalse($this->TrainingRepository->accountHasLockedTrainings(new Account()));
        $this->assertSame(0, $this->TrainingRepository->getNumberOfActivitiesFor(new Account()));
    }

    public function testTrainingTotalsSinceAreScopedByAccountSportAndInclusiveStart(): void
    {
        $runningSport = $this->getDefaultAccountsRunningSport();
        $cyclingSport = $this->getDefaultAccountsCyclingSport();
        $start = strtotime('2026-04-01 00:00:00 UTC');

        $this->insertActivityForDefaultAccount($start - 1, 1800, 3.0, $runningSport);
        $this->insertActivityForDefaultAccount($start, 1800, 5.5, $runningSport);
        $this->insertActivityForDefaultAccount($start + 3600, 2400, 10.25, $runningSport);
        $this->insertActivityForDefaultAccount($start + 7200, 3600, 25.0, $cyclingSport);

        $this->assertSame([
            'activity_count' => 2,
            'distance_sum' => 15.75,
        ], $this->TrainingRepository->findTrainingTotalsSince(
            $this->Account,
            $runningSport->getId(),
            $start
        ));

        $this->assertSame([
            'activity_count' => 1,
            'distance_sum' => 25.0,
        ], $this->TrainingRepository->findTrainingTotalsSince(
            $this->Account,
            $cyclingSport->getId(),
            $start
        ));

        $this->assertSame([
            'activity_count' => 0,
            'distance_sum' => 0.0,
        ], $this->TrainingRepository->findTrainingTotalsSince(
            $this->getEmptyAccount(),
            $runningSport->getId(),
            $start
        ));

        $this->assertSame([
            'activity_count' => 0,
            'distance_sum' => 0.0,
        ], $this->TrainingRepository->findTrainingTotalsSince(
            $this->Account,
            $runningSport->getId(),
            $start + 86400
        ));
    }

    public function testDailyTrimpAggregationRespectsAccountAndTimestampBounds(): void
    {
        $firstDay = strtotime('2026-01-10 08:00:00 UTC');
        $sameDay = strtotime('2026-01-10 18:00:00 UTC');
        $nextDay = strtotime('2026-01-11 07:00:00 UTC');

        $firstActivity = $this->getActivityForDefaultAccount($firstDay);
        $secondActivity = $this->getActivityForDefaultAccount($sameDay);
        $thirdActivity = $this->getActivityForDefaultAccount($nextDay);

        foreach ([$firstActivity, $secondActivity, $thirdActivity] as $activity) {
            $this->TrainingRepository->save($activity);
        }

        $firstActivity->setTrimp(40);
        $secondActivity->setTrimp(35);
        $thirdActivity->setTrimp(20);
        $this->getEntityManager()->flush();

        $this->assertSame([
            [
                'day' => '2026-01-10',
                'trimp' => 75.0,
            ],
            [
                'day' => '2026-01-11',
                'trimp' => 20.0,
            ],
        ], $this->TrainingRepository->findDailyTrimpForAccount($this->Account));

        $this->assertSame([
            [
                'day' => '2026-01-10',
                'trimp' => 35.0,
            ],
        ], $this->TrainingRepository->findDailyTrimpForAccount($this->Account, $sameDay, $sameDay));

        $this->assertSame([], $this->TrainingRepository->findDailyTrimpForAccount($this->getEmptyAccount()));
    }

    public function testShapePlotHistoryIncludesAllSportsButWeightsOnlyRunningVo2max(): void
    {
        $running = $this->getDefaultAccountsRunningSport();
        $cycling = $this->getDefaultAccountsCyclingSport();
        $start = strtotime('2026-01-10 00:00:00 UTC');
        $end = strtotime('2026-01-11 23:59:59 UTC');

        $before = $this->insertActivityForDefaultAccount($start - 1, 1000, 1.0, $running);
        $run = $this->insertActivityForDefaultAccount($start, 3600, 10.0, $running);
        $bike = $this->insertActivityForDefaultAccount($start + 60, 1800, 30.0, $cycling);
        $excluded = $this->insertActivityForDefaultAccount($end, 1200, 5.0, $running);
        $after = $this->insertActivityForDefaultAccount($end + 1, 1000, 1.0, $running);

        $before->setTrimp(5);
        $run->setTrimp(100)->setVO2max(45.0)->setVO2maxWithElevation(50.0)->setUseVO2max(true);
        $bike->setTrimp(80)->setVO2max(60.0)->setUseVO2max(true);
        $excluded->setTrimp(20)->setVO2max(48.0)->setUseVO2max(false);
        $after->setTrimp(5);
        $this->getEntityManager()->flush();

        $expected = [
            [
                'day' => '2026-01-10',
                'trimp' => 100.0,
                'distance' => 10.0,
                'vo2max_weighted' => 162000.0,
                'vo2max_sum_time' => 3600.0,
                'is_running' => true,
            ],
            [
                'day' => '2026-01-10',
                'trimp' => 80.0,
                'distance' => 30.0,
                'vo2max_weighted' => 0.0,
                'vo2max_sum_time' => 0.0,
                'is_running' => false,
            ],
            [
                'day' => '2026-01-11',
                'trimp' => 20.0,
                'distance' => 5.0,
                'vo2max_weighted' => 0.0,
                'vo2max_sum_time' => 0.0,
                'is_running' => true,
            ],
        ];

        $this->assertSame($expected, $this->TrainingRepository->findShapePlotTrainingData(
            $this->Account,
            (int) $running->getId(),
            $start,
            $end,
            false
        ));

        $elevation = $this->TrainingRepository->findShapePlotTrainingData(
            $this->Account,
            (int) $running->getId(),
            $start,
            $end,
            true
        );
        $this->assertEqualsWithDelta(180000.0, $elevation[0]['vo2max_weighted'], PHP_FLOAT_EPSILON);

        $this->assertSame([], $this->TrainingRepository->findShapePlotTrainingData(
            $this->getEmptyAccount(),
            (int) $running->getId(),
            $start,
            $end,
            false
        ));
    }

    public function testShapePlotHistoryUsesSuppliedTimezoneForCalendarDay(): void
    {
        $running = $this->getDefaultAccountsRunningSport();
        $timestamp = strtotime('2026-03-28 23:30:00 UTC');
        $activity = $this->insertActivityForDefaultAccount($timestamp, 1200, 5.0, $running);
        $activity->setTrimp(15);
        $this->getEntityManager()->flush();

        $berlin = $this->TrainingRepository->findShapePlotTrainingData(
            $this->Account,
            (int) $running->getId(),
            $timestamp,
            $timestamp,
            false,
            new DateTimeZone('Europe/Berlin')
        );
        $utc = $this->TrainingRepository->findShapePlotTrainingData(
            $this->Account,
            (int) $running->getId(),
            $timestamp,
            $timestamp,
            false
        );

        $this->assertSame('2026-03-29', $berlin[0]['day']);
        $this->assertSame('2026-03-28', $utc[0]['day']);
        $this->assertEqualsWithDelta(15.0, $berlin[0]['trimp'], PHP_FLOAT_EPSILON);
    }

    public function testShapePlotHistoryMatchesAccountCalendarAcrossSpringDstBoundary(): void
    {
        $this->Account->setTimezone(TimezoneEnum::BERLIN);
        $runningSport = $this->getDefaultAccountsRunningSport();

        $activityBeforeMidnight = $this->insertActivityForDefaultAccount(
            strtotime('2026-03-28 22:59:59 UTC'),
            1200,
            4.0,
            $runningSport
        );
        $activityAtMidnight = $this->insertActivityForDefaultAccount(
            strtotime('2026-03-28 23:00:00 UTC'),
            1200,
            5.0,
            $runningSport
        );
        $activityAfterClockChange = $this->insertActivityForDefaultAccount(
            strtotime('2026-03-29 01:00:00 UTC'),
            1200,
            6.0,
            $runningSport
        );
        $activityAtDayEnd = $this->insertActivityForDefaultAccount(
            strtotime('2026-03-29 21:59:59 UTC'),
            1200,
            7.0,
            $runningSport
        );
        $activityOnNextDay = $this->insertActivityForDefaultAccount(
            strtotime('2026-03-29 22:00:00 UTC'),
            1200,
            8.0,
            $runningSport
        );

        $activityBeforeMidnight->setTrimp(2);
        $activityAtMidnight->setTrimp(3);
        $activityAfterClockChange->setTrimp(5);
        $activityAtDayEnd->setTrimp(7);
        $activityOnNextDay->setTrimp(11);
        $this->getEntityManager()->flush();

        $provider = new RechenspieleShapeDataProvider(
            $this->TrainingRepository,
            new RechenspieleShapeDailyAggregator()
        );
        $timezone = new DateTimeZone('Europe/Berlin');

        $history = $provider->getDailyData(
            $this->Account,
            (int) $runningSport->getId(),
            new DateTimeImmutable('2026-03-29', $timezone),
            new DateTimeImmutable('2026-03-29', $timezone),
            1,
            10.0,
            false
        );

        $this->assertSame([2.0, 15.0, 0.0], $history['trimp']);
        $this->assertSame([4.0, 18.0, 0.0], $history['distance']);
    }

    public function testShapePlotHistoryKeepsBothRepeatedAutumnHoursOnTheSameDay(): void
    {
        $this->Account->setTimezone(TimezoneEnum::BERLIN);
        $runningSport = $this->getDefaultAccountsRunningSport();

        $beforeLocalMidnight = $this->insertActivityForDefaultAccount(
            strtotime('2026-10-24 21:59:59 UTC'),
            1200,
            2.0,
            $runningSport
        );
        $firstRepeatedHour = $this->insertActivityForDefaultAccount(
            strtotime('2026-10-25 00:30:00 UTC'),
            1200,
            3.0,
            $runningSport
        );
        $secondRepeatedHour = $this->insertActivityForDefaultAccount(
            strtotime('2026-10-25 01:30:00 UTC'),
            1200,
            4.0,
            $runningSport
        );
        $nextLocalMidnight = $this->insertActivityForDefaultAccount(
            strtotime('2026-10-25 23:00:00 UTC'),
            1200,
            5.0,
            $runningSport
        );

        $beforeLocalMidnight->setTrimp(2);
        $firstRepeatedHour->setTrimp(3);
        $secondRepeatedHour->setTrimp(4);
        $nextLocalMidnight->setTrimp(5);
        $this->getEntityManager()->flush();

        $provider = new RechenspieleShapeDataProvider(
            $this->TrainingRepository,
            new RechenspieleShapeDailyAggregator()
        );
        $timezone = new DateTimeZone('Europe/Berlin');

        $history = $provider->getDailyData(
            $this->Account,
            (int) $runningSport->getId(),
            new DateTimeImmutable('2026-10-25', $timezone),
            new DateTimeImmutable('2026-10-25', $timezone),
            1,
            10.0,
            false
        );

        $this->assertSame([2.0, 7.0, 0.0], $history['trimp']);
        $this->assertSame([2.0, 7.0, 0.0], $history['distance']);
    }

    public function testPrognosisTrendHistoryPreservesChronologyAndVO2maxWeights(): void
    {
        $runningSport = $this->getDefaultAccountsRunningSport();
        $earlyTimestamp = strtotime('2026-01-10 08:00:00 UTC');
        $laterTimestamp = strtotime('2026-01-11 08:00:00 UTC');

        $later = $this->getActivityForDefaultAccount($laterTimestamp, 1800, 12.0, $runningSport);
        $this->TrainingRepository->save($later);

        $early = $this->getActivityForDefaultAccount($earlyTimestamp, 3600, 10.0, $runningSport);
        $this->TrainingRepository->save($early);

        $this->insertActivityForDefaultAccount($earlyTimestamp, 1800, 20.0, $this->getDefaultAccountsCyclingSport());

        $later->setVO2max(44.0)->setVO2maxWithElevation(48.0)->setUseVO2max(false);
        $early->setVO2max(45.0)->setVO2maxWithElevation(50.0)->setUseVO2max(true);
        $this->getEntityManager()->flush();

        $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
        $earlyAge = (int) $today->diff(new DateTimeImmutable('2026-01-10', new DateTimeZone('UTC')))->format('%r%a');
        $laterAge = (int) $today->diff(new DateTimeImmutable('2026-01-11', new DateTimeZone('UTC')))->format('%r%a');

        $this->assertSame([
            [
                'date_age' => $earlyAge,
                'date' => '2026-01-10',
                'distance' => 10.0,
                'vo2max_weighted' => 162000.0,
                'vo2max_sum_time' => 3600.0,
            ],
            [
                'date_age' => $laterAge,
                'date' => '2026-01-11',
                'distance' => 12.0,
                'vo2max_weighted' => 0.0,
                'vo2max_sum_time' => 0.0,
            ],
        ], $this->TrainingRepository->findPrognosisTrendHistory($this->Account, $runningSport->getId(), false));

        $elevationHistory = $this->TrainingRepository->findPrognosisTrendHistory($this->Account, $runningSport->getId(), true);
        $this->assertEqualsWithDelta(180000.0, $elevationHistory[0]['vo2max_weighted'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3600.0, $elevationHistory[0]['vo2max_sum_time'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $elevationHistory[1]['vo2max_weighted'], PHP_FLOAT_EPSILON);
    }

    public function testPrognosisTrendHistoryIsScopedToAccountAndHandlesMissingVO2max(): void
    {
        $runningSport = $this->getDefaultAccountsRunningSport();
        $activity = $this->getActivityForDefaultAccount(strtotime('2026-01-10 12:00:00 UTC'), 1000, 5.0, $runningSport);
        $activity->setVO2max(null)->setUseVO2max(true);
        $this->TrainingRepository->save($activity);

        $this->assertSame([], $this->TrainingRepository->findPrognosisTrendHistory(
            $this->getEmptyAccount(),
            $runningSport->getId(),
            false
        ));

        $history = $this->TrainingRepository->findPrognosisTrendHistory($this->Account, $runningSport->getId(), false);
        $this->assertCount(1, $history);
        $this->assertEqualsWithDelta(0.0, $history[0]['vo2max_weighted'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $history[0]['vo2max_sum_time'], PHP_FLOAT_EPSILON);
    }

    protected function insertActivityForDefaultAccount(
        ?int $timestamp = null,
        float $duration = 3600,
        ?float $distance = null,
        ?Sport $sport = null
    ): Training {
        $activity = $this->getActivityForDefaultAccount($timestamp, $duration, $distance, $sport);
        $this->TrainingRepository->save($activity);

        return $activity;
    }

    public function testThatSportIsSetToDefaultIfEmpty(): void
    {
        $activity = new Training();
        $activity->setS(3600);
        $activity->setTime(time());
        $activity->setAccount($this->getDefaultAccount());

        $this->TrainingRepository->save($activity);

        $this->assertEquals($this->getDefaultAccountsRunningSport()->getId(), $activity->getSport()->getId());
    }

    public function testThatTypeIsRemovedIfInvalidForSport(): void
    {
        $type = new Type();
        $type->setName('Easy ride');
        $type->setAccount($this->getDefaultAccount());
        $type->setSport($this->getDefaultAccountsCyclingSport());

        $activity = $this->getActivityForDefaultAccount(null, 3600, 25.0);
        $activity->setType($type);

        $this->TrainingRepository->save($activity);

        $this->assertNull($activity->getType());
    }

    public function testPossibleDuplicate(): void
    {
        $existingActivity = $this->getActivityForDefaultAccount();
        $existingActivity->setActivityId(123456789);

        $this->TrainingRepository->save($existingActivity);

        $activityToCheck = new Training();
        $activityToCheck->setActivityId(123456789);

        $this->assertFalse($this->TrainingRepository->isPossibleDuplicate($activityToCheck));

        $activityToCheck->setAccount($this->getEmptyAccount());

        $this->assertFalse($this->TrainingRepository->isPossibleDuplicate($activityToCheck));

        $activityToCheck->setAccount($this->getDefaultAccount());

        $this->assertTrue($this->TrainingRepository->isPossibleDuplicate($activityToCheck));

        $activityToCheck->setActivityId(100000000);

        $this->assertFalse($this->TrainingRepository->isPossibleDuplicate($activityToCheck));

        $activityToCheck->setActivityId(null);

        $this->assertFalse($this->TrainingRepository->isPossibleDuplicate($activityToCheck));
    }

    public function testSpeedUnit(): void
    {
        $this->assertEquals(
            $this->getDefaultAccountsRunningSport()
                ->getSpeed(),
            $this->TrainingRepository->getSpeedUnitFor(
                $this->insertActivityForDefaultAccount(null, 3600, null, $this->getDefaultAccountsRunningSport())
                    ->getId(),
                $this->Account->getId()
            )
        );
        $this->assertEquals(
            $this->getDefaultAccountsCyclingSport()
                ->getSpeed(),
            $this->TrainingRepository->getSpeedUnitFor(
                $this->insertActivityForDefaultAccount(null, 3600, null, $this->getDefaultAccountsCyclingSport())
                    ->getId(),
                $this->Account->getId()
            )
        );
    }

    public function testNumberOfActivities(): void
    {
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2015));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 30, 2016));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 7, 1, 2016), 3600, null, $this->getDefaultAccountsCyclingSport());

        $this->assertSame(4, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account));
        $this->assertSame(1, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account, 2015));
        $this->assertSame(1, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account, 2015, $this->getDefaultAccountsRunningSport()->getId()));
        $this->assertSame(0, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account, 2015, $this->getDefaultAccountsCyclingSport()->getId()));
        $this->assertSame(3, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account, 2016));
        $this->assertSame(2, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account, 2016, $this->getDefaultAccountsRunningSport()->getId()));
        $this->assertSame(1, $this->TrainingRepository->getNumberOfActivitiesFor($this->Account, 2016, $this->getDefaultAccountsCyclingSport()->getId()));
    }

    public function testAccountStatisticsWithoutData(): void
    {
        $statistics = $this->TrainingRepository->getAccountStatistics($this->Account);

        $this->assertSame(0, $statistics->getNumberOfActivities());
        $this->assertEqualsWithDelta(0.0, $statistics->getTotalDuration(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $statistics->getTotalDistance(), PHP_FLOAT_EPSILON);
    }

    public function testAccountStatisticsWithData(): void
    {
        $this->insertActivityForDefaultAccount(null, 3600, 10.0);
        $this->insertActivityForDefaultAccount(null, 3600, 12.0);
        $this->insertActivityForDefaultAccount(null, 7200, 63.5, $this->getDefaultAccountsCyclingSport());

        $statistics = $this->TrainingRepository->getAccountStatistics($this->Account);

        $this->assertSame(3, $statistics->getNumberOfActivities());
        $this->assertEqualsWithDelta(14400.0, $statistics->getTotalDuration(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(85.5, $statistics->getTotalDistance(), PHP_FLOAT_EPSILON);
    }

    public function testPosterStats(): void
    {
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2015), 5400, 17.5);
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016), 3600, 12.5);
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 30, 2016), 3600, 10.0);
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 7, 1, 2016), 3600, 33.3, $this->getDefaultAccountsCyclingSport());

        $this->assertEquals([
            'num' => '1',
            'total_distance' => '17.5',
            'min_distance' => '17.5',
            'max_distance' => '17.5',
        ], $this->TrainingRepository->getStatsForPoster($this->Account, $this->getDefaultAccountsRunningSport(), 2015)
            ->getScalarResult()[0]);
        $this->assertEquals([
            'num' => '2',
            'total_distance' => '22.5',
            'min_distance' => '10',
            'max_distance' => '12.5',
        ], $this->TrainingRepository->getStatsForPoster($this->Account, $this->getDefaultAccountsRunningSport(), 2016)
            ->getScalarResult()[0]);
        $this->assertEquals([
            'num' => '0',
            'total_distance' => null,
            'min_distance' => null,
            'max_distance' => null,
        ], $this->TrainingRepository->getStatsForPoster($this->Account, $this->getDefaultAccountsCyclingSport(), 2015)
            ->getScalarResult()[0]);
        $this->assertEquals([
            'num' => '1',
            'total_distance' => '33.3',
            'min_distance' => '33.3',
            'max_distance' => '33.3',
        ], $this->TrainingRepository->getStatsForPoster($this->Account, $this->getDefaultAccountsCyclingSport(), 2016)
            ->getScalarResult()[0]);
    }

    public function testLockedActivities(): void
    {
        $this->insertActivityForDefaultAccount();

        $this->assertFalse($this->TrainingRepository->accountHasLockedTrainings($this->Account));

        $activity = $this->getActivityForDefaultAccount(time());
        $activity->setLock(true);

        $this->TrainingRepository->save($activity);

        $this->assertTrue($this->TrainingRepository->accountHasLockedTrainings($this->Account));
    }

    public function testThatCreatedAndEditedTimestampsAreUpdatedAutomatically(): void
    {
        $activity = $this->insertActivityForDefaultAccount();

        $this->TrainingRepository->save($activity);

        $this->assertEqualsWithDelta(time(), $activity->getCreated(), 1);
        $this->assertNull($activity->getEdited());

        $createdAt = mktime(12, 0, 0, 3, 14, 2017);
        $activity->setCreated($createdAt);

        $this->TrainingRepository->save($activity);

        $this->assertEquals($createdAt, $activity->getCreated());
        $this->assertEqualsWithDelta(time(), $activity->getEdited(), 1);
    }

    public function testThatActivityCanExistWithoutRelatedObjects(): void
    {
        $activity = $this->insertActivityForDefaultAccount();

        $this->TrainingRepository->save($activity);

        /** @var Training $insertedActivity */
        $insertedActivity = $this->TrainingRepository->find($activity->getId());

        $this->assertNull($insertedActivity->getTrackdata());
        $this->assertNull($insertedActivity->getSwimdata());
        $this->assertNull($insertedActivity->getHrv());
        $this->assertNull($insertedActivity->getRaceresult());
        $this->assertInstanceOf(RoundCollection::class, $insertedActivity->getSplits());
        $this->assertTrue($insertedActivity->getSplits()->isEmpty());
    }

    public function testActivityWithRelatedObjects(): void
    {
        $activity = $this->getActivityForDefaultAccount(
            123456789,
            116,
            0.5
        );

        $route = new Route();
        $route->setElevationsCorrected([122, 125, 128, 130, 129, 130]);

        $trackData = new Trackdata();
        $trackData->setDistance([0.0, 0.1, 0.2, 0.3, 0.4, 0.5]);
        $trackData->setHeartrate([140, 141, 140, 142, 143, 143]);

        $hrv = new Hrv();
        $hrv->setData([428, 429, 425, 426, 428, 440, 424, 415, 422, 465, 421, 420]);

        $activity->setRoute($route);
        $activity->setTrackdata($trackData);
        $activity->setHrv($hrv);

        $this->TrainingRepository->save($activity);

        $result = $this->TrainingRepository->findForAccount($activity->getId(), $this->getDefaultAccount()->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);

        $this->assertTrue($result->hasRoute());
        $this->assertTrue($result->hasTrackdata());
        $this->assertTrue($result->hasHrv());
        $this->assertGreaterThan(0, $result->getRoute()->getElevation());
        $this->assertNotNull($result->getClimbScore());

        $routeId = $result->getRoute()
            ->getId();
        $activityId = $activity->getId();

        $this->TrainingRepository->remove($activity);

        $this->assertNull($this->getEntityManager()->getRepository(Route::class)->find($routeId));
        $this->assertNull($this->getEntityManager()->getRepository(Trackdata::class)->findForActivity($activityId, $this->getDefaultAccount()));
        $this->assertNull($this->getEntityManager()->getRepository(Hrv::class)->findForActivity($activityId));
    }

    public function testThatPowerIsNotRemovedAtUpdate(): void
    {
        $activity = $this->getActivityForDefaultAccount(
            123456789,
            116,
            0.5
        );
        $activity->setPowerCalculated(false);
        $activity->setPower(141);

        $trackData = new Trackdata();
        $trackData->setPower([140, 141, 140, 142, 142, 141]);

        $activity->setTrackdata($trackData);

        $this->TrainingRepository->save($activity);

        $result = $this->TrainingRepository->findForAccount($activity->getId(), $this->getDefaultAccount()->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);

        $this->assertSame(141, $result->getPower());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);
        $this->assertFalse($result->isPowerCalculated());

        $activity->setEdited(time());

        $this->TrainingRepository->save($activity);

        $result = $this->TrainingRepository->findForAccount($activity->getId(), $this->getDefaultAccount()->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);

        $this->assertSame(141, $result->getPower());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);
        $this->assertFalse($result->isPowerCalculated());
    }

    public function testActivityWithRaceResult(): void
    {
        $activity = $this->getActivityForDefaultAccount(
            123456789,
            629,
            3.0
        );

        $raceResult = new Raceresult();
        $raceResult->fillFromActivity($activity);
        $raceResult->setName('Event');

        $activity->setRaceresult($raceResult);

        $this->TrainingRepository->save($activity);

        $result = $this->TrainingRepository->findForAccount($activity->getId(), $this->getDefaultAccount()->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);

        $this->assertTrue($result->hasRaceresult());
        $this->assertEqualsWithDelta(3.0, $result->getRaceresult()->getOfficialDistance(), PHP_FLOAT_EPSILON);

        $activityId = $activity->getId();

        $this->TrainingRepository->remove($activity);

        $this->assertNull($this->getEntityManager()->getRepository(Raceresult::class)->findForActivity($activityId));
    }

    public function testActivityWithSwimData(): void
    {
        $swimData = new Swimdata();
        $swimData->setPoolLength(5000);
        $swimData->setStroke([32, 30, 35, 28]);

        $activity = $this->getActivityForDefaultAccount(
            123456789,
            300,
            0.2
        );
        $activity->setSwimdata($swimData);

        $this->TrainingRepository->save($activity);

        $result = $this->TrainingRepository->findForAccount($activity->getId(), $this->getDefaultAccount()->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);

        $this->assertTrue($result->hasSwimdata());
        $this->assertSame(125, $result->getTotalStrokes());
        $this->assertSame(5000, $result->getSwimdata()->getPoolLength());

        $activityId = $activity->getId();

        $this->TrainingRepository->remove($activity);

        $this->assertNull($this->getEntityManager()->getRepository(Swimdata::class)->findForActivity($activityId));
    }

    #[\PHPUnit\Framework\Attributes\Group('idontcare')]
    public function testThatVO2maxUpdateInListenerRecalculatesMarathonShape(): void
    {
        $recalculationManager = $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager::class);

        $activity = $this->getActivityForDefaultAccount(time(), 2700, 10.0, $this->getDefaultAccountsRunningSport());
        $activity->setPulseAvg(140);

        $this->TrainingRepository->save($activity);

        $this->assertTrue($recalculationManager->isTaskScheduled($this->getDefaultAccount(), VO2maxShapeCalculation::class));
        $this->assertTrue($recalculationManager->isTaskScheduled($this->getDefaultAccount(), MarathonShapeCalculation::class));

        $recalculationManager->runScheduledTasks();

        $this->assertFalse($recalculationManager->isTaskScheduled($this->getDefaultAccount(), VO2maxShapeCalculation::class));
        $this->assertFalse($recalculationManager->isTaskScheduled($this->getDefaultAccount(), MarathonShapeCalculation::class));

        $this->getEntityManager()
            ->getUnitOfWork()
            ->refresh($activity);
        $activity->setPulseAvg(150);

        $this->TrainingRepository->save($activity);

        $this->assertTrue($recalculationManager->isTaskScheduled($this->getDefaultAccount(), VO2maxShapeCalculation::class));
        $this->assertTrue($recalculationManager->isTaskScheduled($this->getDefaultAccount(), MarathonShapeCalculation::class));
    }

    public function testStartTimeForEmptyAccount(): void
    {
        $this->assertNull($this->TrainingRepository->getStartTime($this->getDefaultAccount()));
    }

    public function testStartTimeForSimpleExample(): void
    {
        $this->insertActivityForDefaultAccount(987654321);
        $this->insertActivityForDefaultAccount(123456789);

        $this->assertSame(123456789, $this->TrainingRepository->getStartTime($this->getDefaultAccount()));
    }

    public function testThatStartTimeForSimpleExampleIsCalculated(): void
    {
        $this->insertActivityForDefaultAccount(987654321);
        $firstActivity = $this->insertActivityForDefaultAccount(123456789);

        $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager::class)
            ->runScheduledTasks();

        $configList = $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager::class)
            ->getList($this->getDefaultAccount());

        $this->assertEquals(123456789, $configList->get('data.START_TIME'));

        $firstActivity->setTime(100000000);

        $this->TrainingRepository->save($firstActivity);

        $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager::class)
            ->runScheduledTasks();

        $configList = $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager::class)
            ->getList($this->getDefaultAccount());

        $this->assertEquals(100000000, $configList->get('data.START_TIME'));

        $this->TrainingRepository->remove($firstActivity);

        $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager::class)
            ->runScheduledTasks();

        $configList = $this->getContainer()
            ->get(\Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager::class)
            ->getList($this->getDefaultAccount());

        $this->assertEquals(987654321, $configList->get('data.START_TIME'));
    }

    public function testVO2maxShapeCalculationForEmptyAccount(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->TrainingRepository->calculateVO2maxShape(
            $this->getDefaultAccount(),
            new VO2max(),
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            time()
        ), PHP_FLOAT_EPSILON);
    }

    public function testVO2maxShapeCalculationForASingleActivity(): void
    {
        $activity = $this->getActivityForDefaultAccount(time() - 86400, 3600, 10.0)
            ->setPulseAvg(160);

        $this->TrainingRepository->save($activity);

        $this->assertEqualsWithDelta($activity->getVO2max(), $this->TrainingRepository->calculateVO2maxShape(
            $this->getDefaultAccount(),
            new VO2max(),
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            time()
        ), 0.01);
    }

    public function testVO2maxShapeCalculationForSomeActivities(): void
    {
        $config = new VO2max();
        $config->set('VO2MAX_USE_CORRECTION_FOR_ELEVATION', 'true');

        $activity1 = $this->getActivityForDefaultAccount(time() - 86400, 1000, 4.0)
            ->setPulseAvg(160);
        $activity2 = $this->getActivityForDefaultAccount(time() - 2 * 86400, 2000, 8.0)
            ->setPulseAvg(160);
        $activity3 = $this->getActivityForDefaultAccount(time() - 200 * 86400, 10000, 40.0)
            ->setPulseAvg(160);

        $this->TrainingRepository->save($activity1);
        $this->TrainingRepository->save($activity2);
        $this->TrainingRepository->save($activity3);

        $expectedShape = ($activity1->getVO2max() + 2 * $activity2->getVO2max()) / 3;

        $this->assertEqualsWithDelta($expectedShape, $this->TrainingRepository->calculateVO2maxShape(
            $this->getDefaultAccount(),
            $config,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            time()
        ), 0.01);
    }

    public function testMarathonShapeCalculationForEmptyAccount(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->TrainingRepository->calculateMarathonShape(
            $this->getDefaultAccount(),
            new BasicEndurance(),
            50.0,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            time()
        ), PHP_FLOAT_EPSILON);
    }

    public function testMarathonShapeCalculationForOnlyLongJog(): void
    {
        $date = mktime(12, 0, 0, 1, 10, 2015);
        $config = new BasicEndurance();
        $config->set('BE_DAYS_FOR_LONGJOGS', '10');
        $config->set('BE_DAYS_FOR_WEEK_KM', '365');
        $config->set('BE_PERCENTAGE_WEEK_KM', '0.00');

        $this->insertActivityForDefaultAccount($date - 5 * 86400, 10800, 32.5);

        $this->assertEqualsWithDelta(70.0, $this->TrainingRepository->calculateMarathonShape(
            $this->getDefaultAccount(),
            $config,
            60.0,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            $date
        ), PHP_FLOAT_EPSILON);
    }

    public function testMarathonShapeCalculationForWithRespectingFirstActivityData(): void
    {
        $date = mktime(12, 0, 0, 1, 10, 2015);
        $config = new BasicEndurance();
        $config->set('BE_DAYS_FOR_LONGJOGS', '100');
        $config->set('BE_DAYS_FOR_WEEK_KM', '14');
        $config->set('BE_DAYS_FOR_WEEK_KM_MIN', '7');
        $config->set('BE_PERCENTAGE_WEEK_KM', '1.00');

        $this->insertActivityForDefaultAccount($date - 7 * 86400, 10800, 32.5);

        $this->assertEqualsWithDelta(16.0, $this->TrainingRepository->calculateMarathonShape(
            $this->getDefaultAccount(),
            $config,
            60.0,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            $date
        ), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(31.0, $this->TrainingRepository->calculateMarathonShape(
            $this->getDefaultAccount(),
            $config,
            60.0,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            $date,
            $date - 7 * 86400
        ), PHP_FLOAT_EPSILON);
    }

    public function testMarathonShapeCalculationTreatsUnknownFirstActivityAsFullyEstablished(): void
    {
        $date = mktime(12, 0, 0, 1, 10, 2015);
        $config = new BasicEndurance();
        $config->set('BE_DAYS_FOR_LONGJOGS', '100');
        $config->set('BE_DAYS_FOR_WEEK_KM', '14');
        $config->set('BE_DAYS_FOR_WEEK_KM_MIN', '7');
        $config->set('BE_PERCENTAGE_WEEK_KM', '1.00');

        $this->insertActivityForDefaultAccount($date - 7 * 86400, 10800, 32.5);

        $resultWithoutFirstActivityTimestamp = $this->TrainingRepository->calculateMarathonShape(
            $this->getDefaultAccount(),
            $config,
            60.0,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            $date
        );
        $resultWithLongEstablishedFirstActivity = $this->TrainingRepository->calculateMarathonShape(
            $this->getDefaultAccount(),
            $config,
            60.0,
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            $date,
            $date - 200 * 86400
        );

        $this->assertEqualsWithDelta(16.0, $resultWithoutFirstActivityTimestamp, PHP_FLOAT_EPSILON);
        $this->assertSame(
            $resultWithoutFirstActivityTimestamp,
            $resultWithLongEstablishedFirstActivity,
            'Omitting $timestampOfFirstActivity must be equivalent to '
            . 'passing a timestamp far enough in the past that the '
            . 'account is already fully established - both represent '
            . '"no leniency", not "brand new account".'
        );
    }

    public function testActivityNavigation(): void
    {
        $activity1 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2015));
        $activity2 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2015));
        $activity3 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $activity4 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 30, 2016));
        $activity5 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 30, 2016));

        $this->assertNull($this->TrainingRepository->getIdOfPreviousActivity($activity1));
        $this->assertEquals($activity2->getId(), $this->TrainingRepository->getIdOfNextActivity($activity1));
        $this->assertEquals($activity1->getId(), $this->TrainingRepository->getIdOfPreviousActivity($activity2));
        $this->assertEquals($activity3->getId(), $this->TrainingRepository->getIdOfNextActivity($activity2));
        $this->assertEquals($activity2->getId(), $this->TrainingRepository->getIdOfPreviousActivity($activity3));
        $this->assertEquals($activity4->getId(), $this->TrainingRepository->getIdOfNextActivity($activity3));
        $this->assertEquals($activity3->getId(), $this->TrainingRepository->getIdOfPreviousActivity($activity4));
        $this->assertEquals($activity5->getId(), $this->TrainingRepository->getIdOfNextActivity($activity4));
        $this->assertEquals($activity4->getId(), $this->TrainingRepository->getIdOfPreviousActivity($activity5));
        $this->assertNull($this->TrainingRepository->getIdOfNextActivity($activity5));
    }

    public function testEquipmentStatistics(): void
    {
        $someEquipment = $this->getEntityManager()
            ->getRepository(Equipment::class)
            ->findBy(
                [
                    'account' => $this->getDefaultAccount(),
                ],
                null,
                3
            );

        if (count($someEquipment) < 3) {
            $this->markTestSkipped('Test requires at least three existing equipment objects for default account.');
        } else {
            $activity = $this->getActivityForDefaultAccount(null, 3600, 10.0);
            $activity->addEquipment($someEquipment[0]);

            $this->TrainingRepository->save($activity);

            $this->assertSame(3600, $someEquipment[0]->getTime());
            $this->assertEqualsWithDelta(10.0, $someEquipment[0]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(0, $someEquipment[1]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[1]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(0, $someEquipment[2]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[2]->getDistance(), PHP_FLOAT_EPSILON);

            $activity->addEquipment($someEquipment[1]);
            $activity->setDistance(12.0);
            $activity->setS(3580);

            $this->TrainingRepository->save($activity);
            $this->refreshEquipment($someEquipment);

            $this->assertSame(3580, $someEquipment[0]->getTime());
            $this->assertEqualsWithDelta(12.0, $someEquipment[0]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(3580, $someEquipment[1]->getTime());
            $this->assertEqualsWithDelta(12.0, $someEquipment[1]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(0, $someEquipment[2]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[2]->getDistance(), PHP_FLOAT_EPSILON);

            $activity->removeEquipment($someEquipment[0]);
            $activity->addEquipment($someEquipment[2]);

            $this->TrainingRepository->save($activity);
            $this->refreshEquipment($someEquipment);

            $this->assertSame(0, $someEquipment[0]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[0]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(3580, $someEquipment[1]->getTime());
            $this->assertEqualsWithDelta(12.0, $someEquipment[1]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(3580, $someEquipment[2]->getTime());
            $this->assertEqualsWithDelta(12.0, $someEquipment[2]->getDistance(), PHP_FLOAT_EPSILON);

            $this->TrainingRepository->remove($activity);
            $this->refreshEquipment($someEquipment);

            $this->assertSame(0, $someEquipment[0]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[0]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(0, $someEquipment[1]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[1]->getDistance(), PHP_FLOAT_EPSILON);
            $this->assertSame(0, $someEquipment[2]->getTime());
            $this->assertEqualsWithDelta(0.0, $someEquipment[2]->getDistance(), PHP_FLOAT_EPSILON);
        }
    }

    /**
     * @param Equipment[] $entities
     */
    protected function refreshEquipment(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->getEntityManager()
                ->getUnitOfWork()
                ->refresh($entity);
        }
    }

    public function testAmountOfLoggedKilometers(): void
    {
        $this->assertNull($this->TrainingRepository->getAmountOfLoggedKilometers(false));

        $this->insertActivityForDefaultAccount(null, 3600, 10.0);
        $this->insertActivityForDefaultAccount(null, 3600, 15.5);

        $this->assertEqualsWithDelta(25.5, (float) $this->TrainingRepository->getAmountOfLoggedKilometers(false), PHP_FLOAT_EPSILON);
    }

    public function testActiveYearsFor(): void
    {
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2015));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 30, 2016));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 7, 1, 2016), 3600, null, $this->getDefaultAccountsCyclingSport());

        $allYears = array_map(strval(...), $this->TrainingRepository->getActiveYearsFor($this->Account));
        sort($allYears);
        $this->assertEquals(['2015', '2016'], $allYears);

        $runningYears = array_map(
            strval(...),
            $this->TrainingRepository->getActiveYearsFor($this->Account, $this->getDefaultAccountsRunningSport()->getId())
        );
        sort($runningYears);
        $this->assertEquals(['2015', '2016'], $runningYears);

        $cyclingYears = array_map(
            strval(...),
            $this->TrainingRepository->getActiveYearsFor($this->Account, $this->getDefaultAccountsCyclingSport()->getId())
        );
        $this->assertEquals(['2016'], $cyclingYears);

        $yearsWithAtLeastTwoActivities = array_map(
            strval(...),
            $this->TrainingRepository->getActiveYearsFor($this->Account, null, 2)
        );
        $this->assertEquals(['2016'], $yearsWithAtLeastTwoActivities);
    }

    public function testTypesWithTraining(): void
    {
        $this->assertEquals([], $this->TrainingRepository->getTypesWithTraining($this->Account));

        $type = new Type();
        $type->setName('Interval');
        $type->setAccount($this->getDefaultAccount());
        $type->setSport($this->getDefaultAccountsRunningSport());

        $this->getEntityManager()
            ->persist($type);
        $this->getEntityManager()
            ->flush();

        $activity = $this->getActivityForDefaultAccount(null, 3600, 10.0, $this->getDefaultAccountsRunningSport());
        $activity->setType($type);

        $this->TrainingRepository->save($activity);

        $this->assertEquals([$type->getId()], $this->TrainingRepository->getTypesWithTraining($this->Account));
    }

    public function testNumberOfActivitiesWithActivityType(): void
    {
        $type = new Type();
        $type->setName('Interval');
        $type->setAccount($this->getDefaultAccount());
        $type->setSport($this->getDefaultAccountsRunningSport());

        $this->getEntityManager()
            ->persist($type);
        $this->getEntityManager()
            ->flush();

        $this->assertSame(0, (int) $this->TrainingRepository->getNumberOfActivitiesWithActivityType($type));

        $activity = $this->getActivityForDefaultAccount(null, 3600, 10.0, $this->getDefaultAccountsRunningSport());
        $activity->setType($type);

        $this->TrainingRepository->save($activity);

        $this->assertSame(1, (int) $this->TrainingRepository->getNumberOfActivitiesWithActivityType($type));
    }

    public function testSportsWithTraining(): void
    {
        $this->assertEquals([], $this->TrainingRepository->getSportsWithTraining($this->Account));

        $this->insertActivityForDefaultAccount(null, 3600, 10.0, $this->getDefaultAccountsRunningSport());

        $this->assertEquals(
            [$this->getDefaultAccountsRunningSport()->getId()],
            $this->TrainingRepository->getSportsWithTraining($this->Account)
        );

        $this->insertActivityForDefaultAccount(null, 3600, 20.0, $this->getDefaultAccountsCyclingSport());

        $sportIds = $this->TrainingRepository->getSportsWithTraining($this->Account);
        sort($sportIds);

        $expectedIds = [
            $this->getDefaultAccountsRunningSport()
                ->getId(),
            $this->getDefaultAccountsCyclingSport()
                ->getId(),
        ];
        sort($expectedIds);

        $this->assertEquals($expectedIds, $sportIds);
    }

    public function testMonthlyStatsFor(): void
    {
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 3, 1, 2016), 3600, 10.0);
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 3, 15, 2016), 3600, 5.0);
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 4, 1, 2016), 3600, 8.0);
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 4, 2, 2016), 3600, 12.0, $this->getDefaultAccountsCyclingSport());

        $activityCountsByMonth = $this->indexMonthlyStatsByYearAndMonth(
            $this->TrainingRepository->getMonthlyStatsFor($this->Account)
        );

        $this->assertEquals(2, $activityCountsByMonth[2016][3]['value']);
        $this->assertEquals(2, $activityCountsByMonth[2016][4]['value']);

        $distanceByMonth = $this->indexMonthlyStatsByYearAndMonth(
            $this->TrainingRepository->getMonthlyStatsFor($this->Account, 'distance')
        );

        $this->assertEqualsWithDelta(15.0, (float) $distanceByMonth[2016][3]['value'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(20.0, (float) $distanceByMonth[2016][4]['value'], PHP_FLOAT_EPSILON);

        $cyclingDistanceByMonth = $this->indexMonthlyStatsByYearAndMonth(
            $this->TrainingRepository->getMonthlyStatsFor(
                $this->Account,
                'distance',
                $this->getDefaultAccountsCyclingSport()
                    ->getId()
            )
        );

        $this->assertArrayNotHasKey(3, $cyclingDistanceByMonth[2016] ?? []);
        $this->assertEqualsWithDelta(12.0, (float) $cyclingDistanceByMonth[2016][4]['value'], PHP_FLOAT_EPSILON);
    }

    /**
     * @param array<int, array{year: mixed, month: mixed, value: mixed}> $rows
     * @return array<int, array<int, array{year: mixed, month: mixed, value: mixed}>>
     */
    private function indexMonthlyStatsByYearAndMonth(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(int) $row['year']][(int) $row['month']] = $row;
        }

        return $indexed;
    }

    public function testPartialEntitiesForPreviewByAccount(): void
    {
        $this->assertEquals([], $this->TrainingRepository->getPartialEntitiesForPreview([]));

        $activity1 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $activity2 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 2, 2016));

        $results = $this->TrainingRepository->getPartialEntitiesForPreview([], $this->Account);

        $this->assertCount(2, $results);

        $ids = array_map(static fn (Training $training): int => $training->getId(), $results);
        sort($ids);

        $expectedIds = [$activity1->getId(), $activity2->getId()];
        sort($expectedIds);

        $this->assertEquals($expectedIds, $ids);
    }

    public function testPartialEntitiesForPreviewByIds(): void
    {
        $activity1 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 2, 2016));

        $results = $this->TrainingRepository->getPartialEntitiesForPreview([$activity1->getId()]);

        $this->assertCount(1, $results);
        $this->assertEquals($activity1->getId(), $results[0]->getId());
    }

    public function testLatestActivitiesOrderAndLimit(): void
    {
        $this->assertEquals([], $this->TrainingRepository->getLatestActivities($this->Account));

        $activity1 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $activity2 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 2, 2016));
        $activity3 = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 3, 2016));

        $latest = $this->TrainingRepository->getLatestActivities($this->Account);

        $this->assertCount(3, $latest);
        $this->assertEquals($activity3->getId(), $latest[0]->getId());
        $this->assertEquals($activity2->getId(), $latest[1]->getId());
        $this->assertEquals($activity1->getId(), $latest[2]->getId());

        $limited = $this->TrainingRepository->getLatestActivities($this->Account, 2);

        $this->assertCount(2, $limited);
        $this->assertEquals($activity3->getId(), $limited[0]->getId());
        $this->assertEquals($activity2->getId(), $limited[1]->getId());
    }

    public function testLatestActivitiesOnlyPublicFilter(): void
    {
        $privateActivity = $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $publicActivity = $this->getActivityForDefaultAccount(mktime(12, 0, 0, 6, 2, 2016));
        $publicActivity->setPublic(true);

        $this->TrainingRepository->save($publicActivity);

        $allActivities = $this->TrainingRepository->getLatestActivities($this->Account);

        $this->assertCount(2, $allActivities);

        $onlyPublic = $this->TrainingRepository->getLatestActivities($this->Account, 20, true);

        $this->assertCount(1, $onlyPublic);
        $this->assertEquals($publicActivity->getId(), $onlyPublic[0]->getId());

        $privateIds = array_map(
            static fn (Training $training): int => $training->getId(),
            $onlyPublic
        );

        $this->assertNotContains($privateActivity->getId(), $privateIds);
    }

    public function testLatestPublicActivitiesDelegatesToLatestActivities(): void
    {
        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 1, 2016));
        $publicActivity = $this->getActivityForDefaultAccount(mktime(12, 0, 0, 6, 2, 2016));
        $publicActivity->setPublic(true);

        $this->TrainingRepository->save($publicActivity);

        $result = $this->TrainingRepository->getLatestPublicActivities($this->Account);

        $this->assertCount(1, $result);
        $this->assertEquals($publicActivity->getId(), $result[0]->getId());
    }

    public function testLatestPublicActivitiesRespectsLimit(): void
    {
        for ($day = 1; $day <= 3; $day++) {
            $activity = $this->getActivityForDefaultAccount(mktime(12, 0, 0, 6, $day, 2016));
            $activity->setPublic(true);

            $this->TrainingRepository->save($activity);
        }

        $result = $this->TrainingRepository->getLatestPublicActivities($this->Account, 2);

        $this->assertCount(2, $result);
    }

    public function testQueryForJsonPosterDataOnlyReturnsMatchingActivitiesWithARoute(): void
    {
        $year = 2016;
        $sport = $this->getDefaultAccountsRunningSport();

        $matchingActivity = $this->getActivityForDefaultAccount(
            mktime(12, 0, 0, 6, 1, $year),
            3600,
            10.0,
            $sport
        );
        $route = new Route();
        $route->setElevationsCorrected([100, 105, 110]);

        $matchingActivity->setRoute($route);

        $this->TrainingRepository->save($matchingActivity);

        $this->insertActivityForDefaultAccount(mktime(12, 0, 0, 6, 2, $year), 3600, 12.0, $sport);

        $zeroDistanceActivity = $this->getActivityForDefaultAccount(
            mktime(12, 0, 0, 6, 3, $year),
            3600,
            null,
            $sport
        );
        $zeroDistanceRoute = new Route();
        $zeroDistanceRoute->setElevationsCorrected([100]);

        $zeroDistanceActivity->setRoute($zeroDistanceRoute);

        $this->TrainingRepository->save($zeroDistanceActivity);

        $cyclingActivity = $this->getActivityForDefaultAccount(
            mktime(12, 0, 0, 6, 4, $year),
            3600,
            20.0,
            $this->getDefaultAccountsCyclingSport()
        );
        $cyclingRoute = new Route();
        $cyclingRoute->setElevationsCorrected([100]);

        $cyclingActivity->setRoute($cyclingRoute);

        $this->TrainingRepository->save($cyclingActivity);

        $wrongYearActivity = $this->getActivityForDefaultAccount(
            mktime(12, 0, 0, 6, 1, $year - 1),
            3600,
            15.0,
            $sport
        );
        $wrongYearRoute = new Route();
        $wrongYearRoute->setElevationsCorrected([100]);

        $wrongYearActivity->setRoute($wrongYearRoute);

        $this->TrainingRepository->save($wrongYearActivity);

        $results = $this->TrainingRepository->getQueryForJsonPosterData($this->Account, $sport, $year)
            ->getArrayResult();

        $this->assertCount(1, $results);
        $this->assertEqualsWithDelta(10.0, (float) $results[0]['distance'], PHP_FLOAT_EPSILON);
    }
}
