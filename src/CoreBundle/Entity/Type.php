<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;
use Runalyze\Profile\View\DataBrowserRowProfile;

#[ORM\Table(name: 'type')]
#[ORM\Entity(repositoryClass: TypeRepository::class)]
class Type implements IdentifiableEntityInterface, NamedEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 50, nullable: false)]
    private string $name = '';

    #[ORM\Column(name: 'abbr', type: \Doctrine\DBAL\Types\Types::STRING, length: 5, nullable: false, options: [
        'default' => '',
    ])]
    private string $abbr = '';

    #[ORM\JoinColumn(name: 'sportid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'types')]
    private Sport $sport;

    /**
     * @see DataBrowserRowProfile
     */
    #[ORM\Column(name: 'short', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $displayMode = 2;

    /**
     * @var int [bpm]
     */
    #[ORM\Column(name: 'hr_avg', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $hrAvg = 100;

    #[ORM\Column(name: 'quality_session', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $qualitySession = false;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Training::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $trainings;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'activityTypes')]
    private Account $account;

    public function __construct()
    {
        $this->trainings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): self
    {
        $this->name = $name ?? '';

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAbbr(?string $abbr): self
    {
        $this->abbr = $abbr ?? '';

        return $this;
    }

    public function getAbbr(): string
    {
        return $this->abbr;
    }

    public function setSport(Sport $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }

    /**
     * @param int $mode see DataBrowserRowProfile
     */
    public function setDisplayMode(int $mode): self
    {
        $this->displayMode = $mode;

        return $this;
    }

    /**
     * @return int see DataBrowserRowProfile
     */
    public function getDisplayMode(): int
    {
        return $this->displayMode;
    }

    public function showsCompleteRow(): bool
    {
        if ($this->inheritsDisplayMode()) {
            return ! $this->sport->getShort();
        }

        return $this->displayMode === DataBrowserRowProfile::COMPLETE_ROW;
    }

    public function showsOnlyIcon(): bool
    {
        if ($this->inheritsDisplayMode()) {
            return $this->sport->getShort();
        }

        return $this->displayMode === DataBrowserRowProfile::ONLY_ICON;
    }

    public function inheritsDisplayMode(): bool
    {
        return $this->displayMode === DataBrowserRowProfile::INHERIT_FROM_PARENT;
    }

    /**
     * @param int $hrAvg [bpm]
     */
    public function setHrAvg(int $hrAvg): self
    {
        $this->hrAvg = $hrAvg;

        return $this;
    }

    /**
     * @return int [bpm]
     */
    public function getHrAvg(): int
    {
        return $this->hrAvg;
    }

    public function setQualitySession(bool $qualitySession): self
    {
        $this->qualitySession = $qualitySession;

        return $this;
    }

    public function getQualitySession(): bool
    {
        return $this->qualitySession;
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

    /**
     * @return Collection<int, Training>
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }
}
