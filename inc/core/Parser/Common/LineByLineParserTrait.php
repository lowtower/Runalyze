<?php

namespace Runalyze\Parser\Common;

use Runalyze\Import\Exception\ParserException;

trait LineByLineParserTrait
{
    protected string $FileName;

    /**
     * @var resource|null
     */
    protected $Handle;

    public function setFileName(string $file): void
    {
        $this->FileName = $file;
    }

    public function parseFileLineByLine(): void
    {
        if (! method_exists($this, 'parseLine')) {
            throw new \RuntimeException('Line-by-line parser must implement parseLine($line)');
        }

        $this->Handle = @fopen($this->FileName, 'r');

        if ($this->Handle === false) {
            throw new ParserException(sprintf('File "%s" can\'t be opened for reading.', $this->FileName));
        }

        while (($line = stream_get_line($this->Handle, 4096, PHP_EOL)) !== false && ! feof($this->Handle)) {
            $this->parseLine($line);
        }

        fclose($this->Handle);
    }
}
