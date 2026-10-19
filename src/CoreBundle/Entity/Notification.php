<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\MessageInterface;
use Runalyze\Bundle\CoreBundle\Component\Notifications\MessageFactory;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;

#[ORM\Table(name: 'notification')]
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification implements AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @see \Runalyze\Profile\Notifications\MessageTypeProfile
     */
    #[ORM\Column(name: 'messageType', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $messageType;

    /**
     * @var int [timestamp]
     */
    #[ORM\Column(name: 'createdAt', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $createdAt;

    /**
     * @var ?int [timestamp]
     */
    #[ORM\Column(name: 'expirationAt', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $expirationAt = null;

    #[ORM\Column(name: 'data', type: \Doctrine\DBAL\Types\Types::TEXT, length: 255)]
    private string $data;

    #[ORM\Column(name: 'wasRead', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $wasRead = false;

    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    public function __construct()
    {
        $this->createdAt = time();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @see \Runalyze\Profile\Notifications\MessageTypeProfile
     */
    public function setMessageType(int $messageType): self
    {
        $this->messageType = $messageType;

        return $this;
    }

    /**
     * @see \Runalyze\Profile\Notifications\MessageTypeProfile
     */
    public function getMessageType(): int
    {
        return $this->messageType;
    }

    /**
     * @param int $createdAt [timestamp]
     */
    public function setCreatedAt(int $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return int [timestamp]
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * @param ?int $lifetime [days]
     */
    public function setLifetime(?int $lifetime = null): self
    {
        if ($lifetime === null) {
            $this->expirationAt = null;
        } else {
            $this->expirationAt = new DateTime()->setTimestamp($this->createdAt)
                ->modify('+' . $lifetime . ' days')->getTimestamp();
        }

        return $this;
    }

    /**
     * @param ?int $expirationAt [timestamp]
     */
    public function setExpirationAt(?int $expirationAt = null): self
    {
        $this->expirationAt = $expirationAt;

        return $this;
    }

    /**
     * @return ?int [timestamp]
     */
    public function getExpirationAt(): ?int
    {
        return $this->expirationAt;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setRead(bool $flag = true): self
    {
        $this->wasRead = $flag;

        return $this;
    }

    public function wasRead(): bool
    {
        return $this->wasRead;
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

    public function getMessage(): MessageInterface
    {
        return new MessageFactory()->getMessage($this);
    }

    public static function createFromMessage(MessageInterface $message, Account $account): self
    {
        $notification = new self();
        $notification->setMessageType($message->getMessageType());
        $notification->setLifetime($message->getLifetime());
        $notification->setData($message->getData());
        $notification->setAccount($account);

        return $notification;
    }
}
