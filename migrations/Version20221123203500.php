<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20221123203500 extends AbstractMigration
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
        return 'Add pulse_avg_active to training.';
    }

    public function up(Schema $schema): void
    {
        $table = '`' . $this->DatabasePrefix . 'training`';

        $this->addSql('ALTER TABLE ' . $table . '
                                ADD `pulse_avg_active` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `pulse_max`');
        $this->addSql('update ' . $this->DatabasePrefix . 'training 
                                set pulse_avg_active = pulse_avg 
                                where pulse_avg > 0 and pulse_avg_active is null and (splits is null or splits not like \'%-R%\')');
    }

    public function down(Schema $schema): void
    {
        $table = '`' . $this->DatabasePrefix . 'training`';
        $this->addSql('ALTER TABLE ' . $table . '
                               DROP `pulse_avg_active`;');
    }
}
