<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'user')]
#[ORM\Index(name: 'accountid_time', columns: ['accountid', 'time'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\EntityListeners([\Runalyze\Bundle\CoreBundle\EntityListener\UserListener::class])]
class User implements IdentifiableEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'time', type: 'integer', precision: 11, nullable: false, options: [
        'unsigned' => true,
    ])]
    private $time;

    /**
     * @var float|null [kg]
     */
    #[ORM\Column(name: 'weight', type: 'casted_decimal_2', precision: 5, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private $weight;

    /**
     * @var int|null [bpm]
     */
    #[ORM\Column(name: 'pulse_rest', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $pulseRest;

    /**
     * @var int|null [bpm]
     */
    #[ORM\Column(name: 'pulse_max', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $pulseMax;

    /**
     * @var float|null [%]
     * @Assert\Range(
     *     min=0,
     *     max=100)
     */
    #[ORM\Column(name: 'fat', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true)]
    private $fat;

    /**
     * @var float|null [%]
     * @Assert\Range(
     *     min=0,
     *     max=100)
     */
    #[ORM\Column(name: 'water', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true)]
    private $water;

    /**
     * @var float|null [%]
     *
     * @Assert\Range(
     *     min=0,
     *     max=100)
     */
    #[ORM\Column(name: 'muscles', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true)]
    private $muscles;

    /**
     * @var int|null [min]
     */
    #[ORM\Column(name: 'sleep_duration', type: 'smallint', precision: 3, nullable: true, options: [
        'unsigned' => true,
    ])]
    private $sleepDuration;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'notes', type: 'text', length: 65535, nullable: true)]
    private $notes;

    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Account
     */
    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private $account;

    public function __clone()
    {
        $this->id = null;
        $this->notes = '';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $time
     */
    public function setTime($time): self
    {
        $this->time = $time;

        return $this;
    }

    public function setCurrentTimestamp(): self
    {
        $this->time = time();

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param float|null $weight [kg]
     */
    public function setWeight($weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return float|null [kg]
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int|null $pulseRest [bpm]
     */
    public function setPulseRest($pulseRest): self
    {
        $this->pulseRest = $pulseRest;

        return $this;
    }

    /**
     * @return int|null [bpm]
     */
    public function getPulseRest()
    {
        return $this->pulseRest;
    }

    /**
     * @param int|null $pulseMax [bpm]
     */
    public function setPulseMax($pulseMax): self
    {
        $this->pulseMax = $pulseMax;

        return $this;
    }

    /**
     * @return int|null [bpm]
     */
    public function getPulseMax()
    {
        return $this->pulseMax;
    }

    /**
     * @param float|null $fat [%]
     */
    public function setFat($fat): self
    {
        $this->fat = $fat;

        return $this;
    }

    /**
     * @return float|null [%]
     */
    public function getFat()
    {
        return $this->fat;
    }

    /**
     * @param float|null $water [%]
     */
    public function setWater($water): self
    {
        $this->water = $water;

        return $this;
    }

    /**
     * @return float|null [%]
     */
    public function getWater()
    {
        return $this->water;
    }

    /**
     * @param float|null $muscles [%]
     */
    public function setMuscles($muscles): self
    {
        $this->muscles = $muscles;

        return $this;
    }

    /**
     * @return float|null [%]
     */
    public function getMuscles()
    {
        return $this->muscles;
    }

    /**
     * @param int|null $sleepDuration [min]
     */
    public function setSleepDuration($sleepDuration): self
    {
        $this->sleepDuration = $sleepDuration;

        return $this;
    }

    /**
     * @return int|null [min]
     */
    public function getSleepDuration()
    {
        return $this->sleepDuration;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes($notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
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

    public function cloneObjectForForm(): self
    {
        return (new self())
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
