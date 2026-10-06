<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\EnergyUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class EnergyTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $this->assertEquals('100', (new Energy(100, new EnergyUnit(EnergyUnit::KCAL)))->string(false));
        $this->assertEquals('419', (new Energy(100, new EnergyUnit(EnergyUnit::KJ)))->string(false));
    }

    public function testConstructorFallsBackToConfiguredUnitWhenNoneGiven(): void
    {
        Configuration::General()->energyUnit()->set(EnergyUnit::KCAL);

        $this->assertSame(EnergyUnit::KCAL, (new Energy(100))->unit());
    }

    public function testSettingInPreferredUnit(): void
    {
        $this->assertSame(100, (new Energy(0, new EnergyUnit(EnergyUnit::KCAL)))->setInPreferredUnit(100)->kcal());
        $this->assertSame(24, (new Energy(0, new EnergyUnit(EnergyUnit::KJ)))->setInPreferredUnit(100)->kcal());
    }

    public function testFromKilocalorie(): void
    {
        $Energy = new Energy();
        $Energy->set(100);

        $this->assertSame(100, $Energy->kcal());
        $this->assertSame(419, $Energy->kj());
    }

    public function testFromKilojule(): void
    {
        $Energy = new Energy();
        $Energy->setKJ(41868);

        $this->assertSame(10000, $Energy->kcal());
        $this->assertSame(41868, $Energy->kj());
    }

    public function testStaticMethod(): void
    {
        Configuration::General()->energyUnit()->set(EnergyUnit::KCAL);

        $this->assertEquals('123', Energy::format(123, false));
    }

    public function testSetAcceptsNumericString(): void
    {
        $Energy = new Energy();
        $Energy->set('100');

        $this->assertSame(100, $Energy->kcal());
    }

    public function testValueIsAliasForKcal(): void
    {
        $Energy = new Energy(150);

        $this->assertSame($Energy->kcal(), $Energy->value());
    }

    public function testValueInPreferredUnitReturnsKcalForKcalPreference(): void
    {
        $Energy = new Energy(100, new EnergyUnit(EnergyUnit::KCAL));

        $this->assertSame(100, $Energy->valueInPreferredUnit());
    }

    public function testValueInPreferredUnitReturnsKjForKjPreference(): void
    {
        $Energy = new Energy(100, new EnergyUnit(EnergyUnit::KJ));

        $this->assertSame(419, $Energy->valueInPreferredUnit());
    }

    public function testStringKcalWithUnit(): void
    {
        $Energy = new Energy(100);

        $this->assertSame('100&nbsp;kcal', $Energy->stringKcal());
    }

    public function testStringKJWithUnit(): void
    {
        $Energy = new Energy(100);

        $this->assertSame('419&nbsp;kJ', $Energy->stringKJ());
    }

    public function testStringKcalDoesNotGroupThousands(): void
    {
        $Energy = new Energy(150000);

        $this->assertSame('150000', $Energy->stringKcal(false));
    }

    public function testSetRoundsFractionalKcalInsteadOfTruncating(): void
    {
        $Energy = new Energy();
        $Energy->set(100.9);

        $this->assertSame(101, $Energy->kcal());
    }

    public function testSetKJRoundsInsteadOfTruncating(): void
    {
        $Energy = new Energy();
        $Energy->setKJ(100);

        $this->assertSame(24, $Energy->kcal());
    }
}
