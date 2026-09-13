<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractMultipleParserWithSubParser;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\FileContentAwareParserTrait;

class Csv extends AbstractMultipleParserWithSubParser implements FileContentAwareParserInterface
{
    use FileContentAwareParserTrait;

    public function parse(): void
    {
        if ($this->isEpsonFile()) {
            $this->useSubParser(new CsvEpson());
        } elseif ($this->isWahooFile()) {
            $this->useSubParser(new CsvWahoo());
        } else {
            throw new UnsupportedFileException();
        }
    }

    protected function isEpsonFile(): bool
    {
        return str_contains($this->FileContent, '[[Training]]');
    }

    protected function isWahooFile(): bool
    {
        return str_contains($this->FileContent, 'File created by Wahoo Fitness iPhone App');
    }
}
