<?php

declare(strict_types=1);

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\MailingCommand;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

#[\PHPUnit\Framework\Attributes\Group('default')]
class MailingCommandTest extends TestCase
{
    private AccountMailer&MockObject $mailer;

    private AccountRepository&MockObject $accountRepository;

    private string $dataDirectory;

    private TestableMailingCommand $command;

    protected function setUp(): void
    {
        $this->dataDirectory = sys_get_temp_dir() . '/mailing-command-test-' . bin2hex(random_bytes(8));
        mkdir($this->dataDirectory . '/views/mail/custom', 0777, true);

        $this->mailer = $this->createMock(AccountMailer::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);

        $this->command = new TestableMailingCommand(
            $this->dataDirectory,
            $this->mailer,
            $this->accountRepository
        );
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dataDirectory);
    }

    public function testCommandConfiguration(): void
    {
        $command = new MailingCommand($this->dataDirectory, $this->mailer, $this->accountRepository);

        self::assertSame('runalyze:mails:send', $command->getName());
        self::assertSame(
            'Send out a mails to users with custom templates in data/views/mail/custom',
            $command->getDescription()
        );

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('template'));
        self::assertTrue($definition->getArgument('template')->isRequired());

        self::assertTrue($definition->hasOption('subject'));
        self::assertTrue($definition->hasOption('lang'));
        self::assertTrue($definition->hasOption('exclude-lang'));
        self::assertTrue($definition->hasOption('account'));
        self::assertTrue($definition->hasOption('force'));
        self::assertTrue($definition->hasOption('last-action-before'));
        self::assertTrue($definition->hasOption('last-action-after'));
        self::assertTrue($definition->hasOption('registration-before'));
        self::assertTrue($definition->hasOption('registration-after'));
    }

    /**
     * @param string[] $languages
     */
    #[DataProvider('validLanguages')]
    public function testValidateLanguageAcceptsValidValues(array $languages): void
    {
        self::assertTrue($this->command->validateLanguagePublic($languages));
    }

    public static function validLanguages(): iterable
    {
        yield 'empty list' => [[]];
        yield 'one language' => [['de']];
        yield 'several languages' => [['de', 'en', 'fr']];
        yield 'uppercase characters' => [['DE']];
    }

    /**
     * @param string[] $languages
     */
    #[DataProvider('invalidLanguages')]
    public function testValidateLanguageRejectsInvalidValues(array $languages): void
    {
        self::assertFalse($this->command->validateLanguagePublic($languages));
    }

    public static function invalidLanguages(): iterable
    {
        yield 'number in language key' => [['de2']];
        yield 'hyphen in language key' => [['de-DE']];
        yield 'underscore in language key' => [['de_DE']];
        yield 'empty language key' => [['']];
        yield 'one invalid value among valid values' => [['de', 'en2']];
    }

    /**
     * @param string[] $accountIds
     */
    #[DataProvider('validAccountIds')]
    public function testValidateAccountIdsAcceptsValidValues(array $accountIds): void
    {
        self::assertTrue($this->command->validateAccountIdsPublic($accountIds));
    }

    public static function validAccountIds(): iterable
    {
        yield 'empty list' => [[]];
        yield 'one ID' => [['1']];
        yield 'zero' => [['0']];
        yield 'several IDs' => [['1', '20', '300']];
    }

    /**
     * @param string[] $accountIds
     */
    #[DataProvider('invalidAccountIds')]
    public function testValidateAccountIdsRejectsInvalidValues(array $accountIds): void
    {
        self::assertFalse($this->command->validateAccountIdsPublic($accountIds));
    }

    public static function invalidAccountIds(): iterable
    {
        yield 'negative ID' => [['-1']];
        yield 'decimal value' => [['1.5']];
        yield 'alphabetic value' => [['abc']];
        yield 'empty value' => [['']];
        yield 'one invalid ID among valid IDs' => [['1', 'invalid']];
    }

    #[DataProvider('lifetimeValues')]
    public function testValidateLifetime(?string $value, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->command->validateLifetimePublic($value)
        );
    }

    public static function lifetimeValues(): iterable
    {
        yield 'null' => [null, true];
        yield 'zero' => ['0', true];
        yield 'positive integer' => ['123456', true];
        yield 'negative integer' => ['-1', false];
        yield 'decimal value' => ['1.5', false];
        yield 'text' => ['yesterday', false];
        yield 'empty string' => ['', false];
    }

    #[DataProvider('validSubjects')]
    public function testValidateSubjectAcceptsNonEmptyStrings(string $subject): void
    {
        self::assertTrue($this->command->validateSubjectPublic($subject));
    }

    public static function validSubjects(): iterable
    {
        yield 'single word' => ['Maintenance'];
        yield 'sentence' => ['Maintenance on Sunday'];
        yield 'zero' => ['0'];
    }

    #[DataProvider('invalidSubjects')]
    public function testValidateSubjectRejectsMissingOrBlankValues(mixed $subject): void
    {
        self::assertFalse($this->command->validateSubjectPublic($subject));
    }

    public static function invalidSubjects(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'only whitespace' => ['   '];
        yield 'boolean' => [true];
        yield 'array' => [['Subject']];
    }

    public function testValidateTemplateReturnsTrueForExistingTemplate(): void
    {
        $this->createTemplate('newsletter.html.twig');

        self::assertTrue($this->command->validateTemplatePublic('newsletter.html.twig'));
    }

    public function testValidateTemplateReturnsFalseForMissingTemplate(): void
    {
        self::assertFalse($this->command->validateTemplatePublic('missing.html.twig'));
    }

    public function testValidateTemplateReturnsFalseForEmptyTemplateName(): void
    {
        self::assertFalse($this->command->validateTemplatePublic(''));
    }

    public function testValidateTemplateReturnsFalseForDirectory(): void
    {
        mkdir($this->dataDirectory . '/views/mail/custom/subdirectory');

        self::assertFalse($this->command->validateTemplatePublic('subdirectory'));
    }

    public function testCheckValidationWritesErrorMessage(): void
    {
        $output = new BufferedOutput();

        $result = $this->command->checkValidationPublic(
            false,
            $output,
            'Account IDs must be integers.'
        );

        self::assertFalse($result);
        self::assertStringContainsString(
            'Invalid input: Account IDs must be integers.',
            $output->fetch()
        );
    }

    public function testCheckValidationDoesNotWriteOnSuccess(): void
    {
        $output = new BufferedOutput();

        $result = $this->command->checkValidationPublic(
            true,
            $output,
            'This message must not be shown.'
        );

        self::assertTrue($result);
        self::assertSame('', $output->fetch());
    }

    public function testExecuteSendsMailToEveryMatchingAccountWhenForced(): void
    {
        $command = $this->createCommandWithSuccessfulValidation();
        $this->configureQueryResult([$this->createMock(Account::class), $this->createMock(Account::class)]);

        $this->mailer
            ->expects(self::exactly(2))
            ->method('sendMailTo')
            ->with(
                self::isInstanceOf(Account::class),
                'Maintenance information',
                '/mail/custom/newsletter.html.twig',
                self::callback(
                    static fn (array $data): bool => isset($data['account'])
                        && $data['account'] instanceof Account
                )
            );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'template' => 'newsletter.html.twig',
            '--subject' => 'Maintenance information',
            '--force' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(
            '2 mail(s) have been sent.',
            $tester->getDisplay()
        );
    }

    public function testExecuteSendsMailWhenUserConfirms(): void
    {
        $command = $this->createCommandWithSuccessfulValidation();
        $command->setApplication(new Application());
        $this->configureQueryResult([$this->createMock(Account::class)]);

        $this->mailer
            ->expects(self::once())
            ->method('sendMailTo');

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $exitCode = $tester->execute([
            'template' => 'newsletter.html.twig',
            '--subject' => 'Maintenance information',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('1 mail(s) will be sent.', $tester->getDisplay());
        self::assertStringContainsString('1 mail(s) have been sent.', $tester->getDisplay());
    }

    public function testExecuteDoesNotSendMailWhenUserDeclinesConfirmation(): void
    {
        $command = $this->createCommandWithSuccessfulValidation();
        $command->setApplication(new Application());
        $this->configureQueryResult([$this->createMock(Account::class)]);

        $this->mailer
            ->expects(self::never())
            ->method('sendMailTo');

        $tester = new CommandTester($command);
        $tester->setInputs(['n']);

        $exitCode = $tester->execute([
            'template' => 'newsletter.html.twig',
            '--subject' => 'Maintenance information',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(
            '1 mail(s) will be sent.',
            $tester->getDisplay()
        );
        self::assertStringNotContainsString(
            '1 mail(s) have been sent.',
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenValidationFails(): void
    {
        $command = $this->getMockBuilder(TestableMailingCommand::class)
            ->setConstructorArgs([
                $this->dataDirectory,
                $this->mailer,
                $this->accountRepository,
            ])
            ->onlyMethods(['validateInput'])
            ->getMock();

        $command
            ->expects(self::once())
            ->method('validateInput')
            ->willReturn(false);

        $this->accountRepository
            ->expects(self::never())
            ->method('createQueryBuilder');

        $this->mailer
            ->expects(self::never())
            ->method('sendMailTo');

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'template' => 'missing.html.twig',
            '--subject' => 'Subject',
            '--force' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidInputs')]
    public function testExecuteReportsInvalidInputAndSendsNothing(string $template, array $options, string $expectedMessage): void
    {
        $this->createTemplate('newsletter.html.twig');

        $this->accountRepository
            ->expects(self::never())
            ->method('createQueryBuilder');

        $this->mailer
            ->expects(self::never())
            ->method('sendMailTo');

        $tester = new CommandTester($this->command);

        $exitCode = $tester->execute([
            'template' => $template,
            '--force' => true,
        ] + $options);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Invalid input: ' . $expectedMessage, $tester->getDisplay());
    }

    public static function invalidInputs(): iterable
    {
        yield 'missing template' => [
            'missing.html.twig',
            [
                '--subject' => 'Subject',
            ],
            'Template must exist in /data/views/mail/custom.',
        ];
        yield 'missing subject' => [
            'newsletter.html.twig',
            [],
            'Subject must not be empty.',
        ];
        yield 'blank subject' => [
            'newsletter.html.twig',
            [
                '--subject' => '  ',
            ],
            'Subject must not be empty.',
        ];
        yield 'invalid language' => [
            'newsletter.html.twig',
            [
                '--subject' => 'Subject',
                '--lang' => ['de2'],
            ],
            'Language keys must be alphabetic strings.',
        ];
        yield 'invalid excluded language' => [
            'newsletter.html.twig',
            [
                '--subject' => 'Subject',
                '--exclude-lang' => ['de-DE'],
            ],
            'Language keys to exclude must be alphabetic strings.',
        ];
        yield 'invalid account id' => [
            'newsletter.html.twig',
            [
                '--subject' => 'Subject',
                '--account' => ['abc'],
            ],
            'Account IDs must be integers.',
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param string[] $expectedConditions
     * @param array<string, mixed> $expectedParameters
     */
    #[DataProvider('queryFilterOptions')]
    public function testExecuteFiltersAccountsByGivenOptions(array $options, array $expectedConditions, array $expectedParameters): void
    {
        $this->createTemplate('newsletter.html.twig');
        $this->configureQueryResult([], $conditions, $parameters);

        $tester = new CommandTester($this->command);

        $exitCode = $tester->execute(
            [
                'template' => 'newsletter.html.twig',
                '--subject' => 'Subject',
                '--force' => true,
            ] + $options
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame($expectedConditions, $conditions);
        self::assertSame($expectedParameters, $parameters);
    }

    public static function queryFilterOptions(): iterable
    {
        yield 'no options' => [
            [],
            ['a.allowMails = 1'],
            [],
        ];
        yield 'last action after' => [
            [
                '--last-action-after' => '1700000000',
            ],
            ['a.allowMails = 1', 'a.lastaction > :lastActionAfter'],
            [
                'lastActionAfter' => 1700000000,
            ],
        ];
        yield 'last action before' => [
            [
                '--last-action-before' => '1700000000',
            ],
            ['a.allowMails = 1', 'a.lastaction < :lastActionBefore'],
            [
                'lastActionBefore' => 1700000000,
            ],
        ];
        yield 'registration after' => [
            [
                '--registration-after' => '1600000000',
            ],
            ['a.allowMails = 1', 'a.registerdate > :registrationAfter'],
            [
                'registrationAfter' => 1600000000,
            ],
        ];
        yield 'registration before' => [
            [
                '--registration-before' => '1600000000',
            ],
            ['a.allowMails = 1', 'a.registerdate < :registrationBefore'],
            [
                'registrationBefore' => 1600000000,
            ],
        ];
        yield 'languages' => [
            [
                '--lang' => ['de', 'en'],
            ],
            ['a.allowMails = 1', 'a.language IN (:languages)'],
            [
                'languages' => ['de', 'en'],
            ],
        ];
        yield 'excluded languages' => [
            [
                '--exclude-lang' => ['fr'],
            ],
            ['a.allowMails = 1', 'a.language NOT IN (:languages)'],
            [
                'languages' => ['fr'],
            ],
        ];
        yield 'excluded languages take precedence over languages' => [
            [
                '--lang' => ['de'],
                '--exclude-lang' => ['fr'],
            ],
            ['a.allowMails = 1', 'a.language NOT IN (:languages)'],
            [
                'languages' => ['fr'],
            ],
        ];
        yield 'account ids are converted to integers' => [
            [
                '--account' => ['1', '20'],
            ],
            ['a.allowMails = 1', 'a.id IN (:accountIds)'],
            [
                'accountIds' => [1, 20],
            ],
        ];
    }

    private function createTemplate(string $templateName): void
    {
        file_put_contents($this->dataDirectory . '/views/mail/custom/' . $templateName, 'Mail content');
    }

    private function createCommandWithSuccessfulValidation(): TestableMailingCommand&MockObject
    {
        $command = $this->getMockBuilder(TestableMailingCommand::class)
            ->setConstructorArgs([
                $this->dataDirectory,
                $this->mailer,
                $this->accountRepository,
            ])
            ->onlyMethods(['validateInput'])
            ->getMock();

        $command
            ->expects(self::once())
            ->method('validateInput')
            ->willReturn(true);

        return $command;
    }

    /**
     * @param (MockObject&Account)[] $accounts
     * @param string[] $conditions
     * @param array<string, mixed> $parameters
     */
    private function configureQueryResult(array $accounts, ?array &$conditions = null, ?array &$parameters = null): void
    {
        $conditions = [];
        $parameters = [];

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['andWhere', 'setParameter', 'getQuery'])
            ->getMock();

        $query = $this->createMock(AbstractQuery::class);

        $this->accountRepository
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::atLeastOnce())
            ->method('andWhere')
            ->willReturnCallback(
                static function (string $condition) use (&$conditions, $queryBuilder): QueryBuilder {
                    $conditions[] = $condition;

                    return $queryBuilder;
                }
            );

        $queryBuilder
            ->method('setParameter')
            ->willReturnCallback(
                static function (string $name, mixed $value) use (&$parameters, $queryBuilder): QueryBuilder {
                    $parameters[$name] = $value;

                    return $queryBuilder;
                }
            );

        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects(self::once())
            ->method('getResult')
            ->willReturn($accounts);
    }
}

class TestableMailingCommand extends MailingCommand
{
    public function validateTemplatePublic(string $templateName): bool
    {
        return $this->validateTemplate($templateName);
    }

    public function validateSubjectPublic(mixed $subject): bool
    {
        return $this->validateSubject($subject);
    }

    /**
     * @param array<array-key, mixed> $languages
     */
    public function validateLanguagePublic(array $languages): bool
    {
        return $this->validateLanguage($languages);
    }

    /**
     * @param array<array-key, mixed> $accountIds
     */
    public function validateAccountIdsPublic(array $accountIds): bool
    {
        return $this->validateAccountIds($accountIds);
    }

    public function validateLifetimePublic(?string $lifetime): bool
    {
        return $this->validateLifetime($lifetime);
    }

    public function checkValidationPublic(
        bool $success,
        OutputInterface $output,
        string $message
    ): bool {
        return $this->checkValidation($success, $output, $message);
    }
}
