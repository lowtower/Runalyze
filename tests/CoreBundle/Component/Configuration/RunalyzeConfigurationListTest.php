<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Configuration;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class RunalyzeConfigurationListTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $config = new RunalyzeConfigurationList();
        $this->assertInstanceOf(RunalyzeConfigurationList::class, $config);
    }

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

    public function testSetPropagatesUpdateIntoAlreadyCachedCategoryObject(): void
    {
        $config = new RunalyzeConfigurationList();

        $privacy = $config->getPrivacy();
        $this->assertFalse($privacy->isListPublic(), 'default value must be false before the update');

        $config->set('privacy.TRAINING_LIST_PUBLIC', 'true');

        $this->assertTrue($privacy->isListPublic(), 'the previously-cached Privacy instance must reflect the update in place');
        $this->assertSame($privacy, $config->getPrivacy(), 'getPrivacy() must still return the very same cached instance');
    }

    public function testSetDoesNotAffectDifferentCachedCategory(): void
    {
        $config = new RunalyzeConfigurationList();

        $privacy = $config->getPrivacy();
        $this->assertFalse($privacy->isListPublic());

        $config->set('not-a-real-category.SOME_KEY', 'irrelevant');

        $this->assertFalse($privacy->isListPublic(), 'an update to an unrelated/uncached category prefix must not leak into Privacy');
    }
}
