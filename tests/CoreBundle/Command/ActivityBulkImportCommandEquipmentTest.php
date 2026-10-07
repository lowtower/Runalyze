<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\ActivityBulkImportCommand;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Util\LocalTime;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityBulkImportCommandEquipmentTest extends TestCase
{
    private function createCommand(): ActivityBulkImportCommand
    {
        return new ActivityBulkImportCommand(
            $this->createStub(ParameterBagInterface::class),
            $this->createStub(ManagerRegistry::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(Filesystem::class),
            $this->createStub(FileImporter::class),
            $this->createStub(ActivityDataContainerFilter::class),
            $this->createStub(ActivityDataContainerToActivityContextConverter::class),
            $this->createStub(ActivityContextAdapterFactory::class),
            $this->createStub(ConfigurationManager::class)
        );
    }

    private function invokeCreateEquiqmentArray(ActivityBulkImportCommand $command, Account $account, BufferedOutput $output): void
    {
        $method = new \ReflectionMethod(ActivityBulkImportCommand::class, 'createEquiqmentArray');
        $method->invoke($command, $account, $output);
    }

    private function invokeSetEquipmentIfPossible(ActivityBulkImportCommand $command, Training $activity, BufferedOutput $output): void
    {
        $method = new \ReflectionMethod(ActivityBulkImportCommand::class, 'setEquipmentIfPossible');
        $method->invoke($command, $activity, $output);
    }

    private function readSportEquipmentProperty(ActivityBulkImportCommand $command): array
    {
        $property = new \ReflectionProperty(ActivityBulkImportCommand::class, 'sportEquipment');

        return $property->getValue($command);
    }

    /**
     * @param Sport[] $sports
     */
    private function createEquipment(
        int $id,
        string $name,
        EquipmentType $type,
        array $sports,
        ?\DateTime $dateStart = null,
        ?\DateTime $dateEnd = null
    ): Stub&Equipment {
        $equipment = $this->createStub(Equipment::class);
        $equipment->method('getId')
            ->willReturn($id);
        $equipment->method('getName')
            ->willReturn($name);
        $equipment->method('getType')
            ->willReturn($type);
        $equipment->method('getSport')
            ->willReturn(new ArrayCollection($sports));
        $equipment->method('getDateStart')
            ->willReturn($dateStart);
        $equipment->method('getDateEnd')
            ->willReturn($dateEnd);

        return $equipment;
    }

    private function createEquipmentType(int $id, string $name, int $input): Stub&EquipmentType
    {
        $type = $this->createStub(EquipmentType::class);
        $type->method('getId')
            ->willReturn($id);
        $type->method('getName')
            ->willReturn($name);
        $type->method('getInput')
            ->willReturn($input);

        return $type;
    }

    private function createSport(int $id, string $name = 'Running'): Stub&Sport
    {
        $sport = $this->createStub(Sport::class);
        $sport->method('getId')
            ->willReturn($id);
        $sport->method('getName')
            ->willReturn($name);

        return $sport;
    }

    /**
     * @param Equipment[] $equipment
     */
    private function createAccount(array $equipment): Stub&Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getUsername')
            ->willReturn('tester');
        $account->method('getEquipment')
            ->willReturn(new ArrayCollection($equipment));

        return $account;
    }

    /**
     * Stays a mock: every test that uses it sets expects() on addEquipment.
     */
    private function createTrainingActivity(int $sportId, \DateTime $activityDateTime): MockObject&Training
    {
        $sport = $this->createSport($sportId);

        $activity = $this->createMock(Training::class);
        $activity->method('getSport')
            ->willReturn($sport);
        $activity->method('getDateTime')
            ->willReturn(new LocalTime($activityDateTime->getTimestamp()));

        return $activity;
    }

    public function testCreateEquiqmentArrayIsEmptyWhenAccountHasNoEquipment(): void
    {
        $command = $this->createCommand();

        $account = $this->createAccount([]);

        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $this->assertSame([], $this->readSportEquipmentProperty($command));
    }

    public function testCreateEquiqmentArraySkipsEquipmentThatIsNotSingleChoice(): void
    {
        $command = $this->createCommand();

        $multiChoiceType = $this->createEquipmentType(1, 'Shoes', EquipmentType::CHOICE_MULTIPLE);
        $sport = $this->createSport(10);
        $equipment = $this->createEquipment(100, 'Trail Shoes', $multiChoiceType, [$sport]);

        $account = $this->createAccount([$equipment]);

        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $this->assertSame([], $this->readSportEquipmentProperty($command));
    }

    public function testCreateEquiqmentArrayIndexesSingleChoiceEquipmentBySportId(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Cycling');
        $equipment = $this->createEquipment(100, 'Road Bike', $type, [$sport]);

        $account = $this->createAccount([$equipment]);

        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $sportEquipment = $this->readSportEquipmentProperty($command);
        $this->assertArrayHasKey(10, $sportEquipment);
        $this->assertCount(1, $sportEquipment[10]);
        $this->assertSame($equipment, $sportEquipment[10][0]);
    }

    public function testCreateEquiqmentArrayIndexesEquipmentUnderEverySportItIsAssignedTo(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Shoes', EquipmentType::CHOICE_SINGLE);
        $runningSport = $this->createSport(10, 'Running');
        $hikingSport = $this->createSport(20, 'Hiking');
        $equipment = $this->createEquipment(100, 'Trail Shoes', $type, [$runningSport, $hikingSport]);

        $account = $this->createAccount([$equipment]);

        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $sportEquipment = $this->readSportEquipmentProperty($command);
        $this->assertArrayHasKey(10, $sportEquipment);
        $this->assertArrayHasKey(20, $sportEquipment);
    }

    public function testSetEquipmentIfPossibleAddsNothingWhenNoEquipmentConfiguredForSport(): void
    {
        $command = $this->createCommand();

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-06-01'));
        $activity->expects($this->never())
            ->method('addEquipment');

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }

    public function testSetEquipmentIfPossibleAddsEquipmentWithNoDateRestrictions(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Cycling');
        $equipment = $this->createEquipment(100, 'Road Bike', $type, [$sport]);

        $account = $this->createAccount([$equipment]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-06-01 10:00'));
        $activity->expects($this->once())
            ->method('addEquipment')
            ->with($equipment);

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }

    public function testSetEquipmentIfPossibleAddsEquipmentWhenActivityDateIsWithinRange(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Cycling');
        $equipment = $this->createEquipment(
            100,
            'Road Bike',
            $type,
            [$sport],
            new \DateTime('2026-01-01'),
            new \DateTime('2026-12-31')
        );

        $account = $this->createAccount([$equipment]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-06-15 08:00'));
        $activity->expects($this->once())
            ->method('addEquipment')
            ->with($equipment);

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }

    public function testSetEquipmentIfPossibleSkipsEquipmentWhenActivityDateIsBeforeRange(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Cycling');
        $equipment = $this->createEquipment(
            100,
            'Road Bike',
            $type,
            [$sport],
            new \DateTime('2026-06-01'),
            new \DateTime('2026-12-31')
        );

        $account = $this->createAccount([$equipment]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-01-15 08:00'));
        $activity->expects($this->never())
            ->method('addEquipment');

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }

    public function testSetEquipmentIfPossibleSkipsEquipmentWhenActivityDateIsAfterRange(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Cycling');
        $equipment = $this->createEquipment(
            100,
            'Road Bike',
            $type,
            [$sport],
            new \DateTime('2026-01-01'),
            new \DateTime('2026-06-01')
        );

        $account = $this->createAccount([$equipment]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-07-15 08:00'));
        $activity->expects($this->never())
            ->method('addEquipment');

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }

    public function testSetEquipmentIfPossibleIncludesActivityOnTheExactEndDate(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Cycling');
        $equipment = $this->createEquipment(
            100,
            'Road Bike',
            $type,
            [$sport],
            new \DateTime('2026-01-01'),
            new \DateTime('2026-06-01')
        );

        $account = $this->createAccount([$equipment]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-06-01 22:00'));
        $activity->expects($this->once())
            ->method('addEquipment')
            ->with($equipment);

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }

    public function testSetEquipmentIfPossibleSkipsCategoryWhenMultipleMatchesAreAmbiguous(): void
    {
        $command = $this->createCommand();

        $type = $this->createEquipmentType(1, 'Shoes', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Running');

        $shoesA = $this->createEquipment(100, 'Shoes A', $type, [$sport]);
        $shoesB = $this->createEquipment(101, 'Shoes B', $type, [$sport]);

        $account = $this->createAccount([$shoesA, $shoesB]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-06-01 08:00'));
        $activity->expects($this->never())
            ->method('addEquipment');

        $output = new BufferedOutput();
        $this->invokeSetEquipmentIfPossible($command, $activity, $output);

        $this->assertStringContainsString('more than one active equipment found', $output->fetch());
    }

    public function testSetEquipmentIfPossibleAddsMultipleNonAmbiguousCategoriesIndependently(): void
    {
        $command = $this->createCommand();

        $shoeType = $this->createEquipmentType(1, 'Shoes', EquipmentType::CHOICE_SINGLE);
        $bikeType = $this->createEquipmentType(2, 'Bike', EquipmentType::CHOICE_SINGLE);
        $sport = $this->createSport(10, 'Triathlon');

        $shoes = $this->createEquipment(100, 'Running Shoes', $shoeType, [$sport]);
        $bike = $this->createEquipment(200, 'Road Bike', $bikeType, [$sport]);

        $account = $this->createAccount([$shoes, $bike]);
        $this->invokeCreateEquiqmentArray($command, $account, new BufferedOutput());

        $activity = $this->createTrainingActivity(10, new \DateTime('2026-06-01 08:00'));
        $activity->expects($this->exactly(2))
            ->method('addEquipment');

        $this->invokeSetEquipmentIfPossible($command, $activity, new BufferedOutput());
    }
}
