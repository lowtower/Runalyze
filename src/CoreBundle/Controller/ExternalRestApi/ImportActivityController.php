<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\ExternalRestApi;

use Exception;
use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Exception\DuplicateUploadFileNameException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Uploads the files with this REST API and call the "runalyze:activity:bulk-import" to import the files.
 * The POST request must be a "multipart/form-data" to support multiple times in one request.
 *
 * If no problems occur and all uploaded files are imported successfully a HTTP state 200 is returned otherwise (duplicates or failed) a state 202 is returned.
 * If there are technical trouble (while creating the tmp-folder, moving files ...) a HTTP state 500 is returned.
 * Additional infos and the output of the "runalyze:activity:bulk-import" are set in the response content as "Content-Type: text/plain"
 * Only duplicate file names are explained in detail in the response. The details of any other technical trouble (including the stack trace) are written to the log.
 *
 * example with 2 files:
 * curl -k -u "user:pwd" -H "Accept-Language: de,en" -X POST https://<domain>/api/import/activity -F 'file1=@afile.fit' -F 'file2=@another.fit' -w 'HttpCode: %{http_code}\n'
 *
 * #TSC
 */
class ImportActivityController extends AbstractController
{
    public const string IMPORT_CMD = 'runalyze:activity:bulk-import';

    public const string SUCCESS_MSG = 'successfully imported';

    public const string GENERIC_ERROR_MSG = 'ERROR: The import failed. Details have been written to the server log.';

