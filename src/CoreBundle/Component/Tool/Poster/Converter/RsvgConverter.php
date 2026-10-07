<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RsvgConverter extends AbstractSvgToPngConverter
{
    /**
     * @param string $rsvgPath absolut path to rsvg[-convert]
     */
    public function __construct(string $rsvgPath)
    {
        $this->Command = $rsvgPath;
    }

    public function setHeight($height): void
    {
        $this->Parameter[] = '-h ' . (int) $height;
    }

    public function setWidth($width): void
    {
        $this->Parameter[] = '-w ' . (int) $width;
    }

    public function callConverter($source, $target): int
    {
        if (new Filesystem()->exists($source)) {
            $command = array_merge(
                [$this->Command, '-f', 'png'],
                $this->Parameter,
                [$source, '-o', $target]
            );
            $builder = new Process($command);
            $builder->run();
            return $builder->getExitCode() ?? 1;
        }

        return 1;
    }
}
