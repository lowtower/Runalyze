<?php

declare(strict_types=1);

namespace Runalyze\Parser\Common;

use Runalyze\Parser\Activity\Common\ParserInterface;

interface FileContentAwareParserInterface extends ParserInterface
{
    public function setFileContent(string $content);
}
