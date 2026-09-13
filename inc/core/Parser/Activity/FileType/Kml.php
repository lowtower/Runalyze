<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractMultipleParserWithSubParser;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\FileContentAwareParserTrait;

class Kml extends AbstractMultipleParserWithSubParser implements FileContentAwareParserInterface
{
    use FileContentAwareParserTrait;

    public function parse(): void
    {
        if ($this->isTomTomFile()) {
            $this->useSubParser(new KmlExtended());
        } elseif ($this->isNamespacedFile()) {
            $this->useSubParser(new KmlGoogle('kml'));
        } elseif ($this->isDefaultFile()) {
            $this->useSubParser(new KmlGoogle());
        } else {
            throw new UnsupportedFileException('Kml file is not from TomTom or Google or does not contain any coordinates.');
        }
    }

    protected function isTomTomFile(): bool
    {
        return str_contains($this->FileContent, '<gx:Track');
    }

    protected function isNamespacedFile(): bool
    {
        return str_contains($this->FileContent, '<kml:coordinates');
    }

    protected function isDefaultFile(): bool
    {
        return str_contains($this->FileContent, '<coordinates');
    }
}
