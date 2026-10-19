<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\EquipmentType as EquipmentTypeQueryGroup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Month;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroups;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Sport as SportQueryGroup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Type as TypeQueryGroup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Year;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;

final class QueryGroupsTest extends TestCase
{
    public function testGetEnumForEquipmentTypeBuildsExpectedString(): void
    {
        $equipmentType = $this->createStub(EquipmentType::class);
        $equipmentType->method('getId')
            ->willReturn(7);

        $result = QueryGroups::getEnumForEquipmentType($equipmentType);

        $this->assertSame('equipment_7', $result);
    }

    public function testGetGroupReturnsEquipmentTypeQueryGroupForEquipmentPrefixedEnum(): void
    {
        $result = QueryGroups::getGroup('equipment_7');

        $this->assertInstanceOf(EquipmentTypeQueryGroup::class, $result);

        $reflection = new \ReflectionObject($result);
        $property = $reflection->getProperty('EquipmentTypeId');

        $this->assertSame(7, $property->getValue($result));
    }

    public function testGetGroupReturnsMonthForMonthEnum(): void
    {
        $this->assertInstanceOf(Month::class, QueryGroups::getGroup(QueryGroups::MONTH));
    }

    public function testGetGroupReturnsYearForYearEnum(): void
    {
        $this->assertInstanceOf(Year::class, QueryGroups::getGroup(QueryGroups::YEAR));
    }

    public function testGetGroupReturnsSportForSportEnum(): void
    {
        $this->assertInstanceOf(SportQueryGroup::class, QueryGroups::getGroup(QueryGroups::SPORT));
    }

    public function testGetGroupReturnsTypeForTypeEnum(): void
    {
        $this->assertInstanceOf(TypeQueryGroup::class, QueryGroups::getGroup(QueryGroups::TYPE));
    }

    public function testGetGroupThrowsInvalidArgumentExceptionForUnknownEnum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        QueryGroups::getGroup('this-is-not-a-real-group');
    }

    public function testGetGroupWithBareEquipmentConstantThrowsArgumentCountErrorNotInvalidArgumentException(): void
    {
        $this->expectException(\ArgumentCountError::class);

        QueryGroups::getGroup(QueryGroups::EQUIPMENT_TYPE);
    }
}
