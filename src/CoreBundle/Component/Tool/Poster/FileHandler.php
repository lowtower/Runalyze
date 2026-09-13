<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

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

    public function getFileList(Account $account): array
    {
        $finder = new Finder();
        $finder->files()
            ->name($account->getId() . '-*')
            ->sort(fn (\SplFileInfo $a, \SplFileInfo $b) => $b->getMTime() - $a->getMTime())
            ->in($this->pathToImages());

        $list = [];

        foreach ($finder as $file) {
            $list[substr($file->getBasename(), strlen((string) $account->getId()) + 1)] = $file->getSize();
        }

        return $list;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function getPosterDownloadResponse(Account $account, string $filename): Response
    {
        $fs = new Filesystem();
        $filename = $account->getId() . '-' . $filename;

        if ($fs->exists($this->pathToImages() . $filename)) {
            $response = new Response();
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', 'image/png');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '";');
            $response->headers->set('Content-length', (string) filesize($this->pathToImages() . $filename));
            $response->setContent(file_get_contents($this->pathToImages() . $filename));

            return $response;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param string|int $year
     * @param string|int $size
     */
    public function buildFinalFileName(Account $account, Sport $sport, $year, string $type, $size): string
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
        return preg_replace('~[^a-zA-Z0-9]+~', '', $string);
    }
}
