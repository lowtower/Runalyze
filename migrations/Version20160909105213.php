<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20160909105213 extends AbstractMigration
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
        return 'Remove \'tools\' from plugins.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM `' . $this->DatabasePrefix . 'plugin` WHERE `type`="tool"');
    }

    public function down(Schema $schema): void
    {
        // Tools must be installed by hand if required
    }
}
