<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\ConfRepository;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Entity\UserRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResult;
use Runalyze\Parameter\Boolean;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Internal class represents a maximal zone-target heart-rate.
 * #TSC
 */
class MaxZoneHR
{
    private bool $import;

    private ManagerRegistry $doctrine;

    // is import allowed by configuration
    public int $MaxHR = 0;

    // max HR of all imported activities
    public ?int $Time; // timestamp of the first highest HR

    public function __construct(\Runalyze\Bundle\CoreBundle\Entity\Account $account, ManagerRegistry $doctrine)
    {
        // we can't use here in a command not the \Runalyze\Configuration::ActivityForm()
        // so read the conf via repository
        $this->doctrine = $doctrine;
        /** @var ConfRepository $repository */
        $repository = $this->doctrine->getRepository(Conf::class);

        $conf = $repository->findByAccountAndKey($account, 'IMPORT_MAX_HR_USER');
        // some confuse logic with the Conf boolean parameter...to get the boolean value from the Conf
        $bool = new Boolean(false);
        $bool->setFromString($conf === null ? '' : $conf->getValue());

        $this->import = $bool->value();
    }

    public function collect($activity, $container)
    {
        // used this HR if
        // 1. import is allowed by configuration (=IMPORT_MAX_HR_USER)
        // 2. its the main sport (in normal cases 'running')
        // 3. a MaxZoneHR is in the import/FIT and its the highest of all the imported
        if ($this->import
            && method_exists($activity, 'getSport') && $activity->getSport()
                ->isMain()
            && isset($container->ActivityData->MaxHeartRateUser)
            && $container->ActivityData->MaxHeartRateUser > $this->MaxHR) {
            $this->MaxHR = $container->ActivityData->MaxHeartRateUser;
            $this->Time = $container->Metadata->getTimestamp();
        }
    }

    public function isCollected(): bool
    {
        return $this->MaxHR > 0;
    }
}

class ActivityBulkImportCommand extends Command
{
    // move files after import to "this"-folder
    private ?string $moveFolder = null;

    protected array $FailedImports = [];

    // 2 dim-array with 1-Idx=sportId & 2-Idx=Equipment
    private array $sportEquipment = [];

    private ParameterBagInterface $parameterBag;

    private ManagerRegistry $doctrine;

    private TokenStorageInterface $tokenStorage;

    private Filesystem $filesystem;

    private FileImporter $fileImporter;

    private ActivityDataContainerFilter $activityDataContainerFilter;

    private ActivityDataContainerToActivityContextConverter $activityDataContainerConverter;

    private ActivityContextAdapterFactory $activityContextAdapterFactory;

    private ConfigurationManager $configurationManager;

