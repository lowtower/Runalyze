<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170202220915 extends AbstractMigration
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
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'route` SET `geohashes` = NULL WHERE `geohashes`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'route` SET `elevations_original` = NULL WHERE `elevations_original`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'route` SET `elevations_corrected` = NULL WHERE `elevations_corrected`= ""');

        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `time` = NULL WHERE `time`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `distance` = NULL WHERE `distance`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `heartrate` = NULL WHERE `heartrate`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `cadence` = NULL WHERE `cadence`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `power` = NULL WHERE `power`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `temperature` = NULL WHERE `temperature`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `groundcontact` = NULL WHERE `groundcontact`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `vertical_oscillation` = NULL WHERE `vertical_oscillation`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `groundcontact_balance` = NULL WHERE `groundcontact_balance`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `smo2_0` = NULL WHERE `smo2_0`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `smo2_1` = NULL WHERE `smo2_1`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `thb_0` = NULL WHERE `thb_0`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `thb_1` = NULL WHERE `thb_1`= ""');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `pauses` = NULL WHERE `pauses`= ""');

    }

    public function down(Schema $schema): void
    {
    }
}
