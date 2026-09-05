<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Validator\Constraints as RunalyzeAssert;
use Runalyze\Model\Account\UserRole;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Profile\Athlete\Gender;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="account", uniqueConstraints={@ORM\UniqueConstraint(name="username", columns={"username"}), @ORM\UniqueConstraint(name="mail", columns={"mail"})})
 * @ORM\Entity(repositoryClass="Runalyze\Bundle\CoreBundle\Entity\AccountRepository")
 * @UniqueEntity("mail", message="This mail address is already in use")
 * @UniqueEntity("username", message="This username is already in use")
 */
class Account implements UserInterface, \Serializable, IdentifiableEntityInterface
{
    /**
     * @Assert\Length(
     *     min=8,
     *     minMessage="Your password must be at least {{ limit }} characters long"
     * )
     */
    private string $plainPassword;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer", precision=10, unique=true, nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Assert\NotBlank(message="You need to enter a username.")
     * @Assert\Length(
     *     min=3,
     *     max=32,
     *     minMessage="Your username must be at least {{ limit }} characters long",
     *     maxMessage="Your username cannot be longer than {{ limit }} characters")
     * @Assert\Regex(
     *     pattern="#[^a-zA-Z0-9\.\_\-]#i",
     *     match=false,
     *     message="Besides digits and letters, only the following characters are allowed: . _ -"
     * )
     * @ORM\Column(name="username", type="string", length=60, nullable=false, unique=true)
     */
    private string $username = '';

    /**
     * @ORM\Column(name="name", type="string", length=50, options={"default"=""})
     */
    private string $name = '';

