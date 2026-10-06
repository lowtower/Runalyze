<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\HrvRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class HrvRepositoryTest extends AbstractRepositoryTestCase
{
    protected HrvRepository $HrvRepository;

    protected TrainingRepository $TrainingRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->HrvRepository = $this->getEntityManager()
            ->getRepository(Hrv::class);
        $this->TrainingRepository = $this->getEntityManager()
            ->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase(): void
    {
        $this->assertNull($this->HrvRepository->findForActivity(1));
    }

    public function testFindingHrvDataForActivity(): void
    {
        $activity = $this->insertActivityWithHrv([800, 810, 795, 805]);

        $hrv = $this->HrvRepository->findForActivity($activity->getId());

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Hrv::class, $hrv);
        $this->assertSame([800, 810, 795, 805], $hrv->getData());
        $this->assertSame($activity->getId(), $hrv->getActivity()->getId());
    }

    public function testFindingHrvDataForWrongActivityReturnsNull(): void
    {
        $activity = $this->insertActivityWithHrv([800, 810]);

        $this->assertNull($this->HrvRepository->findForActivity($activity->getId() + 1));
    }

    protected function insertActivityWithHrv(array $data): Training
    {
        $activity = $this->getActivityForDefaultAccount();
        $this->TrainingRepository->save($activity);

        $hrv = new Hrv();
        $hrv->setActivity($activity);
        $hrv->setAccount($this->Account);
        $hrv->setData($data);

        $this->getEntityManager()
            ->persist($hrv);
        $this->getEntityManager()
            ->flush();

        return $activity;
    }
}
