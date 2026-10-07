<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Recalculation;

use PHPUnit\Framework\MockObject\MockObject;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\ConfRepository;
use Runalyze\Bundle\CoreBundle\Entity\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

#[\PHPUnit\Framework\Attributes\Group('default')]
class RecalculationManagerTest extends \PHPUnit\Framework\TestCase
{
    protected RecalculationManager $Manager;

    protected Account $Account;

    protected ConfigurationManager $ConfigurationManager;

    protected ConfigurationUpdater $ConfigurationUpdater;

    protected function setUp(): void
    {
        $this->Account = new Account();
        /** @var ConfRepository&MockObject $confRepository */
        $confRepository = $this->getConfRepositoryStub();
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new PreAuthenticatedToken($this->Account, 'bar'));

        $this->ConfigurationManager = new ConfigurationManager($confRepository, $tokenStorage);
        $list = $this->ConfigurationManager->getList();
        $list->set('data.START_TIME', '0');
        $list->set('data.VO2MAX_FORM', '0');
        $list->set('data.VO2MAX_CORRECTOR', '1.00');
        $list->set('data.BASIC_ENDURANCE', '0');

        $this->ConfigurationUpdater = new ConfigurationUpdater($confRepository, $this->ConfigurationManager);

        $this->Manager = new RecalculationManager(
            $this->ConfigurationManager,
            $this->ConfigurationUpdater,
            $this->getTrainingRepositoryStub(),
            $this->getRaceResultRepositoryStub()
        );
    }

    public function testNoScheduledTasks(): void
    {
        $this->assertSame(0, $this->Manager->getNumberOfScheduledTasksFor($this->Account));

        $this->Manager->runScheduledTasks();

        $this->assertEquals('0', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
        $this->assertEquals('0', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_FORM'));
        $this->assertEquals('1.00', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_CORRECTOR'));
        $this->assertEquals('0', $this->ConfigurationManager->getList($this->Account)->get('data.BASIC_ENDURANCE'));
    }

    public function testThatMarathonShapeHasToBeCalculatedIfVO2maxShapeIsCalculated(): void
    {
        $this->Manager->scheduleEffectiveVO2maxShapeCalculation($this->Account);

        $this->assertSame(2, $this->Manager->getNumberOfScheduledTasksFor($this->Account));
    }

    public function testThatTasksAreNotScheduledMultipleTimes(): void
    {
        $this->Manager->scheduleStartTimeCalculation($this->Account);
        $this->Manager->scheduleStartTimeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);

        $this->assertSame(2, $this->Manager->getNumberOfScheduledTasksFor($this->Account));
    }

    public function testThatResultsOfTasksAreForwardedToConfiguration(): void
    {
        $this->Manager->scheduleEffectiveVO2maxShapeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);
        $this->Manager->scheduleStartTimeCalculation($this->Account);
        $this->Manager->scheduleEffectiveVO2maxCorrectionFactorCalculation($this->Account);

        $this->assertSame(4, $this->Manager->getNumberOfScheduledTasksFor($this->Account));

        $this->Manager->runScheduledTasks();

        $this->assertEquals('123456789', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
        $this->assertEquals('35.7', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_FORM'));
        $this->assertEquals('0.85', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_CORRECTOR'));
        $this->assertEquals('68', $this->ConfigurationManager->getList($this->Account)->get('data.BASIC_ENDURANCE'));
    }

    public function testThatStartTimeCanBeUpdatedWithoutFullRecalculation(): void
    {
        $this->Manager->addStartTimeCheck($this->Account, 100000000, false);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('100000000', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testThatNewStartTimeAboveCurrentValueDoesNotChangeAnything(): void
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 123456789, false);
        $this->Manager->addStartTimeCheck($this->Account, 154321000, false);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('100000000', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testMultipleStartTimeUpdates(): void
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 123456789, false);
        $this->Manager->addStartTimeCheck($this->Account, 154321000, false);
        $this->Manager->addStartTimeCheck($this->Account, 98765432, false);
        $this->Manager->addStartTimeCheck($this->Account, 23456789, false);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('23456789', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testThatStartTimeIsNotChangedIfRemovedActivityIsTooNew(): void
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 100000001, true);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('100000000', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testThatStartTimeIsChangedIfRemovedActivityWasTheOldestOne(): void
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 100000000, true);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('123456789', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    protected function getAccountStub(): Account
    {
        $account = $this->createStub(Account::class);
        $account
            ->method('getId')
            ->willReturn(1);

        return $account;
    }

    protected function getTrainingRepositoryStub(): TrainingRepository
    {
        $repository = $this->createStub(TrainingRepository::class);
        $repository
            ->method('getStartTime')
            ->willReturn(123456789);
        $repository
            ->method('calculateVO2maxShape')
            ->willReturn(42.0);
        $repository
            ->method('calculateMarathonShape')
            ->willReturn(68);

        return $repository;
    }

    protected function getRaceResultRepositoryStub(): RaceresultRepository
    {
        $repository = $this->createStub(RaceresultRepository::class);
        $repository
            ->method('getEffectiveVO2maxCorrectionFactor')
            ->willReturn(0.85);

        return $repository;
    }

    protected function getConfRepositoryStub(): ConfRepository
    {
        $repository = $this->createStub(ConfRepository::class);
        $repository
            ->method('findForAccount')
            ->willReturn([]);
        $repository
            ->method('findForAccountAndKey')
            ->willReturn(null);

        return $repository;
    }
}
