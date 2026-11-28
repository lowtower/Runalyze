<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Cache;
use DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\ConfRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Dataset\Keys;
use Runalyze\Model\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class CallControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('call_test_' . uniqid('', true))
            ->setMail('call_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function tinyPngDataUri(): string
    {
        $rawPngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        return 'data:image/png;base64,' . base64_encode($rawPngBytes);
    }

    public function testDataBrowserActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/call.DataBrowser.display.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDataBrowserActionRendersForAuthenticatedUserWithoutLeakingASessionException(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/call.DataBrowser.display.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testDataBrowserActionRendersTheTwigTemplate(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/databrowser');

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()->getContent();

        $this->assertStringContainsString('id="data-browser-calendar"', $content);
        $this->assertStringContainsString('id="data-browser-container"', $content);
        $this->assertStringContainsString('zebra-style', $content);
        $this->assertStringContainsString('id="calendar-start"', $content);
        $this->assertStringContainsString('id="direct-timerange"', $content);
    }

    #[DataProvider('athletePageVisibility')]
    public function testDataBrowserAthleteLinkUsesTheAccountsPrivacySetting(bool $isPublic): void
    {
        self::getContainer()->get(ConfRepository::class)->updateOrInsert(
            $this->Account,
            'privacy',
            'TRAINING_LIST_PUBLIC',
            $isPublic ? 'true' : 'false'
        );
        $this->loginClient($this->Client, $this->Account, 'default');
        $page = $this->Client->request('GET', '/runalyze/databrowser', server: [
            'SCRIPT_NAME' => '/runalyze/index.php',
            'SCRIPT_FILENAME' => '/var/www/public/index.php',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $this->assertStatusCode(200, $this->Client);
        $link = $page->filter('.panel-menu a')->reduce(static fn ($link): bool => $link->filter('i.fa-address-card')->count() === 1);
        $this->assertCount(1, $link);
        if ($isPublic) {
            $this->assertSame('/runalyze/athlete/' . $this->Account->getUsername(), $link->attr('href'));
            $this->assertSame('_blank', $link->attr('target'));
        } else {
            $this->assertSame('/runalyze/settings/privacy', $link->attr('href'));
            $this->assertSame('window', $link->attr('class'));
        }
    }

    public static function athletePageVisibility(): iterable
    {
        yield 'public profile' => [true];
        yield 'privacy settings link' => [false];
    }

    #[DataProvider('datasetPages')]
    public function testDatasetShareLinksUseThePublicRoute(string $method, string $path): void
    {
        $db = DB::getInstance();
        $accountId = $this->Account->getId();
        $sportId = 2302;
        $publicId = 2303;
        $privateId = 2304;
        $time = time();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO ' . PREFIX . 'sport (id, accountid, name) VALUES (?, ?, ?)')
                ->execute([$sportId, $accountId, 'Running']);
            $dataset = $db->prepare('INSERT INTO ' . PREFIX . 'dataset (keyid, accountid, position) VALUES (?, ?, ?)');
            $dataset->execute([Keys::SHARED_LINK, $accountId, 1]);
            $dataset->execute([Keys::SETTING, $accountId, 2]);
            $training = $db->prepare('INSERT INTO ' . PREFIX . 'training (id, accountid, sportid, time, s, distance, is_public) VALUES (?, ?, ?, ?, 1800, 5, ?)');
            $training->execute([$publicId, $accountId, $sportId, $time, 1]);
            $training->execute([$privateId, $accountId, $sportId, $time, 0]);
            unset(Factory::$StaticCache['sport']);
            Cache::delete('training' . $publicId);
            Cache::delete('training' . $privateId);

            $this->loginClient($this->Client, $this->Account, 'default');
            $base = 'https://example.test/runalyze';
            $page = $this->Client->request($method, $base . $path . '?start=' . ($time - 86400) . '&end=' . ($time + 86400), [
                'resultsPerPage' => 10,
                'page' => 1,
            ], server: [
                'SCRIPT_NAME' => '/runalyze/index.php',
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]);

            $this->assertStatusCode(200, $this->Client);
            $this->assertCount(1, $page->filter('[data-action="privacy"][data-activityid="' . $publicId . '"]'));
            $this->assertCount(1, $page->filter('[data-action="privacy"][data-activityid="' . $privateId . '"]'));
            $publicUrl = $base . '/shared/' . base_convert((string) $publicId, 10, 35);
            $privateUrl = $base . '/shared/' . base_convert((string) $privateId, 10, 35);
            $this->assertCount(2, $page->filter('a[href="' . $publicUrl . '"][target="_blank"]'));
            $this->assertCount(1, $page->filter('.inline-menu a[href="' . $publicUrl . '"][onclick]'));
            $this->assertCount(0, $page->filter('a[href="' . $privateUrl . '"]'));
        } finally {
            $db->rollBack();
            unset(Factory::$StaticCache['sport']);
            Cache::delete('training' . $publicId);
            Cache::delete('training' . $privateId);
        }
    }

    public static function datasetPages(): iterable
    {
        yield 'activity browser' => ['GET', '/databrowser'];
        yield 'search results' => ['POST', '/my/search'];
    }

    public function testSavePngActionRequiresAuthentication(): void
    {
        $this->Client->request('POST', '/call/savePng.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSavePngActionReturns400WhenFilenameIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/call/savePng.php', [
            'image' => $this->tinyPngDataUri(),
        ]);

        $this->assertStatusCode(400, $this->Client);
    }

    public function testSavePngActionReturns400WhenImageIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/call/savePng.php', [
            'filename' => 'my export.png',
        ]);

        $this->assertStatusCode(400, $this->Client);
    }

    public function testSavePngActionReturnsTheDecodedImageWithCorrectHeadersForAValidSubmission(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/call/savePng.php', [
            'filename' => 'my export.png',
            'image' => $this->tinyPngDataUri(),
        ]);

        $this->assertStatusCode(200, $this->Client);

        $response = $this->Client->getResponse();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('my_export.png', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            $response->getContent()
        );
    }

    public function testWindowConfigActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowConfigActionRendersTheConfigTabsForAuthenticatedUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/settings');

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()->getContent();

        $this->assertStringContainsString('Runalyze.Overlay.removeClasses();', $content);
        $this->assertStringContainsString('id="headline"', $content);
    }

    public function testAjaxChangeConfigActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/ajax.change.Config.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testAjaxChangeConfigActionNoOpsWhenKeyIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testAjaxChangeConfigActionNoOpsForAnUnrecognizedKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php?key=some-unrecognized-key&value=1');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testAjaxChangeConfigActionAcceptsAValidLeafletLayer(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php?key=leaflet-layer&value=' . urlencode('OpenStreetMap (de)'));

        $this->assertStatusCode(200, $this->Client);

        $configuration = self::getContainer()->get(ConfRepository::class)
            ->findForAccountAndKey($this->Account, 'TRAINING_LEAFLET_LAYER');

        $this->assertNotNull($configuration);
        $this->assertSame('OpenStreetMap (de)', $configuration->getValue());
    }

    public function testAjaxChangeConfigActionRejectsAnUnsafeLeafletLayer(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php?key=leaflet-layer&value=' . urlencode('x");alert(1);//'));

        $this->assertStatusCode(400, $this->Client);
    }

    public function testAjaxChangeConfigActionNoOpsForAnUnknownLegendKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php?key=show-UNKNOWN&value=1');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testWindowSearchActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/my/search');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowSearchActionRendersTheSearchFormForAuthenticatedUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/my/search');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'Search for activities',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testWindowsPlotSumDataActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/window.plotSumData.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowsPlotSumDataActionRendersForAuthenticatedUserWithoutLeakingASessionException(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $page = $this->Client->request('GET', '/call/window.plotSumData.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Monthly chart:', $page->filter('.panel-heading h1')->text());
        $this->assertGreaterThan(0, $page->filter('.panel-menu li.active a[href^="/call/window.plotSumData.php?type=month&y=last12months"]')->count());
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testLoginActionRendersWithoutRequiringAuthentication(): void
    {
        $this->Client->request('GET', '/call/login.php');

        $this->assertStatusCode(200, $this->Client);
    }
}
