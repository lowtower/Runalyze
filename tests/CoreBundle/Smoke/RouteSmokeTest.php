<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Smoke;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversNothing]
final class RouteSmokeTest extends WebTestCase
{
    public $EntityManager;

    public $client;

    public $container;

    private ?UserInterface $user = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->container = self::getContainer();

        $this->user = $this->getOrCreateTestUser('test@test.com');
    }

    private function getOrCreateTestUser(string $email): ?UserInterface
    {
        $userProvider = $this->container->get('security.user.provider.concrete.runalyzeuser');

        try {
            return $userProvider->loadUserByIdentifier($email);
        } catch (\Exception) {
            $em = $this->container->get('doctrine')->getManager();
            $user = new Account()
                ->setMail($email)
                ->setPassword('');

            $em->persist($user);
            $em->flush();

            return $userProvider->loadUserByIdentifier($email);
        }
    }

    #[DataProvider('routesProvider')]
    public function testPublicRouteNoServerErrors(
        string $label,
        string $path,
        string $method,
        int $expectedStatus,
        bool $requiresAuth
    ): void {
        $resolvedPath = preg_replace('/\{[^}]+\}/', '1', $path);
        $this->client->request($method, $resolvedPath);

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertSame(
            $expectedStatus,
            $statusCode,
            "{$label} ({$method} {$resolvedPath}): expected {$expectedStatus}, got {$statusCode}"
        );
        if ($statusCode === 202) {
            $this->assertResponseIsSuccessful();
        }
    }

    #[DataProvider('routesProvider')]
    public function testAuthenticatedRouteAccessible(
        string $label,
        string $path,
        string $method,
        int $expectedStatus,
        bool $requiresAuth = true
    ): void {
        $userProvider = $this->container->get('security.user.provider.concrete.runalyzeuser');
        $userProvider->loadUserByIdentifier('test@test.com');

        $this->client->loginUser($this->user);
        $resolvedPath = preg_replace('/\{[^}]+\}/', '1', $path);

        $this->client->request($method, $resolvedPath);

        $statusCode = $this->client->getResponse()->getStatusCode();
        // 200 = OK
        // 301/302 = redirect (e.g. to login)
        // 401/403 = auth necessary
        // 404 = route exists, but placeholder parameter is missing
        $this->assertNotEquals(401, $statusCode, "{$label} ({$method} {$resolvedPath}): not authenticated");
        $this->assertNotEquals(403, $statusCode, "{$label} ({$method} {$resolvedPath}): forbidden");
        $this->assertNotEquals(500, $statusCode, "{$label} ({$method} {$resolvedPath}): server error");
    }

    public static function routesProvider(): \Generator
    {
        yield ['account_delete_confirmed', '/{_locale}/account/delete/{hash}/confirmed', 'GET', 404, false];
        yield ['account_delete', '/{_locale}/account/delete/{hash}', 'GET', 404, false];
        yield ['account_recover_hash', '/{_locale}/account/recover/{hash}', 'GET', 404, false];
        yield ['account_recover', '/{_locale}/account/recover', 'GET', 200, false];
        yield ['account_activate', '/{_locale}/account/activate/{hash}', 'GET', 404, false];
        yield ['account_activate_with_username', '/{_locale}/account/activate/{hash}/{username}', 'GET', 404, false];
        yield ['activity-add', '/activity/add', 'GET', 302, false];
        yield ['activity-communicator', '/activity/communicator', 'GET', 302, false];
        yield ['activity-communicator-iframe', '/activity/communicator/iframe', 'GET', 302, false];
        yield ['activity-upload', '/activity/upload', 'GET', 302, false];
        yield ['activity-multi-importer', '/activity/multi-import', 'GET', 302, false];
        yield ['activity-new', '/activity/new', 'GET', 302, false];
        yield ['activity-edit', '/activity/{id}/edit', 'GET', 302, true];
        yield ['multi-editor', '/activity/multi-editor', 'GET', 302, true];
        yield ['activity-delete', '/activity/{id}/delete', 'GET', 302, true];
        yield ['activity-elevation-correction', '/activity/{id}/elevation-correction', 'GET', 302, true];
        yield ['runalyze_core_activity_export_exporterexport', '/activity/{id}/export/social/{typeid}', 'GET', 302, true];
        yield ['runalyze_core_activity_export_fileexport', '/activity/{id}/export/file/{typeid}', 'GET', 302, true];
        yield ['ActivityShow', '/activity/{id}', 'GET', 302, true];
        yield ['runalyze_core_activity_view_vo2maxinfo', '/activity/{id}/vo2max-info', 'GET', 302, true];
        yield ['runalyze_core_activity_view_splitsinfo', '/activity/{id}/splits-info', 'GET', 302, true];
        yield ['runalyze_core_activity_view_elevationinfo', '/activity/{id}/elevation-info', 'GET', 302, true];
        yield ['activity-tool-time-series-info', '/activity/{id}/time-series-info', 'GET', 302, true];
        yield ['activity-tool-sub-segments-info', '/activity/{id}/sub-segments-info', 'GET', 302, true];
        yield ['activity-tool-climb-score', '/activity/{id}/climb-score', 'GET', 404, true];
        yield ['runalyze_core_call_databrowser', '/call/call.DataBrowser.display.php', 'GET', 302, false];
        yield ['databrowser', '/databrowser', 'GET', 302, false];
        yield ['runalyze_core_call_garmincommunicator', '/call/call.garminCommunicator.php', 'GET', 302, false];
        yield ['runalyze_core_call_garmincommunicator_1', '/upload/garminCommunicator', 'GET', 302, false];
        yield ['runalyze_core_call_savepng', '/call/savePng.php', 'POST', 302, true];
        yield ['settings', '/settings', 'GET', 302, true];
        yield ['runalyze_core_call_ajaxchanceconfig', '/call/ajax.change.Config.php', 'GET', 302, false];
        yield ['my-search', '/my/search', 'GET', 302, true];
        yield ['runalyze_core_call_windowsplotsumdata', '/call/window.plotSumData.php', 'GET', 302, false];
        yield ['runalyze_core_call_login', '/call/login.php', 'GET', 200, false];
        yield ['dashboard', '/dashboard', 'GET', 302, false];
        yield ['base_url', '/', 'GET', 302, false];
        yield ['register', '/{_locale}/register', 'GET', 200, false];
        yield ['login', '/{_locale}/login', 'GET', 200, false];
        yield ['login_check', '/{_locale}/login_check', 'POST', 400, false];
        yield ['logout', '/logout', 'GET', 302, false];
        yield ['runalyze_core_default_plugin', '/plugin/{plugin}/{file}', 'GET', 302, false];
        yield ['runalyze_core_default_indexphp', '/index.php', 'GET', 302, false];
        yield ['runalyze_core_default_loginphp', '/login.php', 'GET', 302, false];
        yield ['unsubscribe-mail', '/unsubscribe/{mail}/{hash}', 'GET', 200, false];
        yield ['unsubscribe-mail-confirm', '/unsubscribe/{mail}/{hash}/confirm', 'GET', 200, false];
        yield ['feedback', '/feedback', 'GET', 302, false];
        yield ['runalyze_core_externalrestapi_importactivity_postimportactivity', '/api/import/activity', 'POST', 401, true];
        yield ['install', '/install', 'GET', 200, false];
        yield ['install_start', '/install/start', 'GET', 302, false];
        yield ['install_finish', '/install/finish', 'GET', 302, false];
        yield ['internal-activity-matcher', '/_internal/activity/matcher', 'GET', 302, true];
        yield ['internal-data-race-results-all', '/_internal/data/race-results/all', 'GET', 302, true];
        yield ['internal-notifications-read-all', '/_internal/notifications/read/all', 'GET', 302, true];
        yield ['internal-notifications-read', '/_internal/notifications/read/{id}', 'GET', 302, true];
        yield ['internal-notifications-list', '/_internal/notifications', 'GET', 302, true];
        yield ['internal-sport-panel', '/_internal/panel/sport', 'GET', 302, true];
        yield ['internal-plugin-toggle', '/_internal/plugin/toggle/{id}', 'GET', 302, true];
        yield ['internal-plugin-move-up', '/_internal/plugin/move/{id}/up', 'GET', 302, true];
        yield ['internal-plugin-move-down', '/_internal/plugin/move/{id}/down', 'GET', 302, true];
        yield ['internal-plugin-all-panels', '/_internal/plugin/all-panels', 'GET', 302, true];
        yield ['internal-service-weather', '/_internal/service/weather', 'GET', 302, true];
        yield ['internal-activity-upload', '/_internal/upload', 'GET', 302, true];
        yield ['internal-activity-upload-tcx', '/_internal/upload/tcx', 'GET', 302, true];
        yield ['body-values-add', '/my/body-values/add', 'GET', 302, true];
        yield ['body-values-edit', '/my/body-values/{id}/edit', 'GET', 302, true];
        yield ['body-values-delete', '/my/body-values/{id}/delete', 'GET', 302, true];
        yield ['body-values-table', '/my/body-values/table', 'GET', 302, true];
        yield ['equipment-category-table', '/my/equipment/category/{typeid}/table', 'GET', 302, true];
        yield ['equipment-overview', '/my/equipment/overview', 'GET', 302, true];
        yield ['equipment-category-add', '/my/equipment/category/add', 'GET', 302, true];
        yield ['equipment-category-edit', '/my/equipment/category/{id}/edit', 'GET', 302, true];
        yield ['equipment-category-delete', '/my/equipment/category/{id}/delete', 'GET', 302, true];
        yield ['equipment-add', '/my/equipment/add/{id}', 'GET', 302, true];
        yield ['equipment-edit', '/my/equipment/{id}/edit', 'GET', 302, true];
        yield ['equipment-delete', '/my/equipment/{id}/delete', 'GET', 302, true];
        yield ['raceresult-form', '/my/raceresult/{activityId}', 'GET', 302, true];
        yield ['raceresult-delete', '/my/raceresult/{activityId}/delete', 'GET', 302, true];
        yield ['race-results-performance-chart', '/my/raceresult/performance-chart', 'GET', 302, true];
        yield ['tools-backup-download', '/my/tools/backup/download/{filename}', 'GET', 302, true];
        yield ['tools-backup', '/my/tools/backup', 'GET', 302, true];
        yield ['tools-backup-json-upload', '/my/tools/backup-import/upload', 'GET', 302, true];
        yield ['tools-backup-json-import', '/my/tools/backup-import/import', 'GET', 302, true];
        yield ['tools-backup-json-import-do', '/my/tools/backup-import/import/do', 'GET', 302, true];
        yield ['tools-backup-json', '/my/tools/backup-import', 'GET', 302, true];
        yield ['tools-cleanup', '/my/tools/cleanup', 'GET', 302, true];
        yield ['tools-tables-vo2max-pace', '/my/tools/tables/vo2max-pace', 'GET', 302, true];
        yield ['tools-tables-general-pace', '/my/tools/tables/general-pace', 'GET', 302, true];
        yield ['tools-tables-vo2max', '/my/tools/tables/vo2max', 'GET', 302, true];
        yield ['tools-tables', '/my/tools/tables', 'GET', 302, true];
        yield ['tools-vo2max-analysis', '/my/tools/vo2max-analysis', 'GET', 302, true];
        yield ['tools-anova', '/my/tools/anova', 'GET', 302, true];
        yield ['tools-trend-analysis', '/my/tools/trend-analysis', 'GET', 302, true];
        yield ['poster', '/my/tools/poster', 'GET', 302, true];
        yield ['poster-download', '/my/tools/poster/{name}', 'GET', 302, true];
        yield ['tools', '/my/tools', 'GET', 302, true];
        yield ['notifications-list', '/notifications', 'GET', 302, true];
        yield ['runalyze_core_plugin_plugininstall', '/call/call.Plugin.install.php', 'GET', 302, true];
        yield ['plugin-display', '/my/plugin/{id}', 'GET', 302, true];
        yield ['plugin-config', '/call/call.Plugin.config.php', 'GET', 302, true];
        yield ['settings-privacy', '/settings/privacy', 'GET', 302, true];
        yield ['settings-account', '/settings/account', 'GET', 302, true];
        yield ['settings-password', '/settings/password', 'GET', 302, true];
        yield ['settings-mail', '/settings/mail', 'GET', 302, true];
        yield ['settings-account-delete', '/settings/account/delete', 'GET', 302, true];
        yield ['settings-dataset-update', '/settings/dataset', 'POST', 302, true];
        yield ['settings-dataset', '/settings/dataset', 'GET', 302, true];
        yield ['settings-sports', '/settings/sport', 'GET', 302, true];
        yield ['sport-type-add', '/settings/sport/{sportid}/type/add', 'GET', 302, true];
        yield ['sport-type-edit', '/settings/sport/type/{id}/edit', 'GET', 302, true];
        yield ['sport-type-delete', '/settings/sport/type/{id}/delete', 'GET', 302, true];
        yield ['sport-add', '/settings/sport/add/{internalType}', 'GET', 302, true];
        yield ['sport-add-custom', '/settings/sport/add/custom', 'GET', 302, true];
        yield ['sport-edit', '/settings/sport/{id}/edit', 'GET', 302, true];
        yield ['sport-delete', '/settings/sport/{id}/delete', 'GET', 302, true];
        yield ['settings-tags', '/settings/tags', 'GET', 302, true];
        yield ['settings-tags-edit', '/settings/tags/{id}/edit', 'GET', 302, true];
        yield ['settings-tags-delete', '/settings/tags/{id}/delete', 'GET', 302, true];
        yield ['runalyze_core_shared_sharedtraining', '/shared/{activityHash}', 'GET', 200, false];
        yield ['runalyze_core_shared_sharedtraining_1', '/shared/{activityHash}', 'GET', 200, false];
        yield ['runalyze_core_shared_sharedtraining_2', '/shared/{activityHash}', 'GET', 200, false];
        yield ['shared-activity', '/shared/{activityHash}', 'GET', 200, false];
        yield ['runalyze_core_shared_oldshareduser', '/shared/{username}/', 'GET', 301, false];
        yield ['shared-athlete', '/athlete/{username}', 'GET', 200, false];
        yield ['shared-athlete-feed', '/athlete/{username}/feed', 'GET', 200, false];
        yield ['help', '/dashboard/help', 'GET', 302, false];
        yield ['help-calculations', '/dashboard/help-calculations', 'GET', 302, false];
        yield ['update', '/update', 'GET', 200, false];
        yield ['update_start', '/update/start', 'GET', 200, false];
    }
}
