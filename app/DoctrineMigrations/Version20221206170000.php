<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20221206170000 extends AbstractMigration implements ContainerAwareInterface
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
                                ADD `fit_load_peak` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_self_evaluation_perceived_effort`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . 'training`
                                DROP `fit_load_peak`;
                                ');
    }
}
