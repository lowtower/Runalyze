<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'hrv')]
#[ORM\Entity(repositoryClass: HrvRepository::class)]
class Hrv implements AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'data', type: 'pipe_array', nullable: true)]
    private ?array $data = null;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(inversedBy: 'hrv', targetEntity: Training::class)]
    private Training $activity;

    public function setData(?array $data = null): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account ?? null;
    }

    public function setActivity(Training $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getActivity(): ?Training
    {
        return $this->activity ?? null;
    }
}
