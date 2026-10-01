<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20180117090538 extends AbstractMigration
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
        return 'Add runscribe data to trackdata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata` ADD `impact_gs_left` longtext AFTER `thb_0`,
                                ADD `impact_gs_right` longtext AFTER `impact_gs_left`,
                                ADD `braking_gs_left` longtext AFTER `impact_gs_right`,
                                ADD `braking_gs_right` longtext AFTER `braking_gs_left`,
                                ADD `footstrike_type_left` longtext AFTER `braking_gs_right`,
                                ADD `footstrike_type_right` longtext AFTER `footstrike_type_left`,
                                ADD `pronation_excursion_left` longtext AFTER `footstrike_type_right`,
                                ADD `pronation_excursion_right` longtext AFTER `pronation_excursion_left`
                                ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata`
                                DROP `impact_gs_left`,
                                DROP `impact_gs_right`,
                                DROP `braking_gs_left`,
                                DROP `braking_gs_right`,
                                DROP `footstrike_type_left`,
                                DROP `footstrike_type_right`,
                                DROP `pronation_excursion_left`,
                                DROP `pronation_excursion_right`
                                ');
    }
}
