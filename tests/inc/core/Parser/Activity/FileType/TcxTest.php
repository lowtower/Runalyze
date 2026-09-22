<?php

namespace Runalyze\Tests\Parser\Activity\FileType;

use Runalyze\Parser\Activity\FileType\Tcx;
use Runalyze\Util\LocalTime;

#[\PHPUnit\Framework\Attributes\Group('import')]
class TcxTest extends AbstractActivityParserTestCase
{
    protected Tcx $Parser;

    protected function setUp(): void
    {
        $this->Parser = new Tcx();
    }

    public function testStandardFile(): void
    {
        $this->parseFile($this->Parser, 'tcx/Standard.tcx');

        $this->assertEquals('2011-07-10 11:47', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container->Metadata->getTimezoneOffset());

        $this->assertEqualsWithDelta(6523, $this->Container->ActivityData->Duration, 30);
        $this->assertEqualsWithDelta(7200 - 8 * 60 - 21, $this->Container->ActivityData->ElapsedTime, 5);

        $this->assertEqualsWithDelta(22.224, $this->Container->ActivityData->Distance, 0.1);
        $this->assertEqualsWithDelta(1646, $this->Container->ActivityData->EnergyConsumption, 10);
        $this->assertEqualsWithDelta(145, $this->Container->ActivityData->AvgHeartRate, 2);
        $this->assertEqualsWithDelta(172, $this->Container->ActivityData->MaxHeartRate, 2);

        $this->assertNotEmpty($this->Container->ContinuousData->Time);
        $this->assertNotEmpty($this->Container->ContinuousData->Altitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Distance);
        $this->assertNotEmpty($this->Container->ContinuousData->HeartRate);
        $this->assertNotEmpty($this->Container->ContinuousData->Latitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Longitude);

        $this->checkExpectedPauseData([
            [19, 57, 112, 73],
            [19, 1, 73, 71],
            [3676, 92, 143, 110],
            [3720, 11, 125, 126],
            [6176, 20, 140, 133],
        ]);
    }

    public function testSwimTraining(): void
    {
        $this->parseFile($this->Parser, 'tcx/Swim-without-time_by-Timekiller.tcx');

        $this->assertEquals('2012-04-13 13:51', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container->Metadata->getTimezoneOffset());

        $this->assertEqualsWithDelta(2100, $this->Container->ActivityData->Duration, 30);
        $this->assertEqualsWithDelta(2100, $this->Container->ActivityData->ElapsedTime, 30);

        $this->assertEquals('Other', $this->Container->Metadata->getSportName());
        $this->assertEquals('Forerunner 310XT-000', $this->Container->Metadata->getCreatorDetails());
    }

