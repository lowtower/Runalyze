<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class NotificationsControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected NotificationRepository $NotificationRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->NotificationRepository = $this->EntityManager->getRepository(Notification::class);
        $this->Account = $this->getDefaultAccount();
    }

    protected function insertMessageFor(Account $account): int
    {
        $notification = Notification::createFromMessage(
            new TemplateBasedMessage('../../../tests/CoreBundle/DataFixtures/messages/test-message.yml'),
            $account
        );
        $notification->setCreatedAt(10);

        $this->NotificationRepository->save($notification);

        return $notification->getId();
    }

    protected function reloadNotification(int $id): ?Notification
    {
        $this->EntityManager->clear();

        return $this->NotificationRepository->find($id);
    }

    public function testThatReadAllNotificationsActionMarksNotificationAsReadForCurrentAccount(): void
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClient();
        $this->loginClient($client, $this->getDefaultAccount(), 'default');
        $client->request('GET', '/_internal/notifications/read/all');

        $this->isSuccessful($client->getResponse());
        $this->assertTrue($this->reloadNotification($id)->wasRead());
    }

    public function testThatReadAllNotificationsActionDoesNotAffectNotificationsOfOtherAccounts(): void
    {
        $ownNotificationId = $this->insertMessageFor($this->Account);
        $otherAccountNotificationId = $this->insertMessageFor($this->getEmptyAccount());

        $client = $this->makeClient();
        $this->loginClient($client, $this->getDefaultAccount(), 'default');
        $client->request('GET', '/_internal/notifications/read/all');

        $this->isSuccessful($client->getResponse());
        $this->assertTrue($this->reloadNotification($ownNotificationId)->wasRead());
        $this->assertFalse($this->reloadNotification($otherAccountNotificationId)->wasRead());
    }

    public function testThatReadNotificationThrowsAccessDeniedForUnknownId(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/_internal/notifications/read/314159');

        $this->assertStatusCode(404, $client);
    }

    public function testThatReadNotificationThrowsAccessDeniedForWrongUser(): void
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClient();
        $this->loginClient($client, $this->getEmptyAccount(), 'default');
        $client->request('GET', '/_internal/notifications/read/' . $id);

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testThatReadNotificationWorksForCorrectUser(): void
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClient();
        $this->loginClient($client, $this->getDefaultAccount(), 'default');
        $client->request('GET', '/_internal/notifications/read/' . $id);

        $this->isSuccessful($client->getResponse());
    }

    public function testThatNewNotificationActionIsEmptyForEmptyAccount(): void
    {
        $this->insertMessageFor($this->Account);

        $client = $this->makeClient();
        $this->loginClient($client, $this->getEmptyAccount(), 'default');
        $client->request('GET', '/_internal/notifications');

        $this->assertEquals(
            json_encode([]),
            $client->getResponse()
                ->getContent()
        );
    }

    public function testThatNewNotificationActionReturnsCorrectTextAndLink(): void
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClient();
        $this->loginClient($client, $this->getDefaultAccount(), 'default');
        $client->request('GET', '/_internal/notifications');

        $this->assertEquals(
            json_encode([[
                'id' => $id,
                'link' => 'http://runalyze.com/',
                'text' => 'foobar',
                'size' => 'external',
                'createdAt' => 10,
            ]]),
            $client->getResponse()
                ->getContent()
        );
    }
}
