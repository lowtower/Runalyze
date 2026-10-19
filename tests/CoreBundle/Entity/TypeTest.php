<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Profile\View\DataBrowserRowProfile;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class TypeTest extends TestCase
{
    private Type $type;

    protected function setUp(): void
    {
        $this->type = new Type();
    }

    public function testNameDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->type->getName());
    }

    public function testAbbrDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->type->getAbbr());
    }

    public function testDisplayModeDefaultsToTwo(): void
    {
        $this->assertSame(2, $this->type->getDisplayMode());
    }

    public function testHrAvgDefaultsTo100(): void
    {
        $this->assertSame(100, $this->type->getHrAvg());
    }

    public function testQualitySessionDefaultsToFalse(): void
    {
        $this->assertFalse($this->type->getQualitySession());
    }

    public function testSetNameGetName(): void
    {
        $this->type->setName('Intervals');

        $this->assertSame('Intervals', $this->type->getName());
    }

    public function testSetNameNormalizesNullToEmptyString(): void
    {
        $this->type->setName('Intervals');
        $this->type->setName(null);

        $this->assertSame('', $this->type->getName());
    }

    public function testSetAbbrGetAbbr(): void
    {
        $this->type->setAbbr('INT');

        $this->assertSame('INT', $this->type->getAbbr());
    }

    public function testSetAbbrNormalizesNullToEmptyString(): void
    {
        $this->type->setAbbr('INT');
        $this->type->setAbbr(null);

        $this->assertSame('', $this->type->getAbbr());
    }

    public function testSetSportGetSport(): void
    {
        $sport = new Sport();
        $this->type->setSport($sport);

        $this->assertSame($sport, $this->type->getSport());
    }

    public function testSetDisplayModeGetDisplayMode(): void
    {
        $this->type->setDisplayMode(DataBrowserRowProfile::COMPLETE_ROW);

        $this->assertSame(DataBrowserRowProfile::COMPLETE_ROW, $this->type->getDisplayMode());
    }

    public function testInheritsDisplayModeIsTrueForInheritFromParent(): void
    {
        $this->type->setDisplayMode(DataBrowserRowProfile::INHERIT_FROM_PARENT);

        $this->assertTrue($this->type->inheritsDisplayMode());
    }

    public function testInheritsDisplayModeIsFalseForExplicitMode(): void
    {
        $this->type->setDisplayMode(DataBrowserRowProfile::COMPLETE_ROW);

        $this->assertFalse($this->type->inheritsDisplayMode());
    }

    public function testShowsCompleteRowWhenDisplayModeIsCompleteRow(): void
    {
        $this->type->setDisplayMode(DataBrowserRowProfile::COMPLETE_ROW);

        $this->assertTrue($this->type->showsCompleteRow());
        $this->assertFalse($this->type->showsOnlyIcon());
    }

    public function testShowsOnlyIconWhenDisplayModeIsOnlyIcon(): void
    {
        $this->type->setDisplayMode(DataBrowserRowProfile::ONLY_ICON);

        $this->assertTrue($this->type->showsOnlyIcon());
        $this->assertFalse($this->type->showsCompleteRow());
    }

    public function testShowsCompleteRowInheritsFromSportWhenDisplayModeIsInherit(): void
    {
        $sport = new Sport();
        $sport->setShort(false);

        $this->type->setSport($sport);
        $this->type->setDisplayMode(DataBrowserRowProfile::INHERIT_FROM_PARENT);

        $this->assertTrue($this->type->showsCompleteRow());
    }

    public function testShowsOnlyIconInheritsFromSportWhenDisplayModeIsInherit(): void
    {
        $sport = new Sport();
        $sport->setShort(true);

        $this->type->setSport($sport);
        $this->type->setDisplayMode(DataBrowserRowProfile::INHERIT_FROM_PARENT);

        $this->assertTrue($this->type->showsOnlyIcon());
    }

    public function testSetHrAvgGetHrAvg(): void
    {
        $this->type->setHrAvg(145);

        $this->assertSame(145, $this->type->getHrAvg());
    }

    public function testSetQualitySessionGetQualitySession(): void
    {
        $this->type->setQualitySession(true);

        $this->assertTrue($this->type->getQualitySession());
    }

    public function testSetAccountGetAccount(): void
    {
        $account = new Account();
        $this->type->setAccount($account);

        $this->assertSame($account, $this->type->getAccount());
    }

    public function testGetTrainingsReturnsEmptyCollectionByDefault(): void
    {
        $this->assertTrue($this->type->getTrainings()->isEmpty());
    }
}
