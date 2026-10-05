<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'swimdata')]
#[ORM\Entity(repositoryClass: SwimdataRepository::class)]
class Swimdata implements AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'stroke', type: 'pipe_array', nullable: true)]
    private ?array $stroke = null;

    #[ORM\Column(name: 'stroketype', type: 'pipe_array', nullable: true)]
    private ?array $stroketype = null;

    /**
     * @var int [cm]
     */
    #[ORM\Column(name: 'pool_length', type: 'smallint', precision: 5, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $poolLength = 0;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(inversedBy: 'swimdata', targetEntity: Training::class)]
    private Training $activity;

    public function setStroke(?array $stroke = null): self
    {
        $this->stroke = $stroke;

        return $this;
    }

    public function getStroke(): ?array
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

    public function getStroketype(): ?array
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
    public function setPoolLength(int $poolLength): self
    {
        $this->poolLength = $poolLength;

        return $this;
    }

    /**
     * @return int [cm]
     */
    public function getPoolLength(): int
    {
        return $this->poolLength;
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

    public function isEmpty(): bool
    {
        return
            ($this->stroke === null || empty($this->stroke)) &&
            ($this->stroketype === null || empty($this->stroketype))
        ;
    }
}
