<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'runalyze:mails:send', description: 'Send out a mails to users with custom templates in data/views/mail/custom')]
class MailingCommand extends Command
{
    private const string CUSTOM_MAIL_DIRECTORY = '/mail/custom/';

    /**
     * Option name => [DQL condition, query parameter name]
     */
    private const array TIMESTAMP_FILTERS = [
        'last-action-after' => ['a.lastaction > :lastActionAfter', 'lastActionAfter'],
        'last-action-before' => ['a.lastaction < :lastActionBefore', 'lastActionBefore'],
        'registration-after' => ['a.registerdate > :registrationAfter', 'registrationAfter'],
        'registration-before' => ['a.registerdate < :registrationBefore', 'registrationBefore'],
    ];

    public function __construct(
        #[Autowire(param: 'app.data_directory')]
        private readonly string $dataDirectory,
        private readonly AccountMailer $mailer,
        private readonly AccountRepository $accountRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('template', InputArgument::REQUIRED, 'Template file')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Mail subject')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Languages to select accounts')
            ->addOption('exclude-lang', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Excluded languages to select accounts')
            ->addOption('account', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Account ids')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation of notifications without prompt')
            ->addOption('last-action-before', null, InputOption::VALUE_OPTIONAL, 'Last action before x (timestamp)')
            ->addOption('last-action-after', null, InputOption::VALUE_OPTIONAL, 'Last action after x (timestamp)')
            ->addOption('registration-before', null, InputOption::VALUE_OPTIONAL, 'Registration before x (timestamp)')
            ->addOption('registration-after', null, InputOption::VALUE_OPTIONAL, 'Registration after x (timestamp)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->validateInput($input, $output)) {
            return Command::FAILURE;
        }

        $accounts = $this->findAccounts($input);

        if (! $input->getOption('force')) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $output->writeln(sprintf('<info>%u mail(s) will be sent.</info>', count($accounts)));
            $question = new ConfirmationQuestion('Continue with this action? (y/n)', false);

            if (! $helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $subject = (string) $input->getOption('subject');
        $template = self::CUSTOM_MAIL_DIRECTORY . $input->getArgument('template');

        foreach ($accounts as $account) {
            /** @var Account $account */
            $this->mailer->sendMailTo(
                $account,
                $subject,
                $template,
                [
                    'account' => $account,
                ]
            );
        }

        $output->writeln(sprintf('<info>%u mail(s) have been sent.</info>', count($accounts)));
        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * @return Account[]
     */
    private function findAccounts(InputInterface $input): array
    {
        $query = $this->accountRepository->createQueryBuilder('a');

        $query->andWhere('a.allowMails = 1');

        foreach (self::TIMESTAMP_FILTERS as $option => [$condition, $parameter]) {
            $value = $input->getOption($option);

            if ($value) {
                $query->andWhere($condition)
                    ->setParameter($parameter, (int) $value);
            }
        }

        $languages = $input->getOption('lang');
        $excludedLanguages = $input->getOption('exclude-lang');

        if (! empty($excludedLanguages)) {
            $query->andWhere('a.language NOT IN (:languages)')
                ->setParameter('languages', $excludedLanguages);
        } elseif (! empty($languages)) {
            $query->andWhere('a.language IN (:languages)')
                ->setParameter('languages', $languages);
        }

        $accountIds = $input->getOption('account');

        if (! empty($accountIds)) {
            $query->andWhere('a.id IN (:accountIds)')
                ->setParameter(
                    'accountIds',
                    array_map(static fn ($accountId): int => (int) $accountId, $accountIds)
                );
        }

        return $query->getQuery()
            ->getResult();
    }

    protected function validateInput(InputInterface $input, OutputInterface $output): bool
    {
        return $this->checkValidation($this->validateTemplate((string) $input->getArgument('template')), $output, 'Template must exist in /data/views/mail/custom.')
               && $this->checkValidation($this->validateSubject($input->getOption('subject')), $output, 'Subject must not be empty.')
               && $this->checkValidation($this->validateLanguage($input->getOption('lang')), $output, 'Language keys must be alphabetic strings.')
               && $this->checkValidation($this->validateLanguage($input->getOption('exclude-lang')), $output, 'Language keys to exclude must be alphabetic strings.')
               && $this->checkValidation($this->validateAccountIds($input->getOption('account')), $output, 'Account IDs must be integers.')
        ;
    }

    protected function checkValidation(bool $success, OutputInterface $output, string $messageOnError): bool
    {
        if (! $success) {
            $output->writeln(sprintf('<error>Invalid input: %s</error>', $messageOnError));
            $output->writeln('');

            return false;
        }

        return true;
    }

    protected function validateTemplate(string $templateName): bool
    {
        return $templateName !== ''
            && is_file($this->dataDirectory . '/views' . self::CUSTOM_MAIL_DIRECTORY . $templateName);
    }

    protected function validateSubject(mixed $subject): bool
    {
        return is_string($subject) && trim($subject) !== '';
    }

    /**
     * @param array<array-key, mixed> $lang
     */
    protected function validateLanguage(array $lang): bool
    {
        return array_reduce(
            $lang,
            static fn (bool $state, mixed $value): bool => $state && ctype_alpha((string) $value),
            true
        );
    }

    /**
     * @param array<array-key, mixed> $accountIds
     */
    protected function validateAccountIds(array $accountIds): bool
    {
        return array_reduce(
            $accountIds,
            static fn (bool $state, mixed $value): bool => $state && ctype_digit((string) $value),
            true
        );
    }

    protected function validateLifetime(?string $lifetime): bool
    {
        return $lifetime === null || ctype_digit($lifetime);
    }
}
