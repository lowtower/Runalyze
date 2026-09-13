<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractMultipleParserWithSubParser;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\FileContentAwareParserTrait;

class Xml extends AbstractMultipleParserWithSubParser implements FileContentAwareParserInterface
{
    use FileContentAwareParserTrait;

    public function parse(): void
    {
        if ($this->isPolarFile()) {
            $this->useSubParser(new XmlPolar());
        } elseif ($this->isSuuntoFile()) {
            $this->useSubParser(new XmlSuunto());
        } elseif ($this->isRunningAheadFile()) {
            throw new UnsupportedFileException('Support for xml-files from RunningAHEAD has been dropped.');
        } else {
            throw new UnsupportedFileException();
        }
    }

    protected function isRunningAheadFile(): bool
    {
        return str_contains($this->FileContent, '<RunningAHEADLog');
    }

    protected function isSuuntoFile(): bool
    {
        return str_contains($this->FileContent, '<header>');
    }

    protected function isPolarFile(): bool
    {
        return str_contains($this->FileContent, '<polar-exercise-data');
    }
}
