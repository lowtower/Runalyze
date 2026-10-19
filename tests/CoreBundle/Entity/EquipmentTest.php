<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class EquipmentTest extends TestCase
{
    private Equipment $equipment;

    protected function setUp(): void
    {
        $this->equipment = new Equipment();
    }

    public function testNotesDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->equipment->getNotes());
    }

    public function testDistanceDefaultsToZero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->equipment->getDistance(), PHP_FLOAT_EPSILON);
    }

    public function testTimeDefaultsToZero(): void
    {
        $this->assertSame(0, $this->equipment->getTime());
    }

    public function testAdditionalKmDefaultsToZero(): void
    {
        $this->assertSame(0, $this->equipment->getAdditionalKm());
    }

    public function testSetNameGetName(): void
    {
        $this->equipment->setName('Trail Shoes');

        $this->assertSame('Trail Shoes', $this->equipment->getName());
    }

    public function testSetNotesGetNotes(): void
    {
        $this->equipment->setNotes('Bought in 2024');

        $this->assertSame('Bought in 2024', $this->equipment->getNotes());
    }

    public function testSetDistanceGetDistance(): void
    {
        $this->equipment->setDistance(42.0);

        $this->assertEqualsWithDelta(42.0, $this->equipment->getDistance(), PHP_FLOAT_EPSILON);
    }

    public function testAddDistanceAccumulatesExistingDistance(): void
    {
        $this->equipment->setDistance(10.0);
        $this->equipment->addDistance(5.5);

        $this->assertEqualsWithDelta(15.5, $this->equipment->getDistance(), PHP_FLOAT_EPSILON);
    }

    public function testAddDistanceWithNullDoesNotChangeDistance(): void
    {
        $this->equipment->setDistance(10.0);
        $this->equipment->addDistance();

        $this->assertEqualsWithDelta(10.0, $this->equipment->getDistance(), PHP_FLOAT_EPSILON);
    }

    public function testSetTimeGetTime(): void
    {
        $this->equipment->setTime(3600);

        $this->assertSame(3600, $this->equipment->getTime());
    }

    public function testAddTimeAccumulatesExistingTime(): void
    {
        $this->equipment->setTime(1800);
        $this->equipment->addTime(900);

        $this->assertSame(2700, $this->equipment->getTime());
    }

    public function testAddTimeWithNullDoesNotChangeTime(): void
    {
        $this->equipment->setTime(1800);
        $this->equipment->addTime();

        $this->assertSame(1800, $this->equipment->getTime());
    }

    public function testGetPaceReturnsNullWhenDistanceIsZero(): void
    {
        $this->equipment->setDistance(0.0);
        $this->equipment->setTime(3600);

        $this->assertNull($this->equipment->getPace());
    }

    public function testGetPaceComputesSecondsPerKilometer(): void
    {
        $this->equipment->setDistance(10.0);
        $this->equipment->setTime(3000);

        $this->assertEqualsWithDelta(300.0, $this->equipment->getPace(), PHP_FLOAT_EPSILON);
    }

    public function testSetAdditionalKmNormalizesNullToZero(): void
    {
        $this->equipment->setAdditionalKm(5);
        $this->equipment->setAdditionalKm(null);

        $this->assertSame(0, $this->equipment->getAdditionalKm());
    }

    public function testGetTotalDistanceSumsDistanceAndAdditionalKm(): void
    {
        $this->equipment->setDistance(10.0);
        $this->equipment->setAdditionalKm(5);

        $this->assertEqualsWithDelta(15.0, $this->equipment->getTotalDistance(), PHP_FLOAT_EPSILON);
    }

    public function testHasStartDateIsFalseByDefault(): void
    {
        $this->assertFalse($this->equipment->hasStartDate());
        $this->assertNull($this->equipment->getDateStart());
    }

    public function testSetDateStartGetDateStart(): void
    {
        $dateStart = new \DateTime('2024-01-01');
        $this->equipment->setDateStart($dateStart);

        $this->assertTrue($this->equipment->hasStartDate());
        $this->assertSame($dateStart, $this->equipment->getDateStart());
    }

    public function testIsActiveIsTrueByDefault(): void
    {
        $this->assertTrue($this->equipment->isActive());
    }

    public function testIsActiveIsFalseWhenDateEndIsSet(): void
    {
        $this->equipment->setDateEnd(new \DateTime('2024-06-01'));

        $this->assertFalse($this->equipment->isActive());
    }

    public function testSetTypeGetType(): void
    {
        $type = new EquipmentType();
        $this->equipment->setType($type);

        $this->assertSame($type, $this->equipment->getType());
    }

    public function testAddAndRemoveSport(): void
    {
        $sport = new Sport();
        $this->equipment->addSport($sport);

        $this->assertTrue($this->equipment->getSport()->contains($sport));

        $this->equipment->removeSport($sport);

        $this->assertFalse($this->equipment->getSport()->contains($sport));
    }

    public function testSetAccountGetAccount(): void
    {
        $account = new Account();
        $this->equipment->setAccount($account);

        $this->assertSame($account, $this->equipment->getAccount());
    }

    public function testAddAndRemoveActivity(): void
    {
        $training = new Training();
        $this->equipment->addActivity($training);

        $this->assertTrue($this->equipment->getActivity()->contains($training));

        $this->equipment->removeActivity($training);

        $this->assertFalse($this->equipment->getActivity()->contains($training));
    }
}
