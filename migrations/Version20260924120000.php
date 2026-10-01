<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260924120000 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly string $DatabasePrefix
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription(): string
    {
        return 'Normalize legacy training.wind_deg values outside the valid 0..359 range (>= 360 or negative) back into that range.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'training` SET wind_deg = MOD(MOD(wind_deg, 360) + 360, 360) WHERE wind_deg >= 360 OR wind_deg < 0');
    }

    /**
     * @throws IrreversibleMigration always, since this migration cannot be undone
     */
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'This data-cleanup migration is irreversible: normalized wind_deg values '
            .'(e.g. 360 -> 0, -10 -> 350) cannot be reliably distinguished from originally-valid readings.'
        );
    }
}
