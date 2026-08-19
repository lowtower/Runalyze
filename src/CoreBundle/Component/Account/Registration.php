<?php

namespace Runalyze\Bundle\CoreBundle\Component\Account;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Profile\Sport\SportProfile;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class Registration
{
    /**
     * @var Account
     */
    protected $Account;

    /**
     * @var \Doctrine\Persistence\ObjectManager
     */
    protected $em;

    /**
     * @var object[]
     */
    protected $specialVars;

    public function __construct(\Doctrine\Persistence\ObjectManager $em, Account $account)
    {
        $this->em = $em;
        $this->Account = $account;
    }

    /**
     * Add hash to activation_hash
     */
    public function requireAccountActivation()
    {
        $this->Account->setActivationHash(self::getNewSalt());
    }

    /**
     * @param string $timezoneName
     */
    public function setTimezoneByName($timezoneName)
    {
        try {
            $this->Account->setTimezone(Timezone::getEnumByOriginalName($timezoneName));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->Account->setTimezone(Timezone::getEnumByOriginalName(date_default_timezone_get()));
        }
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->Account->setLanguage($locale);
    }

    /**
     * @param string $password
     */
    public function setPassword($password, EncoderFactoryInterface $encoderFactory)
    {
        $encoder = $encoderFactory->getEncoder($this->Account);
        $this->Account->setPassword($encoder->encodePassword($password, $this->Account->getSalt()));
    }

    private function setEmptyData()
    {
        $this->setEquipmentData();
        $this->setPluginData();
        $this->setSportData();
        $this->collectSpecialVars();
        $this->setTypeData();
        $this->setSpecialVars();
    }

    private function setEquipmentData()
    {
        $equipmentType = [
            [__('Shoes'), 0],
            [__('Clothes'), 1],
            [__('Bikes'), 0],
        ];

        foreach ($equipmentType as $eqType) {
            $Type = new EquipmentType();
            $Type->setAccount($this->Account);
            $Type->setName($eqType[0]);
            $Type->setInput($eqType[1]);
            $this->em->persist($Type);
        }
    }

    private function setPluginData()
    {
        $pluginData = [
            ['RunalyzePluginPanel_Sports', 'panel', 1, 1],
            ['RunalyzePluginPanel_Rechenspiele', 'panel', 1, 2],
            ['RunalyzePluginPanel_Prognose', 'panel', 2, 3],
            ['RunalyzePluginPanel_Equipment', 'panel', 2, 4],
            ['RunalyzePluginPanel_Sportler', 'panel', 1, 5],
            ['RunalyzePluginStat_Analyse', 'stat', 1, 2],
            ['RunalyzePluginStat_Statistiken', 'stat', 1, 1],
            ['RunalyzePluginStat_Wettkampf', 'stat', 1, 3],
            ['RunalyzePluginStat_Wetter', 'stat', 1, 5],
            ['RunalyzePluginStat_Rekorde', 'stat', 2, 6],
            ['RunalyzePluginStat_Strecken', 'stat', 2, 7],
            ['RunalyzePluginStat_Trainingszeiten', 'stat', 2, 8],
            ['RunalyzePluginStat_Trainingspartner', 'stat', 2, 9],
            ['RunalyzePluginStat_Hoehenmeter', 'stat', 2, 10],
            ['RunalyzePluginStat_Tag', 'stat', 1, 11],
            ['RunalyzePluginPanel_Ziele', 'panel', 0, 6],
        ];

        foreach ($pluginData as $pData) {
            $Plugin = new Plugin();
            $Plugin->setKey($pData[0]);
            $Plugin->setType($pData[1]);
            $Plugin->setActive($pData[2]);
            $Plugin->setOrder($pData[3]);
            $Plugin->setAccount($this->Account);
            $this->em->persist($Plugin);
        }

        $this->em->flush();
    }

    private function setSportData()
    {
        $sportData = [
            [__('Running'), 'icons8-Running', 0, 880, 140, 1, PaceEnum::SECONDS_PER_KILOMETER, 0, 1, true, SportProfile::RUNNING],
            [__('Swimming'), 'icons8-Swimming', 0, 743, 130, 1, PaceEnum::SECONDS_PER_100M, 0, 0, true, SportProfile::SWIMMING],
            [__('Biking'), 'icons8-Regular-Biking', 0, 770, 120, 1, PaceEnum::KILOMETER_PER_HOUR, 1, 1, true, SportProfile::CYCLING],
            [__('Gymnastics'), 'icons8-Yoga', 1, 280, 100, 0, PaceEnum::KILOMETER_PER_HOUR, 0, 0, false, null],
            [__('Other'), 'icons8-Sports-Mode', 0, 500, 120, 0, PaceEnum::KILOMETER_PER_HOUR, 0, 0, false, null],
        ];

        foreach ($sportData as $sData) {
            $Sport = new Sport();
            $Sport->setAccount($this->Account);
            $Sport->setName($sData[0]);
            $Sport->setImg($sData[1]);
            $Sport->setShort($sData[2]);
            $Sport->setKcal($sData[3]);
            $Sport->setHfavg($sData[4]);
            $Sport->setDistances($sData[5]);
            $Sport->setSpeed($sData[6]);
            $Sport->setPower($sData[7]);
            $Sport->setOutside($sData[8]);
            $Sport->setIsMain($sData[9]);
            $Sport->setInternalSportId($sData[10]);

            $this->em->persist($Sport);
        }

        $this->em->flush();
    }

