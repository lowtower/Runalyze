<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\InstallationSpecificException;
use Runalyze\Import\Exception\UnexpectedContentException;
use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractMultipleParserWithSubParser;
use Runalyze\Parser\Common\FileNameAwareParserInterface;
use Runalyze\Parser\Common\LineByLineParserTrait;

class Fit extends AbstractMultipleParserWithSubParser implements FileNameAwareParserInterface
{
    use LineByLineParserTrait;

    public const string PERL_FIT_ERROR_MESSAGE_START = 'main::Garmin::FIT';

    public const string PERL_GENERAL_MESSAGE_START = 'perl: warning:';

    protected bool $ParsingStarted = false;

    protected FitActivity $CurrentSubParser;

    public function __construct()
    {
        parent::__construct();

        $this->CurrentSubParser = new FitActivity();
    }

    public function parse(): void
    {
        $this->parseFileLineByLine();
        $this->finishCurrentSubParser();
    }

    protected function parseLine(string $line)
    {
        if (! $this->ParsingStarted) {
            $this->checkFirstLine($line);
        } else {
            if (str_ends_with($line, 'NAME=sport NUMBER=12') && ! $this->currentContainerIsEmpty()) {
                $this->finishCurrentSubParser();
                $this->startAnotherSubParser();
            }

            $this->CurrentSubParser->parseLine($line);
        }
    }

    protected function currentContainerIsEmpty(): bool
    {
        return $this->CurrentSubParser->getActivityDataContainer()
            ->ContinuousData->isEmpty();
    }

    protected function finishCurrentSubParser()
    {
        $this->CurrentSubParser->finishParsing();

        $this->Container[] = $this->CurrentSubParser->getActivityDataContainer();
    }

    protected function startAnotherSubParser()
    {
        $this->CurrentSubParser = new FitActivity();
        $this->CurrentSubParser->readMetadataForMultiSessionFrom(end($this->Container)->Metadata);
    }

    protected function checkFirstLine(string $line)
    {
        if (trim($line) !== 'SUCCESS') {
            fclose($this->Handle);

            $this->throwErrorForFirstLine($line);
        }

        $this->ParsingStarted = true;
    }

    /**
     * @param string $line
     *
     * @throws InstallationSpecificException
     * @throws UnexpectedContentException
     * @throws UnsupportedFileException
     */
    protected function throwErrorForFirstLine($line)
    {
        $message = 'Reading *.fit-file failed. First line was "' . $line . '".';

        if (str_starts_with($line, self::PERL_FIT_ERROR_MESSAGE_START)) {
            throw new UnexpectedContentException($message);
        }

        if (str_starts_with($line, self::PERL_GENERAL_MESSAGE_START)) {
            $message .= NL . NL . 'See https://github.com/Runalyze/Runalyze/issues/1701';

            throw new InstallationSpecificException($message);
        }

        throw new UnsupportedFileException($message);
    }
}
