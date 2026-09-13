<?php

/**
 * This file contains class::SelectFile
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

/**
 * Select file
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class SelectFile extends Select
{
    protected bool $UseFallback = false;

    /**
     * Boolean flag: allow uppercase variants of file extensions
     */
    protected bool $AllowUppercaseVariants = true;

    /**
     * Construct
     * @param string $default
     * @param array $options [optional]
     */
    public function __construct($default, array $options = [])
    {
        $options = array_merge(
            [
                'folder' => '',
                'extensions' => [],
                'filename_only' => true,
            ],
            $options
        );

        parent::__construct($default, $options);
    }

    /**
     * Allowed file extensions
     */
    public function extensions(): array
    {
        return $this->Options['extensions'];
    }

    public function allowUppercaseVariants(bool $flag = true): void
    {
        $this->AllowUppercaseVariants = $flag;
    }

    /**
     * Set value
     * @param mixed $value new value
     * @throws \InvalidArgumentException
     */
    public function set($value): void
    {
        if ($this->Options['filename_only']) {
            $value = basename($value);
        }

        parent::set($value);
    }

    /**
     * File allowed?
     */
    protected function valueIsAllowed(string $fileName): bool
    {
        $firstChar = substr($fileName, 0, 1);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($firstChar === '.' || $firstChar === '/') {
            return false;
        }

        if ($this->AllowUppercaseVariants) {
            $extension = strtolower($extension);
        }

        return in_array($extension, $this->Options['extensions']);
    }

    /**
     * Options
     */
    public function options(): array
    {
        $Options = [];
        $Folder = $this->Options['folder'];

        if (! is_array($Folder)) {
            $Folder = [$Folder];
        }

        foreach ($Folder as $Fold) {
            $handle = opendir(FRONTEND_PATH . '../' . $Fold);
            if ($handle) {
                if ($this->Options['filename_only']) {
                    $Fold = '';
                }

                while (false !== ($file = readdir($handle))) {
                    if ($this->valueIsAllowed($file)) {
                        $Options[$Fold . $file] = $file;
                    }
                }
            }
        }

        return $Options;
    }
}
