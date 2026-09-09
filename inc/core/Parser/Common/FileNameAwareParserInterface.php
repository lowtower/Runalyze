<?php

declare(strict_types=1);

namespace Runalyze\Parser\Common;

use Runalyze\Parser\Activity\Common\ParserInterface;

interface FileNameAwareParserInterface extends ParserInterface
{
    public function setFileName(string $file);
}
