<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\Privacy as BundlePrivacy;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Parameter\Application\ActivityRoutePrivacy;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PrivacyGuardTest extends TestCase
{
    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }

    private function createAccountWithId(int $id): Account
    {
        $account = new Account();
        $this->setEntityId($account, $id);

        return $account;
    }

    private function createTrainingForAccount(Account $account): Training
    {
        $training = new Training();
        $training->setAccount($account);

        return $training;
    }

    /**
     * @param ?Account $loggedInUser null simulates an anonymous/not-logged-in visitor.
     */
    private function createTokenStorage(?Account $loggedInUser): TokenStorageInterface
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);

        if (! $loggedInUser instanceof Account) {
            $tokenStorage->method('getToken')
                ->willReturn(null);

            return $tokenStorage;
        }

        $token = new UsernamePasswordToken($loggedInUser, 'main');
        $tokenStorage->method('getToken')
            ->willReturn($token);

        return $tokenStorage;
    }

    private function createConfigurationManagerWithRoutePrivacyMode(string $mode): ConfigurationManager
    {
        $bundlePrivacy = new BundlePrivacy([
            'TRAINING_MAP_PUBLIC_MODE' => $mode,
        ]);

        $configurationList = $this->createStub(RunalyzeConfigurationList::class);
        $configurationList->method('getPrivacy')
            ->willReturn($bundlePrivacy);

        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')
            ->willReturn($configurationList);

        return $configurationManager;
    }

    private function createPrivacyGuard(TokenStorageInterface $tokenStorage, ConfigurationManager $configurationManager): PrivacyGuard
    {
        return new PrivacyGuard($tokenStorage, $configurationManager);
    }

    public function testIsMapVisibleReturnsTrueForOwnerRegardlessOfPrivacySettings(): void
    {
        $owner = $this->createAccountWithId(7);
        $training = $this->createTrainingForAccount($owner);

        $tokenStorage = $this->createTokenStorage($owner);
        $configurationManager = $this->createConfigurationManagerWithRoutePrivacyMode(ActivityRoutePrivacy::NEVER);

        $guard = $this->createPrivacyGuard($tokenStorage, $configurationManager);

        self::assertTrue($guard->isMapVisible($training));
    }

    public function testIsMapVisibleForOwnerNeverConsultsConfigurationManager(): void
    {
        $owner = $this->createAccountWithId(7);
        $training = $this->createTrainingForAccount($owner);

        $tokenStorage = $this->createTokenStorage($owner);
        $configurationManager = $this->createMock(ConfigurationManager::class);
        $configurationManager->expects(self::never())->method('getList');

        $guard = $this->createPrivacyGuard($tokenStorage, $configurationManager);

        $guard->isMapVisible($training);
    }

    /**
     * @return array<string, array<int, bool|string>>
     */
    public static function routePrivacyProvider(): array
    {
        return [
            'never, no raceresult' => [ActivityRoutePrivacy::NEVER, false, false],
            'never, with raceresult' => [ActivityRoutePrivacy::NEVER, true, false],
            'race, no raceresult' => [ActivityRoutePrivacy::RACE, false, false],
            'race, with raceresult' => [ActivityRoutePrivacy::RACE, true, true],
            'always, no raceresult' => [ActivityRoutePrivacy::ALWAYS, false, true],
            'always, with raceresult' => [ActivityRoutePrivacy::ALWAYS, true, true],
        ];
    }

    #[DataProvider('routePrivacyProvider')]
    public function testIsMapVisibleForNonOwnerFollowsRoutePrivacyMode(string $mode, bool $withRaceresult, bool $expectedVisible): void
    {
        $owner = $this->createAccountWithId(7);
        $viewer = $this->createAccountWithId(99);
        $training = $this->createTrainingForAccount($owner);

        $raceresult = null;
        if ($withRaceresult) {
            $raceresult = new Raceresult();
            $raceresult->setAccount($owner);
            $raceresult->setActivity($training);
        }

        $tokenStorage = $this->createTokenStorage($viewer);
        $configurationManager = $this->createConfigurationManagerWithRoutePrivacyMode($mode);

        $guard = $this->createPrivacyGuard($tokenStorage, $configurationManager);

        self::assertSame($expectedVisible, $guard->isMapVisible($training, $raceresult));
    }

    public function testIsMapVisibleForAnonymousVisitorFollowsRoutePrivacyMode(): void
    {
        $owner = $this->createAccountWithId(7);
        $training = $this->createTrainingForAccount($owner);

        $tokenStorage = $this->createTokenStorage(null);
        $configurationManager = $this->createConfigurationManagerWithRoutePrivacyMode(ActivityRoutePrivacy::ALWAYS);

        $guard = $this->createPrivacyGuard($tokenStorage, $configurationManager);

        self::assertTrue($guard->isMapVisible($training));
    }

    public function testIsMapVisibleForAnonymousVisitorWithRestrictivePrivacyReturnsFalse(): void
    {
        $owner = $this->createAccountWithId(7);
        $training = $this->createTrainingForAccount($owner);

        $tokenStorage = $this->createTokenStorage(null);
        $configurationManager = $this->createConfigurationManagerWithRoutePrivacyMode(ActivityRoutePrivacy::NEVER);

        $guard = $this->createPrivacyGuard($tokenStorage, $configurationManager);

        self::assertFalse($guard->isMapVisible($training));
    }
}
