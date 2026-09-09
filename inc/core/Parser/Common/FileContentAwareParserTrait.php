<?php

namespace Runalyze\Parser\Common;

trait FileContentAwareParserTrait
{
    protected string $FileContent;

    public function setFileContent(string $content)
    {
        $this->FileContent = $content;
    }
}
