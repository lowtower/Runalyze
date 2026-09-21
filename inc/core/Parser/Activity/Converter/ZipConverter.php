<?php

namespace Runalyze\Parser\Activity\Converter;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Common\FileTypeConverterInterface;

class ZipConverter implements FileTypeConverterInterface
{
    public function __construct(
        protected ?array $AcceptedFileExtensions = null
    ) {
    }

    public function getConvertibleFileExtension(): string
    {
        return 'zip';
    }

    public function getConvertedFileName($inputFile): array
    {
        return $this->readArchive($inputFile, false);
    }

    public function convertFile($inputFile): array
    {
        return $this->readArchive($inputFile, true);
    }

    /**
     * @param string $inputFile
     * @param bool $extractFiles
     *
     * @return string[]
     */
    protected function readArchive($inputFile, $extractFiles): array
    {
        $archive = new \ZipArchive();

        $this->tryToOpenArchive($archive, $inputFile);

        $inputFileNames = $this->getFileNamesFrom($archive);
        $outputFileNames = array_map(fn ($file): string => $inputFile . '.' . $file, $inputFileNames);

        if ($extractFiles) {
            foreach ($inputFileNames as $i => $currentFile) {
                copy('zip://' . $inputFile . '#' . $currentFile, $outputFileNames[$i]);
            }
        }

        $archive->close();

        return $outputFileNames;
    }

    /**
     * @param string $inputFile
     * @throws UnsupportedFileException
     */
    protected function tryToOpenArchive(\ZipArchive $archive, $inputFile)
    {
        if ($archive->open($inputFile) !== true) {
            throw new UnsupportedFileException("Can't open zip file.");
        }
    }

    /**
     * @return mixed[]
     */
    protected function getFileNamesFrom(\ZipArchive $archive): array
    {
        $files = [];

        for ($i = 0; $i < $archive->numFiles; ++$i) {
            $currentFile = $archive->getNameIndex($i);
            $pathinfo = pathinfo($currentFile);

            if (
                is_array($pathinfo) && isset($pathinfo['dirname']) && isset($pathinfo['extension']) &&
                $pathinfo['dirname'] == '.' && ! str_starts_with($currentFile, '.') &&
                $this->isFileExtensionAllowed($pathinfo['extension'])
            ) {
                $files[] = $currentFile;
            }
        }

        return $files;
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    protected function isFileExtensionAllowed($extension)
    {
        if (is_array($this->AcceptedFileExtensions)) {
            return in_array(mb_strtolower($extension), $this->AcceptedFileExtensions);
        }

        return true;
    }
}
