<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20180118193118 extends AbstractMigration
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
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                ADD `avg_impact_gs_left` FLOAT DEFAULT NULL AFTER `vertical_ratio`,
                                ADD `avg_impact_gs_right` FLOAT DEFAULT NULL AFTER `avg_impact_gs_left`,
                                ADD `avg_braking_gs_left` FLOAT DEFAULT NULL AFTER `avg_impact_gs_right`,
                                ADD `avg_braking_gs_right` FLOAT DEFAULT NULL AFTER `avg_braking_gs_left`,
                                ADD `avg_footstrike_type_left` TINYINT UNSIGNED DEFAULT NULL COMMENT \'(DC2Type:tinyint)\' AFTER `avg_braking_gs_right`,
                                ADD `avg_footstrike_type_right` TINYINT UNSIGNED DEFAULT NULL COMMENT \'(DC2Type:tinyint)\' AFTER `avg_footstrike_type_left`,
                                ADD `avg_pronation_excursion_left` FLOAT DEFAULT NULL AFTER `avg_footstrike_type_right`,
                                ADD `avg_pronation_excursion_right` FLOAT DEFAULT NULL AFTER `avg_pronation_excursion_left`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                DROP `avg_impact_gs_left`,
                                DROP `avg_impact_gs_right`,
                                DROP `avg_braking_gs_left`,
                                DROP `avg_braking_gs_right`,
                                DROP `avg_footstrike_type_left`,
                                DROP `avg_footstrike_type_right`,
                                DROP `avg_pronation_excursion_left`,
                                DROP `avg_pronation_excursion_right`;
                                ');
    }
}
