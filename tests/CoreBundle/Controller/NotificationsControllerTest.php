<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class NotificationsControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    private function persistNotificationFor(Account $account): void
    {
        $notification = Notification::createFromMessage(
            new TemplateBasedMessage('../../../tests/CoreBundle/DataFixtures/messages/test-message.yml'),
            $account
        );

        $this->getEntityManager()
            ->persist($notification);
        $this->getEntityManager()
            ->flush();
    }

    public function testNewNotificationsActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/notifications');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testNewNotificationsActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/notifications');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testNewNotificationsActionPassesOnlyTheCurrentAccountsNotificationsToTheTemplate(): void
    {
        $this->persistNotificationFor($this->Account);
        $this->persistNotificationFor($this->Account);
        $this->persistNotificationFor($this->getEmptyAccount());

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/notifications');

        $this->assertStatusCode(200, $this->Client);

        $notifications = $this->getEntityManager()
            ->getRepository(Notification::class)
            ->findAllFor($this->Account);
        $this->assertCount(2, $notifications);
    }
}
