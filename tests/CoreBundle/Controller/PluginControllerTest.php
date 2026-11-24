<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Profile\Sport\SportProfile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class PluginControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();

        ob_start();
    }

    protected function tearDown(): void
    {
        gc_collect_cycles();

        ob_end_clean();

        parent::tearDown();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('plugin_test_' . uniqid('', true))
            ->setMail('plugin_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function request(string $method, string $uri): void
    {
        $this->Client->request($method, $uri);
    }

    public function testPluginInstallActionRequiresAuthentication(): void
    {
        $this->request('GET', '/call/call.Plugin.install.php?key=SomethingIrrelevant');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testMonthlyStatisticsCanBeInstalledConfiguredAndReloaded(): void
    {
        $key = 'RunalyzePluginStat_MonthlyStats';
        $overview = '/settings?key=config_tab_plugins&external=true';
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', $overview);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('key=' . $key, (string) $this->Client->getResponse()->getContent());

        $this->request('GET', '/call/call.Plugin.install.php?key=' . $key);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('successfully installed', (string) $this->Client->getResponse()->getContent());

        $plugin = $this->getEntityManager()->getRepository(Plugin::class)->findOneBy([
            'account' => $this->Account,
            'key' => $key,
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::TYPE_STAT, $plugin->getType());
        $id = $plugin->getId();
        $configuration = '/call/call.Plugin.config.php?id=' . $id;

        $this->request('GET', $overview);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('plugin_modus_' . $id, (string) $this->Client->getResponse()->getContent());

        $this->request('GET', $configuration . '&active=0');
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('There are no settings.', $html);
        $this->assertStringContainsString('Activate plugin', $html);
        $this->assertStringContainsString('Runalyze.reload', $html);

        $this->request('GET', $configuration . '&active=99');
        $this->assertStatusCode(400, $this->Client);

        $this->request('GET', $configuration . '&active=2');
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Deactivate plugin', (string) $this->Client->getResponse()->getContent());

        $this->request('GET', '/dashboard?pluginid=' . $id);
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('Miscellaneous', $html);
        $this->assertStringContainsString('analysis-table', $html);

        $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?sport=all&dat=n');
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('analysis-table', $html);
        $this->assertStringContainsString('target="statistics-inner"', $html);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $html);

        $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?sport[]=all');
        $this->assertStatusCode(400, $this->Client);

        $this->loginClient($this->Client, $this->registerUniqueAccount(), 'default');
        $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id);
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('The plugin could not be found.', $html);
        $this->assertStringNotContainsString('analysis-table', $html);
    }

    public function testPluginInstallActionRejectsAnUnrecognizedKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.install.php?key=ThisPluginDirectoryDoesNotExist');

        $this->assertStatusCode(404, $this->Client);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testTrainingPartnersFiltersAndRendersAnExistingInstallation(): void
    {
        $entityManager = $this->getEntityManager();
        $otherAccount = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $this->assertInstanceOf(Account::class, $account);

        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Trainingspartner',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::TYPE_STAT, $plugin->getType());

        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findOneBy([
            'account' => $account,
            'internalSportId' => SportProfile::RUNNING,
        ]);
        $cycling = $sports->findOneBy([
            'account' => $account,
            'internalSportId' => SportProfile::CYCLING,
        ]);
        $otherSport = $sports->findOneBy([
            'account' => $otherAccount,
            'internalSportId' => SportProfile::RUNNING,
        ]);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $this->assertInstanceOf(Sport::class, $otherSport);

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $year = (int) $now->format('Y');
        $january = $now->setDate($year, 1, 1)->setTime(0, 0)->getTimestamp();
        $nextJanuary = $now->setDate($year + 1, 1, 1)->setTime(0, 0)->getTimestamp();
        $name = 'Chris & <Pat>';
        foreach ([
            [$account, $running, $january, ' Alice, ' . $name . ', 123'],
            [$account, $running, $january + 1, 'Alice'],
            [$account, $running, $january + 2, null],
            [$account, $running, $january + 3, ''],
            [$account, $cycling, $january + 4, 'Cycling partner'],
            [$account, $running, $january - 1, 'Previous year'],
            [$account, $running, $nextJanuary, 'Next year'],
            [$otherAccount, $otherSport, $january + 5, 'Other account partner'],
        ] as [$owner, $sport, $time, $partner]) {
            $entityManager->persist(new Training()
                ->setAccount($owner)
                ->setSport($sport)
                ->setTime($time)
                ->setS(3600)
                ->setPartner($partner));
        }

        $entityManager->flush();

        $id = $plugin->getId();
        $runningId = $running->getId();
        $this->loginClient($this->Client, $account, 'default');
        $this->request('GET', '/dashboard?pluginid=' . $id);
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('Training partner: ' . $year, $html);
        $this->assertStringContainsString('Cycling partner', $html);
        $this->assertStringNotContainsString('Other account partner', $html);

        $page = $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?sport=' . $runningId . '&jahr=' . $year);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Training partner: ' . $running->getName() . ', ' . $year, $page->filter('.panel-heading h1')->text());
        $rows = $page->filter('.statistics-container tbody tr');
        $this->assertCount(2, $rows);
        $this->assertSame('2x', $rows->eq(0)->filter('td')->first()->text());
        $this->assertSame('Alice', $rows->eq(0)->filter('a')->text());
        $this->assertSame('1x', $rows->eq(1)->filter('td')->first()->text());
        $this->assertEqualsCanonicalizing([$name, '123'], $rows->eq(1)->filter('a')->each(static fn ($link): string => $link->text()));
        $link = $rows->eq(1)->selectLink($name)->getNode(0);
        $this->assertInstanceOf(\DOMElement::class, $link);
        $this->assertSame('big', $link->getAttribute('data-size'));
        $uri = $link->getAttribute('href');
        $this->assertSame('/my/search', parse_url($uri, PHP_URL_PATH));
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        $this->assertSame([
            'get' => 'true',
            'partner' => $name,
            'opt' => [
                'partner' => 'like',
            ],
        ], $query);
        $this->assertSame(['4x', '2x', '50'], $page->filter('.statistics-container p strong')->each(static fn ($node): string => $node->text()));
        $this->assertSame([$running->getName(), (string) $year], $page->filter('.panel-menu li.active a')->each(static fn ($node): string => $node->text()));

        $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?sport=' . $runningId . '&jahr=-1');
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('Previous year', $html);
        $this->assertStringContainsString('Next year', $html);

        $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?sport=' . $runningId . '&jahr=' . ($year - 2));
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('You have only trained on your own.', $html);
        $this->assertStringContainsString('<strong>0x</strong>', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testTagStatisticsLoadsAnExistingSelectionAndValidatesChanges(): void
    {
        $entityManager = $this->getEntityManager();
        $otherAccount = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $this->assertInstanceOf(Account::class, $account);
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Tag',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);

        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $otherSport = $sports->findInternalIdFor(SportProfile::RUNNING, $otherAccount);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $this->assertInstanceOf(Sport::class, $otherSport);

        $trail = new Tag()->setAccount($account)->setTag('Trail & <Hill>');
        $intervals = new Tag()->setAccount($account)->setTag('Intervals');
        $foreign = new Tag()->setAccount($otherAccount)->setTag('Foreign tag');
        $entityManager->persist($trail);
        $entityManager->persist($intervals);
        $entityManager->persist($foreign);
        $entityManager->flush();

        $configuration = new PluginConf()->setPlugin($plugin)->setConfig('tag')->setValue((string) $trail->getId());
        $entityManager->persist($configuration);
        foreach ([
            [$account, $running, '2024-02-02', $trail],
            [$account, $running, '2025-10-03', $trail],
            [$account, $running, '2025-10-04', $trail],
            [$account, $cycling, '2025-10-05', $trail],
            [$account, $running, '2025-10-06', $foreign],
            [$otherAccount, $otherSport, '2025-10-07', $trail],
        ] as [$owner, $sport, $date, $tag]) {
            $entityManager->persist(new Training()
                ->setAccount($owner)
                ->setSport($sport)
                ->setTime(strtotime($date . ' 12:00:00 UTC'))
                ->setS(3600)
                ->addTag($tag));
        }

        $entityManager->flush();
        $id = $plugin->getId();
        $display = '/my/plugin/' . $id;
        $config = '/call/call.Plugin.config.php?id=' . $id;
        $settings = $entityManager->getRepository(PluginConf::class);
        $savedTag = static fn (): string => $settings->findValuesForPlugins([$id])[$id]['tag'];
        $this->loginClient($this->Client, $account, 'default');
        $dashboard = $this->Client->request('GET', '/dashboard?pluginid=' . $id);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Tag analysis: ' . $running->getName(), (string) $this->Client->getResponse()->getContent());
        $rows = $dashboard->filter('.statistics-container tbody tr');
        $this->assertCount(2, $rows);
        $this->assertSame('2025', $rows->eq(0)->filter('td')->eq(0)->text());
        $this->assertSame('2x', $rows->eq(0)->filter('td')->eq(10)->text());
        $this->assertSame('2024', $rows->eq(1)->filter('td')->eq(0)->text());
        $this->assertSame('1x', $rows->eq(1)->filter('td')->eq(2)->text());
        $this->assertSame('Tag: Trail & <Hill>', $dashboard->filter('.statistics-container p')->text());

        $filtered = $this->Client->xmlHttpRequest('GET', $display . '?sport=-1&jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $rows = $filtered->filter('.statistics-container tbody tr');
        $this->assertCount(1, $rows);
        $this->assertSame('3x', $rows->filter('td')->eq(10)->text());

        $tagLink = $filtered->filter('.panel-menu')->selectLink('Intervals');
        $this->assertSame('statistics-inner', $tagLink->attr('target'));
        $changed = $this->Client->xmlHttpRequest('GET', (string) $tagLink->attr('href'));
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('No activities found.', $changed->filter('.statistics-container tbody td')->text());
        $this->assertSame('Intervals', $changed->filter('.panel-menu li.active a')->first()->text());
        $this->assertSame((string) $intervals->getId(), $savedTag());

        foreach (['dat=' . $foreign->getId(), 'dat[]=1', 'dat=invalid'] as $query) {
            $this->Client->xmlHttpRequest('GET', $display . '?' . $query);
            $this->assertStatusCode(200, $this->Client);
            $this->assertSame((string) $intervals->getId(), $savedTag());
        }

        $options = $this->Client->xmlHttpRequest('GET', $config . '&active=0');
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Activate plugin', $options->filter('.panel-content')->text());
        $this->assertSame(['Intervals', 'Trail & <Hill>'], $options->filter('select[name=tag] option')->each(static fn ($option): string => $option->text()));
        $this->assertSame((string) $intervals->getId(), $options->filter('select[name=tag] option[selected]')->attr('value'));

        $this->Client->xmlHttpRequest('POST', $config, [
            'edit' => 'true',
            'tag' => (string) $foreign->getId(),
        ]);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame((string) $intervals->getId(), $savedTag());

        $this->Client->xmlHttpRequest('POST', $config, [
            'edit' => 'true',
            'tag' => (string) $trail->getId(),
        ]);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame((string) $trail->getId(), $savedTag());
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testTagStatisticsSavesTheFirstSelection(): void
    {
        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginStat_Tag',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->loginClient($this->Client, $this->Account, 'default');
        $display = '/my/plugin/' . $plugin->getId();

        $page = $this->Client->xmlHttpRequest('GET', $display);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Please choose a tag.', $page->filter('.statistics-container .warning')->text());
        $this->assertCount(1, $page->filter('.panel-menu .with-submenu'));

        $tag = new Tag()->setAccount($this->Account)->setTag('Intervals');
        $entityManager->persist($tag);
        $entityManager->flush();

        $this->Client->xmlHttpRequest('GET', $display . '?dat=' . $tag->getId());
        $this->assertStatusCode(200, $this->Client);
        $settings = $entityManager->getRepository(PluginConf::class)->findBy([
            'plugin' => $plugin,
            'config' => 'tag',
        ]);
        $this->assertCount(1, $settings);
        $this->assertSame((string) $tag->getId(), $settings[0]->getValue());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPluginInstallActionPersistsPluginForAuthenticatedAccount(): void
    {
        $pluginKey = 'RunalyzePluginPanel_TagsSummary';

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.install.php?key=' . $pluginKey);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'The plugin has been successfully installed.',
            (string) $this->Client->getResponse()->getContent()
        );

        $plugin = $this->getEntityManager()
            ->getRepository(Plugin::class)
            ->findOneBy([
                'account' => $this->Account,
                'key' => $pluginKey,
            ]);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::TYPE_PANEL, $plugin->getType());
        $this->assertSame(Plugin::STATE_ACTIVE, $plugin->getActive());
        $this->assertSame(99, $plugin->getOrder());

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);

        $this->assertTrue($installationManager->isInstalled($pluginKey));

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $plugin->getId());

        $this->assertStatusCode(200, $this->Client);

        $this->request('GET', '/call/call.Plugin.install.php?key=' . $pluginKey);

        $this->assertStatusCode(404, $this->Client);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testSportsPanelCanBeInstalledAndConfiguredWithoutLegacyClass(): void
    {
        $repository = $this->getEntityManager()->getRepository(Plugin::class);
        $existing = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Sports',
        ]);

        if ($existing instanceof Plugin) {
            $this->getEntityManager()->remove($existing);
            $this->getEntityManager()->flush();
        }

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.install.php?key=RunalyzePluginPanel_Sports');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'The plugin has been successfully installed.',
            (string) $this->Client->getResponse()->getContent()
        );

        $plugin = $this->getEntityManager()
            ->getRepository(Plugin::class)
            ->findOneBy([
                'account' => $this->Account,
                'key' => 'RunalyzePluginPanel_Sports',
            ]);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::TYPE_PANEL, $plugin->getType());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $plugin->getId());

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'This plugin has no configurable options.',
            (string) $this->Client->getResponse()->getContent()
        );

        $this->request('GET', '/my/plugin/' . $plugin->getId());

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="sports_0"', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testInstallationManagerRefreshesInstalledPluginDataAfterDatabaseChanges(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/dashboard');

        $this->assertStatusCode(200, $this->Client);

        /** @var PluginInstallationManager $installationManager */
        $installationManager = self::getContainer()->get(PluginInstallationManager::class);

        $this->assertFalse($installationManager->isInstalled('plugin-added-after-first-read'));

        $plugin = new Plugin()
            ->setAccount($this->Account)
            ->setKey('plugin-added-after-first-read')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(99);

        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $this->assertTrue($installationManager->isInstalled('plugin-added-after-first-read'));
    }

    public function testPluginInstallActionRejectsAMissingKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.install.php');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testPluginConfigActionRequiresAuthentication(): void
    {
        $this->request('GET', '/call/call.Plugin.config.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPluginConfigActionShowsErrorMessageWhenIdIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.config.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Something went wrong', (string) $this->Client->getResponse()->getContent());
    }

    public function testPluginConfigActionShowsErrorMessageWhenIdIsNotNumeric(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.config.php?id=not-a-number');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Something went wrong', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPluginConfigActionUpdatesActivationThroughTheInstallationManager(): void
    {
        $plugin = new Plugin()
            ->setAccount($this->Account)
            ->setKey('RunalyzePluginPanel_TagsSummary')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(1);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.config.php?id=' . $plugin->getId() . '&active=0');

        $this->assertStatusCode(200, $this->Client);
        $plugin = $this->getEntityManager()->getRepository(Plugin::class)->find($plugin->getId());
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::STATE_INACTIVE, $plugin->getActive());
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPluginConfigActionRejectsAnInvalidActivationState(): void
    {
        $plugin = new Plugin()
            ->setAccount($this->Account)
            ->setKey('RunalyzePluginPanel_TagsSummary')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(1);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.config.php?id=' . $plugin->getId() . '&active=99');

        $this->assertStatusCode(400, $this->Client);
        $plugin = $this->getEntityManager()->getRepository(Plugin::class)->find($plugin->getId());
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::STATE_ACTIVE, $plugin->getActive());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPluginConfigActionPassesPostDataToLegacyConfiguration(): void
    {
        $plugin = new Plugin()
            ->setAccount($this->Account)
            ->setKey('RunalyzePluginPanel_TagsSummary')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(1);
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request(
            'POST',
            '/call/call.Plugin.config.php?id=' . $plugin->getId(),
            [
                'edit' => 'true',
                'tagssummary_timescale' => '3',
            ]
        );

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());

        $configuration = $this->getEntityManager()
            ->getRepository(PluginConf::class)
            ->findForPluginAndKey($plugin->getId(), 'tagssummary_timescale');

        $this->assertInstanceOf(PluginConf::class, $configuration);
        $this->assertSame('3', $configuration->getValue());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testTagsSummaryConfigurationWorksWithoutLegacyPluginClass(): void
    {
        $plugin = new Plugin()
            ->setAccount($this->Account)
            ->setKey('RunalyzePluginPanel_TagsSummary')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(1);

        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $plugin->getId());

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'name="tagssummary_timescale"',
            (string) $this->Client->getResponse()->getContent()
        );

        $this->Client->request('POST', '/call/call.Plugin.config.php?id=' . $plugin->getId(), [
            'edit' => 'true',
            'tagssummary_timescale' => '6',
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());

        $configuration = $this->getEntityManager()
            ->getRepository(PluginConf::class)
            ->findForPluginAndKey($plugin->getId(), 'tagssummary_timescale');

        $this->assertInstanceOf(PluginConf::class, $configuration);
        $this->assertSame('6', $configuration->getValue());

        $pluginId = $plugin->getId();
        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId . '&active=0');

        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $reloadedPlugin = $this->getEntityManager()->getRepository(Plugin::class)->find($pluginId);
        $this->assertInstanceOf(Plugin::class, $reloadedPlugin);
        $this->assertSame(Plugin::STATE_INACTIVE, $reloadedPlugin->getActive());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId . '&active=1');

        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $reloadedPlugin = $this->getEntityManager()->getRepository(Plugin::class)->find($pluginId);
        $this->assertInstanceOf(Plugin::class, $reloadedPlugin);
        $this->assertSame(Plugin::STATE_ACTIVE, $reloadedPlugin->getActive());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testGoalsPanelCanBeInstalledWithoutItsLegacyClass(): void
    {
        $repository = $this->getEntityManager()->getRepository(Plugin::class);
        $existing = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Ziele',
        ]);

        $this->assertInstanceOf(Plugin::class, $existing);
        $this->getEntityManager()->remove($existing);
        $this->getEntityManager()->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.install.php?key=RunalyzePluginPanel_Ziele');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'The plugin has been successfully installed.',
            (string) $this->Client->getResponse()->getContent()
        );

        $installed = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Ziele',
        ]);
        $this->assertInstanceOf(Plugin::class, $installed);
        $this->assertSame(Plugin::TYPE_PANEL, $installed->getType());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $installed->getId());
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'name="ziel_woche"',
            (string) $this->Client->getResponse()->getContent()
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testGoalsPanelRendersAndPersistsLegacySettingsViaModernRoutes(): void
    {
        $plugin = $this->getEntityManager()
            ->getRepository(Plugin::class)
            ->findOneBy([
                'account' => $this->Account,
                'key' => 'RunalyzePluginPanel_Ziele',
            ]);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame(Plugin::STATE_INACTIVE, $plugin->getActive());

        $plugin->setActive(Plugin::STATE_ACTIVE);
        $this->getEntityManager()->flush();

        $pluginId = (int) $plugin->getId();
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'name="ziel_woche"',
            (string) $this->Client->getResponse()->getContent()
        );

        $this->Client->request('POST', '/call/call.Plugin.config.php?id=' . $pluginId, [
            'edit' => 'true',
            'ziel_show_woche' => '1',
            'ziel_woche' => '100',
            'details' => '0',
        ]);
        $this->assertStatusCode(200, $this->Client);

        $goal = $this->getEntityManager()
            ->getRepository(PluginConf::class)
            ->findForPluginAndKey($pluginId, 'ziel_woche');
        $this->assertInstanceOf(PluginConf::class, $goal);
        $this->assertSame('100', $goal->getValue());

        $hiddenMonth = $this->getEntityManager()
            ->getRepository(PluginConf::class)
            ->findForPluginAndKey($pluginId, 'ziel_show_mon');
        $this->assertInstanceOf(PluginConf::class, $hiddenMonth);
        $this->assertSame('0', $hiddenMonth->getValue());

        $this->request('GET', '/my/plugin/' . $pluginId);
        $this->assertStatusCode(200, $this->Client);
        $html = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('id="bunny_woche"', $html);
        $this->assertStringNotContainsString('id="bunny_mon"', $html);
        $this->assertStringContainsString('Pace', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testEquipmentConfigurationPersistsSelectedTypeWithoutLegacyPanel(): void
    {
        $plugin = new Plugin()
            ->setAccount($this->Account)
            ->setKey('RunalyzePluginPanel_Equipment')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(1);

        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();

        $equipmentType = new EquipmentType()
            ->setAccount($this->Account)
            ->setName('Shoes');
        $this->getEntityManager()->persist($equipmentType);
        $this->getEntityManager()->flush();

        $pluginId = (int) $plugin->getId();
        $typeId = (int) $equipmentType->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('name="type"', (string) $this->Client->getResponse()->getContent());

        $this->Client->request('POST', '/call/call.Plugin.config.php?id=' . $pluginId, [
            'edit' => 'true',
            'type' => (string) $typeId,
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());

        $saved = $this->getEntityManager()->getRepository(PluginConf::class)
            ->findForPluginAndKey($pluginId, 'type');
        $this->assertInstanceOf(PluginConf::class, $saved);
        $this->assertSame((string) $typeId, $saved->getValue());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId . '&active=0');
        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $reloaded = $this->getEntityManager()->getRepository(Plugin::class)->find($pluginId);
        $this->assertInstanceOf(Plugin::class, $reloaded);
        $this->assertSame(Plugin::STATE_INACTIVE, $reloaded->getActive());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId . '&active=1');
        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $reloaded = $this->getEntityManager()->getRepository(Plugin::class)->find($pluginId);
        $this->assertInstanceOf(Plugin::class, $reloaded);
        $this->assertSame(Plugin::STATE_ACTIVE, $reloaded->getActive());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testSportlerConfigurationWorksWithoutLegacyPanelClass(): void
    {
        $repository = $this->getEntityManager()->getRepository(Plugin::class);
        $plugin = $repository->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Sportler',
        ]);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $pluginId = (int) $plugin->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId);

        $this->assertStatusCode(200, $this->Client);
        $content = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('name="wunschgewicht"', $content);
        $this->assertStringContainsString('name="plot_points"', $content);
        $this->assertStringContainsString('name="plot_timerange"', $content);
        $this->assertStringContainsString('<div class="w100 block">', $content);

        $this->Client->request('POST', '/call/call.Plugin.config.php?id=' . $pluginId, [
            'edit' => 'true',
            'wunschgewicht' => '75',
            'plot_points' => '12',
            'plot_timerange' => '30',
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());

        foreach ([
            'wunschgewicht' => '75',
            'plot_points' => '12',
            'plot_timerange' => '30',
        ] as $key => $expected) {
            $setting = $this->getEntityManager()->getRepository(PluginConf::class)
                ->findForPluginAndKey($pluginId, $key);
            $this->assertInstanceOf(PluginConf::class, $setting);
            $this->assertSame($expected, $setting->getValue());
        }

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId . '&active=0');
        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $inactive = $this->getEntityManager()->getRepository(Plugin::class)->find($pluginId);
        $this->assertInstanceOf(Plugin::class, $inactive);
        $this->assertSame(Plugin::STATE_INACTIVE, $inactive->getActive());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $pluginId . '&active=1');
        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $active = $this->getEntityManager()->getRepository(Plugin::class)->find($pluginId);
        $this->assertInstanceOf(Plugin::class, $active);
        $this->assertSame(Plugin::STATE_ACTIVE, $active->getActive());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testPrognoseSettingsUseModernConfigurationWithoutBreakingLegacyWindows(): void
    {
        $plugin = $this->getEntityManager()->getRepository(Plugin::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginPanel_Prognose',
        ]);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $id = (int) $plugin->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/call/call.Plugin.config.php?id=' . $id);

        $this->assertStatusCode(200, $this->Client);
        $content = (string) $this->Client->getResponse()->getContent();
        $this->assertStringContainsString('name="distances"', $content);
        $this->assertStringContainsString('name="model"', $content);
        $this->assertStringContainsString('name="use_be"', $content);
        $this->assertStringNotContainsString('VO&lt;sub&gt;2&lt;/sub&gt;max', $content);
        $this->assertStringContainsString('<div class="w100 block">', $content);

        $this->Client->request('POST', '/call/call.Plugin.config.php?id=' . $id, [
            'edit' => 'true',
            'distances' => '5, 10, 21.1',
            'model' => 'cpp',
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());

        foreach ([
            'distances' => '5,10,21.1',
            'model' => 'cpp',
            'use_be' => '0',
        ] as $key => $expected) {
            $setting = $this->getEntityManager()->getRepository(PluginConf::class)
                ->findForPluginAndKey($id, $key);
            $this->assertInstanceOf(PluginConf::class, $setting);
            $this->assertSame($expected, $setting->getValue());
        }

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $id . '&active=0');
        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $disabled = $this->getEntityManager()->getRepository(Plugin::class)->find($id);
        $this->assertInstanceOf(Plugin::class, $disabled);
        $this->assertSame(Plugin::STATE_INACTIVE, $disabled->getActive());

        $this->request('GET', '/call/call.Plugin.config.php?id=' . $id . '&active=1');
        $this->assertStatusCode(200, $this->Client);
        $this->getEntityManager()->clear();
        $enabled = $this->getEntityManager()->getRepository(Plugin::class)->find($id);
        $this->assertInstanceOf(Plugin::class, $enabled);
        $this->assertSame(Plugin::STATE_ACTIVE, $enabled->getActive());
    }

    public function testPluginDisplayActionRequiresAuthentication(): void
    {
        $this->request('GET', '/my/plugin/999999999');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPluginDisplayActionShowsNotFoundMessageForAnUnknownPluginId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/my/plugin/999999999');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('could not be found', (string) $this->Client->getResponse()->getContent());
    }
}
