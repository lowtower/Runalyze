<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'swimdata')]
#[ORM\Entity(repositoryClass: SwimdataRepository::class)]
class Swimdata implements AccountRelatedEntityInterface
{
    /**
     * @var array|null
     */
    #[ORM\Column(name: 'stroke', type: 'pipe_array', nullable: true)]
    private $stroke;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'stroketype', type: 'pipe_array', nullable: true)]
    private $stroketype;

    /**
     * @var int [cm]
     */
    #[ORM\Column(name: 'pool_length', type: 'smallint', precision: 5, nullable: false, options: [
        'unsigned' => true,
    ])]
    private $poolLength = 0;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private ?Account $account = null;

    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(inversedBy: 'swimdata', targetEntity: Training::class)]
    private ?Training $activity = null;

    public function setStroke(?array $stroke = null): self
    {
        $this->stroke = $stroke;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getStroke()
    {
        return $this->stroke;
    }

    public function hasStrokes(): bool
    {
        return $this->stroke !== null;
    }

    public function setStroketype(?array $strokeType = null): self
    {
        $this->stroketype = $strokeType;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getStroketype()
    {
        return $this->stroketype;
    }

    public function hasStrokeTypes(): bool
    {
        return $this->stroketype !== null;
    }

    /**
     * @param int $poolLength [cm]
     */
    public function setPoolLength($poolLength): self
    {
        $this->poolLength = $poolLength;

        return $this;
    }

    /**
     * @return int [cm]
     */
    public function getPoolLength()
    {
        return $this->poolLength;
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

    public function isEmpty(): bool
    {
        return
            ($this->stroke === null || empty($this->stroke)) &&
            ($this->stroketype === null || empty($this->stroketype))
        ;
    }
}
