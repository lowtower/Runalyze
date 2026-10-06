<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileHandler
{
    public function __construct(
        protected string $DataDir
    ) {
    }

    protected function pathToImages(): string
    {
        return $this->DataDir . '/poster/';
    }

    /**
     * @return array<string, int> file sizes by file name without account prefix, newest file first
     */
    public function getFileList(Account $account): array
    {
        if (! is_dir($this->pathToImages())) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->name($account->getId() . '-*')
            ->sort(fn (SplFileInfo $a, SplFileInfo $b): int => $b->getMTime() - $a->getMTime())
            ->in($this->pathToImages());

        $list = [];

        foreach ($finder as $file) {
            $list[substr($file->getBasename(), strlen((string) $account->getId()) + 1)] = $file->getSize();
        }

        return $list;
    }

    /**
     * @throws NotFoundHttpException if the file does not exist, is not a plain file name or does not belong to the account
     */
    public function getPosterDownloadResponse(Account $account, string $filename): Response
    {
        $internalFilename = $account->getId() . '-' . $filename;
        $path = $this->pathToImages() . $internalFilename;

        if ($filename === '' || $filename !== basename($filename) || ! is_file($path)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new NotFoundHttpException();
        }

        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'image/png');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $internalFilename . '";');
        $response->headers->set('Content-length', (string) strlen($content));
        $response->setContent($content);

        return $response;
    }

    public function buildFinalFileName(Account $account, Sport $sport, string|int $year, string $type, string|int $size): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s-%s.%s',
            $account->getId(),
            $this->filesystemFriendlyName($sport->getName()),
            $year,
            $type,
            $size,
            date('Ymd-Hi'),
            'png'
        );
    }

    protected function filesystemFriendlyName(string $string): string
    {
        return preg_replace('~[^a-zA-Z0-9]+~', '', $string) ?? '';
    }
}
