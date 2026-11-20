<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use PluginPanel;
use PluginStat;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginEntity;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Model\Account\UserRole;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardInstantiationTestPanel extends PluginPanel
{
    public static int $instantiationCount = 0;

    public function __construct(int $id)
    {
        ++self::$instantiationCount;

        parent::__construct($id);
    }

    public function name(): string
    {
        return 'Dashboard instantiation test';
    }

    public function description(): string
    {
        return 'Dashboard instantiation test panel.';
    }

    protected function defaultSport(): int
    {
        return -1;
    }

    protected function displayContent(): void {}
}

final class DashboardStatisticsInstantiationTestPlugin extends PluginStat
{
    public static int $instantiationCount = 0;

    public function __construct(int $id)
    {
        ++self::$instantiationCount;

        parent::__construct($id);
    }

    public function name(): string
    {
        return 'Dashboard statistics instantiation test';
    }

    public function description(): string
    {
        return 'Dashboard statistics instantiation test plugin.';
    }

    protected function displayContent(): void {}
}

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class DefaultControllerTest extends AbstractFixturesAwareWebTestCase
{
    private const string KNOWN_PASSWORD = 'irrelevant-for-this-test';

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('default_test_' . uniqid('', true))
            ->setMail('default_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, self::KNOWN_PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function registerDisabledAccount(): Account
    {
        $account = new Account()
            ->setUsername('default_test_' . uniqid('', true))
            ->setMail('default_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);
        $registration->requireAccountActivation();

        $account->setPassword($passwordEncoder->hashPassword($account, self::KNOWN_PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function registerAdminAccount(): Account
    {
        $account = new Account()
            ->setUsername('default_test_admin_' . uniqid('', true))
            ->setMail('default_test_admin_' . uniqid('', true) . '@example.test')
            ->setRole(UserRole::ROLE_ADMIN);

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, self::KNOWN_PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function createHashGenerator(): UnsubscribeHashGenerator
    {
        return self::getContainer()->get(UnsubscribeHashGenerator::class);
    }

    private function allowSupportFor(Account $account): void
    {
        $account->setAllowSupport(true);

        $this->getEntityManager()
            ->persist($account);
        $this->getEntityManager()
            ->flush();
    }

    public function testIndexActionRedirectsToLoginWhenNotAuthenticated(): void
    {
        $this->Client->request('GET', '/');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/en/login', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testIndexActionRedirectsToDashboardWhenAuthenticated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginActionRendersTheLoginFormWhenNotAuthenticated(): void
    {
        $this->Client->request('GET', '/en/login');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testLoginActionRedirectsToDashboardWhenAlreadyAuthenticated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/en/login');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginActionRendersTheAjaxTemplateForAnXmlHttpRequest(): void
    {
        $this->Client->request('GET', '/en/login', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testLoginCheckWithValidCredentialsLogsInAndRedirectsToDashboard(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/', $this->Client->getResponse()->headers->get('Location'));

        $this->Client->followRedirect();

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginCheckWithWrongPasswordRedirectsBackToLoginWithError(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => 'this-is-not-the-correct-password',
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithUnknownUsernameRedirectsBackToLoginWithError(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => 'this-user-does-not-exist',
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithDisabledAccountRedirectsBackToLoginWithError(): void
    {
        $disabledAccount = $this->registerDisabledAccount();

        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $disabledAccount->getUsername(),
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithoutCsrfTokenIsRejected(): void
    {
        $this->Client->request('POST', '/en/login_check', [
            '_username' => $this->Account->getUsername(),
            '_password' => self::KNOWN_PASSWORD,
        ]);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithInvalidCsrfTokenIsRejected(): void
    {
        $this->Client->request('POST', '/en/login_check', [
            '_username' => $this->Account->getUsername(),
            '_password' => self::KNOWN_PASSWORD,
            '_csrf_token' => 'not-a-valid-csrf-token',
        ]);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testSuccessfulLoginWithRememberMeSetsAnAdditionalCookie(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $cookieNames = array_map(
            static fn (\Symfony\Component\BrowserKit\Cookie $cookie): string => $cookie->getName(),
            $this->Client->getCookieJar()
                ->all()
        );

        $this->assertNotEmpty(array_diff($cookieNames, ['MOCKSESSID']));
    }

    public function testSwitchUserAllowsAdminToImpersonateAnotherUser(): void
    {
        $this->allowSupportFor($this->Account);

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/dashboard', $this->Client->getResponse()->headers->get('Location'));

        $tokenStorage = self::getContainer()->get('security.token_storage');
        $impersonatedUser = $tokenStorage->getToken()
            ->getUser();

        $this->assertInstanceOf(Account::class, $impersonatedUser);
        $this->assertSame($this->Account->getUsername(), $impersonatedUser->getUserIdentifier());
    }

    public function testSwitchUserExitReturnsToOriginalUser(): void
    {
        $this->allowSupportFor($this->Account);

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());
        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/dashboard', $this->Client->getResponse()->headers->get('Location'));

        $this->Client->request('GET', '/dashboard?_switch_user=_exit');
        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/dashboard', $this->Client->getResponse()->headers->get('Location'));

        $tokenStorage = self::getContainer()->get('security.token_storage');
        $currentUser = $tokenStorage->getToken()
            ->getUser();

        $this->assertInstanceOf(Account::class, $currentUser);
        $this->assertSame($admin->getUsername(), $currentUser->getUserIdentifier());
    }

    public function testSwitchUserIsDeniedForNonAdminUser(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $otherAccount->getUsername());

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSwitchUserIsDeniedWhenTargetAccountDoesNotAllowSupport(): void
    {
        $this->assertFalse($this->Account->getAllowSupport());

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testLogoutActionRedirectsToLogin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/logout');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testRegisterActionRendersTheRegistrationFormWhenRegistrationIsEnabled(): void
    {
        $this->Client->request('GET', '/en/register');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('name="registration"', (string) $this->Client->getResponse()->getContent());
    }

    public function testDashboardActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/dashboard');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDashboardActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/dashboard');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testDashboardActionRendersTheDashboardThroughTwig(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/dashboard');

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()->getContent();

        $this->assertStringContainsString('id="data-browser"', $content);
        $this->assertStringContainsString('id="statistics"', $content);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('id="headline"', $content);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersMonthlyStatisticsThroughItsTwigTemplate(): void
    {
        $plugin = new PluginEntity()
            ->setKey('RunalyzePluginStat_MonthlyStats')
            ->setType(PluginEntity::TYPE_STAT)
            ->setActive(PluginEntity::STATE_ACTIVE)
            ->setOrder(0)
            ->setAccount($this->Account);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $pluginId = (int) $plugin->getId();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard?pluginid=' . $pluginId . '&sport=all');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();

            $this->assertStringContainsString('Monthly Stats', $content);
            $this->assertStringContainsString('analysis-table', $content);
            $this->assertStringContainsString('colspan="13"', $content);
            $this->assertSame(1, substr_count($content, 'id="headline"'));
        } finally {
            $this->getEntityManager()->remove($plugin);
            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersMonthlyStatisticsWithActivities(): void
    {
        $sport = $this->getEntityManager()
            ->getRepository(Sport::class)
            ->findOneBy([
                'account' => $this->Account,
                'img' => 'icons8-Running',
            ]);

        $this->assertInstanceOf(Sport::class, $sport);

        $training = new Training()
            ->setTime(mktime(12, 0, 0, 6, 1, 2025))
            ->setS(3600)
            ->setDistance(10.0)
            ->setSport($sport)
            ->setAccount($this->Account);

        $this->getEntityManager()->persist($training);
        $this->getEntityManager()->flush();

        $plugin = new PluginEntity()
            ->setKey('RunalyzePluginStat_MonthlyStats')
            ->setType(PluginEntity::TYPE_STAT)
            ->setActive(PluginEntity::STATE_ACTIVE)
            ->setOrder(0)
            ->setAccount($this->Account);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $pluginId = (int) $plugin->getId();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard?pluginid=' . $pluginId . '&sport=all');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();

            $this->assertStringContainsString('Monthly Stats', $content);
            $this->assertStringContainsString('analysis-table', $content);
            $this->assertStringContainsString('2025', $content);
            $this->assertStringNotContainsString('colspan="13"', $content);
        } finally {
            $this->getEntityManager()->remove($plugin);
            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardInstantiatesEachPanelOnlyOnce(): void
    {
        $plugin = new PluginEntity()
            ->setKey(DashboardInstantiationTestPanel::class)
            ->setType(PluginEntity::TYPE_PANEL)
            ->setActive(PluginEntity::STATE_ACTIVE)
            ->setOrder(1)
            ->setAccount($this->Account);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        DashboardInstantiationTestPanel::$instantiationCount = 0;

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);
            $this->assertSame(1, DashboardInstantiationTestPanel::$instantiationCount);
        } finally {
            $this->getEntityManager()->remove($plugin);
            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardInstantiatesTheSelectedStatisticsPluginOnlyOnce(): void
    {
        $plugin = new PluginEntity()
            ->setKey(DashboardStatisticsInstantiationTestPlugin::class)
            ->setType(PluginEntity::TYPE_STAT)
            ->setActive(PluginEntity::STATE_ACTIVE)
            ->setOrder(0)
            ->setAccount($this->Account);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        DashboardStatisticsInstantiationTestPlugin::$instantiationCount = 0;

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);
            $this->assertSame(1, DashboardStatisticsInstantiationTestPlugin::$instantiationCount);
        } finally {
            $this->getEntityManager()->remove($plugin);
            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognoseCalculatorWindowUsesTheInstalledPlugin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Prognose');

        $post = $_POST;
        $_POST = [];

        try {
            $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.php');

            $this->assertStatusCode(200, $this->Client);
            $this->assertStringContainsString('id="prognosis-calculator"', (string) $this->Client->getResponse()->getContent());
        } finally {
            $_POST = $post;
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testRechenspieleWindowUsesTheInstalledPlugin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Rechenspiele');

        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Rechenspiele/window.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="rechenspiele-calculator"', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testRechenspieleShapePlotUsesTheInstalledPlugin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Rechenspiele');

        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Rechenspiele/window.plot.php');

        $this->assertStatusCode(200, $this->Client);
        $content = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('Shape', $content);
        $this->assertStringContainsString('formlasthalftsb', $content);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowUsesTenKilometersAsDefaultWhenConfigured(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Prognose');

        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('formverlauf_10', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowWorksWithoutTheOptionalRacePlugin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Prognose');

        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php?distance=10');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('formverlauf_10', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowUsesCustomConfiguredDistances(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Prognose');

        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(PluginEntity::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Prognose',
        ]);
        $this->assertInstanceOf(PluginEntity::class, $plugin);

        $configuration = new PluginConf()
            ->setPlugin($plugin)
            ->setConfig('distances')
            ->setValue('5.72,8.9');
        $entityManager->persist($configuration);
        $entityManager->flush();

        try {
            $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php?distance=5.72');

            $this->assertStatusCode(200, $this->Client);
            $content = (string) $this->Client->getResponse()->getContent();
            $this->assertStringContainsString('formverlauf_5_72', $content);
            $this->assertStringContainsString('distance=8.9', $content);
        } finally {
            $entityManager->remove($configuration);
            $entityManager->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowDefaultsToFirstCustomDistance(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Prognose');

        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(PluginEntity::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Prognose',
        ]);
        $this->assertInstanceOf(PluginEntity::class, $plugin);

        $configuration = new PluginConf()
            ->setPlugin($plugin)
            ->setConfig('distances')
            ->setValue('5.72,8.9');
        $entityManager->persist($configuration);
        $entityManager->flush();

        try {
            $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php');

            $this->assertStatusCode(200, $this->Client);
            $content = (string) $this->Client->getResponse()->getContent();
            $this->assertStringContainsString('formverlauf_5_72', $content);
            $this->assertStringContainsString('distance=8.9', $content);
        } finally {
            $entityManager->remove($configuration);
            $entityManager->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowRequiresInstalledPanel(): void
    {
        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(PluginEntity::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Prognose',
        ]);
        $this->assertInstanceOf(PluginEntity::class, $plugin);

        $entityManager->remove($plugin);
        $entityManager->flush();

        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php');

        $this->assertStatusCode(404, $this->Client);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognosisPlotWindowUsesTheInstalledOptionalRacePlugin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);
        $installationManager->install('RunalyzePluginPanel_Prognose');
        $installationManager->install('RunalyzePluginStat_Wettkampf');

        $this->Client->request('GET', '/plugin/RunalyzePluginPanel_Prognose/window.plot.php?distance=10');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('formverlauf_10', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testGenericPluginIncludeRouteIsNotAvailable(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/plugin/SomePlugin/index.php');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testIndexActionRendersTheDashboardFragmentForAnXmlHttpRequest(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()->getContent();

        $this->assertStringContainsString('id="data-browser"', $content);
        $this->assertStringContainsString('id="statistics"', $content);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $content);
        $this->assertStringNotContainsString('id="headline"', $content);
    }

    public function testIndexPhpActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/index.php', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testIndexPhpActionRedirectsToBaseUrlForANonXhrRequestWhenAuthenticated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/index.php');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testIndexPhpActionRendersTheDashboardForAnXmlHttpRequest(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/index.php', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()->getContent();

        $this->assertStringContainsString('id="data-browser"', $content);
        $this->assertStringContainsString('id="statistics"', $content);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $content);
        $this->assertStringNotContainsString('id="headline"', $content);
    }

    public function testLoginPhpActionRedirectsToDashboard(): void
    {
        $this->Client->request('GET', '/login.php');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testUnsubscribeMailActionShowsInfoPageForAValidHash(): void
    {
        $hash = $this->createHashGenerator()
            ->generate($this->Account->getUsername());

        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/' . $hash);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUnsubscribeMailActionShowsFailurePageForAnInvalidHash(): void
    {
        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/not-a-valid-hash');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUnsubscribeMailActionShowsFailurePageForAnUnknownMailAddress(): void
    {
        $hash = $this->createHashGenerator()
            ->generate('someone-who-does-not-exist');

        $this->Client->request('GET', '/unsubscribe/unknown@example.test/' . $hash);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUnsubscribeMailConfirmActionDisablesMailsForAValidHash(): void
    {
        $this->assertTrue($this->Account->getAllowMails());

        $hash = $this->createHashGenerator()
            ->generate($this->Account->getUsername());

        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/' . $hash . '/confirm');

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Account::class, $reloadedAccount);
        $this->assertFalse($reloadedAccount->getAllowMails());
    }

    public function testUnsubscribeMailConfirmActionDoesNotDisableMailsForAnInvalidHash(): void
    {
        $this->assertTrue($this->Account->getAllowMails());

        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/not-a-valid-hash/confirm');

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Account::class, $reloadedAccount);
        $this->assertTrue($reloadedAccount->getAllowMails());
    }

    public function testFeedbackActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/feedback');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testFeedbackActionReturns404WhenNoRecipientIsConfigured(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/feedback');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testSwitchedUserStaysLoggedInOnTheFollowingRequest(): void
    {
        $this->allowSupportFor($this->Account);

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());
        $this->assertStatusCode(302, $this->Client);

        $this->Client->request('GET', '/dashboard');

        $this->assertStatusCode(200, $this->Client);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersSportlerPanelThroughTheModernTwigPath(): void
    {
        $plugin = $this->getEntityManager()->getRepository(PluginEntity::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Sportler',
        ]);

        $this->assertInstanceOf(PluginEntity::class, $plugin);
        $configuration = new PluginConf()
            ->setPlugin($plugin)
            ->setConfig('wunschgewicht')
            ->setValue('75');
        $this->getEntityManager()->persist($configuration);

        $user = new User()
            ->setAccount($this->Account)
            ->setTime(time())
            ->setWeight(70.5)
            ->setPulseRest(55)
            ->setPulseMax(190)
            ->setFat(20.0)
            ->setWater(55.0)
            ->setMuscles(40.0);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();

            $this->assertStringContainsString('class="panel dont-reload-for-training" id="panel-' . $plugin->getId() . '"', $content);
            $this->assertStringContainsString('Body values', $content);
            $this->assertStringContainsString('Resting HR', $content);
            $this->assertStringContainsString('sportler_weights', $content);
            $this->assertStringContainsString('sportler_analyse', $content);
            $this->assertStringContainsString('RunalyzePlot.addPlot', $content);
            $this->assertStringContainsString('from: 75, to: 75', $content);
        } finally {
            $this->getEntityManager()->remove($user);
            $this->getEntityManager()->remove($configuration);
            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersSportsPanelThroughTheModernPluginTemplate(): void
    {
        $repository = $this->getEntityManager()->getRepository(PluginEntity::class);
        $plugin = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Sports',
        ]);
        $created = ! $plugin instanceof PluginEntity;

        if ($created) {
            $plugin = new PluginEntity()
                ->setKey('RunalyzePluginPanel_Sports')
                ->setType(PluginEntity::TYPE_PANEL)
                ->setActive(PluginEntity::STATE_ACTIVE)
                ->setOrder(1)
                ->setAccount($this->Account);
            $this->getEntityManager()->persist($plugin);
            $this->getEntityManager()->flush();
        }

        $this->assertInstanceOf(PluginEntity::class, $plugin);
        $originalActive = $plugin->getActive();
        $plugin->setActive(PluginEntity::STATE_ACTIVE);
        $this->getEntityManager()->flush();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();

            $this->assertStringContainsString('class="panel" id="panel-' . $plugin->getId() . '"', $content);
            $this->assertStringContainsString('id="sports_0"', $content);
            $this->assertStringContainsString('id="sports_3"', $content);
        } finally {
            if ($created) {
                $this->getEntityManager()->remove($plugin);
            } else {
                $plugin->setActive($originalActive);
            }

            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersEquipmentPanelThroughTheModernPluginTemplate(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $pluginRepository = $this->getEntityManager()->getRepository(PluginEntity::class);
        $plugin = $pluginRepository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Equipment',
        ]);
        $created = ! $plugin instanceof PluginEntity;

        if ($created) {
            /** @var PluginInstallationManager $installationManager */
            $installationManager = self::getContainer()->get(PluginInstallationManager::class);
            $this->assertTrue($installationManager->install('RunalyzePluginPanel_Equipment'));
            $plugin = $pluginRepository->findOneBy([
                'account' => $this->Account,
                'key' => 'RunalyzePluginPanel_Equipment',
            ]);
        }

        $this->assertInstanceOf(PluginEntity::class, $plugin);
        $originalActive = $plugin->getActive();
        $plugin->setActive(PluginEntity::STATE_ACTIVE);

        $firstType = new EquipmentType()
            ->setName('Running shoes')
            ->setMaxKm(500)
            ->setAccount($this->Account);
        $secondType = new EquipmentType()
            ->setName('Cycling shoes')
            ->setMaxKm(1000)
            ->setAccount($this->Account);

        $activeEquipment = new Equipment()
            ->setName('Trail shoes')
            ->setAccount($this->Account)
            ->setType($firstType)
            ->setDistance(250.0)
            ->setTime(3600);
        $inactiveEquipment = new Equipment()
            ->setName('Old shoes')
            ->setAccount($this->Account)
            ->setType($firstType)
            ->setDistance(100.0)
            ->setTime(1800)
            ->setDateEnd(new \DateTime('2025-01-01'));

        $this->getEntityManager()->persist($firstType);
        $this->getEntityManager()->persist($secondType);
        $this->getEntityManager()->persist($activeEquipment);
        $this->getEntityManager()->persist($inactiveEquipment);
        $this->getEntityManager()->flush();

        $configuration = new PluginConf()
            ->setPlugin($plugin)
            ->setConfig('type')
            ->setValue((string) $firstType->getId());
        $this->getEntityManager()->persist($configuration);
        $this->getEntityManager()->flush();
        $pluginId = (int) $plugin->getId();
        $firstTypeId = (int) $firstType->getId();
        $secondTypeId = (int) $secondType->getId();
        $activeEquipmentId = (int) $activeEquipment->getId();
        $inactiveEquipmentId = (int) $inactiveEquipment->getId();

        try {
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();

            $this->assertStringContainsString('class="panel" id="panel-' . $plugin->getId() . '"', $content);
            $this->assertStringContainsString('Trail shoes', $content);
            $this->assertStringContainsString('Show/Hide unused equipment', $content);
            $this->assertStringContainsString('Old shoes', $content);
            $this->assertStringContainsString('equipmentid=' . $activeEquipment->getId(), $content);

            $this->Client->request('GET', '/my/plugin/' . $plugin->getId() . '?type=' . $secondType->getId());

            $this->assertStatusCode(200, $this->Client);

            $storedConfiguration = $this->getEntityManager()->getRepository(PluginConf::class)->findOneBy([
                'plugin' => $plugin,
                'config' => 'type',
            ]);

            $this->assertInstanceOf(PluginConf::class, $storedConfiguration);
            $this->assertSame((string) $secondType->getId(), $storedConfiguration->getValue());
        } finally {
            $entityManager = $this->getEntityManager();

            foreach ([$activeEquipmentId, $inactiveEquipmentId] as $equipmentId) {
                $equipmentToRemove = $entityManager->find(Equipment::class, $equipmentId);

                if ($equipmentToRemove instanceof Equipment) {
                    $entityManager->remove($equipmentToRemove);
                }
            }

            $configurationToRemove = $entityManager->getRepository(PluginConf::class)->findOneBy([
                'plugin' => $pluginId,
                'config' => 'type',
            ]);

            if ($configurationToRemove instanceof PluginConf) {
                $entityManager->remove($configurationToRemove);
            }

            foreach ([$firstTypeId, $secondTypeId] as $equipmentTypeId) {
                $equipmentTypeToRemove = $entityManager->find(EquipmentType::class, $equipmentTypeId);

                if ($equipmentTypeToRemove instanceof EquipmentType) {
                    $entityManager->remove($equipmentTypeToRemove);
                }
            }

            $pluginToUpdate = $entityManager->find(PluginEntity::class, $pluginId);

            if ($pluginToUpdate instanceof PluginEntity) {
                if ($created) {
                    $entityManager->remove($pluginToUpdate);
                } else {
                    $pluginToUpdate->setActive($originalActive);
                }
            }

            $entityManager->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersRechenspieleThroughTheModernPluginTemplate(): void
    {
        $repository = $this->getEntityManager()->getRepository(PluginEntity::class);
        $plugin = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Rechenspiele',
        ]);
        $created = ! $plugin instanceof PluginEntity;

        if ($created) {
            $plugin = new PluginEntity()
                ->setKey('RunalyzePluginPanel_Rechenspiele')
                ->setType(PluginEntity::TYPE_PANEL)
                ->setActive(PluginEntity::STATE_ACTIVE)
                ->setOrder(1)
                ->setAccount($this->Account);
            $this->getEntityManager()->persist($plugin);
            $this->getEntityManager()->flush();
        }

        $this->assertInstanceOf(PluginEntity::class, $plugin);
        $originalActive = $plugin->getActive();
        $plugin->setActive(PluginEntity::STATE_ACTIVE);
        $this->getEntityManager()->flush();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();
            $this->assertStringContainsString('class="panel" id="panel-' . $plugin->getId() . '"', $content);
            $this->assertStringContainsString('id="rechenspiele-values"', $content);
            $this->assertStringContainsString('/plugin/RunalyzePluginPanel_Rechenspiele/window.plot.php', $content);
        } finally {
            if ($created) {
                $this->getEntityManager()->remove($plugin);
            } else {
                $plugin->setActive($originalActive);
            }

            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersTagsSummaryThroughTheModernPluginTemplate(): void
    {
        $repository = $this->getEntityManager()->getRepository(PluginEntity::class);
        $plugin = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_TagsSummary',
        ]);
        $created = ! $plugin instanceof PluginEntity;

        if ($created) {
            $plugin = new PluginEntity()
                ->setKey('RunalyzePluginPanel_TagsSummary')
                ->setType(PluginEntity::TYPE_PANEL)
                ->setActive(PluginEntity::STATE_ACTIVE)
                ->setOrder(1)
                ->setAccount($this->Account);
            $this->getEntityManager()->persist($plugin);
            $this->getEntityManager()->flush();
        }

        $this->assertInstanceOf(PluginEntity::class, $plugin);
        $originalActive = $plugin->getActive();
        $plugin->setActive(PluginEntity::STATE_ACTIVE);
        $this->getEntityManager()->flush();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();
            $this->assertStringContainsString('class="panel" id="panel-' . $plugin->getId() . '"', $content);
            $this->assertStringContainsString('id="tags-summary"', $content);
            $this->assertStringContainsString('tagssummary_timescale', $content);
        } finally {
            if ($created) {
                $this->getEntityManager()->remove($plugin);
            } else {
                $plugin->setActive($originalActive);
            }

            $this->getEntityManager()->flush();
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testDashboardRendersPrognosisPanelThroughTheModernPluginTemplate(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $repository = $this->getEntityManager()->getRepository(PluginEntity::class);
        $plugin = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Prognose',
        ]);
        $created = ! $plugin instanceof PluginEntity;

        if ($created) {
            /** @var PluginInstallationManager $installationManager */
            $installationManager = self::getContainer()->get(PluginInstallationManager::class);
            $installationManager->install('RunalyzePluginPanel_Prognose');
            $plugin = $repository->findOneBy([
                'account' => $this->Account,
                'key' => 'RunalyzePluginPanel_Prognose',
            ]);
        }

        $this->assertInstanceOf(PluginEntity::class, $plugin);
        $originalActive = $plugin->getActive();
        $plugin->setActive(PluginEntity::STATE_ACTIVE);
        $this->getEntityManager()->flush();

        try {
            $this->Client->request('GET', '/dashboard');

            $this->assertStatusCode(200, $this->Client);

            $content = (string) $this->Client->getResponse()->getContent();

            $this->assertStringContainsString('class="panel" id="panel-' . $plugin->getId() . '"', $content);
            $this->assertStringContainsString('Prognosis', $content);
            $this->assertStringContainsString('title="Prognosis calculator"', $content);
        } finally {
            if ($created) {
                $this->getEntityManager()->remove($plugin);
            } else {
                $plugin->setActive($originalActive);
            }

            $this->getEntityManager()->flush();
        }
    }
}
