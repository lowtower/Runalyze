<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Profile\View\DatasetPrivacyProfile;

#[ORM\Table(name: 'dataset')]
#[ORM\Index(columns: ['accountid', 'position'], name: 'position')]
#[ORM\UniqueConstraint(name: 'unique_key', columns: ['accountid', 'keyid'])]
#[ORM\Entity(repositoryClass: DatasetRepository::class)]
class Dataset implements AccountRelatedEntityInterface
{
    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[Orm\Id]
    private Account $account;

    #[ORM\Column(name: 'keyid', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    private int $keyid;

    #[ORM\Column(name: 'active', type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(name: 'style', type: 'string', length: 100, nullable: false, options: [
        'default' => '',
    ])]
    private string $style = '';

    #[ORM\Column(name: 'position', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $position = 0;

    /**
     * @see DatasetPrivacyProfile
     */
    #[ORM\Column(name: 'privacy', type: 'boolean')]
    private bool $privacy = true;

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setKeyId(int $keyId): self
    {
        $this->keyid = $keyId;

        return $this;
    }

    public function getKeyId(): int
    {
        return $this->keyid;
    }

    public function setActive(bool $flag = true): self
    {
        $this->active = $flag;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setStyle(string $style): self
    {
        $this->style = $style;

        return $this;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPrivacy(bool $privacy): self
    {
        $this->privacy = $privacy;

        return $this;
    }

    public function getPrivacy(): bool
    {
        return $this->privacy;
    }

    public function isPrivate(): bool
    {
        return (bool) DatasetPrivacyProfile::PRIVATE_KEY === $this->privacy;
    }

    public function isPublic(): bool
    {
        return (bool) DatasetPrivacyProfile::PUBLIC_KEY === $this->privacy;
    }
}
