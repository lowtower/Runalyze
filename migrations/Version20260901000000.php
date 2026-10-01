<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20260901000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove obsolete bernard queue tables, replaced by symfony/messenger.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS bernard_messages');
        $this->addSql('DROP TABLE IF EXISTS bernard_queues');
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty: recreating the legacy Bernard schema
        // is not supported. Restore from a backup if needed.
    }
}