    /**
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message="The email '{{ value }}' is not a valid email.",
     * )
     * @RunalyzeAssert\ContainsDisposableMailAddress()
     * @ORM\Column(name="mail", type="string", length=100, nullable=false)
     */
    private string $mail;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="language", type="string", length=5, nullable=false, options={"default"="en"})
     */
    private string $language = 'en';

    /**
     * @var int enum
     * @Assert\NotBlank()
     * @Assert\Type("int")
     * @RunalyzeAssert\IsValidTimezone()
     * @ORM\Column(name="timezone", type="smallint", nullable=false, options={"unsigned"=true, "default"=0})
     */
    private int $timezone = Timezone::UTC;

    /**
     * @var int enum, see \Runalyze\Profile\Athlete\Gender
     * @Assert\Type("int")
     * @ORM\Column(name="gender", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private ?int $gender = Gender::NONE;

    /**
     * @Assert\Type("int")
     * @ORM\Column(name="birthyear", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private ?int $birthyear = null;

    /**
     * @ORM\Column(name="password", type="string", length=64, nullable=false, options={"default"=""})
     */
    private string $password = '';

    /**
     * @ORM\Column(name="salt", type="string", length=64, nullable=false, options={"fixed"=true, "default"=""})
     */
    private string $salt = '';

    /**
     * @var int|null [timestamp]
     * @Assert\Type("int")
     * @ORM\Column(name="registerdate", type="integer", nullable=true, options={"unsigned"=true})
     */
    private ?int $registerdate = null;

    /**
     * @var int|null [timestmap]
     * @Assert\Type("int")
     * @ORM\Column(name="lastaction", type="integer", nullable=true, options={"unsigned"=true})
     */
    private ?int $lastaction = null;

    /**
     * @ORM\Column(name="changepw_hash", type="string", length=32, nullable=true, options={"fixed"=true})
     */
    private ?string $changepwHash = null;

    /**
     * @var int|null [timestamp]
     *
     * @ORM\Column(name="changepw_timelimit", type="integer", nullable=true, options={"unsigned"=true})
     */
    private ?int $changepwTimelimit = null;

    /**
     * @ORM\Column(name="activation_hash", type="string", length=32, nullable=true, options={"fixed"=true})
     */
    private ?string $activationHash = null;

    /**
     * @ORM\Column(name="deletion_hash", type="string", length=32, nullable=true, options={"fixed"=true})
     */
    private ?string $deletionHash = null;

    /**
     * @Assert\Type("bool")
     * @ORM\Column(name="allow_mails", type="boolean")
     */
    private bool $allowMails = true;

    /**
     * @Assert\Type("bool")
     * @ORM\Column(name="allow_support", type="boolean")
     */
    private bool $allowSupport = false;

    /**
     * @ORM\OneToMany(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Sport", mappedBy="account", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $sports;

    /**
     * @ORM\OneToMany(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Type", mappedBy="account", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $activityTypes;

    /**
     * @ORM\OneToMany(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Tag", mappedBy="account", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"tag"="ASC"})
     */
    protected Collection $tags;

    /**
     * @ORM\OneToMany(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Equipment", mappedBy="account", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $equipment;

    /**
     * @ORM\OneToMany(targetEntity="Runalyze\Bundle\CoreBundle\Entity\EquipmentType", mappedBy="account", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $equipmentTypes;

    /**
     * @ORM\Column(name="role", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private ?int $role = UserRole::ROLE_USER;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->equipment = new ArrayCollection();
        $this->equipmentTypes = new ArrayCollection();

        $this->setRegisterdate(time());
        $this->setLastAction(time());
        $this->setNewSalt();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setMail(string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getMail(): string
    {
        return $this->mail;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @Assert\Type("string")
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param int $enum enum
     */
    public function setTimezone(int $enum): self
    {
        $this->timezone = $enum;

        return $this;
    }

    /**
     * @return int enum
     */
    public function getTimezone(): int
    {
        return $this->timezone;
    }

    /**
     * @param int $enum enum, see \Runalyze\Profile\Athlete\Gender
     */
    public function setGender(int $enum): self
    {
        $this->gender = $enum;

        return $this;
    }

    /**
     * @return int enum, see \Runalyze\Profile\Athlete\Gender
     */
    public function getGender(): int
    {
        return $this->gender;
    }

    public function knowsGender(): bool
    {
        return $this->gender !== Gender::NONE;
    }

    public function isMale(): bool
    {
        return $this->gender == Gender::MALE;
    }

    public function isFemale(): bool
    {
        return $this->gender == Gender::FEMALE;
    }

    public function setBirthyear(?int $birthYear): self
    {
        $this->birthyear = $birthYear;

        return $this;
    }

    public function getBirthyear(): ?int
    {
        return $this->birthyear;
    }

    public function knowsBirthYear(): bool
    {
        return $this->birthyear !== null;
    }

    /**
     * @return int|null [years]
     */
    public function getAge(): ?int
    {
        return $this->knowsBirthYear() ? (int) date('Y') - $this->birthyear : null;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setNewSalt(): self
    {
        return $this->setSalt(self::getRandomHash(32));
    }

    public function setSalt(string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @return string hash of length 2*$bytes
     */
    public static function getRandomHash(int $bytes = 16): string
    {
        return bin2hex(openssl_random_pseudo_bytes($bytes));
    }

    /**
     * @param int $registerDate [timestamp]
     */
    public function setRegisterdate(int $registerDate): self
    {
        $this->registerdate = $registerDate;

        return $this;
    }

    /**
     * @return int [timestamp]
     */
    public function getRegisterdate(): int
    {
        return $this->registerdate;
    }

    /**
     * @param int|null $lastAction [timestamp]
     */
    public function setLastAction(?int $lastAction = null): self
    {
        if ($lastAction === null) {
            $lastAction = (new \DateTime())->getTimestamp();
        }

        $this->lastaction = $lastAction;

        return $this;
    }

    /**
     * @return int [timestamp]
     */
    public function getLastAction(): ?int
    {
        return $this->lastaction;
    }

    public function setNewChangePasswordHash(): self
    {
        $this->setChangepwHash(self::getRandomHash(16));
        $this->setChangepwTimelimit(time() + 86400);

        return $this;
    }

    public function removeChangePasswordHash(): self
    {
        $this->setChangepwHash(null);
        $this->setChangepwTimelimit(null);

        return $this;
    }

    public function setChangepwHash(?string $changepwHash): self
    {
        $this->changepwHash = $changepwHash;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getChangepwHash()
    {
        return $this->changepwHash;
    }

    public function setChangepwTimelimit(?int $changepwTimelimit): self
    {
        $this->changepwTimelimit = $changepwTimelimit;

        return $this;
    }

    public function getChangepwTimelimit(): ?int
    {
        return $this->changepwTimelimit;
    }

    public function setNewActivationHash(): self
    {
        return $this->setActivationHash(self::getRandomHash(16));
    }

    public function removeActivationHash(): self
    {
        $this->setActivationHash(null);

        return $this;
    }

    public function setActivationHash(?string $activationHash): self
    {
        $this->activationHash = $activationHash;

        return $this;
    }

    public function getActivationHash(): ?string
    {
        return $this->activationHash;
    }

    public function setNewDeletionHash(): self
    {
        return $this->setDeletionHash(self::getRandomHash(16));
    }

    public function setDeletionHash(?string $deletionHash): self
    {
        $this->deletionHash = $deletionHash;

        return $this;
    }

    public function getDeletionHash(): ?string
    {
        return $this->deletionHash;
    }

    public function setAllowMails(bool $flag): self
    {
        $this->allowMails = $flag;

        return $this;
    }

    public function getAllowMails(): bool
    {
        return $this->allowMails;
    }

    public function setAllowSupport(bool $flag): self
    {
        $this->allowSupport = $flag;

        return $this;
    }

    public function getAllowSupport(): bool
    {
        return $this->allowSupport;
    }

    public function setRole(int $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?int
    {
        return $this->role;
    }

    public function eraseCredentials()
    {
        $this->setPlainPassword('');
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return [UserRole::getRoleName($this->role)];
    }

    public function getSports(): Collection
    {
        return $this->sports;
    }

    /**
     * Get activity/sport types
     */
    public function getActivityTypes(): Collection
    {
        return $this->activityTypes;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    public function getEquipmentTypes(): Collection
    {
        return $this->equipmentTypes;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            $this->salt,
            $this->activationHash,
            $this->language,
        ]);
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        [
            $this->id,
            $this->username,
            $this->password,
            $this->salt,
            $this->activationHash,
            $this->language
        ] = unserialize($serialized);
    }

    public function isEnabled(): bool
    {
        return empty($this->getActivationHash());
    }
}
