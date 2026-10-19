<?php

namespace Runalyze\Bundle\CoreBundle\Component\Account;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use InvalidArgumentException;
use LogicException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Profile\Sport\SportProfile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class Registration
{
    public $Account;

    private ?Sport $runningSport = null;

    private ?Sport $bikeSport = null;

    private ?EquipmentType $equipmentTypeClothes = null;

    private ?EquipmentType $equipmentTypeShoes = null;

    private ?EquipmentType $equipmentTypeBikes = null;

    public function __construct(
        protected ObjectManager $em,
        protected UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function setAccount(Account $Account): void
    {
        $this->Account = $Account;
    }

    /**
     * Add hash to activation_hash
     *
     * @throws Exception if no secure random source is available
     */
    public function requireAccountActivation(): void
    {
        $this->Account->setActivationHash(self::getNewSalt());
    }

    /**
     * @throws InvalidArgumentException if neither the given nor the default timezone is known
     */
    public function setTimezoneByName(string $timezoneName): void
    {
        try {
            $this->Account->setTimezone(Timezone::getEnumByOriginalName($timezoneName));
        } catch (InvalidArgumentException) {
            $this->Account->setTimezone(Timezone::getEnumByOriginalName(date_default_timezone_get()));
        }
    }

    public function setLocale(string $locale): void
    {
        $this->Account->setLanguage($locale);
    }

    public function setPassword(string $password): void
    {
        $this->Account->setPassword($this->passwordHasher->hashPassword($this->Account, $password));
    }

    /**
     * @throws LogicException if a default sport or equipment type could not be found
     */
    private function setEmptyData(): void
    {
        $this->setEquipmentData();
        $this->setPluginData();
        $this->setSportData();
        $this->collectSpecialVars();
        $this->setTypeData();
        $this->setSpecialVars();
    }

    private function setEquipmentData(): void
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

    private function setPluginData(): void
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

    private function setSportData(): void
    {
        $sportData = [
            [__('Running'), 'icons8-Running', false, 880, 140, true, PaceEnum::SECONDS_PER_KILOMETER, false, true, true, SportProfile::RUNNING],
            [__('Swimming'), 'icons8-Swimming', false, 743, 130, true, PaceEnum::SECONDS_PER_100M, false, false, true, SportProfile::SWIMMING],
            [__('Biking'), 'icons8-Regular-Biking', false, 770, 120, true, PaceEnum::KILOMETER_PER_HOUR, true, true, true, SportProfile::CYCLING],
            [__('Gymnastics'), 'icons8-Yoga', true, 280, 100, false, PaceEnum::KILOMETER_PER_HOUR, false, false, false, null],
            [__('Other'), 'icons8-Sports-Mode', false, 500, 120, true, PaceEnum::KILOMETER_PER_HOUR, false, false, false, null],
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

    /**
     * @throws LogicException if the default running sport is not available
     */
    private function setTypeData(): void
    {
        $TypeData = [
            [__('Jogging'), __('JOG'), 143, false],
            [__('Fartlek'), __('FL'), 150, true],
            [__('Interval training'), __('IT'), 165, true],
            [__('Tempo Run'), __('TR'), 165, true],
            [__('Race'), __('RC'), 190, true],
            [__('Regeneration Run'), __('RG'), 128, false],
            [__('Long Slow Distance'), __('LSD'), 150, true],
            [__('Warm-up'), __('WU'), 128, false],
            [__('Pace Goal'), __('PG'), 150, true],
            [__('Grundlagenausdauer'), __('GA'), 130, false],
        ];

        $runningSport = $this->getRegisteredSportForRunning();

        foreach ($TypeData as $tData) {
            $Type = new Type();
            $Type->setAccount($this->Account);
            $Type->setName($tData[0]);
            $Type->setAbbr($tData[1]);
            $Type->setHrAvg($tData[2]);
            $Type->setQualitySession($tData[3]);
            $Type->setSport($runningSport);
            $this->em->persist($Type);
        }

        $this->em->flush();
    }

    /**
     * @throws LogicException if a default sport or equipment type could not be found for the new account
     */
    private function collectSpecialVars(): void
    {
        /** @var SportRepository $repository */
        $repository = $this->em->getRepository(Sport::class);

        foreach ($repository->findAllFor($this->Account) as $item) {
            switch ($item->getImg()) {
                case 'icons8-Running':
                    $this->runningSport = $item;
                    break;
                case 'icons8-Regular-Biking':
                    $this->bikeSport = $item;
                    break;
            }
        }

        if (! $this->runningSport instanceof Sport) {
            throw new LogicException('Default running sport could not be found for the new account.');
        }

        if (! $this->bikeSport instanceof Sport) {
            throw new LogicException('Default cycling sport could not be found for the new account.');
        }

        $equipmentTypeRepository = $this->em->getRepository(EquipmentType::class);

        $this->equipmentTypeClothes = $this->findEquipmentType($equipmentTypeRepository, __('Clothes'));
        $this->equipmentTypeShoes = $this->findEquipmentType($equipmentTypeRepository, __('Shoes'));
        $this->equipmentTypeBikes = $this->findEquipmentType($equipmentTypeRepository, __('Bikes'));
    }

    /**
     * @param ObjectRepository<EquipmentType> $repository
     *
     * @throws LogicException if the equipment type does not exist for the new account
     */
    private function findEquipmentType(ObjectRepository $repository, string $name): EquipmentType
    {
        $equipmentType = $repository->findOneBy([
            'name' => $name,
            'account' => $this->Account->getId(),
        ]);

        if (! $equipmentType instanceof EquipmentType) {
            throw new LogicException(sprintf('Equipment type "%s" could not be found for the new account.', $name));
        }

        return $equipmentType;
    }

    /**
     * @throws LogicException if the special sports or equipment types have not been collected yet
     */
    private function setSpecialVars(): void
    {
        $running = $this->getRegisteredSportForRunning();
        $biking = $this->getRegisteredSportForCycling();
        $clothes = $this->getRegisteredEquipmentTypeClothes();
        $shoes = $this->equipmentTypeShoes;
        $bikes = $this->equipmentTypeBikes;

        if (! $shoes instanceof EquipmentType || ! $bikes instanceof EquipmentType) {
            throw new LogicException('Account has to be registered first.');
        }

        $Clothes = [__('long sleeve'), __('T-shirt'), __('singlet'), __('jacket'), __('long pants'), __('shorts'), __('gloves'), __('hat')];
        foreach ($Clothes as $cloth) {
            $Equipment = new Equipment();
            $Equipment->setAccount($this->Account);
            $Equipment->setName($cloth);
            $Equipment->setType($clothes);
            $this->em->persist($Equipment);
        }

        foreach (['MAINSPORT', 'RUNNINGSPORT'] as $cKey) {
            $Conf = new Conf();
            $Conf->setAccount($this->Account);
            $Conf->setCategory('general');
            $Conf->setKey($cKey);
            $Conf->setValue((string) $running->getId());
            $this->em->persist($Conf);
        }

        $running->setMainEquipmenttype($shoes);
        $running->addEquipmentType($clothes);
        $running->addEquipmentType($shoes);

        $this->em->persist($running);

        $biking->addEquipmentType($bikes);

        $this->em->persist($biking);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @throws LogicException if a default sport or equipment type could not be found for the new account
     */
    public function registerAccount(): Account
    {
        $this->em->persist($this->Account);
        $this->em->flush();
        $this->setEmptyData();

        return $this->Account;
    }

    /**
     * @throws LogicException if the account has not been registered yet
     */
    public function getRegisteredSportForRunning(): Sport
    {
        if (! $this->runningSport instanceof Sport) {
            throw new LogicException('Account has to be registered first.');
        }

        return $this->runningSport;
    }

    /**
     * @throws LogicException if the account has not been registered yet
     */
    public function getRegisteredSportForCycling(): Sport
    {
        if (! $this->bikeSport instanceof Sport) {
            throw new LogicException('Account has to be registered first.');
        }

        return $this->bikeSport;
    }

    /**
     * @throws LogicException if the account has not been registered yet
     */
    public function getRegisteredEquipmentTypeClothes(): EquipmentType
    {
        if (! $this->equipmentTypeClothes instanceof EquipmentType) {
            throw new LogicException('Account has to be registered first.');
        }

        return $this->equipmentTypeClothes;
    }

    /**
     * @throws Exception if no secure random source is available
     */
    public static function getNewSalt(): string
    {
        return bin2hex(random_bytes(16));
    }
}
