<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'plugin')]
#[ORM\Entity(repositoryClass: PluginRepository::class)]
class Plugin implements AccountRelatedEntityInterface
{
    /**
     * @var int
     */
    public const STATE_INACTIVE = 0;

    /**
     * @var int
     */
    public const STATE_ACTIVE = 1;

    /**
     * @var int
     */
    public const STATE_HIDDEN = 2;

    /**
     * @var string
     */
    public const TYPE_STAT = 'stat';

    /**
     * @var string
     */
    public const TYPE_PANEL = 'panel';

    #[ORM\Column(name: 'id', type: 'integer', options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(name: '`key`', type: 'string', length: 100, nullable: false)]
    private $key;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 5, nullable: false, options: [
        'default' => 'stat',
    ])]
    private $type = 'stat';

    /**
     * @var int 0: inactive, 1: active, 2: hidden/misc
     */
    #[ORM\Column(name: 'active', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $active = 1;

    /**
     * @var int
     */
    #[ORM\Column(name: '`order`', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $order = 0;

    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Account
     */
    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private $account;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $key
     */
    public function setKey($key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function isStatistic(): bool
    {
        return $this->type == self::TYPE_STAT;
    }

    public function isPanel(): bool
    {
        return $this->type == self::TYPE_PANEL;
    }

    /**
     * @param int $active 0: inactive, 1: active, 2: hidden/misc
     */
    public function setActive($active): self
    {
        $this->active = $active;

        return $this;
    }

    public function toggleHidden(): self
    {
        if ($this->isActive()) {
            $this->setActive(self::STATE_HIDDEN);
        } elseif ($this->isHidden()) {
            $this->setActive(self::STATE_ACTIVE);
        }

        return $this;
    }

    /**
     * @return int 0: inactive, 1: active, 2: hidden/misc
     */
    public function getActive()
    {
        return $this->active;
    }

    public function isInactive(): bool
    {
        return $this->active == self::STATE_INACTIVE;
    }

    public function isActive(): bool
    {
        return $this->active == self::STATE_ACTIVE;
    }

    public function isHidden(): bool
    {
        return $this->active == self::STATE_HIDDEN;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    public function moveUp(): self
    {
        --$this->order;

        return $this;
    }

    public function moveDown(): self
    {
        ++$this->order;

        return $this;
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
}
