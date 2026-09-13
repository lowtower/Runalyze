<?php

/**
 * This file contains class::GZipWriter
 * @package Runalyze\Util\File
 */

namespace Runalyze\Util\File;

/**
 * Class for writing big gzipped files
 *
 * @author Hannes Christiansen
 * @package Runalyze\Util\File
 */
class GZipWriter
{
    /**
     * Resource for file
     * @var ?resource
     */
    protected $Resource;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $fileName)
    {
        $this->Resource = gzopen($fileName, 'wb');

        if (! $this->Resource) {
            throw new \RuntimeException('The file "' . $fileName . '" couldn\'t be opened for writing.');
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->finish();
    }

    /**
     * Add string to file
     */
    public function addToFile(string $string): void
    {
        if ($this->isOpen()) {
            gzwrite($this->Resource, $string);
        }
    }

    /**
     * Is file open?
     */
    protected function isOpen(): bool
    {
        return $this->Resource !== null;
    }

    /**
     * Finish: close file
     */
    public function finish(): void
    {
        if ($this->isOpen()) {
            gzclose($this->Resource);
            $this->Resource = null;
        }
    }
}
