<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityTypeChoiceType;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class ActivityTypeChoiceTypeTest extends TestCase
{
    public function testChoicesFollowTheAccountWhenTheFormFactoryIsReused(): void
    {
        $firstAccount = new Account();
        $secondAccount = new Account();
        $activityType = $this->createStub(Type::class);
        $activityType->method('getId')->willReturn(1);
        $firstAccount->getActivityTypes()->add($activityType);

        $tokenStorage = new TokenStorage();
        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->willReturn(new RunalyzeConfigurationList());
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([
                new ActivityTypeChoiceType($tokenStorage, $configurationManager),
            ], []))
            ->getFormFactory();

        $tokenStorage->setToken(new UsernamePasswordToken($firstAccount, 'main'));
        $firstChoices = $formFactory->create(ActivityTypeChoiceType::class)->getConfig()->getOption('choices');

        $tokenStorage->setToken(new UsernamePasswordToken($secondAccount, 'main'));
        $secondChoices = $formFactory->create(ActivityTypeChoiceType::class)->getConfig()->getOption('choices');

        $this->assertInstanceOf(Collection::class, $firstChoices);
        $this->assertInstanceOf(Collection::class, $secondChoices);
        $this->assertSame([$activityType], $firstChoices->getValues());
        $this->assertSame([], $secondChoices->getValues());
    }
}
