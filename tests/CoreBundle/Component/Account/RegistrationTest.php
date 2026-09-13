<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Account;

use Doctrine\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group requiresKernel
 * @group requiresClient
 */
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

        $passwordEncoder = $this->getContainer()
            ->get('security.user_password_encoder.generic');

        $registration = new Registration($this->EntityManager, $account, $passwordEncoder);
        $registration->requireAccountActivation();
        $registration->setPassword('Pa$$w0rd');

        $registeredAccount = $registration->registerAccount();

        $this->assertEquals('foobar', $this->AccountRepository->find($registeredAccount->getId())->getUsername());
        $this->assertNotNull($registeredAccount->getActivationHash());
    }
}
