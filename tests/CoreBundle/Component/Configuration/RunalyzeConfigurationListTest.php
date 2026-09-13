<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Configuration;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;

class RunalyzeConfigurationListTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $config = new RunalyzeConfigurationList();
        $this->assertInstanceOf(RunalyzeConfigurationList::class, $config);
    }

    /**
     * @group review-later
     * TODO: This test needs a deeper look later. It passes, but check if $config->getVO2maxCorrectionFactor() should return float instead of an object.
     */
    public function testThatAllCategoriesAreAccessible(): void
    {
        $config = new RunalyzeConfigurationList();

        // Prüfe, ob die Methoden entweder Objekte oder numerische Werte zurückgeben
        $this->assertIsObject($config->getActivityForm());
        $this->assertIsObject($config->getActivityView());
        $this->assertIsObject($config->getBasicEndurance());
        $this->assertIsObject($config->getData());
        $this->assertIsObject($config->getDataBrowser());
        $this->assertIsObject($config->getDesign());
        $this->assertIsObject($config->getGeneral());
        $this->assertIsObject($config->getPrivacy());
        $this->assertIsObject($config->getTrimp());
        $this->assertIsFloat($config->getVO2maxCorrectionFactor());
    }

    public function testThatUnitSystemIsPersistent(): void
    {
        $config = new RunalyzeConfigurationList();

        $this->assertEquals($config->getUnitSystem(), $config->getUnitSystem());
    }
}
