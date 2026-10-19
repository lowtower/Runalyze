<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class EquipmentRepositoryTest extends AbstractRepositoryTestCase
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
        $this->assertEquals(10.0, $statistics->getStatistics()[0]->getMaximalDistance());
        $this->assertEquals(240, $statistics->getStatistics()[0]->getMaximalPace());
        $this->assertEquals(1, $statistics->getStatistics()[1]->getNumberOfActivities());
        $this->assertEquals(12.0, $statistics->getStatistics()[1]->getMaximalDistance());
        $this->assertEquals(300, $statistics->getStatistics()[1]->getMaximalPace());
    }
}
