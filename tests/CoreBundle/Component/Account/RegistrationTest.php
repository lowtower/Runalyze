<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Account;

use Doctrine\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Client;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class RegistrationTest extends WebTestCase
{
    protected Client $Client;

    protected ObjectManager $EntityManager;

    protected AccountRepository $AccountRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class);
        $databaseTool->get()
            ->loadFixtures([])->getReferenceRepository();

        $this->Client = $this->getContainer()
            ->get('test.client');
        $this->Client->disableReboot();

        $this->EntityManager = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->EntityManager->clear();

        /** @var AccountRepository $accountRepository */
        $accountRepository = $this->EntityManager->getRepository(Account::class);
        $this->AccountRepository = $accountRepository;
    }

    public function testThatRegistrationProcessWorks(): void
    {
        $account = new Account();
        $account->setUsername('foobar');
        $account->setMail('foo@bar.com');

        $passwordHasher = $this->getContainer()
            ->get('security.user_password_hasher');

        $registration = new Registration($this->EntityManager, $account, $passwordHasher);
        $registration->requireAccountActivation();
        $registration->setPassword('Pa$$w0rd');

        $registeredAccount = $registration->registerAccount();

        $this->assertEquals('foobar', $this->AccountRepository->find($registeredAccount->getId())->getUsername());
        $this->assertNotNull($registeredAccount->getActivationHash());
    }
}
