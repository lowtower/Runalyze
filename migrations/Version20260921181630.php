<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20260921181630 extends AbstractMigration implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDescription() : string
    {
        return 'Make elevations_source nullable and set default to NULL.';
    }

    public function up(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('app.database_prefix');

        $this->addSql('ALTER TABLE `' . $prefix . 'route` CHANGE `elevations_source` `elevations_source` VARCHAR(255) NULL DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('app.database_prefix');

        $this->addSql('ALTER TABLE `' . $prefix . 'route` CHANGE `elevations_source` `elevations_source` VARCHAR(255) NOT NULL DEFAULT \'\'');
    }
}
