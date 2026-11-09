<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Prognosis;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisModel;
use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\Cameron;
use Runalyze\Sports\Running\Prognosis\Steffny;
use Runalyze\Sports\Running\Prognosis\VO2max;

final class PrognosisModelTest extends TestCase
{
    public function testCppModelUsesBockWhenTwoTopResultsExist(): void
    {
        $prognosis = new PrognosisModel('cpp', [
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
            [
                'distance' => 10.0,
                's' => 2550.0,
            ],
        ], 50.0, 60, true)->getPrognosisObject();

        $this->assertInstanceOf(Bock::class, $prognosis);
        $this->assertTrue($prognosis->areValuesValid());
    }

    public function testCppModelLeavesBockInvalidWhenFewerThanTwoTopResultsExist(): void
    {
        $prognosis = new PrognosisModel('cpp', [
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
        ], 50.0, 60, true)->getPrognosisObject();

        $this->assertInstanceOf(Bock::class, $prognosis);
        $this->assertFalse($prognosis->areValuesValid());
    }

    public function testSteffnyModelUsesSteffny(): void
    {
        $prognosis = new PrognosisModel('steffny', [
            [
                'distance' => 10.0,
                's' => 2550.0,
            ],
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
        ], 50.0, 60, true)->getPrognosisObject();

        $this->assertInstanceOf(Steffny::class, $prognosis);
    }

    public function testCameronModelUsesCameron(): void
    {
        $prognosis = new PrognosisModel('cameron', [
            [
                'distance' => 10.0,
                's' => 2550.0,
            ],
            [
                'distance' => 5.0,
                's' => 1200.0,
            ],
        ], 50.0, 60, true)->getPrognosisObject();

        $this->assertInstanceOf(Cameron::class, $prognosis);
    }

    public function testVo2maxModelUsesVO2max(): void
    {
        $prognosis = new PrognosisModel('vo2max', [], 50.0, 60, true)->getPrognosisObject();

        $this->assertInstanceOf(VO2max::class, $prognosis);
    }

    public function testUnknownModelFallsBackToVO2max(): void
    {
        $prognosis = new PrognosisModel('some_unknown_model', [], 50.0, 60, true)->getPrognosisObject();

        $this->assertInstanceOf(VO2max::class, $prognosis);
    }

    public function testCppModelKeepsDecimalDistancesAndIsInvalidIfLongerResultIsNotSlower(): void
    {
        $prognosis = new PrognosisModel('cpp', [
            [
                'distance' => 8.9,
                's' => 3196.0,
            ],
            [
                'distance' => 5.72,
                's' => 2064.0,
            ],
        ], 27.25, 2, true)->getPrognosisObject();

        $this->assertInstanceOf(Bock::class, $prognosis);
        $this->assertEqualsWithDelta(0.989078, $prognosis->getE(), 0.000001);
        $this->assertEqualsWithDelta(367.777973, $prognosis->getK(), 0.000001);
        $this->assertFalse($prognosis->areValuesValid());
    }
}
