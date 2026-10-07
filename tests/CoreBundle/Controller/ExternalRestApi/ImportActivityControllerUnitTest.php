<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\ExternalRestApi;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Controller\ExternalRestApi\ImportActivityController;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ImportActivityControllerUnitTest extends TestCase
{
    private const string USERNAME = 'unit_test_user';

    // Stubs by default. Tests that need expects() on the logger or a
    // different authorizationChecker behavior build their own and pass
    // them to createController().
    private LoggerInterface&Stub $logger;

    private AuthorizationCheckerInterface&Stub $authorizationChecker;

    private ImportActivityController $controller;

    /**
     * @var string[]
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $this->authorizationChecker->method('isGranted')
            ->willReturn(true);

        $this->controller = $this->createController();
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();

        $filesystem->remove($this->temporaryFiles);
        $filesystem->remove($this->findTemporaryUploadDirectories(self::USERNAME));
    }

    private function createController(
        ?LoggerInterface $logger = null,
        ?AuthorizationCheckerInterface $authorizationChecker = null
    ): ImportActivityController {
        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker ?? $this->authorizationChecker);

        $controller = new ImportActivityController(
            $this->createStub(KernelInterface::class),
            $logger ?? $this->logger
        );
        $controller->setContainer($container);

        return $controller;
    }

    private function createAccount(string $username = self::USERNAME): Account
    {
        return new Account()->setUsername($username);
    }

    private function createUploadedFile(string $clientFileName, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'import_unit_');
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $clientFileName, 'application/octet-stream', $error, true);
    }

    /**
     * @param UploadedFile[] $files
     */
    private function postImport(array $files, ?Account $account): Response
    {
        return $this->controller->postImportActivity(new Request(files: $files), $account);
    }

    /**
     * @return string[]
     */
    private function findTemporaryUploadDirectories(string $username): array
    {
        return glob(sys_get_temp_dir() . '/' . $username . '_*') ?: [];
    }

    public function testAccessIsDeniedWhenUserIsNotLoggedIn(): void
    {
        // A stub is enough: only one call pattern (isGranted('ROLE_USER'))
        // ever occurs here, so an unconditional return is equivalent to the
        // previous ->with('ROLE_USER') constraint. with() on a stub has no
        // effect and is deprecated, so we don't use it here.
        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')
            ->willReturn(false);

        $controller = $this->createController(authorizationChecker: $authorizationChecker);

        $this->expectException(AccessDeniedException::class);

        $controller->postImportActivity(new Request(), $this->createAccount());
    }

    public function testAccessIsDeniedWhenNoAccountIsAvailable(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('error');

        $controller = $this->createController(logger: $logger);

        $this->expectException(AccessDeniedException::class);

        $controller->postImportActivity(new Request());
    }

    public function testDuplicateFileNamesAreReportedWithTheirNameAsPlainText(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('error');

        $controller = $this->createController(logger: $logger);

        $response = $controller->postImportActivity(
            new Request(files: [
                'file1' => $this->createUploadedFile('same-name.fit'),
                'file2' => $this->createUploadedFile('same-name.fit'),
            ]),
            $this->createAccount()
        );

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('Multiple uploaded files share the file name "same-name.fit"', (string) $response->getContent());
        self::assertStringNotContainsString(ImportActivityController::GENERIC_ERROR_MSG, (string) $response->getContent());
    }

    public function testTemporaryUploadDirectoryIsRemovedAfterDuplicateFileNames(): void
    {
        $this->postImport(
            [
                'file1' => $this->createUploadedFile('same-name.fit'),
                'file2' => $this->createUploadedFile('same-name.fit'),
            ],
            $this->createAccount()
        );

        self::assertSame([], $this->findTemporaryUploadDirectories(self::USERNAME));
    }

    #[DataProvider('uploadErrors')]
    public function testFileThatCannotBeMovedProducesGenericErrorWithoutDetails(int $uploadError): void
    {
        $response = $this->postImport(
            [
                'file1' => $this->createUploadedFile('private-name.fit', $uploadError),
            ],
            $this->createAccount()
        );

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertStringContainsString(ImportActivityController::GENERIC_ERROR_MSG, (string) $response->getContent());
        self::assertStringNotContainsString('private-name.fit', (string) $response->getContent());
    }

    #[DataProvider('uploadErrors')]
    public function testFileThatCannotBeMovedIsLoggedWithUsernameAndException(int $uploadError): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Activity import via REST API failed.',
                self::callback(
                    static fn (array $context): bool => $context['username'] === self::USERNAME
                        && $context['exception'] instanceof FileException
                )
            );

        $controller = $this->createController(logger: $logger);

        $controller->postImportActivity(
            new Request(files: [
                'file1' => $this->createUploadedFile('private-name.fit', $uploadError),
            ]),
            $this->createAccount()
        );
    }

    public static function uploadErrors(): iterable
    {
        yield 'file exceeds upload_max_filesize' => [UPLOAD_ERR_INI_SIZE];
        yield 'file was only partially uploaded' => [UPLOAD_ERR_PARTIAL];
        yield 'temporary folder is missing' => [UPLOAD_ERR_NO_TMP_DIR];
        yield 'file cannot be written to disk' => [UPLOAD_ERR_CANT_WRITE];
        yield 'a php extension stopped the upload' => [UPLOAD_ERR_EXTENSION];
    }

    public function testTemporaryUploadDirectoryIsRemovedAfterFileThatCannotBeMoved(): void
    {
        $this->postImport(
            [
                'file1' => $this->createUploadedFile('private-name.fit', UPLOAD_ERR_CANT_WRITE),
            ],
            $this->createAccount()
        );

        self::assertSame([], $this->findTemporaryUploadDirectories(self::USERNAME));
    }

    public function testImportFailsWithTempFolderMessageWhenUploadDirectoryCannotBeCreated(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Temporary upload directory could not be created.',
                self::callback(static fn (array $context): bool => is_string($context['directory']) && isset($context['exception']))
            );

        $controller = $this->createController(logger: $logger);

        $response = $controller->postImportActivity(
            new Request(files: [
                'file1' => $this->createUploadedFile('any.fit'),
            ]),
            $this->createAccount(str_repeat('a', 300))
        );

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertStringContainsString(ImportActivityController::TEMP_FOLDER_ERROR_MSG, (string) $response->getContent());
    }
}
