<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Message;

final class BackupGenerationMessage
{
    public function __construct(
        private readonly int $accountId,
        private readonly string $exportType
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