    public function __construct(
        ParameterBagInterface $parameterBag,
        ManagerRegistry $doctrine,
        TokenStorageInterface $tokenStorage,
        Filesystem $filesystem,
        FileImporter $fileImporter,
        ActivityDataContainerFilter $activityDataContainerFilter,
        ActivityDataContainerToActivityContextConverter $activityDataContainerConverter,
        ActivityContextAdapterFactory $activityContextAdapterFactory,
        ConfigurationManager $configurationManager
    ) {
        parent::__construct();

        $this->parameterBag = $parameterBag;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->filesystem = $filesystem;
        $this->fileImporter = $fileImporter;
        $this->activityDataContainerFilter = $activityDataContainerFilter;
        $this->activityDataContainerConverter = $activityDataContainerConverter;
        $this->activityContextAdapterFactory = $activityContextAdapterFactory;
        $this->configurationManager = $configurationManager;
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:activity:bulk-import')
            ->setDescription('Bulk import of activity files')
            ->addArgument('username', InputArgument::REQUIRED, 'username')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to files')
            // #TSC: new optional to set the sports profile if the imported file has no sport (f.e. GPX files) -> usage "--sport=mountaineering"
            ->addOption('sport', null, InputOption::VALUE_OPTIONAL, 'Override sport profile')
            // #TSC: new optional to move files to other folder under the path-argument -> usage "--move=foldername"
            ->addOption('move', null, InputOption::VALUE_OPTIONAL, 'Move imported files to specified folder');
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->doctrine->getRepository(Training::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var AccountRepository $repository */
        $repository = $this->doctrine->getRepository(Account::class);
        $user = $repository->loadUserByUsername($input->getArgument('username'));

        if ($user === null) {
            $output->writeln('<fg=red>Unknown User</>');

            return 1;
        }

        # TSC create equipment array for automatic mapping
        $this->createEquiqmentArray($user, $output);

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $dataDirectory = $this->parameterBag->get('app.data_directory');
        $path = $input->getArgument('path');

        // #TSC - new arguments/options
        $sport = $input->getOption('sport');
        $this->moveFolder = $input->getOption('move');

        if (! empty($sport)) {
            $output->writeln('<info>using sport=' . $sport . '</info>');
        }

        if (! empty($this->moveFolder)) {
            $output->writeln('<info>move imported files to ' . $this->moveFolder . '</info>');
        }

        $it = new \FilesystemIterator($path);

        $files = [];

        foreach ($it as $fileinfo) {
            $file = $fileinfo->getFilename();

            if (! is_file($path . '/' . $file)) {
                continue;
            }

            // TSC: do not use "'bulk-import'.uniqid()" for bulk uploads, because the filename is set in the activity title
            // $filename = 'bulk-import'.uniqid().$file;
            $filename = $file;
            $this->filesystem->copy($path . '/' . $file, $dataDirectory . '/import/' . $filename);
            $files[] = $dataDirectory . '/import/' . $filename;
        }

        asort($files);

        $importResult = $this->fileImporter->importFiles($files);
        $importResult->completeAndFilterResults($this->activityDataContainerFilter);

        $defaultLocation = $this->configurationManager
            ->getList()
            ->getActivityForm()
            ->getDefaultLocationForWeatherForecast();

        $maxZoneHR = new MaxZoneHR($user, $this->doctrine);

        /** @var FileImportResult $result */
        foreach ($importResult as $result) {
            foreach ($result->getContainer() as $container) {

                // #TSC override the sportname to set the sport profile
                if (! empty($sport)) {
                    $container->Metadata->setSportName($sport);
                }

                $activity = $this->containerToActivity($container, $user);

                // #TSC set the equipments for this training
                $this->setEquipmentIfPossible($activity, $output);

                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $contextAdapter = $this->activityContextAdapterFactory->getAdapterFor($context);
                $output->writeln('<info>' . $result->getOriginalFileName() . '</info>');

                if ($contextAdapter->isPossibleDuplicate()) {
                    $output->writeln('<fg=yellow> ... is a duplicate</>');

                    $this->moveFile($path, $result->getOriginalFileName(), $output);
                    break;
                }

                $contextAdapter->guessWeatherConditions($defaultLocation, $user);
                $contextAdapter->guessRouteDetails();
                $this->getTrainingRepository()
                    ->save($activity);
                $output->writeln('<fg=green> ... successfully imported</>');
                $this->moveFile($path, $result->getOriginalFileName(), $output);

                // collect the max pulse from the ZoneTarget in case of main-sports
                $maxZoneHR->collect($activity, $container);
            }
        }

        if ($maxZoneHR->isCollected()) {
            $this->checkStoreMaxZoneHR($user, $output, $maxZoneHR);
        }

        if ($this->FailedImports !== []) {
            $output->writeln('');
            $output->writeln('<fg=red>Failed imports:</>');

            foreach ($this->FailedImports as $fileName => $message) {
                $output->writeln('<fg=red> - ' . $fileName . ': ' . $message . '</>');
            }
        }

        $output->writeln('');
        $output->writeln('Done.');

        return 0;
    }

    /**
     * TSC: move the imported file to a specified folder under path-argument.
     */
    private function moveFile(string $path, string $importedFilename, OutputInterface $output)
    {
        if (! empty($this->moveFolder)) {
            $path_parts = pathinfo($importedFilename);
            $source = $path . '/' . $path_parts['basename'];
            $moveTo = $path . '/' . $this->moveFolder . '/' . $path_parts['basename'];
            echo 'moveTo:' . $moveTo;

            if (! rename($source, $moveTo)) {
                $output->writeln('<fg=red>Cant move to ' . $moveTo . '</>');
            }
        }
    }

    protected function containerToActivity(ActivityDataContainer $container, Account $account): Training
    {
        return $this->activityDataContainerConverter->getActivityFor($container, $account);
    }

    /**
     * #TSC
     * Creates a two-array where first IDX=sportsId and second IDX=the_equipment.
     * this is only build for "single-choice" equipments of the account.
     * if there are more sports assigned, every sport/equipment combination is stored in the array.
     */
    private function createEquiqmentArray(Account $account, OutputInterface $output)
    {
        $accountEquipment = $account->getEquipment();

        // build a array where first index is the sports-id
        $this->sportEquipment = [];
        $output->writeln('Use account equipments for automatic-equipment-mapping of ' . $account->getUsername() . ':');
        foreach ($accountEquipment as $eqp) {
            if ($eqp->getType()->getInput() == EquipmentType::CHOICE_SINGLE) {
                // use only the sports from the equipment (for the bulk import)
                foreach ($eqp->getSport() as $sport) {
                    $idx = $sport->getId();
                    $this->sportEquipment[$idx][] = $eqp;

                    $output->writeln('- ' . $eqp->getType()->getName() . ':' . $eqp->getName() . ' -> sport=' . $idx . '/' . $sport->getName());
                }
            }
        }
    }

    /**
     * #TSC
     * automatic mapping of preloaded/preselected equipments (in method createEquiqmentArray) to the imported activity.
     * this means, that the equipments stored in the sportEquipment array are searched for relevant equipment that
     * fits to the activity date. if there more than one equipment found within ONE type/category, the mapping is ignored.
     */
    private function setEquipmentIfPossible(Training $activity, OutputInterface $output)
    {
        $actDate = $activity->getDateTime();
        $sportId = $activity->getSport()
            ->getId();

        // use equipmentId as key
        $equipments = [];

        // use typeId(categoryId) as key
        $typeUnique = [];

        // are there equipments for this (activity) sport?
        if (isset($this->sportEquipment[$sportId])) {
            // got to all equipments for this sport
            // its possible that several equipments exists with different time-ranges
            foreach ($this->sportEquipment[$sportId] as $eqp) {

                // set the end-date to time 23:59 for the compare
                $endDate = null;
                if ($eqp->getDateEnd() !== null) {
                    $endDate = clone $eqp->getDateEnd();
                    $endDate->setTime(23, 59, 59);
                }

                if (($eqp->getDateStart() === null || $eqp->getDateStart() < $actDate) && ($endDate === null || $actDate <= $endDate)) {
                    // the date fits
                    $equipments[$eqp->getId()] = $eqp;

                    // store if this type already selected (to avoid multiple mappings of the same category)
                    if (! array_key_exists($eqp->getType()->getId(), $typeUnique)) {
                        $typeUnique[$eqp->getType()->getId()] = true;
                    } else {
                        $typeUnique[$eqp->getType()->getId()] = false;
                    }
                }
            }
        }

        foreach ($equipments as $v) {
            // add every equipment, if it not "unique"
            if ($typeUnique[$v->getType()->getId()]) {
                $activity->addEquipment($v);
            } else {
                $output->writeln('<fg=yellow>more than one active equipment found for activity-date=' . $actDate->format('Y-m-d H:i') .
                    ': category=' . $v->getType()->getName() . ' and equipment=' . $v->getId() . '/' . $v->getName() .
                    '; mapping canceled for this category!</>');
            }
        }
    }

    /**
     * #TSC
     * Check if the reported max pulse differs from the current one. If yes, create new "User" entity.
     */
    private function checkStoreMaxZoneHR(Account $account, OutputInterface $output, MaxZoneHR $maxZoneHR)
    {
        /** @var UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);
        $oldUser = $repository->getLatestEntryFor($account);

        // if new max differs from the stored one
        if ($oldUser !== null && $maxZoneHR->MaxHR != $oldUser->getPulseMax()) {
            // clone current user and create a new one
            $newuser = $oldUser->cloneObjectForForm();
            $newuser->setTime($maxZoneHR->Time);
            $newuser->setPulseMax($maxZoneHR->MaxHR);
            $newuser->setNotes('New max pulse is set while importing main-sports activity.');
            $repository->save($newuser, $account);

            $output->writeln('<fg=yellow>Max heart-rate of user is changed from ' . $oldUser->getPulseMax() . ' to ' . $maxZoneHR->MaxHR . '</>');
        }
    }
}
