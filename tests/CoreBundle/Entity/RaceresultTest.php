<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;

#[\PHPUnit\Framework\Attributes\Group('default')]
class RaceresultTest extends TestCase
{
    private Raceresult $raceresult;

    protected function setUp(): void
    {
        $this->raceresult = new Raceresult();
    }

    public function testNameDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->raceresult->getName());
    }

    public function testOfficialDistanceDefaultsToNull(): void
    {
        $this->assertNull($this->raceresult->getOfficialDistance());
    }

    public function testGetAccountDefaultsToNull(): void
    {
        $this->assertNull($this->raceresult->getAccount());
    }

    public function testGetActivityDefaultsToNull(): void
    {
        $this->assertNull($this->raceresult->getActivity());
    }

    public function testOfficiallyMeasuredDefaultsToFalse(): void
    {
        $this->assertFalse($this->raceresult->getOfficiallyMeasured());
    }

    public function testSetOfficialDistanceGetOfficialDistance(): void
    {
        $this->raceresult->setOfficialDistance(42.195);

        $this->assertSame(42.195, $this->raceresult->getOfficialDistance());
    }

    public function testSetOfficialTimeGetOfficialTime(): void
    {
        $this->raceresult->setOfficialTime(9045.0);

        $this->assertSame(9045.0, $this->raceresult->getOfficialTime());
    }

    public function testSetOfficiallyMeasuredGetOfficiallyMeasured(): void
    {
        $this->raceresult->setOfficiallyMeasured(true);

        $this->assertTrue($this->raceresult->getOfficiallyMeasured());
    }

    public function testSetNameGetName(): void
    {
        $this->raceresult->setName('Berlin Marathon');

        $this->assertSame('Berlin Marathon', $this->raceresult->getName());
    }

    public function testSetPlacesGetPlaces(): void
    {
        $this->raceresult->setPlaceTotal(120);
        $this->raceresult->setPlaceGender(100);
        $this->raceresult->setPlaceAgeclass(15);

        $this->assertSame(120, $this->raceresult->getPlaceTotal());
        $this->assertSame(100, $this->raceresult->getPlaceGender());
        $this->assertSame(15, $this->raceresult->getPlaceAgeclass());
    }

    public function testSetParticipantsGetParticipants(): void
    {
        $this->raceresult->setParticipantsTotal(5000);
        $this->raceresult->setParticipantsGender(3000);
        $this->raceresult->setParticipantsAgeclass(200);

        $this->assertSame(5000, $this->raceresult->getParticipantsTotal());
        $this->assertSame(3000, $this->raceresult->getParticipantsGender());
        $this->assertSame(200, $this->raceresult->getParticipantsAgeclass());
    }

    public function testSetAccountGetAccount(): void
    {
        $account = new Account();
        $this->raceresult->setAccount($account);

        $this->assertSame($account, $this->raceresult->getAccount());
    }

    public function testSetActivityGetActivity(): void
    {
        $activity = new Training();
        $this->raceresult->setActivity($activity);

        $this->assertSame($activity, $this->raceresult->getActivity());
    }

    public function testFillFromActivityCopiesActivityAndAccount(): void
    {
        $account = new Account();
        $activity = new Training();
        $activity->setAccount($account);

        $this->raceresult->fillFromActivity($activity);

        $this->assertSame($activity, $this->raceresult->getActivity());
        $this->assertSame($account, $this->raceresult->getAccount());
    }

    public function testFillFromActivityUsesActivityDistanceAsOfficialDistance(): void
    {
        $activity = new Training();
        $activity->setAccount(new Account());
        $activity->setDistance(21.1);

        $this->raceresult->fillFromActivity($activity);

        $this->assertSame(21.1, $this->raceresult->getOfficialDistance());
    }

    public function testFillFromActivityFallsBackToZeroDistanceWhenActivityHasNoDistance(): void
    {
        $activity = new Training();
        $activity->setAccount(new Account());

        $this->raceresult->fillFromActivity($activity);

        $this->assertSame(0.0, $this->raceresult->getOfficialDistance());
    }

    public function testFillFromActivityUsesActivityDurationAsOfficialTime(): void
    {
        $activity = new Training();
        $activity->setAccount(new Account());
        $activity->setS(9045.0);

        $this->raceresult->fillFromActivity($activity);

        $this->assertSame(9045.0, $this->raceresult->getOfficialTime());
    }

    public function testFillFromActivityUsesActivityTitleAsName(): void
    {
        $activity = new Training();
        $activity->setAccount(new Account());
        $activity->setTitle('Berlin Marathon');

        $this->raceresult->fillFromActivity($activity);

        $this->assertSame('Berlin Marathon', $this->raceresult->getName());
    }

    public function testFillFromActivityFallsBackToEmptyNameWhenActivityHasNoTitle(): void
    {
        $activity = new Training();
        $activity->setAccount(new Account());

        $this->raceresult->fillFromActivity($activity);

        $this->assertSame('', $this->raceresult->getName());
    }
}
