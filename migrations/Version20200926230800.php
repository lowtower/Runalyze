<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20200926230800 extends AbstractMigration implements ContainerAwareInterface
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

        $this->addSql('ALTER TABLE `' . $prefix . 'training`
                                ADD `fit_anaerobic_training_effect` DECIMAL(2,1) UNSIGNED DEFAULT NULL AFTER `fit_training_effect`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . 'training`
                                DROP `fit_anaerobic_training_effect`;
                                ');
    }
}
