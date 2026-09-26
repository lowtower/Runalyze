<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

#[Group('default')]
class AccountMailerTest extends TestCase
{
    private function createAccount(string $username = 'johndoe', string $mail = 'john@example.com', string $language = 'de'): Account
    {
        $account = new Account();
        $account->setUsername($username);
        $account->setMail($mail);
        $account->setLanguage($language);

        return $account;
    }

    private function createTranslator(): TranslatorInterface&\PHPUnit\Framework\MockObject\MockObject
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(
                static fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => $id . ' (translated)'
            );

        return $translator;
    }

    private function createCapturingMailer(mixed &$captured = null): MailerInterface
    {
        $captured = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')
            ->willReturnCallback(function ($message) use (&$captured): void {
                $captured = $message;
            });

        return $mailer;
    }

    private function createMailerService(MailerInterface $mailer, Environment $twig, TranslatorInterface $translator): AccountMailer
    {
        $service = new AccountMailer($mailer, $twig, $translator);
        $service->setFrom('noreply@runalyze.com', 'RUNALYZE');

        return $service;
    }

    /**
     * @param array<int, array{0: string, 1: array<string, mixed>}> $renderCalls
     */
    private function createTwig(bool $languageTemplateExists, string $html, array &$renderCalls = []): Environment
    {
        $renderCalls = [];

        $loader = $this->createMock(LoaderInterface::class);
        $loader->method('exists')
            ->willReturn($languageTemplateExists);

        $twig = $this->createMock(Environment::class);
        $twig->method('getLoader')
            ->willReturn($loader);
        $twig->method('render')
            ->willReturnCallback(function (string $name, array $context = []) use (&$renderCalls, $html): string {
                $renderCalls[] = [$name, $context];

                return $html;
            });

        return $twig;
    }

    public function testSenderConfiguredViaSetFromIsUsedAsEmailFromAddress(): void
    {
        $twig = $this->createTwig(languageTemplateExists: false, html: '<html>Body</html>');
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $service->sendMailTo($this->createAccount(), 'Subject', 'mail/test/example.html.twig', []);

        /** @var Email $captured */
        self::assertCount(1, $captured->getFrom());
        self::assertSame('noreply@runalyze.com', $captured->getFrom()[0]->getAddress());
        self::assertSame('RUNALYZE', $captured->getFrom()[0]->getName());
    }

    public function testSendMailToWithoutPriorSetFromThrowsInvalidArgumentException(): void
    {
        $twig = $this->createTwig(languageTemplateExists: false, html: 'HTML');
        $mailer = $this->createMock(MailerInterface::class);
        $service = new AccountMailer($mailer, $twig, $this->createTranslator());

        $this->expectException(\Symfony\Component\Mime\Exception\InvalidArgumentException::class);

        $service->sendMailTo($this->createAccount(), 'Subject', 'mail/test/example.html.twig', []);
    }

    public function testSendMailToUsesLanguageSpecificTemplateWhenItExists(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: true, html: 'RENDERED_HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $service->sendMailTo(
            $this->createAccount(language: 'de'),
            'Subject',
            'mail/test/example.html.twig',
            [
                'foo' => 'bar',
            ]
        );

        self::assertCount(1, $calls, 'render() must be called exactly once now (no more existence-check render)');
        self::assertSame('mail/test/example.de.html.twig', $calls[0][0]);
        self::assertSame([
            'foo' => 'bar',
        ], $calls[0][1]);

        /** @var Email $captured */
        self::assertSame('RENDERED_HTML', $captured->getHtmlBody());
    }

    public function testSendMailToFallsBackToDefaultTemplateWhenLanguageTemplateDoesNotExist(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: false, html: 'RENDERED_HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $service->sendMailTo(
            $this->createAccount(language: 'de'),
            'Subject',
            'mail/test/example.html.twig',
            [
                'foo' => 'bar',
            ]
        );

        self::assertCount(1, $calls);
        self::assertSame('mail/test/example.html.twig', $calls[0][0]);
        self::assertSame([
            'foo' => 'bar',
        ], $calls[0][1]);

        /** @var Email $captured */
        self::assertSame('RENDERED_HTML', $captured->getHtmlBody());
    }

    public function testSendMailToSetsSubjectAndRecipient(): void
    {
        $twig = $this->createTwig(languageTemplateExists: false, html: '<html>Body</html>');
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', mail: 'jane@example.com');
        $service->sendMailTo($account, 'My Subject', 'mail/test/example.html.twig', []);

        /** @var Email $captured */
        self::assertSame('My Subject', $captured->getSubject());
        self::assertCount(1, $captured->getTo());
        self::assertSame('jane@example.com', $captured->getTo()[0]->getAddress());
        self::assertSame('janedoe', $captured->getTo()[0]->getName());
    }

    public function testSendCustomFeedbackToSystemBuildsExpectedSubjectAndBody(): void
    {
        $twig = $this->createTwig(languageTemplateExists: false, html: 'HTML');
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', mail: 'jane@example.com');
        $service->sendCustomFeedbackToSystem($account, 'support@runalyze.com', 'Something is broken.');

        /** @var Email $captured */
        self::assertSame('RUNALYZE - Customer feedback (translated)', $captured->getSubject());
        self::assertSame('noreply@runalyze.com', $captured->getFrom()[0]->getAddress());
        self::assertCount(1, $captured->getTo());
        self::assertSame('support@runalyze.com', $captured->getTo()[0]->getAddress());

        $body = $captured->getTextBody();
        self::assertStringContainsString('Username: janedoe', $body);
        self::assertStringContainsString('Mail: jane@example.com', $body);
        self::assertStringContainsString("Message:\nSomething is broken.", $body);
    }

    public function testSendCustomFeedbackToSystemAddsOneNamedReplyToAddress(): void
    {
        $twig = $this->createTwig(languageTemplateExists: false, html: 'HTML');
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', mail: 'jane@example.com');
        $service->sendCustomFeedbackToSystem($account, 'support@runalyze.com', 'Something is broken.');

        /** @var Email $captured */
        $replyTo = $captured->getReplyTo();

        self::assertCount(1, $replyTo);
        self::assertSame('jane@example.com', $replyTo[0]->getAddress());
        self::assertSame('janedoe', $replyTo[0]->getName());
    }

    public function testSendActivationLinkToUsesRegistrationTemplateWithHashAndUsername(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: true, html: 'HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', language: 'en');
        $account->setNewActivationHash();

        $service->sendActivationLinkTo($account);

        /** @var Email $captured */
        self::assertSame('Please activate your RUNALYZE account (translated)', $captured->getSubject());
        self::assertSame('mail/account/registration.en.html.twig', $calls[0][0]);
        self::assertSame('janedoe', $calls[0][1]['username']);
        self::assertSame($account->getActivationHash(), $calls[0][1]['activationHash']);
    }

    public function testSendRecoverPasswordLinkToUsesRecoverPasswordTemplateWithHashAndUsername(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: true, html: 'HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', language: 'en');
        $account->setNewChangePasswordHash();

        $service->sendRecoverPasswordLinkTo($account);

        /** @var Email $captured */
        self::assertSame('Reset your RUNALYZE password (translated)', $captured->getSubject());
        self::assertSame('mail/account/recoverPassword.en.html.twig', $calls[0][0]);
        self::assertSame('janedoe', $calls[0][1]['username']);
        self::assertSame($account->getChangepwHash(), $calls[0][1]['changepw_hash']);
    }

    public function testSendDeleteLinkToUsesDeleteAccountRequestTemplateWithHashAndUsername(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: true, html: 'HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', language: 'en');
        $account->setNewDeletionHash();

        $service->sendDeleteLinkTo($account);

        /** @var Email $captured */
        self::assertSame('Deletion request of your RUNALYZE account (translated)', $captured->getSubject());
        self::assertSame('mail/account/deleteAccountRequest.en.html.twig', $calls[0][0]);
        self::assertSame('janedoe', $calls[0][1]['username']);
        self::assertSame($account->getDeletionHash(), $calls[0][1]['deletion_hash']);
    }

    public function testSendBackupGeneratedToUsesBackupGeneratedTemplateWithUsername(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: true, html: 'HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', language: 'en');
        $service->sendBackupGeneratedTo($account);

        /** @var Email $captured */
        self::assertSame('Your RUNALYZE backup is ready (translated)', $captured->getSubject());
        self::assertSame('mail/tools/backupGenerated.en.html.twig', $calls[0][0]);
        self::assertSame([
            'username' => 'janedoe',
        ], $calls[0][1]);
    }

    public function testSendPosterGeneratedToUsesPosterGeneratedTemplateWithUsernameAndPartialFlag(): void
    {
        $calls = [];
        $twig = $this->createTwig(languageTemplateExists: true, html: 'HTML', renderCalls: $calls);
        $captured = null;
        $mailer = $this->createCapturingMailer($captured);

        $service = $this->createMailerService($mailer, $twig, $this->createTranslator());
        $account = $this->createAccount(username: 'janedoe', language: 'en');
        $service->sendPosterGeneratedTo($account, 0);

        /** @var Email $captured */
        self::assertSame('Your RUNALYZE posters are ready (translated)', $captured->getSubject());
        self::assertSame('mail/tools/posterGenerated.en.html.twig', $calls[0][0]);
        self::assertSame('janedoe', $calls[0][1]['username']);
        self::assertIsBool($calls[0][1]['partial']);
    }
}
