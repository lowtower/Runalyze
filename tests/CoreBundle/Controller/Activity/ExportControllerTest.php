<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use Cache;
use DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Export\Share\Types;
use Runalyze\Model\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class ExportControllerTest extends AbstractFixturesAwareWebTestCase
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
            ->setUsername('export_test_' . uniqid('', true))
            ->setMail('export_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    public function testExporterExportActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/activity/1/export/social/1');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testFileExportActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/activity/1/export/file/1');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testExporterExportActionReturnsEmptyResponseForAnInvalidTypeId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/1/export/social/0');

        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('', $this->Client->getResponse()->getContent());
        $this->assertStringNotContainsString(
            'Failed to start the session',
            $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testFileExportActionReturnsEmptyResponseForAnInvalidTypeId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/1/export/file/0');

        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('', $this->Client->getResponse()->getContent());
        $this->assertStringNotContainsString(
            'Failed to start the session',
            $this->Client->getResponse()
                ->getContent()
        );
    }

    #[DataProvider('embedVariants')]
    public function testEmbedUrlsUseTheSymfonyRequestContext(int $typeId, bool $isPublic, string $host): void
    {
        $db = DB::getInstance();
        $accountId = $this->Account->getId();
        $activityId = 2301;
        $sportId = 2302;
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO ' . PREFIX . 'sport (id, accountid, name) VALUES (?, ?, ?)')
                ->execute([$sportId, $accountId, 'Running']);
            $db->prepare('INSERT INTO ' . PREFIX . 'training (id, accountid, sportid, time, s, distance, is_public) VALUES (?, ?, ?, ?, 1800, 5, ?)')
                ->execute([$activityId, $accountId, $sportId, time(), (int) $isPublic]);
            Cache::delete('training' . $activityId);
            unset(Factory::$StaticCache['sport']);

            $this->loginClient($this->Client, $this->Account, 'default');
            $base = 'https://' . $host . '/runalyze';
            $crawler = $this->Client->request('GET', $base . '/activity/' . $activityId . '/export/social/' . $typeId, server: [
                'SCRIPT_NAME' => '/runalyze/index.php',
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
            ]);

            $this->assertStatusCode(200, $this->Client);
            $activityUrl = $base . '/shared/' . base_convert((string) $activityId, 10, 35);
            if ($typeId === Types::IFRAME) {
                $this->assertSame($activityUrl . '?mode=iframe&utm_medium=referral&utm_source=iframe', $crawler->filter('iframe')->attr('src'));
                $this->assertStringContainsString('Only people in your local network', $crawler->text());
            } elseif ($isPublic && $host !== 'localhost') {
                $this->assertSame($activityUrl . '?utm_medium=referral&utm_source=sharehtml', $crawler->filter('a.runalyze-emb-share')->attr('href'));
            } else {
                $this->assertCount(0, $crawler->filter('a.runalyze-emb-share'));
            }
        } finally {
            $db->rollBack();
            unset(Factory::$StaticCache['sport']);
            Cache::delete('training' . $activityId);
        }
    }

    public static function embedVariants(): iterable
    {
        yield 'public HTML' => [Types::HTML, true, 'example.test'];
        yield 'private HTML' => [Types::HTML, false, 'example.test'];
        yield 'HTML on localhost' => [Types::HTML, true, 'localhost'];
        yield 'widget on localhost' => [Types::IFRAME, true, 'localhost'];
    }
}
