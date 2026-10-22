<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly ?UserPasswordHasherInterface $passwordHasher = null
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addEmptyAccount($manager);
        $this->registerDefaultAccount($manager);
    }

    protected function addEmptyAccount(ObjectManager $manager): void
    {
        $emptyAccount = new Account();
        $emptyAccount->setUsername('empty');
        // example.test is reserved for testing/documentation purposes (RFC 2606) and is not a
        // real, registrable domain, unlike test.com - which ContainsDisposableMailAddress()
        // rejects as a known disposable-email domain.
        $emptyAccount->setMail('empty@example.test');
        $emptyAccount->setPassword('emptyPassword');

        $manager->persist($emptyAccount);
        $manager->flush();

        $this->addReference('account-empty', $emptyAccount);
    }

    protected function registerDefaultAccount(ObjectManager $manager): void
    {
        $defaultAccount = new Account();
        $defaultAccount->setUsername('default');
        $defaultAccount->setMail('default@example.test');

        // creates an account and some dependend basic/default data
        $registration = $this->registerAccount($manager, $defaultAccount, 'defaultPassword');

        $this->addReference('account-default', $defaultAccount);
        $this->addReference('account-default.sport-running', $registration->getRegisteredSportForRunning());
        $this->addReference('account-default.sport-cycling', $registration->getRegisteredSportForCycling());
        $this->addReference('account-default.equipment-type-clothes', $registration->getRegisteredEquipmentTypeClothes());
    }

    protected function registerAccount(ObjectManager $manager, Account $account, string $password): Registration
    {
        $registration = new Registration($manager, $this->passwordHasher);
        $registration->setAccount($account);

        if ($this->passwordHasher instanceof UserPasswordHasherInterface) {
            $account->setPassword($this->passwordHasher->hashPassword($account, $password));
        } else {
            // Fallback for testing environments where the hasher factory is not available
            $account->setPassword(password_hash($password, PASSWORD_BCRYPT));
        }

        $registration->registerAccount();

        return $registration;
    }

    public function getOrder(): int
    {
        return 1;
    }
}
