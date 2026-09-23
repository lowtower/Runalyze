<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\MailingCommand;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MailingCommandTest extends TestCase
{
    private ParameterBagInterface&MockObject $parameterBag;

    private AccountMailer&MockObject $mailer;

    private ManagerRegistry&MockObject $doctrine;

    private TestableMailingCommand $command;

    private const SUCCESS = 0;

    private const FAILURE = 1;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->mailer = $this->createMock(AccountMailer::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->command = new TestableMailingCommand(
            $this->parameterBag,
            $this->mailer,
            $this->doctrine
        );
    }

    public function testCommandConfiguration(): void
    {
        self::assertSame('runalyze:mails:send', $this->command->getName());
        self::assertSame(
            'Send out a mails to users with custom templates in data/views/mail/custom',
            $this->command->getDescription()
        );

        $definition = $this->command->getDefinition();

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

    public function testValidateTemplateReturnsTrueForExistingTemplate(): void
    {
        $temporaryDirectory = $this->createTemporaryDataDirectory();
        $templateDirectory = $temporaryDirectory . '/views/mail/custom';

        mkdir($templateDirectory, 0777, true);
        file_put_contents(
            $templateDirectory . '/newsletter.html.twig',
            'Mail content'
        );

        $this->parameterBag
            ->expects(self::once())
            ->method('get')
            ->with('app.data_directory')
            ->willReturn($temporaryDirectory);

        self::assertTrue(
            $this->command->validateTemplatePublic(
                'newsletter.html.twig'
            )
        );
    }

    public function testValidateTemplateReturnsFalseForMissingTemplate(): void
    {
        $temporaryDirectory = $this->createTemporaryDataDirectory();

        $this->parameterBag
            ->expects(self::once())
            ->method('get')
            ->with('app.data_directory')
            ->willReturn($temporaryDirectory);

        self::assertFalse(
            $this->command->validateTemplatePublic(
                'missing.html.twig'
            )
        );
    }

    public function testCheckValidationWritesErrorMessage(): void
    {
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

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
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

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
        $accountOne = $this->createMock(Account::class);
        $accountTwo = $this->createMock(Account::class);

        $command = $this->createCommandWithSuccessfulValidation();
        $this->configureQueryResult([$accountOne, $accountTwo]);

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

        self::assertSame(self::SUCCESS, $exitCode);
        self::assertStringContainsString(
            '2 mail(s) have been sent.',
            $tester->getDisplay()
        );
    }

    public function testExecuteDoesNotSendMailWhenUserDeclinesConfirmation(): void
    {
        $account = $this->createMock(Account::class);

        $command = $this->createCommandWithSuccessfulValidation();
        $this->configureQueryResult([$account]);

        $application = new \Symfony\Component\Console\Application();
        $application->add($command);

        $registeredCommand = $application->find($command->getName());

        $this->mailer
            ->expects(self::never())
            ->method('sendMailTo');

        $tester = new CommandTester($registeredCommand);
        $tester->setInputs(['n']);

        $exitCode = $tester->execute([
            'template' => 'newsletter.html.twig',
            '--subject' => 'Maintenance information',
        ]);

        self::assertSame(0, $exitCode);
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
                $this->parameterBag,
                $this->mailer,
                $this->doctrine,
            ])
            ->onlyMethods(['validateInput'])
            ->getMock();

        $command
            ->expects(self::once())
            ->method('validateInput')
            ->willReturn(false);

        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        $this->mailer
            ->expects(self::never())
            ->method('sendMailTo');

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'template' => 'missing.html.twig',
            '--subject' => 'Subject',
            '--force' => true,
        ]);

        self::assertSame(self::FAILURE, $exitCode);
    }

    private function createCommandWithSuccessfulValidation(): TestableMailingCommand
    {
        $command = $this->getMockBuilder(TestableMailingCommand::class)
            ->setConstructorArgs([
                $this->parameterBag,
                $this->mailer,
                $this->doctrine,
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
     */
    private function configureQueryResult(array $accounts): void
    {
        $repository = $this->createMock(AccountRepository::class);
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['andWhere', 'getQuery'])
            ->getMock();

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMockForAbstractClass();

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Account::class)
            ->willReturn($repository);

        $repository
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::atLeastOnce())
            ->method('andWhere')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects(self::once())
            ->method('getResult')
            ->willReturn($accounts);
    }

    private function createTemporaryDataDirectory(): string
    {
        $directory = sys_get_temp_dir()
            . '/mailing-command-test-'
            . bin2hex(random_bytes(8));

        mkdir($directory, 0777, true);

        return $directory;
    }
}

class TestableMailingCommand extends MailingCommand
{
    public function validateTemplatePublic(string $templateName): bool
    {
        return $this->validateTemplate($templateName);
    }

    public function validateLanguagePublic(array $languages): bool
    {
        return $this->validateLanguage($languages);
    }

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
