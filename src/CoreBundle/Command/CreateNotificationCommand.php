<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CreateNotificationCommand extends Command
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:notifications:create')
            ->setDescription('Create global notifications')
            ->addArgument('template', InputArgument::REQUIRED, 'Template file')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Languages to select accounts')
            ->addOption('exclude-lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Excluded languages to select accounts')
            ->addOption('account', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Account ids')
            ->addOption('lifetime', null, InputOption::VALUE_REQUIRED, 'Lifetime [days]')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation of notifications without prompt')
            ->addOption('last-action-before', null, InputOption::VALUE_OPTIONAL, 'Last action before x (timestamp)')
            ->addOption('last-action-after', null, InputOption::VALUE_OPTIONAL, 'Last action after x (timestamp)')
            ->addOption('registration-before', null, InputOption::VALUE_OPTIONAL, 'Registration before x (timestamp)')
            ->addOption('registration-after', null, InputOption::VALUE_OPTIONAL, 'Registration after x (timestamp)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->validateInput($input, $output)) {
            return 1;
        }

        if (! ($input->getOption('force'))) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? (y/n)', false);

            if (! $helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $notification = $this->createNotification($input->getArgument('template'), $input->getOption('lifetime'));

        if (! empty($input->getOption('account'))) {
            $num = $this->insertSingleNotifications($notification, $input->getOption('account'));
        } else {
            $num = $this->insertNotificationsWithSubquery(
                $notification,
                $input->getOption('lang'),
                $input->getOption('exclude-lang'),
                $input->getOption('last-action-before'),
                $input->getOption('last-action-after'),
                $input->getOption('registration-before'),
                $input->getOption('registration-after')
            );
        }

        $output->writeln(sprintf('<info>%u notifications have been created.</info>', $num));
        $output->writeln('');

        return 0;
    }

    protected function validateInput(InputInterface $input, OutputInterface $output): bool
    {
        return
            $this->checkValidation($this->validateTemplate($input->getArgument('template')), $output, 'Template must exist in /data/views/notifications/.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('lang')), $output, 'Language keys must be alphabetic strings.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('exclude-lang')), $output, 'Language keys to exclude must be alphabetic strings.') &&
            $this->checkValidation($this->validateAccountIds($input->getOption('account')), $output, 'Account IDs must be integers.') &&
            $this->checkValidation($this->validateLifetime($input->getOption('lifetime')), $output, 'Lifetime must be an integer.')
        ;
    }

    /**
     * @param bool $success
     */
    protected function checkValidation($success, OutputInterface $output, string $messageOnError): bool
    {
        if (! $success) {
            $output->writeln(sprintf('<error>Invalid input: %s</error>', $messageOnError));
            $output->writeln('');

            return false;
        }

        return true;
    }

    /**
     * @param string $templateName
     */
    protected function validateTemplate($templateName): bool
    {
        try {
            new TemplateBasedMessage($templateName);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    protected function validateLanguage(array $lang): bool
    {
        return array_reduce(
            $lang,
            fn (bool $state, $value): bool => $state && ctype_alpha($value),
            true
        );
    }

    protected function validateAccountIds(array $accountIds): bool
    {
        return array_reduce(
            $accountIds,
            fn (bool $state, $value): bool => $state && ctype_digit($value),
            true
        );
    }

    protected function validateLifetime(?string $lifetime): bool
    {
        return $lifetime === null || ctype_digit($lifetime);
    }

    protected function createNotification(string $template, ?int $lifetime): \Runalyze\Bundle\CoreBundle\Entity\Notification
    {
        return Notification::createFromMessage(
            new TemplateBasedMessage($template, $lifetime),
            new Account()
        );
    }

    protected function insertSingleNotifications(Notification $notification, array $accountIds): int
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->doctrine->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Notification::class);

        /** @var AccountRepository $accountRepository */
        $accountRepository = $this->doctrine->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Account::class);
        $num = 0;

        foreach ($accountIds as $id) {
            $account = $accountRepository->find($id);

            if ($account !== null) {
                $accountsNotification = clone $notification;
                $accountsNotification->setAccount($account);

                $notificationRepository->save($accountsNotification);
                $num++;
            }
        }

        return $num;
    }

    /**
     * @param mixed $lastActionBefore
     * @param mixed $lastActionAfter
     * @param mixed $registrationBefore
     * @param mixed $registrationAfter
     * @return int number of created notifications
     */
    protected function insertNotificationsWithSubquery(
        Notification $notification,
        array $lang,
        array $excludeLang,
        $lastActionBefore,
        $lastActionAfter,
        $registrationBefore,
        $registrationAfter
    ): int {
        $prefix = $this->parameterBag->get('app.database_prefix');
        $accountWhere = $this->getWhereToFindRelevantAccounts($lang, $excludeLang, $lastActionBefore, $lastActionAfter, $registrationBefore, $registrationAfter);

        $statement = $this->doctrine->getConnection()
            ->prepare(
                'INSERT INTO `' . $prefix . 'notification` (`messageType`, `createdAt`, `expirationAt`, `data`, `account_id`) ' .
                                    'SELECT ?, ?, ?, ?, `a`.`id` FROM `' . $prefix . 'account` AS `a` WHERE ' . $accountWhere
            );

        $statement->execute([
            $notification->getMessageType(),
            $notification->getCreatedAt(),
            $notification->getExpirationAt(),
            $notification->getData(),
        ]);

        return $statement->rowCount();
    }

    /**
     * @param mixed $lastActionBefore
     * @param mixed $lastActionAfter
     * @param mixed $registrationBefore
     * @param mixed $registrationAfter
     */
    protected function getWhereToFindRelevantAccounts(
        array $lang,
        array $excludeLang,
        $lastActionBefore,
        $lastActionAfter,
        $registrationBefore,
        $registrationAfter
    ): string {
        $whereCondition = [];
        $exclude = false;

        if ($lastActionAfter) {
            $whereCondition[] = '`a`.`lastaction` > ' . (int) $lastActionAfter;
        }

        if ($lastActionBefore) {
            $whereCondition[] = '`a`.`lastaction` < ' . (int) $lastActionBefore;
        }

        if ($registrationAfter) {
            $whereCondition[] = '`a`.`registerdate` > ' . (int) $registrationAfter;
        }

        if ($registrationBefore) {
            $whereCondition[] = '`a`.`registerdate` < ' . (int) $registrationBefore;

        }

        if ($excludeLang !== []) {
            $exclude = true;
            $lang = $excludeLang;
        }

        if ($lang !== []) {
            $whereCondition[] = '`a`.`language` ' . ($exclude ? 'NOT' : '') . ' IN ("' . implode('", "', $lang) . '")';
        }

        if ($whereCondition !== []) {
            return implode(' AND ', $whereCondition);
        }

        return '1';
    }
}
