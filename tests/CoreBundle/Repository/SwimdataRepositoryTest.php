<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\SwimdataRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class SwimdataRepositoryTest extends AbstractRepositoryTestCase
{
    protected SwimdataRepository $SwimdataRepository;

    protected TrainingRepository $TrainingRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->SwimdataRepository = $this->getEntityManager()
            ->getRepository(Swimdata::class);
        $this->TrainingRepository = $this->getEntityManager()
            ->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase(): void
    {
        $this->assertNull($this->SwimdataRepository->findForActivity(1));
    }

    public function testFindingSwimdataForActivity(): void
    {
        $activity = $this->insertActivityWithSwimdata([1, 2, 1, 2], [1, 1, 2, 2], 50);

        $swimdata = $this->SwimdataRepository->findForActivity($activity->getId());

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Swimdata::class, $swimdata);
        $this->assertSame([1, 2, 1, 2], $swimdata->getStroke());
        $this->assertSame([1, 1, 2, 2], $swimdata->getStroketype());
        $this->assertSame(50, $swimdata->getPoolLength());
        $this->assertSame($activity->getId(), $swimdata->getActivity()->getId());
    }

    public function testFindingSwimdataForWrongActivityReturnsNull(): void
    {
        $activity = $this->insertActivityWithSwimdata([1, 2], [1, 2], 25);

        $this->assertNull($this->SwimdataRepository->findForActivity($activity->getId() + 1));
    }

    protected function insertActivityWithSwimdata(array $stroke, array $strokeType, int $poolLength): Training
    {
        $activity = $this->getActivityForDefaultAccount();
        $this->TrainingRepository->save($activity);

        $swimdata = new Swimdata();
        $swimdata->setActivity($activity);
        $swimdata->setAccount($this->Account);
        $swimdata->setStroke($stroke);
        $swimdata->setStroketype($strokeType);
        $swimdata->setPoolLength($poolLength);

        $this->getEntityManager()
            ->persist($swimdata);
        $this->getEntityManager()
            ->flush();

        return $activity;
    }
}
