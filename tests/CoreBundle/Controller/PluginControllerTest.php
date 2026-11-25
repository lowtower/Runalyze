<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
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
    public function testElevationStatisticsUsesActivityTotalsAndRanksRoutesAcrossPeriods(): void
    {
        $entityManager = $this->getEntityManager();
        $otherAccount = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $this->assertInstanceOf(Account::class, $account);
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Hoehenmeter',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);

        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $otherSport = $sports->findInternalIdFor(SportProfile::RUNNING, $otherAccount);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $this->assertInstanceOf(Sport::class, $otherSport);

        $steep = new Route()->setAccount($account)->setName('Short & <Steep>')->setDistance(2.0)->setAltitudeBarometric(true);
        $highest = new Route()->setAccount($account)->setName('Long climb')->setDistance(20.0)->setAltitudeBarometric(true);
        $zeroDistance = new Route()->setAccount($account)->setName('Zero distance')->setAltitudeBarometric(true);
        $entityManager->persist($steep);
        $entityManager->persist($highest);
        $entityManager->persist($zeroDistance);

        $steep->setElevation(300);
        $highest->setElevation(1000);
        $zeroDistance->setElevation(250);

        foreach ([
            [$account, $running, '2025-01-05 23:30:00', 100, $steep, 'Hill & <repeat>', 12.5],
            [$account, $running, '2025-01-06 12:00:00', 400, $highest, null, 11.0],
            [$account, $running, '2025-01-07 12:00:00', 175, $zeroDistance, null, null],
            [$account, $running, '2025-01-08 12:00:00', 75, null, 'Manual climb', 19.9],
            [$account, $running, '2024-12-31 23:59:59', 50, null, null, null],
            [$account, $cycling, '2025-01-09 12:00:00', 600, null, 'Cycling climb', 18.0],
            [$otherAccount, $otherSport, '2025-01-10 12:00:00', 900, null, 'Other account climb', 80.0],
            [$account, $running, '2025-01-11 12:00:00', 0, null, 'Flat session', 99.9],
        ] as [$owner, $sport, $time, $meters, $route, $title, $score]) {
            $activity = new Training()
                ->setAccount($owner)
                ->setSport($sport)
                ->setTime(strtotime($time . ' UTC'))
                ->setS(3600)
                ->setElevation($meters)
                ->setRoute($route)
                ->setTitle($title);
            $entityManager->persist($activity);
            $activity->setClimbScore($score);
        }

        $entityManager->flush();
        $this->loginClient($this->Client, $account, 'default');
        $id = $plugin->getId();
        $display = '/my/plugin/' . $id;
        $page = $this->Client->request('GET', '/dashboard?pluginid=' . $id);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Elevation: ' . $running->getName() . ', All years', (string) $this->Client->getResponse()->getContent());

        $monthly = $page->filter('.statistics-container > table tbody tr');
        $this->assertCount(2, $monthly);
        $january = $monthly->eq(1)->filter('td')->eq(1)->filter('a');
        $this->assertSame("750\u{00a0}m", $january->text());
        $this->assertSame("750\u{00a0}m", $monthly->eq(1)->filter('td')->last()->text());
        parse_str((string) parse_url((string) $january->attr('href'), PHP_URL_QUERY), $search);
        $this->assertSame('01.01.2025', $search['date-from']);
        $this->assertSame('31.01.2025', $search['date-to']);
        $this->assertSame('elevation', $search['search-sort-by']);

        $highestRow = $page->filter('#elevation-ranking-elevation tbody tr')->first();
        $this->assertSame('Long climb', $highestRow->filter('td')->eq(2)->text());
        $this->assertStringContainsString("1.000\u{00a0}m", $highestRow->filter('td')->eq(3)->text());
        $steepestRow = $page->filter('#elevation-ranking-grade tbody tr')->first();
        $this->assertSame('Short & <Steep> (Hill & <repeat>)', $steepestRow->filter('td')->eq(2)->text());
        $this->assertStringContainsString("15\u{00a0}%", $steepestRow->filter('td')->eq(3)->text());
        $weekLink = $steepestRow->filter('a[rel=week-link]');
        $this->assertSame('05.01.2025', $weekLink->text());
        $this->assertSame('data-browser-inner', $weekLink->attr('target'));
        parse_str((string) parse_url((string) $weekLink->attr('href'), PHP_URL_QUERY), $week);
        $this->assertSame((string) strtotime('2024-12-30 UTC'), $week['start']);
        $this->assertSame((string) strtotime('2025-01-05 23:59:50 UTC'), $week['end']);
        $scoreRow = $page->filter('#elevation-ranking-cs tbody tr')->first();
        $this->assertSame('Manual climb', $scoreRow->filter('td')->eq(2)->text());
        $this->assertSame('–', $scoreRow->filter('td')->eq(3)->text());
        $this->assertSame('19.9', $scoreRow->filter('td')->eq(4)->text());
        $this->assertStringEndsWith('/climb-score', (string) $scoreRow->filter('a.window')->attr('href'));

        $filtered = $this->Client->xmlHttpRequest('GET', $display . '?sport=-1&jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(0, $filtered->filter('.statistics-container > table'));
        $this->assertCount(5, $filtered->filter('#elevation-ranking-elevation tbody tr'));
        $this->assertStringContainsString('Cycling climb', $filtered->filter('#elevation-rankings')->text());
        $this->assertStringNotContainsString('Other account climb', $filtered->filter('#elevation-rankings')->text());
        $this->assertStringNotContainsString('Flat session', $filtered->filter('#elevation-rankings')->text());

        $lastSixMonths = $this->Client->xmlHttpRequest('GET', $display . '?sport=-1&jahr=6');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Elevation: Last 6 months', $lastSixMonths->filter('.panel-heading h1')->text());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testElevationStatisticsRendersEmptyStatesAndTheAccountsUnits(): void
    {
        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginStat_Hoehenmeter',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);

        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $this->Account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $this->Account);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $entityManager->persist(new Conf()->setAccount($this->Account)->setCategory('general')->setKey('DISTANCE_UNIT_SYSTEM')->setValue('imperial'));
        $entityManager->persist(new Training()->setAccount($this->Account)->setSport($running)->setTime(strtotime('2025-06-01 UTC'))->setS(3600)->setElevation(100));
        $entityManager->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $display = '/my/plugin/' . $plugin->getId();

        $empty = $this->Client->xmlHttpRequest('GET', $display . '?sport=' . $cycling->getId());
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(4, $empty->filter('.statistics-container tbody em'));
        $this->assertSame('No routes found.', $empty->filter('.statistics-container > table tbody em')->text());

        $imperial = $this->Client->xmlHttpRequest('GET', $display . '?sport=-1');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame("328\u{00a0}ft", $imperial->filter('.statistics-container > table tbody a')->text());
        $this->assertSame('unlabeled', $imperial->filter('#elevation-ranking-elevation tbody td')->eq(2)->text());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testRecordStatisticsRanksEachSportAndTotalsConfiguredRunningDistance(): void
    {
        $entityManager = $this->getEntityManager();
        $otherAccount = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $this->assertInstanceOf(Account::class, $account);
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Rekorde',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);

        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $otherSport = $sports->findInternalIdFor(SportProfile::RUNNING, $otherAccount);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $this->assertInstanceOf(Sport::class, $otherSport);

        $year = (int) gmdate('Y');
        $activities = [];
        foreach ([
            ['longer_fast', $account, $running, $year . '-01-05', 10.0, 2400],
            ['shorter_fast', $account, $running, $year . '-01-06', 5.0, 1200],
            ['longest', $account, $running, $year . '-01-07', 18.0, 7200],
            ['duration_only', $account, $running, $year . '-01-08', 0.0, 1800],
            ['cycling', $account, $cycling, $year . '-01-09', 50.0, 6000],
            ['previous_year', $account, $running, ($year - 1) . '-12-31', 40.0, 7200],
            ['other_account', $otherAccount, $otherSport, $year . '-01-09', 100.0, 3600],
        ] as [$key, $owner, $sport, $date, $distance, $duration]) {
            $activity = new Training()
                ->setAccount($owner)
                ->setSport($sport)
                ->setTime(strtotime($date . ' 12:00:00 UTC'))
                ->setDistance($distance)
                ->setS($duration);
            $entityManager->persist($activity);
            $activities[$key] = $activity;
        }

        $entityManager->flush();

        $this->loginClient($this->Client, $account, 'default');
        $id = $plugin->getId();
        $view = $this->Client->request('GET', '/dashboard?pluginid=' . $id);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Records: ' . $year, $view->filter('#statistics-inner .panel-heading h1')->text());
        $tables = $view->filter('.statistics-container table');
        $this->assertSame(
            ['Fastest activities', 'Longest activities', 'Most kilometers'],
            $tables->filter('thead th')->each(static fn ($node): string => $node->text())
        );
        $fastest = $tables->eq(0)->filter('tbody tr')->first()->filter('a.training');
        $this->assertSame(
            array_map(static fn (string $key): string => (string) $activities[$key]->getId(), ['longer_fast', 'shorter_fast', 'longest']),
            $fastest->each(static fn ($node): ?string => $node->attr('rel'))
        );
        $this->assertSame('4:00/km', $fastest->first()->text());
        $this->assertSame('/activity/' . $activities['longer_fast']->getId(), $fastest->first()->attr('href'));
        $this->assertSame((string) $activities['cycling']->getId(), $tables->eq(0)->filter('tbody tr')->eq(1)->filter('a.training')->attr('rel'));

        $longest = $tables->eq(1)->filter('tbody tr')->first()->filter('a.training');
        $this->assertSame((string) $activities['longest']->getId(), $longest->first()->attr('rel'));
        $this->assertSame('30:00', $longest->last()->text());
        $this->assertCount(0, $tables->filter('a[rel=year-link]'));
        $this->assertSame("33,000\u{00a0}km", $tables->filter('a[rel=month-link]')->first()->text());

        $allYears = $view->filter('.panel-menu')->selectLink('All years');
        $this->assertSame('statistics-inner', $allYears->attr('target'));
        $all = $this->Client->xmlHttpRequest('GET', (string) $allYears->attr('href'));
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame("40,000\u{00a0}km", $all->filter('.statistics-container a[rel=year-link]')->first()->text());
        $this->assertCount(0, $all->filter('.statistics-container .training[rel="' . $activities['other_account']->getId() . '"]'));

        $empty = $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?jahr=' . ($year - 2));
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(3, $empty->filter('.statistics-container tbody em'));
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testRecordStatisticsGroupsSundayWeeksAcrossNewYear(): void
    {
        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $this->Account,
            'key' => 'RunalyzePluginStat_Rekorde',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $this->Account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $this->Account);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $entityManager->persist(new Conf()->setAccount($this->Account)->setCategory('general')->setKey('WEEK_START')->setValue('0'));

        $runningSport = $entityManager->getRepository(Conf::class)->findOneBy([
            'account' => $this->Account,
            'category' => 'general',
            'key' => 'RUNNINGSPORT',
        ]);
        $this->assertInstanceOf(Conf::class, $runningSport);
        $runningSport->setValue((string) $cycling->getId());

        foreach ([
            [$cycling, '2024-12-28 23:59:59', 6.0],
            [$cycling, '2024-12-29 00:00:00', 10.0],
            [$cycling, '2025-01-01 00:00:00', 11.0],
            [$running, '2025-01-01 12:00:00', 28.0],
        ] as [$sport, $time, $distance]) {
            $entityManager->persist(new Training()
                ->setAccount($this->Account)
                ->setSport($sport)
                ->setTime(strtotime($time . ' UTC'))
                ->setDistance($distance)
                ->setS(3600));
        }

        $entityManager->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $display = '/my/plugin/' . $plugin->getId();
        $allYears = $this->Client->xmlHttpRequest('GET', $display . '?jahr=-1');
        $this->assertStatusCode(200, $this->Client);
        $week = $allYears->filter('.statistics-container a[rel=week-link]')->first();
        $this->assertSame("21,000\u{00a0}km", $week->text());
        $this->assertSame('Week 1 2025', $week->closest('span')->attr('title'));
        $this->assertSame('data-browser-inner', $week->attr('target'));
        parse_str((string) parse_url((string) $week->attr('href'), PHP_URL_QUERY), $weekBounds);
        $this->assertSame((string) strtotime('2024-12-29 UTC'), $weekBounds['start']);
        $this->assertSame((string) strtotime('2025-01-04 23:59:50 UTC'), $weekBounds['end']);

        $calendarYear = $this->Client->xmlHttpRequest('GET', $display . '?jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame("11,000\u{00a0}km", $calendarYear->filter('.statistics-container a[rel=week-link]')->text());
        $this->assertCount(0, $calendarYear->filter('.statistics-container a[rel=year-link]'));
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testTrainingTimesRendersTheChartsAndPersistsTheNightlyActivitiesSetting(): void
    {
        $entityManager = $this->getEntityManager();
        $otherAccount = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $this->assertInstanceOf(Account::class, $account);
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Trainingszeiten',
        ]);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $otherSport = $sports->findInternalIdFor(SportProfile::RUNNING, $otherAccount);
        $this->assertInstanceOf(Sport::class, $running);
        $this->assertInstanceOf(Sport::class, $cycling);
        $this->assertInstanceOf(Sport::class, $otherSport);
        $cycling->setShort(true);
        $cycling->setName('Ride & <Hill>');

        foreach ([
            [$account, $running, '2025-01-06 23:30:00', 3600],
            [$account, $running, '2025-01-07 00:00:45', 1800],
            [$account, $running, '2025-01-08 02:00:00', 1800],
            [$account, $cycling, '2025-01-08 02:10:00', 900],
            [$account, $running, '2024-12-31 01:00:00', 3600],
            [$otherAccount, $otherSport, '2025-01-08 03:15:00', 3600],
        ] as [$owner, $sport, $startsAt, $duration]) {
            $entityManager->persist(new Training()
                ->setAccount($owner)
                ->setSport($sport)
                ->setTime(strtotime($startsAt . ' UTC'))
                ->setS($duration));
        }

        $entityManager->flush();
        $id = $plugin->getId();
        $this->loginClient($this->Client, $account, 'default');
        $dashboard = $this->Client->request('GET', '/dashboard?pluginid=' . $id . '&jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Training times: 2025', $dashboard->filter('#statistics-inner .panel-heading h1')->text());
        $this->assertCount(2, $dashboard->filter('#daytime, #weekday'));
        $html = (string) $this->Client->getResponse()->getContent();
        $daytime = $this->plotData($html, 'daytime');
        $weekday = $this->plotData($html, 'weekday');
        $runningHours = $daytime[$running->getName()];
        $this->assertSame([0, 0.5], $runningHours[0]);
        $this->assertSame([23, 0.5], $runningHours[23]);
        $this->assertEqualsWithDelta(1.5, array_sum(array_column($runningHours, 1)), PHP_FLOAT_EPSILON);
        $this->assertSame([[0, 1], [1, 0.5], [2, 0.5]], array_slice($weekday[$running->getName()], 0, 3));
        $this->assertSame([2, 0.25], $daytime['Ride &amp; &lt;Hill&gt;'][2]);
        $this->assertSame(['23:30', '02:00'], $dashboard->filter('.statistics-container tbody td.b')->each(static fn ($node): string => $node->text()));
        $this->assertCount(1, $dashboard->filter('.statistics-container tbody tr'));
        $this->assertSame('data-browser-inner', $dashboard->filter('.statistics-container a[rel=week-link]')->first()->attr('target'));

        $display = '/my/plugin/' . $id . '?jahr=2025&sport=' . $cycling->getId();
        $shortSport = $this->Client->xmlHttpRequest('GET', $display);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('02:10', $shortSport->filter('.statistics-container tbody td.b')->text());
        $this->assertCount(1, $this->plotData((string) $this->Client->getResponse()->getContent(), 'daytime'));

        $config = '/call/call.Plugin.config.php?id=' . $id;
        $configuration = $this->Client->xmlHttpRequest('GET', $config);
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $configuration->filter('input[name=show_extreme_times][checked]'));
        $this->Client->xmlHttpRequest('POST', $config, [
            'edit' => 'true',
        ]);
        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.reload', (string) $this->Client->getResponse()->getContent());
        $settings = $entityManager->getRepository(PluginConf::class);
        $this->assertSame('0', $settings->findValuesForPlugins([$id])[$id]['show_extreme_times']);

        $withoutNightList = $this->Client->xmlHttpRequest('GET', $display);
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(0, $withoutNightList->filter('.statistics-container table'));
        $this->assertStringContainsString('RunalyzePlot.addPlot("daytime"', (string) $this->Client->getResponse()->getContent());
        $this->Client->xmlHttpRequest('POST', $config, [
            'edit' => 'true',
            'show_extreme_times' => '1',
        ]);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('1', $settings->findValuesForPlugins([$id])[$id]['show_extreme_times']);

        $empty = $this->Client->xmlHttpRequest('GET', '/my/plugin/' . $id . '?jahr=2023');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(0, $empty->filter('.statistics-container table'));
        $this->assertSame(2, substr_count((string) $this->Client->getResponse()->getContent(), 'No data available.'));
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testAnalysisUsesExistingSettingsAndAggregatesAccountActivities(): void
    {
        $entityManager = $this->getEntityManager();
        $otherAccount = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Analyse',
        ]);
        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $otherRunning = $sports->findInternalIdFor(SportProfile::RUNNING, $otherAccount);
        $type = new Type()->setAccount($account)->setSport($running)->setName('Run & <fast>')->setAbbr('Fast')->setHrAvg(80);
        $entityManager->persist($type);
        $entityManager->persist(new Conf()->setAccount($account)->setCategory('data')->setKey('HF_MAX')->setValue('200'));
        foreach ([
            'lowest_pacegroup' => '435',
            'lowest_pulsegroup' => '68',
            'pulsegroup_step' => '6',
        ] as $key => $value) {
            $entityManager->persist(new PluginConf()->setPlugin($plugin)->setConfig($key)->setValue($value));
        }

        foreach ([
            [$account, $running, '2024-12-31 23:59:59', 600, 2, 0, null],
            [$account, $running, '2025-01-01 00:00:00', 600, 2, 150, $type],
            [$account, $running, '2025-01-31 23:59:59', 900, 2, 130, null],
            [$account, $running, '2025-02-01 00:00:00', 420, 1, 150, $type],
            [$account, $cycling, '2025-01-31 04:15:00', 800, 10, 140, null],
            [$account, $cycling, '2025-01-31 05:15:00', 1000, 10, 150, null],
            [$otherAccount, $otherRunning, '2025-01-31 03:15:00', 3000, 10, 175, null],
        ] as [$owner, $sport, $startsAt, $duration, $distance, $heartRate, $activityType]) {
            $entityManager->persist(new Training()
                ->setAccount($owner)
                ->setSport($sport)
                ->setTime(strtotime($startsAt . ' UTC'))
                ->setS($duration)
                ->setDistance($distance)
                ->setPulseAvg($heartRate)
                ->setType($activityType));
        }

        $entityManager->flush();

        $id = $plugin->getId();
        $this->loginClient($this->Client, $account, 'default');
        $dashboard = $this->Client->request('GET', '/dashboard?pluginid=' . $id . '&jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame($running->getName() . ' 2025', $dashboard->filter('#statistics-inner h1')->text());
        $tables = $dashboard->filter('.analysis-table');
        $this->assertSame(['Training types', 'Pace Zones*', 'Heart Rate Zones*'], $tables->filter('th:first-child')->each(static fn ($node): string => $node->text()));
        $this->assertSame('Run & <fast>', $tables->eq(0)->filter('td[title="Run & <fast>"]')->attr('title'));
        $this->assertSame('40.0 %', $tables->eq(0)->filter('td[title="Run & <fast>"] + td span')->text());
        $this->assertSame('width:40%;opacity:0.4;', $tables->eq(0)->filter('td[title="Run & <fast>"] + td .analysis-bar')->attr('style'));
        $this->assertStringContainsString('up to 7:15', $tables->eq(1)->filter('tbody tr')->first()->text());
        $pulseRows = $tables->eq(2)->filter('tbody tr')->each(static fn ($node): array => [
            $node->filter('td')->first()->text(),
            $node->filter('td span')->each(static fn ($cell): string => $cell->text()),
        ]);
        $this->assertContains(['0 to 68 %', ['60.0 %', '46.9 %']], $pulseRows);
        $this->assertContains(['74 to 80 %', ['40.0 %', '100.0 %', '53.1 %']], $pulseRows);

        $display = '/my/plugin/' . $id;
        $distances = $this->Client->xmlHttpRequest('GET', $display . '?jahr=2025&sport=' . $running->getId() . '&dat=km');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame("2\u{00a0}km", $distances->filter('td[title="Run & <fast>"] + td span')->text());
        $this->assertSame('width:50%;opacity:0.5;', $distances->filter('td[title="Run & <fast>"] + td .analysis-bar')->attr('style'));
        $this->assertStringContainsString('dat=km', $distances->selectLink('Year on year')->link()->getUri());
        $cyclingAnalysis = $this->Client->xmlHttpRequest('GET', $distances->selectLink($cycling->getName())->link()->getUri());
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame(
            ['1x, 16:40', '1x, 13:20'],
            $cyclingAnalysis->filter('.analysis-table')->eq(1)->filter('tbody td:nth-child(2) span')->each(static fn ($node): ?string => $node->attr('title'))
        );

        $years = $this->Client->xmlHttpRequest('GET', $display . '?jahr=-1&dat=s');
        $this->assertStatusCode(200, $this->Client);
        $headers = $years->filter('.analysis-table')->first()->filter('thead th');
        $this->assertSame(['2024', '2025'], $headers->slice(1, 2)->each(static fn ($node): string => $node->text()));
        $this->assertSame('10:00', $years->filter('.analysis-table')->first()->filter('tbody tr')->first()->filter('td')->eq(1)->text());

        $config = '/call/call.Plugin.config.php?id=' . $id;
        $configuration = $this->Client->xmlHttpRequest('GET', $config);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('435', $configuration->filter('input[name=lowest_pacegroup]')->attr('value'));
        $this->assertCount(3, $configuration->filter('input[type=checkbox][checked]'));
        $this->Client->xmlHttpRequest('POST', $config, [
            'edit' => 'true',
            'use_pace' => '1',
            'lowest_pacegroup' => '420',
            'pacegroup_step' => '0',
        ]);
        $this->assertStatusCode(200, $this->Client);
        $settings = $entityManager->getRepository(PluginConf::class)->findValuesForPlugins([$id])[$id];
        $this->assertSame(['420', '0', '68', '0', '0', '1'], [
            $settings['lowest_pacegroup'], $settings['pacegroup_step'], $settings['lowest_pulsegroup'],
            $settings['use_type'], $settings['use_pulse'], $settings['use_pace'],
        ]);
        $paceOnly = $this->Client->xmlHttpRequest('GET', $display . '?jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Pace Zones*', $paceOnly->filter('.analysis-table th:first-child')->text());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testAnalysisRollingWindowWithHeartRateReserveAndWithoutDistances(): void
    {
        $account = $this->Account->setTimezone(Timezone::UTC);
        $entityManager = $this->getEntityManager();
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Analyse',
        ]);
        $sport = $entityManager->getRepository(Sport::class)->findInternalIdFor(SportProfile::CYCLING, $account);
        $sport->setDistances(false);
        foreach ([
            ['data', 'HF_MAX', '200'],
            ['data', 'HF_REST', '50'],
            ['general', 'HEART_RATE_UNIT', 'hfres'],
        ] as [$category, $key, $value]) {
            $entityManager->persist(new Conf()->setAccount($account)->setCategory($category)->setKey($key)->setValue($value));
        }

        $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
        $start = $today->modify('first day of -5 months');
        foreach ([
            [$start, 1200],
            [$start->modify('+1 second'), 900],
            [$today, 600],
        ] as [$date, $duration]) {
            $entityManager->persist(new Training()
                ->setAccount($account)
                ->setSport($sport)
                ->setTime($date->getTimestamp())
                ->setS($duration)
                ->setPulseAvg(155));
        }

        $entityManager->flush();
        $this->loginClient($this->Client, $account, 'default');
        $url = '/my/plugin/' . $plugin->getId() . '?sport=' . $sport->getId();
        $analysis = $this->Client->xmlHttpRequest('GET', $url . '&jahr=6&dat=km');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(0, $analysis->selectLink('by distance'));
        $this->assertCount(2, $analysis->filter('.analysis-table'));
        $pulseTable = $analysis->filter('.analysis-table')->eq(1);
        $this->assertCount(14, $pulseTable->filter('thead th'));
        $this->assertSame($start->format('M'), $pulseTable->filter('thead th')->eq(1)->text());
        $this->assertSame($today->format('M'), $pulseTable->filter('thead th')->eq(6)->text());
        $rows = $pulseTable->filter('tbody tr')->reduce(static fn ($node): bool => $node->filter('td')->first()->text() === '65 to 70 %');
        $this->assertSame('1x, 15:00', $rows->filter('td')->eq(1)->filter('span')->attr('title'));
        $this->assertSame('1x, 10:00', $rows->filter('td')->eq(6)->filter('span')->attr('title'));
        $this->assertSame('2x, 25:00', $rows->filter('td')->last()->filter('span')->attr('title'));

        $empty = $this->Client->xmlHttpRequest('GET', $url . '&jahr=2000&dat=invalid');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame(
            ['No data available.', 'No data available.'],
            $empty->filter('.analysis-table em')->each(static fn ($node): string => $node->text())
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testRouteStatisticsListsOwnedRoutesAndPreservesTheCitySetting(): void
    {
        $entityManager = $this->getEntityManager();
        $other = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Strecken',
        ]);
        $plugin->setActive(Plugin::STATE_ACTIVE);

        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $foreignSport = $sports->findInternalIdFor(SportProfile::RUNNING, $other);
        $route = new Route()->setAccount($account)->setName('City & <Park> - Potsdam');
        $past = new Route()->setAccount($account)->setName('Potsdam - Lake');
        $private = new Route()->setAccount($other)->setName('Private town');
        $entityManager->persist(new Route()->setAccount($account)->setName('Saved town'));
        $entityManager->persist(new Route()->setAccount($account)->setName('Lake - Lake - Lake - Lake'));
        $entityManager->persist(new PluginConf()->setPlugin($plugin)->setConfig('analyze_cities')->setValue('1'));

        foreach ([
            [$account, $running, $route, '2025-01-01 00:00:00', 5.0],
            [$account, $running, $route, '2025-01-02 00:00:00', 7.0],
            [$account, $cycling, $past, '2024-12-31 23:59:59', 30.0],
            [$other, $foreignSport, $private, '2025-01-01 00:00:00', 3.0],
        ] as [$owner, $sport, $course, $date, $distance]) {
            $entityManager->persist($course);
            $entityManager->persist(new Training()->setAccount($owner)->setSport($sport)->setRoute($course)
                ->setTime(strtotime($date . ' UTC'))->setS(1800)->setDistance($distance));
        }

        $entityManager->flush();

        $id = $plugin->getId();
        $this->loginClient($this->Client, $account, 'default');
        $dashboard = $this->Client->request('GET', '/dashboard?pluginid=' . $id . '&jahr=2025&sport=' . $running->getId());
        $this->assertStatusCode(200, $this->Client);
        $tables = $dashboard->filter('#statistics-inner .statistics-container table');
        $this->assertCount(3, $tables);
        $this->assertSame('2x', $tables->first()->filter('tbody td')->eq(0)->text());
        $this->assertSame("12.00\u{00a0}km", $tables->first()->filter('tbody td')->eq(2)->text());
        $search = $tables->first()->selectLink('City & <Park> - Potsdam')->link()->getUri();
        parse_str((string) parse_url($search, PHP_URL_QUERY), $parameters);
        $this->assertSame('City & <Park> - Potsdam', $parameters['route']);
        $this->assertSame(['2x', 'City & <Park>', '2x', 'Potsdam'], $tables->eq(1)->filter('tbody td')->each(static fn ($cell): string => $cell->text()));
        $networkLink = $dashboard->selectLink('Open route network')->link()->getUri();
        $this->assertStringContainsString('/plugin/RunalyzePluginStat_Strecken/window.routenet.php', $networkLink);
        parse_str((string) parse_url($networkLink, PHP_URL_QUERY), $parameters);
        $this->assertSame([
            'sport' => (string) $running->getId(),
            'y' => '2025',
        ], $parameters);

        $display = '/my/plugin/' . $id;
        $all = $this->Client->xmlHttpRequest('GET', $display . '?jahr=-1');
        $this->assertStatusCode(200, $this->Client);
        $places = $all->filter('.statistics-container table')->eq(1)->filter('tbody td')->each(static fn ($cell): string => $cell->text());
        $this->assertSame(['5x', 'Lake', '2x', 'Potsdam', '1x', 'City & <Park>', '1x', 'Saved town'], $places);
        $this->assertStringNotContainsString('Private town', (string) $this->Client->getResponse()->getContent());
        $rare = $all->filter('.statistics-container table')->eq(2);
        $this->assertSame(['City & <Park>', 'Saved town'], $rare->filter('tbody tr')->first()->filter('a')->each(static fn ($link): string => $link->text()));
        $this->assertCount(0, $rare->selectLink('Lake'));
        $this->assertSame('You have visited 4 different places.', $rare->filter('.no-zebra')->text());

        $config = '/call/call.Plugin.config.php?id=' . $id;
        $settings = $this->Client->xmlHttpRequest('GET', $config);
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $settings->filter('input[name=analyze_cities][checked]'));
        $this->Client->xmlHttpRequest('POST', $config, [
            'edit' => 'true',
        ]);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('0', $entityManager->getRepository(PluginConf::class)->findValuesForPlugins([$id])[$id]['analyze_cities']);
        $routesOnly = $this->Client->xmlHttpRequest('GET', $display . '?jahr=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $routesOnly->filter('.statistics-container table'));
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testRouteNetworkUsesTheOriginalUrlAndFiltersAndDrawsOnlyOwnedTracks(): void
    {
        $entityManager = $this->getEntityManager();
        $other = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $foreignSport = $sports->findInternalIdFor(SportProfile::RUNNING, $other);
        $route = new Route()->setName('Hill <trail>')->setLatitudesAndLongitudes(
            [52.50, 52.501, 52.502, 52.503, 52.504, 52.505, 52.506],
            [13.30, 13.301, 13.302, 13.303, 13.304, 13.305, 13.306]
        );
        $entityManager->persist(new Conf()->setAccount($account)->setCategory('activity-view')->setKey('GMAP_PATH_PRECISION')->setValue('1'));
        $entityManager->persist(new Conf()->setAccount($account)->setCategory('activity-view')->setKey('GMAP_PATH_BREAK')->setValue('no'));

        $activities = [];
        foreach ([
            [$account, $running, $route, '2025-05-01'],
            [$account, $running, $route, '2025-05-02'],
            [$account, $running, (clone $route)->setName('Last year'), '2024-05-02'],
            [$account, $cycling, (clone $route)->setName('Bike course'), '2025-05-02'],
            [$other, $foreignSport, (clone $route)->setName('Private waypoint'), '2025-05-03'],
            [$account, $running, new Route()->setName('No GPS'), '2025-05-04'],
        ] as [$owner, $sport, $course, $date]) {
            $activity = new Training()->setAccount($owner)->setSport($sport)->setRoute($course)
                ->setTime(strtotime($date . ' UTC'))->setS(1800)->setDistance(5.0);
            $entityManager->persist($course);
            $entityManager->persist($activity);
            $activities[] = $activity;
        }

        $entityManager->flush();
        $runningId = $running->getId();
        $url = '/plugin/RunalyzePluginStat_Strecken/window.routenet.php';
        $this->Client->request('GET', $url);
        $this->assertStatusCode(302, $this->Client);
        $this->loginClient($this->Client, $account, 'default');
        $window = $this->Client->xmlHttpRequest('GET', $url . '?y=2025&posint=2&sport=' . $runningId);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Route network', $window->filter('h1')->text());
        $this->assertCount(1, $window->filter('#map-routenet'));
        $this->assertStringContainsString('Positions 8.', $window->filter('.panel-content .info')->text());
        $this->assertStringContainsString('Limit 500.', $window->filter('.panel-content .info')->text());
        $this->assertSame(['2', $running->getName(), '2025'], $window->filter('.panel-menu li.active')->each(static fn ($item): string => $item->text()));
        $html = (string) $this->Client->getResponse()->getContent();
        preg_match_all('/RunalyzeLeaflet\.Routes\.addRoute\((\d+),/', $html, $ids);
        $this->assertSame([(string) $activities[1]->getId(), (string) $activities[0]->getId()], $ids[1]);
        $this->assertSame(1, preg_match('/\.fitBounds\((\[.*?\])\);/', $html, $bounds));
        [[, $west], [, $east]] = json_decode($bounds[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertGreaterThan($west, $east);
        $this->assertStringContainsString('Hill', $html);
        $this->assertStringNotContainsString('<trail>', $html);
        $this->assertStringNotContainsString('Private waypoint', $html);

        $direct = $this->Client->request('GET', $url . '?y=-1&posint=999');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $direct->filter('html #map-routenet'));
        $this->assertStringContainsString('Limit 1250.', $direct->filter('.panel-content .info')->text());
        $this->assertStringContainsString('Last year', (string) $this->Client->getResponse()->getContent());
        $this->assertStringContainsString('Bike course', (string) $this->Client->getResponse()->getContent());
        $empty = $this->Client->xmlHttpRequest('GET', $url . '?y=2000&posint=invalid');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(0, $empty->filter('#map-routenet'));
        $this->assertSame('There are no routes matching the criterias.', $empty->filter('.panel-content .error')->text());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testWeatherShowsMonthlyStatisticsAndPersistsOwnedEquipmentSelections(): void
    {
        $entityManager = $this->getEntityManager();
        $other = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $plugin = $entityManager->getRepository(Plugin::class)->findOneBy([
            'account' => $account,
            'key' => 'RunalyzePluginStat_Wetter',
        ]);
        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $cycling = $sports->findInternalIdFor(SportProfile::CYCLING, $account);
        $foreignRunning = $sports->findInternalIdFor(SportProfile::RUNNING, $other);
        $type = new EquipmentType()->setAccount($account)->setName('Winter & road');
        $foreignType = new EquipmentType()->setAccount($other)->setName('Private equipment');
        $shoes = new Equipment()->setAccount($account)->setType($type)->setName('Shoes <winter>');
        foreach ([$type, $foreignType, $shoes] as $entity) {
            $entityManager->persist($entity);
        }

        $activities = [];
        foreach ([
            [$account, $running, '2025-01-01 00:00:00', 0, 20, 45, WeatherConditionProfile::SUNNY, $shoes],
            [$account, $running, '2025-01-01 23:59:59', 10, 40, 80, WeatherConditionProfile::RAINY, $shoes],
            [$account, $running, '2025-02-01 00:00:00', -5, null, null, WeatherConditionProfile::SNOWING, $shoes],
            [$account, $running, '2025-02-04 00:00:00', null, null, null, WeatherConditionProfile::SUNNY, $shoes],
            [$account, $cycling, '2025-01-01 00:00:00', 35, 60, 90, WeatherConditionProfile::RAINY, null],
            [$account, $running, '2024-12-31 23:59:59', 25, null, null, WeatherConditionProfile::SUNNY, $shoes],
            [$other, $foreignRunning, '2025-01-01 00:00:00', 99, 90, 99, WeatherConditionProfile::SUNNY, null],
        ] as [$owner, $sport, $date, $temperature, $wind, $humidity, $condition, $equipment]) {
            $activity = new Training()->setAccount($owner)->setSport($sport)->setTime(strtotime($date . ' UTC'))
                ->setS(1800)->setTemperature($temperature)->setWindSpeed($wind)->setHumidity($humidity)->setWeatherid($condition);
            if ($equipment instanceof \Runalyze\Bundle\CoreBundle\Entity\Equipment) {
                $activity->addEquipment($equipment);
            }

            $entityManager->persist($activity);
            $activities[] = $activity;
        }

        $entityManager->flush();
        $typeId = (string) $type->getId();
        $entityManager->persist(new PluginConf()->setPlugin($plugin)->setConfig('equipment_type')->setValue($typeId));
        $entityManager->flush();

        $id = $plugin->getId();
        $this->loginClient($this->Client, $account, 'default');
        $dashboard = $this->Client->request('GET', '/dashboard?pluginid=' . $id . '&jahr=2025&sport=' . $running->getId());
        $this->assertStatusCode(200, $this->Client);
        $monthly = $dashboard->filter('#statistics-inner .statistics-weather-monthly');
        $this->assertSame(['°C', "5\u{00a0}°C", "-5\u{00a0}°C"], $monthly->filter('tbody tr')->first()->filter('td')->slice(0, 3)->each(static fn ($cell): string => $cell->text()));
        $sunny = $monthly->filter('tbody tr')->reduce(static fn ($row): bool => $row->filter('i.weather-sun')->count() > 0);
        $this->assertSame(['1x', '1x'], $sunny->filter('td')->slice(1, 2)->each(static fn ($cell): string => $cell->text()));
        $equipment = $monthly->filter('tbody tr')->last()->filter('td')->slice(0, 3)->each(static fn ($cell): string => $cell->text());
        $this->assertSame(['Shoes <winter>', '2x', '2x'], $equipment);
        $this->assertSame(
            ['Shoes <winter>', "-5\u{00a0}°C to 10\u{00a0}°C", "2\u{00a0}°C"],
            $dashboard->filter('.statistics-weather-equipment tbody td')->slice(0, 3)->each(static fn ($cell): string => $cell->text())
        );
        $extremes = $dashboard->filter('.statistics-weather-extremes a.training');
        $this->assertSame((string) $activities[1]->getId(), $extremes->first()->attr('rel'));
        $this->assertSame('01.01.2025', $extremes->first()->text());
        $this->assertStringNotContainsString('99', $dashboard->filter('.statistics-weather-extremes')->text());
        $this->assertSame('/plugin/RunalyzePluginStat_Wetter/window.php', $dashboard->filter('a[aria-label="Show temperature plots"]')->attr('href'));
        $this->assertCount(1, $dashboard->filter('#statistics-inner .panel-menu > ul'));
        $this->assertCount(4, $dashboard->filter('#statistics-inner .panel-menu > ul > li'));

        $display = '/my/plugin/' . $id . '?jahr=2025';
        $settings = $entityManager->getRepository(PluginConf::class);
        $this->Client->xmlHttpRequest('GET', $display . '&dat=' . $foreignType->getId());
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame($typeId, $settings->findValuesForPlugins([$id])[$id]['equipment_type']);
        $this->assertStringNotContainsString('Private equipment', (string) $this->Client->getResponse()->getContent());
        $cleared = $this->Client->xmlHttpRequest('GET', $display . '&dat=0');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(0, $cleared->filter('.statistics-weather-equipment'));
        $this->assertSame('0', $settings->findValuesForPlugins([$id])[$id]['equipment_type']);
        $this->Client->xmlHttpRequest('POST', '/call/call.Plugin.config.php?id=' . $id, [
            'edit' => 'true',
            'equipment_type' => $typeId,
        ]);
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame($typeId, $settings->findValuesForPlugins([$id])[$id]['equipment_type']);
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    public function testTemperatureWindowShowsAccountAveragesAndChangesYearAtTheOriginalUrl(): void
    {
        $entityManager = $this->getEntityManager();
        $other = $this->registerUniqueAccount();
        $account = $entityManager->find(Account::class, $this->Account->getId());
        $sports = $entityManager->getRepository(Sport::class);
        $running = $sports->findInternalIdFor(SportProfile::RUNNING, $account);
        $foreignRunning = $sports->findInternalIdFor(SportProfile::RUNNING, $other);
        $entityManager->persist(new Conf()->setAccount($account)->setCategory('general')->setKey('TEMPERATURE_UNIT')->setValue('°F'));
        foreach ([
            [$account, $running, '2025-01-01 08:00:00', 0],
            [$account, $running, '2025-01-01 23:59:59', 10],
            [$account, $running, '2025-01-02 00:00:00', 0],
            [$account, $running, '2024-12-31 23:59:59', 20],
            [$other, $foreignRunning, '2025-01-01 12:00:00', 95],
        ] as [$owner, $sport, $date, $temperature]) {
            $entityManager->persist(new Training()->setAccount($owner)->setSport($sport)->setTime(strtotime($date . ' UTC'))
                ->setS(1800)->setTemperature($temperature));
        }

        $entityManager->flush();

        $url = '/plugin/RunalyzePluginStat_Wetter/window.php';
        $this->Client->request('GET', $url);
        $this->assertStatusCode(302, $this->Client);
        $this->loginClient($this->Client, $account, 'default');
        $window = $this->Client->xmlHttpRequest('GET', $url . '?y=2025');
        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('Temperatures', $window->filter('h1')->text());
        $this->assertCount(2, $window->filter('#average, #year2025'));
        $html = (string) $this->Client->getResponse()->getContent();
        $daily = $this->plotData($html, 'year2025');
        $this->assertSame([[1735689600000, 41], [1735776000000, 32]], $daily['Temperatures 2025']);
        $average = $this->plotData($html, 'average');
        $this->assertSame([0, 38], $average[2025][0]);
        $this->assertSame([1, null], $average[2025][1]);
        $this->assertSame([11, 68], $average[2024][11]);
        $this->assertStringContainsString('"yaxis":{"from":32,"to":32}', $html);

        $previous = $window->selectLink('2024')->link()->getUri();
        $this->assertStringEndsWith('?y=2024', $previous);
        $direct = $this->Client->request('GET', $previous);
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $direct->filter('html #year2024'));
        $this->assertSame([[1735603200000, 68]], $this->plotData((string) $this->Client->getResponse()->getContent(), 'year2024')['Temperatures 2024']);
        $empty = $this->Client->xmlHttpRequest('GET', $url . '?y=2000');
        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $empty->filter('#year2000'));
        $this->assertStringContainsString('No data available.', (string) $this->Client->getResponse()->getContent());
    }

    /**
     * @return array<int|string, list<array{int, int|float|null}>>
     */
    private function plotData(string $html, string $plot): array
    {
        $this->assertSame(1, preg_match('/RunalyzePlot\.addPlot\("' . preg_quote($plot, '/') . '", (\[.*?\]), \{/', $html, $match));

        return array_column(json_decode($match[1], true, flags: JSON_THROW_ON_ERROR), 'data', 'label');
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