    public const string TEMP_FOLDER_ERROR_MSG = 'ERROR: Error while creating temp folder!';

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * import as REST api.
     *
     * basic steps:
     * - create a tmp folder under /tmp/<username_<timestamp>
     * - move uploaded files to the created tmp-folder
     * - call the existing command "runalyze:activity:bulk-import"
     * - cleanup: remove uploaded files and tmp-folder
     *
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/api/import/activity', methods: ['POST'])]
    public function postImportActivity(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        $result = '';

        $resultCode = Response::HTTP_OK; // 200

        $username = $account->getUsername();

        /*
         * $request->files->all() are a array of UploadedFile-Objects
         * [data1] => Symfony\Component\HttpFoundation\File\UploadedFile Object
         * (
         * [test:Symfony\Component\HttpFoundation\File\UploadedFile:private] =>
         * [originalName:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 2020-10-09-15-26-00.fit
         * [mimeType:Symfony\Component\HttpFoundation\File\UploadedFile:private] => application/octet-stream
         * [size:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 162129
         * [error:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 0
         * [pathName:SplFileInfo:private] => /tmp/phpOZLwMG
         * [fileName:SplFileInfo:private] => phpOZLwMG
         * )
         */
        $allfiles = $request->files->all();
        $allfilesCount = count($allfiles);

        $uploadDir = $this->createUploadDir($username);
        if ($uploadDir !== null) {
            $result = $result . 'Storing ' . $allfilesCount . ' uploaded files to temp folder ' . $uploadDir . '.' . PHP_EOL;

            try {
                // process all uploaded files
                $processFilesResult = $this->processFiles($username, $uploadDir, $allfiles);
                $result .= $processFilesResult;

                // if not all files processed "successfully" document it and send HTTP state 202
                $notSuccessful = $allfilesCount - substr_count($processFilesResult, self::SUCCESS_MSG);
                if ($notSuccessful !== 0) {
                    $result = $result . 'WARNING: ' . $notSuccessful . ' of ' . $allfilesCount . ' files are NOT imported successfully - please check the above protocol.' . PHP_EOL;
                    $resultCode = Response::HTTP_ACCEPTED; // 202
                }
            } catch (DuplicateUploadFileNameException $exception) {
                $result = $result . 'ERROR: ' . $exception->getMessage() . PHP_EOL;
                $resultCode = Response::HTTP_INTERNAL_SERVER_ERROR; // 500
            } catch (Exception $exception) {
                $this->logger->error('Activity import via REST API failed.', [
                    'username' => $username,
                    'exception' => $exception,
                ]);

                $result = $result . self::GENERIC_ERROR_MSG . PHP_EOL;
                $resultCode = Response::HTTP_INTERNAL_SERVER_ERROR; // 500
            } finally {
                $this->removeUploadDir($uploadDir);
            }
        } else {
            $result = $result . self::TEMP_FOLDER_ERROR_MSG . PHP_EOL;
            $resultCode = Response::HTTP_INTERNAL_SERVER_ERROR; // 500
        }

        return new Response($result, $resultCode, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * @param UploadedFile[] $allfiles
     *
     * @throws DuplicateUploadFileNameException if two uploaded files share the same client file name
     */
    private function assertNoDuplicateFileNames(array $allfiles): void
    {
        $seenFileNames = [];

        foreach ($allfiles as $file) {
            $fileName = $file->getClientOriginalName();

            if (isset($seenFileNames[$fileName])) {
                throw new DuplicateUploadFileNameException(sprintf(
                    'Multiple uploaded files share the file name "%s". Each uploaded file must have a unique name, otherwise one would silently overwrite the other before import.',
                    $fileName
                ));
            }

            $seenFileNames[$fileName] = true;
        }
    }

    private function createUploadDir(string $username): ?string
    {
        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . uniqid($username . '_', true);

        try {
            new Filesystem()->mkdir($tmpDir);
        } catch (IOException $ioException) {
            $this->logger->error('Temporary upload directory could not be created.', [
                'directory' => $tmpDir,
                'exception' => $ioException,
            ]);

            return null;
        }

        return $tmpDir;
    }

    private function removeUploadDir(string $uploadDir): void
    {
        try {
            new Filesystem()->remove($uploadDir);
        } catch (IOException $ioException) {
            $this->logger->warning('Temporary upload directory could not be removed.', [
                'directory' => $uploadDir,
                'exception' => $ioException,
            ]);
        }
    }

    /**
     * @param UploadedFile[] $allfiles
     *
     * @throws DuplicateUploadFileNameException if two uploaded files share the same client file name
     * @throws FileException if an uploaded file cannot be moved to the upload directory
     */
    private function processFiles(string $username, string $uploadDir, array $allfiles): string
    {
        $result = '';

        $this->assertNoDuplicateFileNames($allfiles);

        // move from tmp-name (/tmp/phpOZLwMG) to the tmp-upload-folder (in error case an exception is thrown)
        foreach ($allfiles as $v) {
            $v->move($uploadDir, $v->getClientOriginalName());
        }

        // process import as command call
        $result = $result . '-------------------' . PHP_EOL;
        $consoleResult = $this->processImport($username, $uploadDir);
        $result = $result . $consoleResult . PHP_EOL;
        $result = $result . '-------------------' . PHP_EOL;

        // cleanup: remove uploaded files
        $result = $result . 'Cleanup temp folder and uploaded files.' . PHP_EOL;
        foreach ($allfiles as $v) {
            $f = $uploadDir . DIRECTORY_SEPARATOR . $v->getClientOriginalName();
            if (! unlink($f)) {
                $result = $result . 'WARNING: Can not remove file ' . $f . PHP_EOL;
            }
        }

        // cleanup: remove temp created folder
        if (! rmdir($uploadDir)) {
            $result = $result . 'WARNING: Can not remove upload-folder ' . $uploadDir . PHP_EOL;
        }

        return $result . PHP_EOL;
    }

    private function processImport(string $username, string $uploadDir): string
    {
        $application = new Application($this->kernel);

        $command = $application->find(self::IMPORT_CMD);

        $input = new ArrayInput([
            'username' => $username,
            'path' => $uploadDir,
        ]);
        $input->setInteractive(false);

        $output = new BufferedOutput();
        $command->run($input, $output);

        return $output->fetch();
    }
}
