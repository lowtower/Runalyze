<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Converter;

use Runalyze\Import\Exception\InstallationSpecificException;
use Runalyze\Import\Exception\ParserException;
use Runalyze\Import\Exception\UnexpectedContentException;
use Runalyze\Parser\Common\AbstractShellBasedFileTypeConverter;

class FitConverter extends AbstractShellBasedFileTypeConverter
{
    /**
     * @var string
     */
    public const PERL_FIT_ERROR_MESSAGE_START = 'main::Garmin::FIT';

    /**
     * @var string
     */
    public const PERL_GENERAL_MESSAGE_START = 'perl: warning:';

    public function __construct(
        protected string $PathToPerl,
        protected string $PathToFitPerlScript
    ) {
    }

    public function getConvertibleFileExtension(): string
    {
        return 'fit';
    }

    public function getConvertedFileName(string $inputFile): string
    {
        return $inputFile . 'temp';
    }

    protected function buildCommand(string $inputFile, string $outputFile): string
    {
        return sprintf('%s %s "%s" 1>"%s"', $this->PathToPerl, $this->PathToFitPerlScript, $inputFile, $outputFile);
    }

    protected function checkFirstLineOfOutput(string $firstLine)
    {
        if (trim($firstLine) !== 'SUCCESS') {
            $message = 'Reading *.fit-file failed. First line was "' . $firstLine . '".';

            if (str_starts_with($firstLine, self::PERL_FIT_ERROR_MESSAGE_START)) {
                throw new UnexpectedContentException($message);
            }

            if (str_starts_with($firstLine, self::PERL_GENERAL_MESSAGE_START)) {
                $message .= PHP_EOL . PHP_EOL . 'See https://github.com/Runalyze/Runalyze/issues/1701';

                throw new InstallationSpecificException($message);
            }

            throw new ParserException($message);
        }
    }
}
