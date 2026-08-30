<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Profile\View\DatasetPrivacyProfile;

/**
 * @ORM\Table(name="dataset", indexes={@ORM\Index(name="position", columns={"accountid", "position"})}, uniqueConstraints={@ORM\UniqueConstraint(name="unique_key", columns={"accountid", "keyid"})})
 * @ORM\Entity(repositoryClass="Runalyze\Bundle\CoreBundle\Entity\DatasetRepository")
 */
class Dataset implements AccountRelatedEntityInterface
{
    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Account
     *
     * @ORM\ManyToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Account")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     * @Orm\Id
     */
    private $account;

    /**
     * @var int
     *
     * @ORM\Column(name="keyid", type="tinyint", options={"unsigned"=true})
     * @ORM\Id
     */
    private $keyid;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;

    /**
     * @var string
     *
     * @ORM\Column(name="style", type="string", length=100, nullable=false, options={"default"=""})
     */
    private $style = '';

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="tinyint", options={"unsigned"=true})
     */
    private $position = 0;

    /**
     * @var bool
     * @see \Runalyze\Profile\View\DatasetPrivacyProfile
     *
     * @ORM\Column(name="privacy", type="boolean")
     */
    private $privacy = true;

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

    public function setKeyId(int $keyId): self
    {
        $this->keyid = $keyId;

        return $this;
    }

    /**
     * @return int
     */
    public function getKeyId()
    {
        return $this->keyid;
    }

    public function setActive(bool $flag = true): self
    {
        $this->active = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param string $style
     */
    public function setStyle($style): self
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param int $position
     */
    public function setPosition($position): self
    {
        $this->position = (int) $position;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param bool $privacy
     */
    public function setPrivacy($privacy): self
    {
        $this->privacy = (bool) $privacy;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    public function isPrivate(): bool
    {
        return (bool) DatasetPrivacyProfile::PRIVATE_KEY == $this->privacy;
    }

    public function isPublic(): bool
    {
        return (bool) DatasetPrivacyProfile::PUBLIC_KEY == $this->privacy;
    }
}
