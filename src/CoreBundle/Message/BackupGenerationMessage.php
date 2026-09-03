<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Message;

final class BackupGenerationMessage
{
    private int $accountId;

    private string $exportType;

    public function __construct(int $accountId, string $exportType)
    {
        $this->accountId = $accountId;
        $this->exportType = $exportType;
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
