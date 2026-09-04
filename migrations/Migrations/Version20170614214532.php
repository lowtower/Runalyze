<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20170614214532 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . 'training` ADD `percentage_hilly` decimal(3,2) unsigned DEFAULT NULL AFTER `elevation`, ADD `climb_score` decimal(3,1) unsigned DEFAULT NULL AFTER `elevation`');

        $this->addSql('UPDATE `' . $prefix . 'route` SET `lock` = 1 WHERE `elevations_original` IS NOT NULL OR `elevations_corrected` IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . 'training` DROP COLUMN `percentage_hilly`, DROP COLUMN `climb_score`');
    }
}
