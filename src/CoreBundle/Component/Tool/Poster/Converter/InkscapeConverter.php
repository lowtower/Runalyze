<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class InkscapeConverter extends AbstractSvgToPngConverter
{
    /**
     * @param string $inkscapePath absolut path to inkscape
     */
    public function __construct($inkscapePath)
    {
        $this->Command = $inkscapePath;
    }

    public function setHeight($height)
    {
        $this->Parameter[] = '-h ' . (int) $height;
    }

    public function setWidth($width)
    {
        $this->Parameter[] = '-w ' . (int) $width;
    }

    public function callConverter($source, $target): int
    {
        if ((new Filesystem())->exists($source)) {
            $command = array_merge(
                [$this->Command, '-z', '-o', $target],
                $this->Parameter,
                [$source]
            );
            $process = new Process($command);
            $process->run();
            return $process->getExitCode() ?? 1;
        }

        return 1;
    }
}
