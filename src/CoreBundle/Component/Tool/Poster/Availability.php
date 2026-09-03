<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Symfony\Component\Filesystem\Filesystem;

class Availability
{
    protected string $RsvgPath;

    protected string $InkscapePath;

    protected string $Python3Path;

    /**
     * @param string $rsvgPath absolute path of rsvg[-convert]
     * @param string $inkscapePath absolute path of inkscape
     * @param string $python3Path absolute path of Python3
     */
    public function __construct(string $rsvgPath, string $inkscapePath, string $python3Path)
    {
        $this->RsvgPath = $rsvgPath;
        $this->InkscapePath = $inkscapePath;
        $this->Python3Path = $python3Path;
    }

    public function isAvailable(): bool
    {
        return $this->isPythonAvailable() && $this->isRsvgConverterAvailable();
    }

    protected function isInkscapeAvailable(): bool
    {
        return (new Filesystem())->exists($this->InkscapePath);
    }

    protected function isRsvgConverterAvailable(): bool
    {
        return (new Filesystem())->exists($this->RsvgPath);
    }

    protected function isPythonAvailable(): bool
    {
        return (new Filesystem())->exists($this->Python3Path);
    }
}
