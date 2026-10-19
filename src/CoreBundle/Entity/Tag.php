<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;

#[ORM\Table(name: 'tag')]
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag implements AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'tag', type: \Doctrine\DBAL\Types\Types::STRING, length: 50, nullable: false)]
    private string $tag = '';

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'tags')]
    private Account $account;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\ManyToMany(targetEntity: Training::class, mappedBy: 'tag')]
    private Collection $activity;

    public function __construct()
    {
        $this->activity = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTag(?string $tag): self
    {
        $this->tag = $tag ?? '';

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
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
