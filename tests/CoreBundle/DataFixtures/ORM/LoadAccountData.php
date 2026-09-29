<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;

class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly ?\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordEncoder = null
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addEmptyAccount($manager);
        $this->registerDefaultAccount($manager);
    }

    protected function addEmptyAccount(ObjectManager $manager)
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

    protected function registerDefaultAccount(ObjectManager $manager)
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
        $registration = new Registration($manager, $account, $this->passwordEncoder);

        if ($this->passwordEncoder instanceof \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface) {
            # $account->setPassword($encoder->encodePassword($password, $account->getSalt()));
            $account->setPassword($this->passwordEncoder->encodePassword($account, $password));
        } else {
            // Fallback for testing environments where the encoder factory is not available
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
