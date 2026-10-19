<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;

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

    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: '`key`', type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: false)]
    private string $key;

    #[ORM\Column(name: 'type', type: \Doctrine\DBAL\Types\Types::STRING, length: 5, nullable: false, options: [
        'default' => 'stat',
    ])]
    private string $type = 'stat';

    /**
     * @var int 0: inactive, 1: active, 2: hidden/misc
     */
    #[ORM\Column(name: 'active', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $active = 1;

    #[ORM\Column(name: '`order`', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $order = 0;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isStatistic(): bool
    {
        return $this->type === self::TYPE_STAT;
    }

    public function isPanel(): bool
    {
        return $this->type === self::TYPE_PANEL;
    }

    /**
     * @param int $active 0: inactive, 1: active, 2: hidden/misc
     */
    public function setActive(int $active): self
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
    public function getActive(): int
    {
        return $this->active;
    }

    public function isInactive(): bool
    {
        return $this->active === self::STATE_INACTIVE;
    }

    public function isActive(): bool
    {
        return $this->active === self::STATE_ACTIVE;
    }

    public function isHidden(): bool
    {
        return $this->active === self::STATE_HIDDEN;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): int
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

    public function getAccount(): Account
    {
        return $this->account;
    }
}
