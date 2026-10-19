<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\My\Tools;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Filesystem\Filesystem;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class BackupToolControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    private function getPathToBackupFiles(): string
    {
        return self::getContainer()->getParameter('app.data_directory') . '/backup-tool/backup/';
    }

    /**
     * @throws \RuntimeException if no matching field is found
     */
    private function findAndFillFileFormatForm(Crawler $pageCrawler, string $value): Form
    {
        $formNodes = $pageCrawler->filter('form');
        $diagnostics = [];

        for ($i = 0; $i < $formNodes->count(); $i++) {
            $form = $formNodes->eq($i)
                ->form();
            $fieldNames = array_keys($form->all());

            foreach ($form->all() as $name => $field) {
                if (str_contains($name, 'fileFormat')) {
                    /** @var FormField $field */
                    $field->setValue($value);

                    return $form;
                }
            }

            $formId = $form->getFormNode()
                ->getAttribute('id') ?: "(no id, index {$i})";
            $diagnostics[] = "Form \"{$formId}\": fields = [" . implode(', ', $fieldNames) . ']';
        }

        throw new \RuntimeException(
            "No form on the page contains a field named \"fileFormat\". Forms inspected:\n" . implode("\n", $diagnostics)
        );
    }

    public function testBackupActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/backup');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testBackupActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/backup');

        $this->assertStatusCode(200, $client);
    }

    public function testSubmittingValidBackupFormRedirectsToSamePage(): void
    {
        $client = $this->makeAuthenticatedClient();

        $crawler = $client->request('GET', '/my/tools/backup');
        $form = $this->findAndFillFileFormatForm($crawler, 'json');
        $client->submit($form);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/tools/backup',
            $client->getResponse()
                ->headers->get('Location')
        );
    }

    public function testSubmittingBackupFormDispatchesBackupGenerationMessage(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/my/tools/backup');
        $this->assertStatusCode(200, $this->Client);

        $form = $this->findAndFillFileFormatForm($crawler, 'json');
        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        /** @var \Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $envelopes = $transport->getSent();

        $this->assertNotEmpty($envelopes, 'Expected a message to have been dispatched to the message bus.');

        $message = end($envelopes)
            ->getMessage();
        $this->assertInstanceOf(BackupGenerationMessage::class, $message);
        $this->assertSame($this->Account->getId(), $message->getAccountId());
        $this->assertSame('json', $message->getExportType());
    }

    public function testBackupActionShowsSuccessMessageAfterSuccessfulSubmission(): void
    {
        $client = $this->makeAuthenticatedClient();

        $crawler = $client->request('GET', '/my/tools/backup');
        $form = $this->findAndFillFileFormatForm($crawler, 'json');
        $client->submit($form);

        $this->assertStatusCode(302, $client);

        $redirectedCrawler = $client->followRedirect();

        $this->assertStatusCode(200, $client);

        $successNodes = $redirectedCrawler->filter('p.okay');
        $this->assertGreaterThan(
            0,
            $successNodes->count(),
            'Expected a "p.okay" success confirmation element in the response after following the redirect.'
        );
        $this->assertStringContainsString('backup job has been created', $successNodes->text());
    }

    public function testDownloadBackupActionReturns404ForMissingFile(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/backup/download/does-not-exist.zip');

        $this->assertStatusCode(404, $client);
    }

    public function testDownloadBackupActionReturns403ForFileWithInvalidExtension(): void
    {
        $residual = 'some-token.txt';
        $internalFilename = $this->Account->getId() . '-' . FilenameHandler::FILENAME_PREFIX . '-' . $residual;

        $filesystem = new Filesystem();
        $backupPath = $this->getPathToBackupFiles();
        $filesystem->mkdir($backupPath);
        $filesystem->dumpFile($backupPath . $internalFilename, 'not a real backup');

        try {
            $client = $this->makeAuthenticatedClient();
            $client->request('GET', '/my/tools/backup/download/' . $residual);

            $this->assertStatusCode(403, $client);
        } finally {
            $filesystem->remove($backupPath . $internalFilename);
        }
    }

    public function testDownloadBackupActionSucceedsForOwnFile(): void
    {
        $residual = 'test-download-token.sql.gz';
        $internalFilename = $this->Account->getId() . '-' . FilenameHandler::FILENAME_PREFIX . '-' . $residual;

        $filesystem = new Filesystem();
        $backupPath = $this->getPathToBackupFiles();
        $filesystem->mkdir($backupPath);
        $filesystem->dumpFile($backupPath . $internalFilename, 'fake backup content');

        try {
            $client = $this->makeAuthenticatedClient();
            $client->request('GET', '/my/tools/backup/download/' . $residual);

            $this->assertStatusCode(200, $client);
        } finally {
            $filesystem->remove($backupPath . $internalFilename);
        }
    }
}
