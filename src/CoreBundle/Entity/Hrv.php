<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'hrv')]
#[ORM\Entity(repositoryClass: HrvRepository::class)]
class Hrv implements AccountRelatedEntityInterface
{
    /**
     * @var array|null
     */
    #[ORM\Column(name: 'data', type: 'pipe_array', nullable: true)]
    private $data;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private ?Account $account = null;

    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(inversedBy: 'hrv', targetEntity: Training::class)]
    private ?Training $activity = null;

    /**
     * @param array|null $data
     */
    public function setData($data = null): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    public function setActivity(Training $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return Training
     */
    public function getActivity()
    {
        return $this->activity;
    }
}
