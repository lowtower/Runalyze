<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20211215213500 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly string $DatabasePrefix
    ) {
        parent::__construct($connection, $logger);
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata` ADD `performance_condition` longtext AFTER `pronation_excursion_right`,
                                                          ADD `respiration_rate` longtext AFTER `performance_condition`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata`
                                DROP `performance_condition`,
                                DROP `respiration_rate`;
                                ');
    }
}
