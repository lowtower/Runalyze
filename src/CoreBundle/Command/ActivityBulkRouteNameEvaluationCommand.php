<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update activities with details of a route.
 */
#[\Symfony\Component\Console\Attribute\AsCommand(name: 'runalyze:activity:bulk-routeval', description: 'Bulk update of trainings route-name evaluation')]
class ActivityBulkRouteNameEvaluationCommand extends Command
{
    private bool $override = false;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $osmOverpassUrl,
        private readonly TrainingRepository $trainingRepository,
        private readonly ActivityContextAdapterFactory $activityContextAdapterFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'username')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of activity')
            ->addOption('nexted', null, InputOption::VALUE_NONE, 'Update next activities')
            ->addOption('override', null, InputOption::VALUE_NONE, 'Override existing route informations');
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->trainingRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var AccountRepository $repository */
        $repository = $this->doctrine->getRepository(Account::class);
        $user = $repository->loadUserByUsername($input->getArgument('username'));
        $activityId = (int) $input->getArgument('id');
        $nexted = $input->getOption('nexted') ? 1 : 0;
        $this->override = (bool) $input->getOption('override');

        if ($user === null) {
            $output->writeln('<fg=red>Unknown User</>');

            return Command::FAILURE;
        }

        if (empty($this->osmOverpassUrl)) {
            $output->writeln('<fg=red>Set environment variable OSM_OVERPASS_URL</>');

            return Command::FAILURE;
        }

        $output->writeln('Start route-name evaluation with id=' . $activityId . ' and nexted=' . $nexted . ', override=' . $this->override);

        $exitCode = 0;

        do {
            $activity = $this->processActivity($output, $activityId, $user);

            if (! $activity instanceof Training) {
                $exitCode = 1;
                break;
            }

            $activityId = $this->getTrainingRepository()
                ->getIdOfNextActivity($activity);
        } while ($nexted === 1 && $activityId != null);

        $output->writeln('');
        $output->writeln('Done.');

        return $exitCode;
    }

    protected function processActivity(OutputInterface $output, int $activityId, Account $user): ?Training
    {
        $activity = $this->getTrainingRepository()
            ->findForAccount($activityId, $user->getId());

        if (! $activity instanceof Training) {
            $output->writeln('<fg=red>Activity not found: id=' . $activityId . '</>');

            return null;
        }

        if ($activity->hasRoute()) {
            if (empty($activity->getRouteName()) || $this->override) {
                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $contextAdapter = $this->activityContextAdapterFactory->getAdapterFor($context);

                $updated = $contextAdapter->guessRouteDetails();

                if ($updated) {
                    $this->getTrainingRepository()
                        ->save($activity);

                    $output->writeln('<fg=green>successfully updated id=' . $activityId . ' / ' . $activity->getTitle()
                        . ' / ' . $activity->getRouteName() . '</>');
                } else {
                    $output->writeln('<fg=yellow>skip id=' . $activityId . ' / ' . $activity->getTitle() . '</>');
                }
            } else {
                $output->writeln('<fg=yellow>skip id=' . $activityId . ' / ' . $activity->getTitle() . '</>');
            }
        }

        return $activity;
    }
}
