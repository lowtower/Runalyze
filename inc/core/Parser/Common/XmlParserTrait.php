<?php

namespace Runalyze\Parser\Common;

use Runalyze\Import\Exception\UnsupportedFileException;

trait XmlParserTrait
{
    protected \SimpleXMLElement $Xml;

    public function setXml(\SimpleXMLElement $xml)
    {
        $this->Xml = $xml;
    }

    /**
     * @throws UnsupportedFileException
     */
    public function setFileContent(string $content)
    {
        libxml_use_internal_errors(true);

        $this->Xml = simplexml_load_string(
            $this->correctXmlNamespace($this->removeBomFromFileContent($content)),
            null,
            LIBXML_PARSEHUGE
        );

        if ($this->Xml === false) {
            $errors = libxml_get_errors();

            throw new UnsupportedFileException($errors[0]->message);
        }
    }

    protected function correctXmlNamespace(string $content): string
    {
        return str_replace('xmlns=', 'ns=', $this->removeBomFromFileContent($content));
    }

    protected function removeBomFromFileContent(string $content): string
    {
        return mb_substr($content, mb_strpos($content, '<'));
    }
}
