<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Message;

final class BackupGenerationMessage
{
    public function __construct(
        private int $accountId,
        private string $exportType
    ) {
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getExportType(): string
    {
        return $this->exportType;
    }
}
