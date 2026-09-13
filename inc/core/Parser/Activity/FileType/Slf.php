<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractMultipleParserWithSubParser;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\FileContentAwareParserTrait;

class Slf extends AbstractMultipleParserWithSubParser implements FileContentAwareParserInterface
{
    use FileContentAwareParserTrait;

    public function parse(): void
    {
        if ($this->isSlf3()) {
            $this->useSubParser(new Slf3());
        } elseif ($this->isSlf4()) {
            $this->useSubParser(new Slf4());
        } else {
            throw new UnsupportedFileException();
        }
    }

    protected function isSlf3(): bool
    {
        return strpos($this->FileContent, '<LogEntries') !== false;
    }

    protected function isSlf4(): bool
    {
        return strpos($this->FileContent, '<Entries') !== false;
    }
}
