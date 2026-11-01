<?php

declare(strict_types=1);

namespace Runalyze\\Migrations;

use Doctrine\\DBAL\\Connection;
use Doctrine\\DBAL\\Schema\\Schema;
use Doctrine\\Migrations\\AbstractMigration;
use Doctrine\\Migrations\\Exception\\IrreversibleMigration;
use Psr\\Log\\LoggerInterface;

final class Version20261101120000 extends AbstractMigration
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
        return 'Widen account.password (64 -> 255) so migrated argon2id/bcrypt hashes fit.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `" . $this->DatabasePrefix . "account` MODIFY `password` VARCHAR(255) NOT NULL DEFAULT ''");
    }

    /**
     * @throws IrreversibleMigration always: already-migrated hashes would be truncated
     */
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Already-migrated argon2id hashes cannot be shrunk back to 64 chars.');
    }
}