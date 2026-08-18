<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CreateNotificationCommand extends ContainerAwareCommand
{
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

    /**
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! $this->validateInput($input, $output)) {
            return 1;
        }

        if (! ($input->getOption('force'))  ) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? (y/n)', false);

            if (! $helper->ask($input, $output, $question)) {
                return null;
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

        return null;
    }

    protected function validateInput(InputInterface $input, OutputInterface $output): bool
    {
        return (
            $this->checkValidation($this->validateTemplate($input->getArgument('template')), $output, 'Template must exist in /data/views/notifications/.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('lang')), $output, 'Language keys must be alphabetic strings.') &&
            $this->checkValidation($this->validateLanguage($input->getOption('exclude-lang')), $output, 'Language keys to exclude must be alphabetic strings.') &&
            $this->checkValidation($this->validateAccountIds($input->getOption('account')), $output, 'Account IDs must be integers.') &&
            $this->checkValidation($this->validateLifetime($input->getOption('lifetime')), $output, 'Lifetime must be an integer.')
        );
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
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * @param string|bool|string[] $lang
     */
    protected function validateLanguage(array $lang): bool
    {
        return array_reduce(
            $lang,
            function (bool $state, $value): bool {
                return $state && ctype_alpha($value);
            },
            true
        );
    }

    /**
     * @param string|bool|string[] $accountIds
     */
    protected function validateAccountIds(array $accountIds): bool
    {
        return array_reduce(
            $accountIds,
            function (bool $state, $value): bool {
                return $state && ctype_digit($value);
            },
            true
        );
    }

    /**
     * @param null|string $lifetime
     */
    protected function validateLifetime($lifetime): bool
    {
        return (null === $lifetime || ctype_digit($lifetime));
    }

    /**
     * @param string $template
     * @param int|null $lifetime [days]
     */
    protected function createNotification($template, $lifetime): \Runalyze\Bundle\CoreBundle\Entity\Notification
    {
        if (null !== $lifetime) {
            $lifetime = (int) $lifetime;
        }

        return Notification::createFromMessage(
            new TemplateBasedMessage($template, $lifetime),
            new Account()
        );
    }

    /**
     * @param string|bool|non-empty-array<string> $accountIds
     */
    protected function insertSingleNotifications(Notification $notification, array $accountIds): int
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('CoreBundle:Notification');

        /** @var AccountRepository $accountRepository */
        $accountRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('CoreBundle:Account');
        $num = 0;

        foreach ($accountIds as $id) {
            $account = $accountRepository->find($id);

            if (null !== $account) {
                $accountsNotification = clone $notification;
                $accountsNotification->setAccount($account);

                $notificationRepository->save($accountsNotification);
                $num++;
            }
        }

        return $num;
    }

    /**
     * @param string|bool|string[] $lang
     * @param string|bool|string[] $excludeLang
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
    )
    {
        $prefix = $this->getContainer()
            ->getParameter('database_prefix');
        $accountWhere = $this->getWhereToFindRelevantAccounts($lang, $excludeLang, $lastActionBefore, $lastActionAfter, $registrationBefore, $registrationAfter);

        $statement = $this->getContainer()
            ->get('doctrine.dbal.default_connection')
            ->prepare(
                'INSERT INTO `'.$prefix.'notification` (`messageType`, `createdAt`, `expirationAt`, `data`, `account_id`) '.
                        'SELECT ?, ?, ?, ?, `a`.`id` FROM `'.$prefix.'account` AS `a` WHERE '.$accountWhere
            );

        $statement->execute([
            $notification->getMessageType(),
            $notification->getCreatedAt(),
            $notification->getExpirationAt(),
            $notification->getData()
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
    ): string
    {
        $whereCondition = [];
        $exclude = false;

        if ($lastActionAfter) {
            $whereCondition[] = '`a`.`lastaction` > '.(int) $lastActionAfter;
        }

        if ($lastActionBefore) {
            $whereCondition[] = '`a`.`lastaction` < '.(int) $lastActionBefore;
        }

        if ($registrationAfter) {
            $whereCondition[] = '`a`.`registerdate` > '.(int) $registrationAfter;
        }

        if ($registrationBefore) {
            $whereCondition[] = '`a`.`registerdate` < '.(int) $registrationBefore;

        }

        if ($excludeLang !== []) {
            $exclude = true;
            $lang = $excludeLang;
        }

        if ($lang !== []) {
            $whereCondition[] = '`a`.`language` '.($exclude ? 'NOT' : '').' IN ("'.implode('", "', $lang).'")';
        }

        if ($whereCondition !== []) {
            return implode(' AND ', $whereCondition);
        }

        return '1';
    }
}
