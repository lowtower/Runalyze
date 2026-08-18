<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractMultipleParserWithSubParser;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\FileContentAwareParserTrait;

class Kml extends AbstractMultipleParserWithSubParser implements FileContentAwareParserInterface
{
    use FileContentAwareParserTrait;

    public function parse()
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
        return strpos($this->FileContent, '<gx:Track') !== false;
    }

    protected function isNamespacedFile(): bool
    {
        return strpos($this->FileContent, '<kml:coordinates') !== false;
    }

    protected function isDefaultFile(): bool
    {
        return strpos($this->FileContent, '<coordinates') !== false;
    }
}
