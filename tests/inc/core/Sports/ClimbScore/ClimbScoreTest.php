<?php

declare(strict_types=1);

namespace Runalyze\Tests\Sports\ClimbScore;

use Runalyze\Sports\ClimbScore\ClimbScore;

final class ClimbScoreTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClimbScore
     */
    protected $Score;

    protected function setUp(): void
    {
        $this->Score = new ClimbScore();
    }

    public function testEmptyConstructor(): void
    {
        $score = new ClimbScore();

        $this->assertFalse($score->isKnown());
        $this->assertNull($score->getScore());
    }

    public function testManualScore(): void
    {
        $this->assertEqualsWithDelta(3.1, new ClimbScore(3.1)->getScore(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(4.2, new ClimbScore()->setScore(4.2)->getScore(), PHP_FLOAT_EPSILON);
    }

    public function testExampleForCyclingTourInSwitzerland(): void
    {
        $this->Score->setScoreFromClassifiedClimbs(
            [0.6, 0.3, 7.7, 5.9, 0.6, 2.8],
            105.8,
            0.43
        );

        $this->assertEqualsWithDelta(5.9, $this->Score->getScore(), 0.05);
    }

    public function testSumOfFietsIndices(): void
    {
        $this->assertEqualsWithDelta(6.0, $this->Score->getSumOfScoresForClassifiedClimbs([1.0, 2.0, 3.0], 20.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3.0, $this->Score->getSumOfScoresForClassifiedClimbs([1.0, 2.0, 3.0], 80.0), PHP_FLOAT_EPSILON);
    }

    public function testThatVeryShortDistancesDoNotDisturbTheScore(): void
    {
        $this->assertEqualsWithDelta(2.7, $this->Score->getSumOfScoresForClassifiedClimbs([2.7], 0.01), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3.0, $this->Score->getSumOfScoresForClassifiedClimbs([3.0], 3.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(4.2, $this->Score->getSumOfScoresForClassifiedClimbs([4.2], 20.0), PHP_FLOAT_EPSILON);
    }

    public function testScaleForSumOfScores(): void
    {
        $this->assertEqualsWithDelta(1.17, $this->Score->getScoreForSumOfSingleScores(0.0), 0.01);
        $this->assertEqualsWithDelta(2.0, $this->Score->getScoreForSumOfSingleScores(0.5), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(4.0, $this->Score->getScoreForSumOfSingleScores(2.5), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(5.17, $this->Score->getScoreForSumOfSingleScores(4.5), 0.01);
        $this->assertEqualsWithDelta(6.0, $this->Score->getScoreForSumOfSingleScores(6.5), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $this->Score->getScoreForSumOfSingleScores(14.5), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(10.0, $this->Score->getScoreForSumOfSingleScores(30.5), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(10.0, $this->Score->getScoreForSumOfSingleScores(100.0), PHP_FLOAT_EPSILON);
    }

    public function testCompensationFactorForFlatParts(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->Score->getCompensationForFlatParts(2.50), 0.0001);
        $this->assertEqualsWithDelta(0.0, $this->Score->getCompensationForFlatParts(-1.23), 0.0001);
        $this->assertEqualsWithDelta(0.0, $this->Score->getCompensationForFlatParts(1.00), 0.0001);
        $this->assertEqualsWithDelta(1.0, $this->Score->getCompensationForFlatParts(0.00), 0.0001);

        $this->assertEqualsWithDelta(0.99, $this->Score->getCompensationForFlatParts(0.10), 0.0001);
        $this->assertEqualsWithDelta(0.75, $this->Score->getCompensationForFlatParts(0.50), 0.0001);
        $this->assertEqualsWithDelta(0.64, $this->Score->getCompensationForFlatParts(0.60), 0.0001);
        $this->assertEqualsWithDelta(0.36, $this->Score->getCompensationForFlatParts(0.80), 0.0001);
    }
}
