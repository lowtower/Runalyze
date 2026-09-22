<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Equipment;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Model\Equipment\EquipmentStatistic;

class EquipmentStatisticTest extends TestCase
{
    public function testGetEquipmentReturnsConstructorValue(): void
    {
        $equipment = $this->createMock(Equipment::class);

        $statistic = new EquipmentStatistic($equipment);

        $this->assertSame($equipment, $statistic->getEquipment());
    }

    public function testNumberOfActivitiesDefaultsToNull(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $this->assertNull($statistic->getNumberOfActivities());
    }

    public function testSetAndGetNumberOfActivities(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $statistic->setNumberOfActivities(42);

        $this->assertSame(42, $statistic->getNumberOfActivities());
    }

    public function testNumberOfActivitiesCanBeSetBackToNull(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $statistic->setNumberOfActivities(42);
        $statistic->setNumberOfActivities(null);

        $this->assertNull($statistic->getNumberOfActivities());
    }

    public function testMaximalPaceDefaultsToNull(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $this->assertNull($statistic->getMaximalPace());
    }

    public function testSetAndGetMaximalPace(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $statistic->setMaximalPace(305.5);

        $this->assertSame(305.5, $statistic->getMaximalPace());
    }

    public function testMaximalPaceCanBeSetBackToNull(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $statistic->setMaximalPace(305.5);
        $statistic->setMaximalPace(null);

        $this->assertNull($statistic->getMaximalPace());
    }

    public function testMaximalDistanceDefaultsToNull(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $this->assertNull($statistic->getMaximalDistance());
    }

    public function testSetAndGetMaximalDistance(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $statistic->setMaximalDistance(21.1);

        $this->assertSame(21.1, $statistic->getMaximalDistance());
    }

    public function testMaximalDistanceCanBeSetBackToNull(): void
    {
        $statistic = new EquipmentStatistic($this->createMock(Equipment::class));

        $statistic->setMaximalDistance(21.1);
        $statistic->setMaximalDistance(null);

        $this->assertNull($statistic->getMaximalDistance());
    }
}