    public function testIndoorActivity(): void
    {
        $this->parseFile($this->Parser, 'tcx/Indoor-Training.tcx');

        $this->assertEquals('2012-02-10 16:48', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(60, $this->Container->Metadata->getTimezoneOffset());

        $this->assertEqualsWithDelta(7204, $this->Container->ActivityData->Duration, 70);
        $this->assertEquals(7204, $this->Container->ActivityData->ElapsedTime);
        $this->assertEqualsWithDelta(122, $this->Container->ActivityData->AvgHeartRate, 2);
        $this->assertEqualsWithDelta(149, $this->Container->ActivityData->MaxHeartRate, 2);
    }

    public function testMultiSport(): void
    {
        $this->parseFile($this->Parser, 'tcx/Multisport.tcx');

        $this->assertTrue(is_array($this->Container));
        $this->assertEquals(3, count($this->Container));

        $this->assertEquals('2013-04-18 18:14', LocalTime::date('Y-m-d H:i', $this->Container[0]->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container[0]->Metadata->getTimezoneOffset());
        $this->assertEqualsWithDelta(494, $this->Container[0]->ActivityData->Duration, 20);
        $this->assertEqualsWithDelta(2.355, $this->Container[0]->ActivityData->Distance, 0.1);

        $this->assertEquals('2013-04-18 18:24', LocalTime::date('Y-m-d H:i', $this->Container[1]->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container[1]->Metadata->getTimezoneOffset());
        $this->assertEqualsWithDelta(3571, $this->Container[1]->ActivityData->Duration, 30);
        $this->assertEqualsWithDelta(11.46, $this->Container[1]->ActivityData->Distance, 0.1);

        $this->assertEquals('2013-04-18 19:35', LocalTime::date('Y-m-d H:i', $this->Container[2]->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container[2]->Metadata->getTimezoneOffset());
        $this->assertEqualsWithDelta(420, $this->Container[2]->ActivityData->Duration, 10);
        $this->assertEqualsWithDelta(2.355, $this->Container[2]->ActivityData->Distance, 0.1);
    }

    public function testFileFromDakota(): void
    {
        $this->parseFile($this->Parser, 'tcx/Dakota.tcx');

        $this->assertEquals('2012-08-19 09:21', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container->Metadata->getTimezoneOffset());

        $this->assertEqualsWithDelta(2.34, $this->Container->ActivityData->Distance, 0.1);

        $this->assertNotEmpty($this->Container->ContinuousData->Time);
        $this->assertNotEmpty($this->Container->ContinuousData->Altitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Distance);
        $this->assertNotEmpty($this->Container->ContinuousData->Latitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Longitude);
        $this->assertEmpty($this->Container->ContinuousData->Power);
    }

    public function testWattExtensionWithoutNamespace(): void
    {
        $this->parseFile($this->Parser, 'tcx/watt-extension-without-namespace.tcx');

        $this->assertEquals('2013-11-03 14:05', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(60, $this->Container->Metadata->getTimezoneOffset());

        $this->assertEquals(
            [0, 10, 20, 30, 41, 41, 41, 117, 155, 182, 188, 186, 182, 178, 181, 180, 179, 178, 179, 180, 181, 180, 180, 178],
            $this->Container->ContinuousData->Power
        );
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/2012
     */
    public function testPowerInNS2Extension(): void
    {
        $this->parseFile($this->Parser, 'tcx/Power-ns2-extension.tcx');

        $this->assertNotEmpty($this->Container->ContinuousData->Power);
        $this->assertEquals(210, round($this->Container->ActivityData->AvgPower));
    }

    public function testMissingDistancePoints(): void
    {
        $this->parseFile($this->Parser, 'tcx/missing-distances.tcx');

        $this->assertEquals('2010-12-27 15:46', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(60, $this->Container->Metadata->getTimezoneOffset());

        foreach ($this->Container->ContinuousData->Distance as $i => $km) {
            if ($i > 0) {
                $this->assertTrue($km >= $this->Container->ContinuousData->Distance[$i - 1], 'Distance is decreasing');
            }
        }
    }

    public function testFileFromRuntastic(): void
    {
        $this->parseFile($this->Parser, 'tcx/Runtastic.tcx');

        $this->assertEquals('2015-05-10 16:13', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
        $this->assertEquals(120, $this->Container->Metadata->getTimezoneOffset());

        $this->assertEqualsWithDelta(61, $this->Container->ActivityData->Duration, 5);
        $this->assertEqualsWithDelta(0.113, $this->Container->ActivityData->Distance, 0.01);
        $this->assertNotEmpty($this->Container->ContinuousData->Altitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Latitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Longitude);
        $this->assertNotEmpty($this->Container->ContinuousData->HeartRate);

        $this->assertEquals(
            [23, 25, 27, 31, 32, 35, 37, 39, 45, 50],
            array_slice($this->Container->ContinuousData->Time, 10, 10)
        );
        $this->assertEquals(
            [0.0, 0.0, 0.0, 0.052, 0.052, 0.052, 0.052, 0.052, 0.071, 0.085],
            array_slice($this->Container->ContinuousData->Distance, 10, 10)
        );
    }

    public function testRouteOnly(): void
    {
        $this->parseFile($this->Parser, 'tcx/Route-only.tcx');

        $this->assertEqualsWithDelta(0.4, $this->Container->ActivityData->Distance, 0.05);

        $this->assertNotEmpty($this->Container->ContinuousData->Distance);
        $this->assertNotEmpty($this->Container->ContinuousData->Latitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Longitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Altitude);
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1679#issuecomment-169980611
     */
    public function testDoubledCadence(): void
    {
        $this->parseFile($this->Parser, 'tcx/Treadmill-doubled-cadence.tcx');

        $this->assertNotEmpty($this->Container->ContinuousData->Time);
        $this->assertNotEmpty($this->Container->ContinuousData->Distance);
        $this->assertNotEmpty($this->Container->ContinuousData->Cadence);
        $this->assertNotEmpty($this->Container->ContinuousData->HeartRate);

        $this->assertEqualsWithDelta(67, $this->Container->ActivityData->AvgCadence, 0.5);
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1445
     */
    public function testFirstPointEmpty(): void
    {
        $this->parseFile($this->Parser, 'tcx/First-point-empty.tcx');

        $this->assertEquals(142.0, $this->Container->ActivityData->Duration);
        $this->assertEqualsWithDelta(0.601, $this->Container->ActivityData->Distance, 0.001);

        $this->assertNotEmpty($this->Container->ContinuousData->Latitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Longitude);
    }

    /**
     * Regression test for TcxActivity::parseLaps() with exactly one <Lap> element.
     *
     * SimpleXMLElement::key() always returns a string. For exactly one <Lap>
     * child, that string is the tag name ("Lap") instead of an integer index.
     * parseLaps() used to detect the first lap via a loose "$i == 0"
     * comparison. Under PHP 7.4, "Lap" == 0 evaluated to true (non-numeric
     * strings were silently cast to 0 for comparisons), so the check passed
     * by accident. Since PHP 8.0's "saner string to number comparisons"
     * (https://wiki.php.net/rfc/string_to_number_comparison), "Lap" == 0
     * evaluates to false, so the correction of Metadata->Timestamp based on
     * the earlier (and correct) <Lap StartTime> / first-trackpoint value was
     * silently skipped for single-lap activities. The later (and incorrect)
     * <Activity><Id> timestamp remained in place instead, which in turn
     * shifted every derived value (e.g. ActivityData->Duration).
     *
     * This is checked directly on Metadata->getTimestamp(), independent of
     * ActivityData->Duration, ContinuousData or any filter, since Duration
     * is only derived from the timestamp several processing stages later
     * (see AbstractActivityParserTestCase::setContainerFrom()). Asserting
     * on the root cause directly makes a future regression immediately
     * diagnosable instead of only showing up as an unexplained Duration
     * mismatch.
     *
     * @see https://wiki.php.net/rfc/string_to_number_comparison
     */
    public function testSingleLapUsesEarlierLapStartTimeNotLaterActivityId(): void
    {
        $this->parseFile($this->Parser, 'tcx/First-point-empty.tcx');

        // <Id> is 2015-07-09T08:38:46Z (later).
        // The single <Lap StartTime="2015-07-09T08:38:33Z"> is 13s earlier and must win,
        // exactly as it already does for multi-lap files.
        // Last trackpoint is 08:40:55Z, i.e. 142s after the (correct) StartTime baseline.
        $this->assertEquals(120, $this->Container->Metadata->getTimezoneOffset());
        $this->assertEquals(
            '2015-07-09 10:38:33',
            LocalTime::date('Y-m-d H:i:s', $this->Container->Metadata->getTimestamp())
        );
        $this->assertEquals(142.0, $this->Container->ActivityData->Duration);
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1782
     * @see https://github.com/Runalyze/Runalyze/issues/1779
     */
    public function testWrongTimeZoneByPolarThatShouldBeFixedWithTimeZoneLookup(): void
    {
        $this->parseFile($this->Parser, 'tcx/Wrong-time-zone-by-polar.tcx');

        if (RUNALYZE_TEST_TZ_LOOKUP) {
            $this->assertTrue($this->applyTimezoneLookup($this->Container), 'Timezone lookup could not be applied.');
        }

        if (RUNALYZE_TEST_TZ_LOOKUP) {
            $this->assertEquals('2016-04-10 19:40', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
            $this->assertEquals(120, $this->Container->Metadata->getTimezoneOffset());
        } else {
            $this->assertEquals('2016-04-10 18:40', LocalTime::date('Y-m-d H:i', $this->Container->Metadata->getTimestamp()));
            $this->assertEquals(60, $this->Container->Metadata->getTimezoneOffset());
        }

        $this->assertEqualsWithDelta(1765, $this->Container->ActivityData->Duration, 10);
        $this->assertEqualsWithDelta(3.64, $this->Container->ActivityData->Distance, 0.01);

        $this->assertNotEmpty($this->Container->ContinuousData->Altitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Latitude);
        $this->assertNotEmpty($this->Container->ContinuousData->Longitude);
        $this->assertNotEmpty($this->Container->ContinuousData->HeartRate);
        $this->assertNotEmpty($this->Container->ContinuousData->Cadence);
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1948
     */
    public function testNegativePauseFromSigma(): void
    {
        $this->parseFile($this->Parser, 'tcx/negative-pause.tcx');

        $this->assertGreaterThan(0, count($this->Container->Pauses));
        foreach ($this->Container->Pauses as $pause) {
            $this->assertGreaterThan(0, $pause->getDuration());
        }
    }

    /**
     * @see Supportticket #001865
     */
    public function testIndoorActivityWithDistanceForSingleLap(): void
    {
        $this->parseFile($this->Parser, 'tcx/Polar-one-lap-indoor-with-distance.tcx');

        $this->assertEquals(13, $this->Container->ActivityData->Duration);
        $this->assertEqualsWithDelta(0.094, $this->Container->ActivityData->Distance, 0.001);

        $this->assertNotEmpty($this->Container->ContinuousData->Time);
        $this->assertNotEmpty($this->Container->ContinuousData->HeartRate);

        $this->assertEmpty($this->Container->ContinuousData->Latitude);
        $this->assertEmpty($this->Container->ContinuousData->Longitude);
        $this->assertEmpty($this->Container->ContinuousData->Distance);
        $this->assertEmpty($this->Container->ContinuousData->Altitude);

        $this->assertNull($this->Container->ActivityData->AvgPower);
    }
}
