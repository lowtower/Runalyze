<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Validator\Constraints as RunalyzeAssert;
use Runalyze\Model\Account\UserRole;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Profile\Athlete\Gender;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'account')]
#[ORM\UniqueConstraint(name: 'username', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'mail', columns: ['mail'])]
#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[UniqueEntity('mail', message: 'This mail address is already in use')]
#[UniqueEntity('username', message: 'This username is already in use')]
class Account implements UserInterface, LegacyPasswordAuthenticatedUserInterface, EquatableInterface, IdentifiableEntityInterface
{
    #[Assert\Length(min: 8, minMessage: 'Your password must be at least {{ limit }} characters long')]
    private ?string $plainPassword = null;

    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, precision: 10, unique: true, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'username', type: \Doctrine\DBAL\Types\Types::STRING, length: 60, unique: true, nullable: false)]
    #[Assert\NotBlank(message: 'You need to enter a username.')]
    #[Assert\Length(min: 3, max: 32, minMessage: 'Your username must be at least {{ limit }} characters long', maxMessage: 'Your username cannot be longer than {{ limit }} characters')]
    #[Assert\Regex(pattern: '#[^a-zA-Z0-9\.\_\-]#i', message: 'Besides digits and letters, only the following characters are allowed: . _ -', match: false)]
    private string $username = '';

    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 50, options: [
        'default' => '',
    ])]
    private string $name = '';

    #[ORM\Column(name: 'mail', type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    #[RunalyzeAssert\ContainsDisposableMailAddress]
    private string $mail = '';

    #[ORM\Column(name: 'language', type: \Doctrine\DBAL\Types\Types::STRING, length: 5, nullable: false, options: [
        'default' => 'en',
    ])]
    #[Assert\NotBlank]
    private string $language = 'en';

    /**
     * @see Timezone
     */
    #[ORM\Column(name: 'timezone', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: false, options: [
        'unsigned' => true,
        'default' => 0,
    ])]
    #[Assert\NotBlank]
    #[Assert\Type('int')]
    #[RunalyzeAssert\IsValidTimezone]
    private int $timezone = Timezone::UTC;

    /**
     * @see Gender
     */
    #[ORM\Column(name: 'gender', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    #[Assert\Type('int')]
    private ?int $gender = Gender::NONE;

    #[ORM\Column(name: 'birthyear', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: true, options: [
        'unsigned' => true,
    ])]
    #[Assert\Type('int')]
    private ?int $birthyear = null;

    #[ORM\Column(name: 'password', type: \Doctrine\DBAL\Types\Types::STRING, length: 64, nullable: false, options: [
        'default' => '',
    ])]
    private string $password = '';

    #[ORM\Column(name: 'salt', type: \Doctrine\DBAL\Types\Types::STRING, length: 64, nullable: false, options: [
        'fixed' => true,
        'default' => '',
    ])]
    private string $salt = '';

    /**
     * @var ?int [timestamp]
     */
    #[ORM\Column(name: 'registerdate', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true, options: [
        'unsigned' => true,
    ])]
    #[Assert\Type('int')]
    private ?int $registerdate = null;

    /**
     * @var ?int [timestamp]
     */
    #[ORM\Column(name: 'lastaction', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true, options: [
        'unsigned' => true,
    ])]
    #[Assert\Type('int')]
    private ?int $lastaction = null;

    #[ORM\Column(name: 'changepw_hash', type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $changepwHash = null;

    /**
     * @var ?int [timestamp]
     */
    #[ORM\Column(name: 'changepw_timelimit', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $changepwTimelimit = null;

    #[ORM\Column(name: 'activation_hash', type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $activationHash = null;

    #[ORM\Column(name: 'deletion_hash', type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $deletionHash = null;

    #[ORM\Column(name: 'allow_mails', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    #[Assert\Type('bool')]
    private bool $allowMails = true;

    #[ORM\Column(name: 'allow_support', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    #[Assert\Type('bool')]
    private bool $allowSupport = false;

    /**
     * @var Collection<int, Sport>
     */
    #[ORM\OneToMany(targetEntity: Sport::class, mappedBy: 'account', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $sports;

    /**
     * @var Collection<int, Type>
     */
    #[ORM\OneToMany(targetEntity: Type::class, mappedBy: 'account', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $activityTypes;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'account', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy([
        'tag' => \SortDirection::Ascending,
    ])]
    protected Collection $tags;

    /**
     * @var Collection<int, Equipment>
     */
    #[ORM\OneToMany(targetEntity: Equipment::class, mappedBy: 'account', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $equipment;

    /**
     * @var Collection<int, EquipmentType>
     */
    #[ORM\OneToMany(targetEntity: EquipmentType::class, mappedBy: 'account', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $equipmentTypes;

    #[ORM\Column(name: 'role', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $role = UserRole::ROLE_USER;

    /**
     * @throws Exception if no secure random source is available
     */
    public function __construct()
    {
        $this->initializeCollections();

        $this->setRegisterdate(time());
        $this->setLastAction(time());
        $this->setNewSalt();
    }

    public function getId(): ?int
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

    public function getUserIdentifier(): string
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

    #[Assert\Type('string')]
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @see Timezone
     */
    public function setTimezone(int $enum): self
    {
        $this->timezone = $enum;

        return $this;
    }

    /**
     * @see Timezone
     */
    public function getTimezone(): int
    {
        return $this->timezone;
    }

    /**
     * @see Gender
     */
    public function setGender(int $enum): self
    {
        $this->gender = $enum;

        return $this;
    }

    /**
     * @see Gender
     */
    public function getGender(): ?int
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
     * @return ?int [years]
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
        return $this->plainPassword ?? '';
    }

    /**
     * @throws Exception if no secure random source is available
     */
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
     *
     * @throws Exception if no secure random source is available
     */
    public static function getRandomHash(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
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
     * @return ?int [timestamp]
     */
    public function getRegisterdate(): ?int
    {
        return $this->registerdate;
    }

    /**
     * @param ?int $lastAction [timestamp]
     */
    public function setLastAction(?int $lastAction = null): self
    {
        $this->lastaction = $lastAction ?? time();

        return $this;
    }

    /**
     * @return ?int [timestamp]
     */
    public function getLastAction(): ?int
    {
        return $this->lastaction;
    }

    /**
     * @throws Exception if no secure random source is available
     */
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

    public function getChangepwHash(): ?string
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

    /**
     * @throws Exception if no secure random source is available
     */
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

    /**
     * @throws Exception if no secure random source is available
     */
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

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return [UserRole::getRoleName($this->role)];
    }

    /**
     * @return Collection<int, Sport>
     */
    public function getSports(): Collection
    {
        return $this->sports;
    }

    /**
     * @return Collection<int, Type>
     */
    public function getActivityTypes(): Collection
    {
        return $this->activityTypes;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    /**
     * @return Collection<int, EquipmentType>
     */
    public function getEquipmentTypes(): Collection
    {
        return $this->equipmentTypes;
    }

    /**
     * Intentionally leaves out the plain password, so that it never ends up in the session.
     *
     * @return array{id: ?int, username: string, mail: string, password: string, salt: string, activationHash: ?string, language: string, role: ?int, allowSupport: bool}
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'mail' => $this->mail,
            'password' => $this->password,
            'salt' => $this->salt,
            'activationHash' => $this->activationHash,
            'language' => $this->language,
            'role' => $this->role,
            'allowSupport' => $this->allowSupport,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->initializeCollections();

        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? '';
        $this->mail = $data['mail'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->salt = $data['salt'] ?? '';
        $this->activationHash = $data['activationHash'] ?? null;
        $this->language = $data['language'] ?? 'en';
        $this->role = $data['role'] ?? UserRole::ROLE_USER;
        $this->allowSupport = $data['allowSupport'] ?? false;
    }

    public function isEnabled(): bool
    {
        return $this->activationHash === null || $this->activationHash === '';
    }

    private function initializeCollections(): void
    {
        $this->sports = new ArrayCollection();
        $this->activityTypes = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->equipment = new ArrayCollection();
        $this->equipmentTypes = new ArrayCollection();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self
        && $this->id === $user->getId()
        && $this->username === $user->getUsername()
        && $this->password === $user->getPassword()
        && $this->salt === $user->getSalt()
        && $this->role === $user->getRole();
    }
}
