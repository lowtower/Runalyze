<?php

namespace Runalyze\Parser\Common;

use Runalyze\Import\Exception\UnsupportedFileException;

abstract class AbstractShellBasedFileTypeConverter implements FileTypeConverterInterface
{
    abstract public function getConvertibleFileExtension();

    abstract public function getConvertedFileName(string $inputFile): string;

    abstract protected function buildCommand(string $inputFile, string $outputFile): string;

    public function convertFile(string $inputFile): string
    {
        $outputFile = $this->getConvertedFileName($inputFile);

        shell_exec($this->buildCommand($inputFile, $outputFile) . ' 2>&1');

        $this->readFirstLineOfOutputAndForwardToSubclass($outputFile);

        return $outputFile;
    }

    private function readFirstLineOfOutputAndForwardToSubclass(string $outputFile)
    {
        $handle = @fopen($outputFile, 'r');

        if (! $handle) {
            throw new UnsupportedFileException(sprintf('Output file %s not found.', $outputFile));
        }

        try {
            $this->checkFirstLineOfOutput(
                stream_get_line($handle, 4096, PHP_EOL)
            );
        } catch (\Exception $exception) {
            unlink($outputFile);

            throw $exception;
        } finally {
            fclose($handle);
        }
    }

    abstract protected function checkFirstLineOfOutput(string $firstLine);
}
