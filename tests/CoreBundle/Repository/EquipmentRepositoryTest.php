<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class EquipmentRepositoryTest extends AbstractRepositoryTestCase
{
    protected EquipmentRepository $EquipmentRepository;

    protected TrainingRepository $TrainingRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->EquipmentRepository = $this->getEntityManager()
            ->getRepository(Equipment::class);
        $this->TrainingRepository = $this->getEntityManager()
            ->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase(): void
    {
        $this->assertEmpty($this->EquipmentRepository->findForTypeId(1, new Account()));
    }

    public function testFindForPanelScopesResultsToTheAccount(): void
    {
        $equipmentTypeId = (int) $this->getDefaultAccountsClothesType()->getId();

        $this->assertSame([], $this->EquipmentRepository->findForPanel($equipmentTypeId, $this->getEmptyAccount()));
    }

    public function testFindForPanelOrdersActiveEquipmentFirstAndThenByDistance(): void
    {
        $equipment = $this->EquipmentRepository->findForTypeId(
            (int) $this->getDefaultAccountsClothesType()->getId(),
            $this->Account
        );

        $this->assertGreaterThanOrEqual(2, count($equipment));

        foreach ($equipment as $index => $item) {
            $item->setDateEnd(new \DateTime('2024-01-01'));
            $item->setDistance((float) (count($equipment) - $index));
        }

        $equipment[0]->setDateEnd();
        $equipment[0]->setDistance(0.5);
        $this->getEntityManager()->flush();

        $expectedIds = array_map(
            static fn (Equipment $item): ?int => $item->getId(),
            [$equipment[0], ...array_slice($equipment, 1)]
        );

        $result = $this->EquipmentRepository->findForPanel(
            (int) $this->getDefaultAccountsClothesType()->getId(),
            $this->Account
        );

        $this->assertSame($expectedIds, array_map(
            static fn (Equipment $item): ?int => $item->getId(),
            $result
        ));
    }

    public function testFindForPanelHydratesImmutableEquipmentDates(): void
    {
        $equipmentTypeId = (int) $this->getDefaultAccountsClothesType()->getId();
        $equipment = $this->EquipmentRepository->findForTypeId($equipmentTypeId, $this->Account)[0];
        $equipmentId = $equipment->getId();

        $equipment->setDateStart(new DateTimeImmutable('2025-11-19'));
        $equipment->setDateEnd(new DateTimeImmutable('2026-11-19'));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $metadata = $this->getEntityManager()->getClassMetadata(Equipment::class);
        $this->assertSame(Types::DATE_IMMUTABLE, $metadata->getTypeOfField('dateStart'));
        $this->assertSame(Types::DATE_IMMUTABLE, $metadata->getTypeOfField('dateEnd'));

        $results = $this->EquipmentRepository->findForPanel($equipmentTypeId, $this->Account);
        $reloaded = array_find($results, fn ($candidate): bool => $candidate->getId() === $equipmentId);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Equipment::class, $reloaded);
        $this->assertInstanceOf(DateTimeImmutable::class, $reloaded->getDateStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $reloaded->getDateEnd());
        $this->assertSame('2025-11-19', $reloaded->getDateStart()->format('Y-m-d'));
        $this->assertSame('2026-11-19', $reloaded->getDateEnd()->format('Y-m-d'));
    }

    public function testEquipmentDatesRoundTripAsImmutableValues(): void
    {
        $equipment = $this->EquipmentRepository->findForTypeId(
            (int) $this->getDefaultAccountsClothesType()->getId(),
            $this->Account
        )[0];

        $equipmentId = $equipment->getId();
        $equipment->setDateStart(new DateTime('2024-03-29'));
        $equipment->setDateEnd(new DateTimeImmutable('2024-10-27'));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $reloaded = $this->EquipmentRepository->find($equipmentId);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Equipment::class, $reloaded);
        $this->assertInstanceOf(DateTimeImmutable::class, $reloaded->getDateStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $reloaded->getDateEnd());
        $this->assertSame('2024-03-29', $reloaded->getDateStart()->format('Y-m-d'));
        $this->assertSame('2024-10-27', $reloaded->getDateEnd()->format('Y-m-d'));
        $this->assertFalse($reloaded->isActive());
    }

    public function testEquipmentStatistics(): void
    {
        $clothes = $this->EquipmentRepository->findForTypeId($this->getDefaultAccountsClothesType()->getId(), $this->Account);

        $this->assertNotEmpty($clothes);

        $this->TrainingRepository->save(
            $this->getActivityForDefaultAccount(null, 3600, 10.0)
                ->addEquipment($clothes[0])
        );
        $this->TrainingRepository->save(
            $this->getActivityForDefaultAccount(null, 1800, 7.5)
                ->addEquipment($clothes[0])
        );
        $this->TrainingRepository->save(
            $this->getActivityForDefaultAccount(null, 3600, 12.0)
                ->addEquipment($clothes[1])
        );

        $statistics = $this->EquipmentRepository->getStatisticsForType($this->getDefaultAccountsClothesType()->getId(), $this->Account);

        $this->assertSame(2, $statistics->getCount());
        $this->assertEquals(2, $statistics->getStatistics()[0]->getNumberOfActivities());
        $this->assertEqualsWithDelta(10.0, $statistics->getStatistics()[0]->getMaximalDistance(), PHP_FLOAT_EPSILON);
        $this->assertEquals(240, $statistics->getStatistics()[0]->getMaximalPace());
        $this->assertEquals(1, $statistics->getStatistics()[1]->getNumberOfActivities());
        $this->assertEqualsWithDelta(12.0, $statistics->getStatistics()[1]->getMaximalDistance(), PHP_FLOAT_EPSILON);
        $this->assertEquals(300, $statistics->getStatistics()[1]->getMaximalPace());
    }
}
