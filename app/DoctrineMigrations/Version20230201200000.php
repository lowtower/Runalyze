<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TSC: Creates new column for split additional informations.
 */
class Version20230201200000 extends AbstractMigration implements ContainerAwareInterface
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
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE `' . $prefix . 'training`
                                ADD `splits_additional` JSON DEFAULT NULL AFTER `splits`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . 'training`
                                DROP `splits_additional`;
                                ');
    }
}