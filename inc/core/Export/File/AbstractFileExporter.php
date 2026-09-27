<?php

namespace Runalyze\Export\File;

use DOMDocument;
use Runalyze\Export\AbstractExporter;
use Runalyze\Util\LocalTime;
use Runalyze\View\Activity\Context;
use SessionAccountHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Create exporter for given type
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\File
 */
abstract class AbstractFileExporter extends AbstractExporter
{
    public const URL = 'activity/{id}/export/file/{enum}';

    protected string $FileContent = '';

    abstract public function extension(): string;

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

    final protected function formatFileContentAsXML(): void
    {
        $XML = new DOMDocument('1.0');
        $XML->preserveWhiteSpace = false;
        $XML->loadXML($this->FileContent);
        $XML->formatOutput = true;

        $this->FileContent = $XML->saveXML();
    }

    final public function fileContent(): string
    {
        return $this->FileContent;
    }

    final public function createFileWithoutDirectDownload(): void
    {
        $this->createFile();
    }

    /**
     * @deprecated in favor of toResponse(), which returns a proper Symfony Response
     */
    final public function downloadFile(): void
    {
        header('Content-Type: ' . $this->mimeType());
        header('Content-Disposition: attachment; filename=' . $this->filename() . '');

        $this->createFile();

        print $this->FileContent;
    }

    final public function toResponse(): Response
    {
        $this->createFile();

        $response = new Response($this->fileContent());
        $response->headers->set('Content-Type', $this->mimeType());
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->filename())
        );

        return $response;
    }

    /**
     * @see https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     */
    abstract protected function mimeType(): string;

    final public function filename(): string
    {
        if (! $this->Context instanceof Context) {
            return 'undefined.' . $this->extension();
        }

        return self::fileNameStart() . LocalTime::date('Y-m-d_H-i', $this->Context->activity()->timestamp()) . '_' . $this->Context->activity()->id() . '.' . $this->extension();
    }

    public static function fileNameStart(): string
    {
        return SessionAccountHandler::getId() . '-Activity_';
    }
}
