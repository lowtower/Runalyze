<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'conf')]
#[ORM\Index(columns: ['accountid'], name: 'accountid')]
#[ORM\Entity(repositoryClass: ConfRepository::class)]
class Conf implements AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer', options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'category', type: 'string', length: 32, nullable: false)]
    private string $category;

    #[ORM\Column(name: '`key`', type: 'string', length: 100, nullable: false)]
    private string $key;

    #[ORM\Column(name: 'value', type: 'string', length: 255, nullable: false)]
    private string $value;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
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

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
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
