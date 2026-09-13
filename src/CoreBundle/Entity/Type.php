<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;
use Runalyze\Profile\View\DataBrowserRowProfile;

#[ORM\Table(name: 'type')]
#[ORM\Entity(repositoryClass: TypeRepository::class)]
class Type implements IdentifiableEntityInterface, NamedEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer', options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'abbr', type: 'string', length: 5, nullable: false, options: [
        'default' => '',
    ])]
    private $abbr = '';

    /**
     * @var Sport
     */
    #[ORM\JoinColumn(name: 'sportid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Sport::class, inversedBy: 'types')]
    private $sport;

    /**
     * @var int
     *
     * @see \Runalyze\Profile\View\DataBrowserRowProfile
     */
    #[ORM\Column(name: 'short', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $displayMode = 2;

    /**
     * @var int [bpm]
     */
    #[ORM\Column(name: 'hr_avg', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $hrAvg = 100;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'quality_session', type: 'boolean')]
    private $qualitySession = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'type', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected $trainings;

    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Account
     */
    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'activityTypes')]
    private $account;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $abbr
     */
    public function setAbbr($abbr): self
    {
        $this->abbr = $abbr;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    public function setSport(Sport $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    /**
     * @return Sport
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @param int $mode see \Runalyze\Profile\View\DataBrowserRowProfile
     */
    public function setDisplayMode($mode): self
    {
        $this->displayMode = $mode;

        return $this;
    }

    /**
     * @return int see \Runalyze\Profile\View\DataBrowserRowProfile
     */
    public function getDisplayMode()
    {
        return $this->displayMode;
    }

    public function showsCompleteRow(): bool
    {
        if ($this->inheritsDisplayMode()) {
            return ! $this->sport->getShort();
        }

        return $this->displayMode == DataBrowserRowProfile::COMPLETE_ROW;
    }

    /**
     * @return bool
     */
    public function showsOnlyIcon()
    {
        if ($this->inheritsDisplayMode()) {
            return $this->sport->getShort();
        }

        return $this->displayMode == DataBrowserRowProfile::ONLY_ICON;
    }

    public function inheritsDisplayMode(): bool
    {
        return $this->displayMode == DataBrowserRowProfile::INHERIT_FROM_PARENT;
    }

    /**
     * @param int $hrAvg [bpm]
     */
    public function setHrAvg($hrAvg): self
    {
        $this->hrAvg = $hrAvg;

        return $this;
    }

    /**
     * @return int [bpm]
     */
    public function getHrAvg()
    {
        return $this->hrAvg;
    }

    /**
     * @param bool $qualitySession
     */
    public function setQualitySession($qualitySession): self
    {
        $this->qualitySession = $qualitySession;

        return $this;
    }

    /**
     * @return bool
     */
    public function getQualitySession()
    {
        return $this->qualitySession;
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

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTrainings()
    {
        return $this->trainings;
    }
}