    private function setTypeData()
    {
        $TypeData = [
            [__('Jogging'), __('JOG'), 143, 0],
            [__('Fartlek'), __('FL'), 150, 1],
            [__('Interval training'), __('IT'), 165, 1],
            [__('Tempo Run'), __('TR'), 165, 1],
            [__('Race'), __('RC'), 190, 1],
            [__('Regeneration Run'), __('RG'), 128, 0],
            [__('Long Slow Distance'), __('LSD'), 150, 1],
            [__('Warm-up'), __('WU'), 128, 0],
            [__('Pace Goal'), __('PG'), 150, 1],
            [__('Grundlagenausdauer'), __('GA'), 130, 0],
        ];

        foreach ($TypeData as $tData) {
            $Type = new Type();
            $Type->setAccount($this->Account);
            $Type->setName($tData[0]);
            $Type->setAbbr($tData[1]);
            $Type->setHrAvg($tData[2]);
            $Type->setQualitySession($tData[3]);
            $Type->setSport($this->specialVars['RUNNINGSPORT']);
            $this->em->persist($Type);
        }

        $this->em->flush();
    }

    private function collectSpecialVars()
    {
        /** @var Sport[] $sport */
        $sport = $this->em->getRepository('CoreBundle:Sport')
            ->findByAccount($this->Account);

        foreach ($sport as $item) {
            switch ($item->getImg()) {
                case 'icons8-Running':
                    $this->specialVars['RUNNINGSPORT'] = $item;
                    break;
                case 'icons8-Regular-Biking':
                    $this->specialVars['BIKESPORT'] = $item;
                    break;
            }
        }

        $equipmentType = $this->em->getRepository('CoreBundle:EquipmentType');
        $equipmentClothes = $equipmentType->findOneBy([
            'name' => __('Clothes'),
            'account' => $this->Account->getId(),
        ]);
        $equipmentShoes = $equipmentType->findOneBy([
            'name' => __('Shoes'),
            'account' => $this->Account->getId(),
        ]);
        $equipmentBikes = $equipmentType->findOneBy([
            'name' => __('Bikes'),
            'account' => $this->Account->getId(),
        ]);

        $this->specialVars['EQUIPMENT_CLOTHES'] = $equipmentClothes;
        $this->specialVars['EQUIPMENT_SHOES'] = $equipmentShoes;
        $this->specialVars['EQUIPMENT_BIKES'] = $equipmentBikes;

    }

    private function setSpecialVars()
    {
        $Clothes = [__('long sleeve'), __('T-shirt'), __('singlet'), __('jacket'), __('long pants'), __('shorts'), __('gloves'), __('hat')];
        foreach ($Clothes as $cloth) {
            $Equipment = new Equipment();
            $Equipment->setAccount($this->Account);
            $Equipment->setName($cloth);
            $Equipment->setType($this->specialVars['EQUIPMENT_CLOTHES']);
            $this->em->persist($Equipment);
        }

        foreach (['MAINSPORT', 'RUNNINGSPORT'] as $cKey) {
            $Conf = new Conf();
            $Conf->setAccount($this->Account);
            $Conf->setCategory('general');
            $Conf->setKey($cKey);
            $Conf->setValue($this->specialVars['RUNNINGSPORT']->getId());
            $this->em->persist($Conf);
        }

        $Running = $this->specialVars['RUNNINGSPORT'];
        $Running->setMainEquipmenttype($this->specialVars['EQUIPMENT_SHOES']);
        $Running->addEquipmentType($this->specialVars['EQUIPMENT_CLOTHES']);
        $Running->addEquipmentType($this->specialVars['EQUIPMENT_SHOES']);

        $this->em->persist($Running);

        $Biking = $this->specialVars['BIKESPORT'];
        $Biking->addEquipmentType($this->specialVars['EQUIPMENT_BIKES']);

        $this->em->persist($Biking);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @return Account
     */
    public function registerAccount()
    {
        $this->em->persist($this->Account);
        $this->em->flush();
        $this->setEmptyData();

        return $this->Account;
    }

    /**
     * @return Sport
     */
    public function getRegisteredSportForRunning()
    {
        if (! isset($this->specialVars['RUNNINGSPORT'])) {
            throw new \LogicException('Account has to be registered first.');
        }

        return $this->specialVars['RUNNINGSPORT'];
    }

    /**
     * @return Sport
     */
    public function getRegisteredSportForCycling()
    {
        if (! isset($this->specialVars['BIKESPORT'])) {
            throw new \LogicException('Account has to be registered first.');
        }

        return $this->specialVars['BIKESPORT'];
    }

    /**
     * @return EquipmentType
     */
    public function getRegisteredEquipmentTypeClothes()
    {
        if (! isset($this->specialVars['EQUIPMENT_CLOTHES'])) {
            throw new \LogicException('Account has to be registered first.');
        }

        return $this->specialVars['EQUIPMENT_CLOTHES'];
    }

    public static function getNewSalt(): string
    {
        return bin2hex(random_bytes(16));
    }
}
