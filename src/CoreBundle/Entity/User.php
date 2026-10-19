<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'user')]
#[ORM\Index(name: 'accountid_time', columns: ['accountid', 'time'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\EntityListeners([\Runalyze\Bundle\CoreBundle\EntityListener\UserListener::class])]
class User implements IdentifiableEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'time', type: \Doctrine\DBAL\Types\Types::INTEGER, precision: 11, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $time;

    /**
     * @var ?float [kg]
     */
    #[ORM\Column(name: 'weight', type: 'casted_decimal_2', precision: 5, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $weight = null;

    /**
     * @var ?int [bpm]
     */
    #[ORM\Column(name: 'pulse_rest', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pulseRest = null;

    /**
     * @var ?int [bpm]
     */
    #[ORM\Column(name: 'pulse_max', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pulseMax = null;

    /**
     * @var ?float [%]
     */
    #[ORM\Column(name: 'fat', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $fat = null;

    /**
     * @var ?float [%]
     */
    #[ORM\Column(name: 'water', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $water = null;

    /**
     * @var ?float [%]
     */
    #[ORM\Column(name: 'muscles', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $muscles = null;

    /**
     * @var ?int [s]
     */
    #[ORM\Column(name: 'sleep_duration', type: \Doctrine\DBAL\Types\Types::SMALLINT, precision: 3, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $sleepDuration = null;

    #[ORM\Column(name: 'notes', type: \Doctrine\DBAL\Types\Types::TEXT, length: 65535, nullable: true)]
    private ?string $notes = null;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    public function __clone()
    {
        $this->id = null;
        $this->notes = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function setCurrentTimestamp(): self
    {
        $this->time = time();

        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param ?float $weight [kg]
     */
    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return ?float [kg]
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param ?int $pulseRest [bpm]
     */
    public function setPulseRest(?int $pulseRest): self
    {
        $this->pulseRest = $pulseRest;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getPulseRest(): ?int
    {
        return $this->pulseRest;
    }

    /**
     * @param ?int $pulseMax [bpm]
     */
    public function setPulseMax(?int $pulseMax): self
    {
        $this->pulseMax = $pulseMax;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getPulseMax(): ?int
    {
        return $this->pulseMax;
    }

    /**
     * @param ?float $fat [%]
     */
    public function setFat(?float $fat): self
    {
        $this->fat = $fat;

        return $this;
    }

    /**
     * @return ?float [%]
     */
    public function getFat(): ?float
    {
        return $this->fat;
    }

    /**
     * @param ?float $water [%]
     */
    public function setWater(?float $water): self
    {
        $this->water = $water;

        return $this;
    }

    /**
     * @return ?float [%]
     */
    public function getWater(): ?float
    {
        return $this->water;
    }

    /**
     * @param ?float $muscles [%]
     */
    public function setMuscles(?float $muscles): self
    {
        $this->muscles = $muscles;

        return $this;
    }

    /**
     * @return ?float [%]
     */
    public function getMuscles(): ?float
    {
        return $this->muscles;
    }

    /**
     * @param ?int $sleepDuration [min]
     */
    public function setSleepDuration(?int $sleepDuration): self
    {
        $this->sleepDuration = $sleepDuration;

        return $this;
    }

    /**
     * @return ?int [min]
     */
    public function getSleepDuration(): ?int
    {
        return $this->sleepDuration;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function cloneObjectForForm(): self
    {
        return new self()
            ->setAccount($this->account)
            ->setCurrentTimestamp()
            ->setPulseMax($this->pulseMax)
            ->setPulseRest($this->pulseRest)
            ->setWeight($this->weight)
            ->setMuscles($this->muscles)
            ->setFat($this->fat)
            ->setWater($this->water);
    }
}
