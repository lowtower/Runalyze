<?php

/**
 * This file contains class::AbstractExporter
 * @package Runalyze\Export\File
 */

namespace Runalyze\Export\File;

use Runalyze\Export\AbstractExporter;
use Runalyze\Util\LocalTime;
use Runalyze\View\Activity\Context;

/**
 * Create exporter for given type
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\File
 */
abstract class AbstractFileExporter extends AbstractExporter
{
    /**
     * @var string
     */
    public const URL = 'activity/{id}/export/file/{enum}';

    /**
     * File content to write
     */
    protected string $FileContent = '';

    /**
     * Get extension
     */
    abstract public function extension(): string;

    /**
     * Export
     */
    abstract protected function createFile();

    abstract public function enum(): int;

    public function iconClass(): string
    {
        return 'far fa-file-alt';
    }

    public function url(): string
    {
        return str_replace(
            ['{id}', '{enum}'],
            [(string) $this->Context->activity()->id(), (string) $this->enum()],
            self::URL
        );
    }

    /**
     * Add indents to file content
     */
    final protected function formatFileContentAsXML()
    {
        $XML = new \DOMDocument('1.0');
        $XML->preserveWhiteSpace = false;
        $XML->loadXML($this->FileContent);
        $XML->formatOutput = true;

        $this->FileContent = $XML->saveXML();
    }

    /**
     * Get file content
     */
    final public function fileContent(): string
    {
        return $this->FileContent;
    }

    /**
     * Create file but don't start download
     */
    final public function createFileWithoutDirectDownload()
    {
        $this->createFile();
    }

    /**
     * Download content
     */
    final public function downloadFile()
    {
        header('Content-Type: ' . $this->mimeType());
        header('Content-Disposition: attachment; filename=' . $this->filename() . '');

        $this->createFile();

        print $this->FileContent;
    }

    /**
     * @see https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     */
    abstract protected function mimeType(): string;

    /**
     * Get filename
     */
    final public function filename(): string
    {
        if (! $this->Context instanceof Context) {
            return 'undefined.' . $this->extension();
        }

        return self::fileNameStart() . LocalTime::date('Y-m-d_H-i', $this->Context->activity()->timestamp()) . '_' . $this->Context->activity()->id() . '.' . $this->extension();
    }

    /**
     * Get file name start
     */
    public static function fileNameStart(): string
    {
        return \SessionAccountHandler::getId() . '-Activity_';
    }
}
