<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Form\BodyValuesType;
use Runalyze\Bundle\CoreBundle\Form\EquipmentCategoryType;
use Runalyze\Bundle\CoreBundle\Form\EquipmentType as EquipmentFormType;
use Runalyze\Bundle\CoreBundle\Form\MultiImporterType;
use Runalyze\Bundle\CoreBundle\Form\RaceResultType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportTypeType;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaType;
use Runalyze\Bundle\CoreBundle\Form\Tools\PosterType;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisType;
use Runalyze\Bundle\CoreBundle\Form\Type\HrZoneBoundType;
use Runalyze\Bundle\CoreBundle\Form\Type\SportChoiceType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormRegistryInterface;

#[Group('requiresKernel')]
final class SimpleFormTypeRegistrationTest extends KernelTestCase
{
    #[DataProvider('formTypes')]
    public function testFormTypeIsAvailableThroughSymfonyRegistry(string $type): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get(FormRegistryInterface::class);

        $this->assertInstanceOf($type, $registry->getType($type)->getInnerType());
    }

    public static function formTypes(): iterable
    {
        yield 'activity' => [ActivityType::class];
        yield 'anova' => [AnovaType::class];
        yield 'body values' => [BodyValuesType::class];
        yield 'equipment category' => [EquipmentCategoryType::class];
        yield 'equipment' => [EquipmentFormType::class];
        yield 'multi importer' => [MultiImporterType::class];
        yield 'race result' => [RaceResultType::class];
        yield 'poster' => [PosterType::class];
        yield 'trend analysis' => [TrendAnalysisType::class];
        yield 'sport settings' => [SportType::class];
        yield 'sport type settings' => [SportTypeType::class];
        yield 'heart rate zone bound' => [HrZoneBoundType::class];
        yield 'sport choice' => [SportChoiceType::class];
    }
}
