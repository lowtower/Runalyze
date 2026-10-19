<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class EquipmentTypeTest extends \PHPUnit\Framework\TestCase
{
    protected EquipmentType $EquipmentType;

    protected function setUp(): void
    {
        $this->EquipmentType = new EquipmentType();
    }

    public function testIdIsNullForNewEntity(): void
    {
        $this->assertNull($this->EquipmentType->getId());
    }

    public function testSetNameAndGetName(): void
    {
        $this->EquipmentType->setName('Running Shoes');

        $this->assertSame('Running Shoes', $this->EquipmentType->getName());
    }

    public function testSetInputAndGetInput(): void
    {
        $this->EquipmentType->setInput(EquipmentType::CHOICE_MULTIPLE);

        $this->assertSame(EquipmentType::CHOICE_MULTIPLE, $this->EquipmentType->getInput());
    }

    public function testAllowsMultipleValuesIsFalseForChoiceSingle(): void
    {
        $this->EquipmentType->setInput(EquipmentType::CHOICE_SINGLE);

        $this->assertFalse($this->EquipmentType->allowsMultipleValues());
    }

    public function testAllowsMultipleValuesIsTrueForChoiceMultiple(): void
    {
        $this->EquipmentType->setInput(EquipmentType::CHOICE_MULTIPLE);

        $this->assertTrue($this->EquipmentType->allowsMultipleValues());
    }

    public function testHasMaximalDistanceIsFalseByDefault(): void
    {
        $this->assertFalse($this->EquipmentType->hasMaximalDistance());
    }

    public function testHasMaximalDistanceIsFalseForZero(): void
    {
        $this->EquipmentType->setMaxKm(0);

        $this->assertFalse($this->EquipmentType->hasMaximalDistance());
    }

    public function testHasMaximalDistanceIsTrueForPositiveValue(): void
    {
        $this->EquipmentType->setMaxKm(500);

        $this->assertTrue($this->EquipmentType->hasMaximalDistance());
        $this->assertSame(500, $this->EquipmentType->getMaxKm());
    }

    public function testHasMaximalDurationIsFalseByDefault(): void
    {
        $this->assertFalse($this->EquipmentType->hasMaximalDuration());
    }

    public function testHasMaximalDurationIsFalseForZero(): void
    {
        $this->EquipmentType->setMaxTime(0);

        $this->assertFalse($this->EquipmentType->hasMaximalDuration());
    }

    public function testHasMaximalDurationIsTrueForPositiveValue(): void
    {
        $this->EquipmentType->setMaxTime(3600);

        $this->assertTrue($this->EquipmentType->hasMaximalDuration());
        $this->assertSame(3600, $this->EquipmentType->getMaxTime());
    }

    public function testSetAccountAndGetAccount(): void
    {
        $account = $this->createStub(Account::class);

        $this->EquipmentType->setAccount($account);

        $this->assertSame($account, $this->EquipmentType->getAccount());
    }

    public function testSportCollectionIsEmptyByDefault(): void
    {
        $this->assertCount(0, $this->EquipmentType->getSport());
    }

    public function testAddSportAddsToCollection(): void
    {
        $sport = $this->createStub(Sport::class);

        $this->EquipmentType->addSport($sport);

        $this->assertCount(1, $this->EquipmentType->getSport());
        $this->assertTrue($this->EquipmentType->getSport()->contains($sport));
    }

    public function testRemoveSportRemovesFromCollection(): void
    {
        $sport = $this->createStub(Sport::class);
        $this->EquipmentType->addSport($sport);

        $this->EquipmentType->removeSport($sport);

        $this->assertCount(0, $this->EquipmentType->getSport());
    }

    public function testEquipmentCollectionIsEmptyByDefault(): void
    {
        $this->assertCount(0, $this->EquipmentType->getEquipment());
    }
}
