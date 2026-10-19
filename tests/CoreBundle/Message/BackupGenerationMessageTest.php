<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Message;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;

final class BackupGenerationMessageTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $message = new BackupGenerationMessage(42, 'json');

        $this->assertSame(42, $message->getAccountId());
        $this->assertSame('json', $message->getExportType());
    }

    public function testGettersReturnConstructorValuesForSqlExport(): void
    {
        $message = new BackupGenerationMessage(7, 'sql');

        $this->assertSame(7, $message->getAccountId());
        $this->assertSame('sql', $message->getExportType());
    }
}
