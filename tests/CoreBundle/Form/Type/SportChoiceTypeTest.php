<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Closure;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Type\SportChoiceType;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class SportChoiceTypeTest extends TestCase
{
    public function testChoicesAndEquipmentCategoriesFollowTheAccount(): void
    {
        $defaultActivityType = $this->createStub(Type::class);
        $defaultActivityType->method('getId')->willReturn(301);

        $firstAccount = new Account();
        $firstSport = $this->createStub(Sport::class);
        $firstSport->method('getId')->willReturn(1);
        $firstSport->method('getDefaultType')->willReturn($defaultActivityType);
        $firstAccount->getSports()->add($firstSport);

        $secondAccount = new Account();
        $secondSport = $this->createStub(Sport::class);
        $secondSport->method('getId')->willReturn(2);
        $secondAccount->getSports()->add($secondSport);

        $tokenStorage = new TokenStorage();
        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->willReturn(new RunalyzeConfigurationList());

        $sportRepository = $this->createStub(SportRepository::class);
        $sportRepository->method('findEquipmentCategoryIdsFor')->willReturnMap([
            [[1], [[
                'sport_id' => 1,
                'equipment_type_id' => 101,
            ]]],
            [[2], [[
                'sport_id' => 2,
                'equipment_type_id' => 202,
            ]]],
        ]);

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([
                new SportChoiceType($tokenStorage, $sportRepository, $configurationManager),
            ], []))
            ->getFormFactory();

        $tokenStorage->setToken(new UsernamePasswordToken($firstAccount, 'main'));
        $firstConfig = $formFactory->create(SportChoiceType::class)->getConfig();

        $tokenStorage->setToken(new UsernamePasswordToken($secondAccount, 'main'));
        $secondConfig = $formFactory->create(SportChoiceType::class)->getConfig();

        $firstChoices = $firstConfig->getOption('choices');
        $secondChoices = $secondConfig->getOption('choices');
        $this->assertInstanceOf(Collection::class, $firstChoices);
        $this->assertInstanceOf(Collection::class, $secondChoices);
        $this->assertSame([$firstSport], $firstChoices->getValues());
        $this->assertSame([$secondSport], $secondChoices->getValues());

        $firstAttributes = $firstConfig->getOption('choice_attr');
        $secondAttributes = $secondConfig->getOption('choice_attr');
        $this->assertInstanceOf(Closure::class, $firstAttributes);
        $this->assertInstanceOf(Closure::class, $secondAttributes);
        $firstMetadata = $firstAttributes($firstSport, 0, '1');
        $secondMetadata = $secondAttributes($secondSport, 0, '2');

        $this->assertSame('[101]', $firstMetadata['data-equipment-types']);
        $this->assertSame('[202]', $secondMetadata['data-equipment-types']);
        $this->assertSame(301, $firstMetadata['data-activity-type']);
        $this->assertSame('', $secondMetadata['data-activity-type']);
    }
}
