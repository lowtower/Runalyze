<?php

namespace Runalyze\Parser\Activity\Converter;

use Runalyze\Import\Exception\InstallationSpecificException;
use Runalyze\Import\Exception\UnexpectedContentException;
use Runalyze\Parser\Common\AbstractShellBasedFileTypeConverter;

class TtbinConverter extends AbstractShellBasedFileTypeConverter
{
    /**
     * @param string $PathToConverter
     */
    public function __construct(
        protected $PathToConverter
    ) {}

    public function getConvertibleFileExtension(): string
    {
        return 'ttbin';
    }

    public function getConvertedFileName($inputFile): string
    {
        return $inputFile . '.tcx';
    }

    protected function buildCommand($inputFile, $outputFile): string
    {
        return sprintf('%s -t -E < "%s" > "%s"', $this->PathToConverter, $inputFile, $outputFile);
    }

    protected function checkFirstLineOfOutput($firstLine)
    {
        if (str_contains($firstLine, 'ttbincnv')) {
            $message = 'Executing ttbincnv did not work: ' . $firstLine;
            $message .= NL . NL . 'You may need to compile ttbincnv for your environment.';

            throw new InstallationSpecificException($message);
        }

        if (! str_starts_with($firstLine, '<')) {
            throw new UnexpectedContentException('Parsing your *.ttbin-file failed: ' . $firstLine);
        }
    }
}
