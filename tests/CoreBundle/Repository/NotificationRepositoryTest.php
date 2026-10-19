<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class NotificationRepositoryTest extends AbstractRepositoryTestCase
{
    protected NotificationRepository $NotificationRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->NotificationRepository = $this->getEntityManager()
            ->getRepository(Notification::class);
        $this->Account = $this->getDefaultAccount();
    }

    /**
     * @param ?int $createdAt [timestamp]
     * @param ?int $expirationAt [timestamp]
     */
    protected function insertMessageFor(Account $account, ?int $createdAt = null, ?int $expirationAt = null): int
    {
        $notification = Notification::createFromMessage(
            new TemplateBasedMessage('../../../tests/CoreBundle/DataFixtures/messages/test-message.yml'),
            $account
        );

        if ($createdAt !== null) {
            $notification->setCreatedAt($createdAt);
            $notification->setExpirationAt($expirationAt);
        }

        $this->NotificationRepository->save($notification);

        return $notification->getId();
    }

    public function testFindAllForNotifications(): void
    {
        $this->insertMessageFor($this->Account);
        $this->insertMessageFor($this->getEmptyAccount());
        $this->insertMessageFor($this->Account);
        $this->insertMessageFor($this->Account);

        $this->assertCount(3, $this->NotificationRepository->findAllFor($this->Account));
        $this->assertCount(1, $this->NotificationRepository->findAllFor($this->getEmptyAccount()));
    }

    public function testFindAllReturnsNotificationsAcrossAllAccounts(): void
    {
        $this->insertMessageFor($this->Account);
        $this->insertMessageFor($this->getEmptyAccount());
        $this->insertMessageFor($this->Account);

        $this->assertCount(3, $this->NotificationRepository->findAll());
    }

    public function testFindAllNotificationsSince(): void
    {
        $this->insertMessageFor($this->Account, 10);
        $this->insertMessageFor($this->Account, 20);
        $this->insertMessageFor($this->Account, 30);

        $this->assertCount(3, $this->NotificationRepository->findAllSince(5, $this->Account));
        $this->assertCount(2, $this->NotificationRepository->findAllSince(15, $this->Account));
        $this->assertCount(1, $this->NotificationRepository->findAllSince(25, $this->Account));
        $this->assertCount(0, $this->NotificationRepository->findAllSince(35, $this->Account));
    }

    public function testMarkSingleNotificationAsRead(): void
    {
        $idRead = $this->insertMessageFor($this->Account);
        $idNotRead = $this->insertMessageFor($this->Account);

        $this->NotificationRepository->markAsRead($this->NotificationRepository->find($idRead));

        $this->assertTrue($this->NotificationRepository->find($idRead)->wasRead());
        $this->assertFalse($this->NotificationRepository->find($idNotRead)->wasRead());
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $idFirst = $this->insertMessageFor($this->Account);
        $idSecond = $this->insertMessageFor($this->Account);
        $idOtherAccount = $this->insertMessageFor($this->getEmptyAccount());

        $this->NotificationRepository->markAllAsRead($this->Account);

        $this->assertTrue($this->NotificationRepository->find($idFirst)->wasRead());
        $this->assertTrue($this->NotificationRepository->find($idSecond)->wasRead());
        $this->assertFalse($this->NotificationRepository->find($idOtherAccount)->wasRead());
    }

    public function testRemovingExpiredNotifications(): void
    {
        $idFirst = $this->insertMessageFor($this->Account, 0, time() - 3600);
        $idSecond = $this->insertMessageFor($this->Account, 0, time() - 3600);
        $idRecent = $this->insertMessageFor($this->Account, 0, time() + 3600);
        $idOtherAccount = $this->insertMessageFor($this->getEmptyAccount(), 0, time() - 3600);

        $this->assertSame(3, $this->NotificationRepository->removeExpiredNotifications());

        $this->assertNull($this->NotificationRepository->find($idFirst));
        $this->assertNull($this->NotificationRepository->find($idSecond));
        $this->assertInstanceOf(Notification::class, $this->NotificationRepository->find($idRecent));
        $this->assertNull($this->NotificationRepository->find($idOtherAccount));
    }
}
