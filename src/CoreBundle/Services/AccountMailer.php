<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AccountMailer
{
    protected Address $sender;

    /**
     * @throws RfcComplianceException if the sender mail address is invalid
     */
    public function __construct(
        protected MailerInterface $mailer,
        protected Environment $twig,
        protected TranslatorInterface $translator,
        #[Autowire(param: 'app.mail_sender')]
        string $senderMail,
        #[Autowire(param: 'app.mail_name')]
        string $senderName
    ) {
        $this->sender = new Address($senderMail, $senderName);
    }

    /**
     * @deprecated Pass the sender to the constructor instead.
     *
     * @throws RfcComplianceException if the sender mail address is invalid
     */
    public function setFrom(string $senderMail, string $senderName): void
    {
        $this->sender = new Address($senderMail, $senderName);
    }

    /**
     * @param array<string, scalar|object|null> $templateData
     *
     * @throws RfcComplianceException if the mail address of the account is invalid
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendMailTo(Account $account, string $subject, string $template, array $templateData): void
    {
        $template = $this->customLanguageTemplates($template, $account->getLanguage());

        $email = (new Email())
            ->from($this->sender)
            ->to(new Address($account->getMail(), $account->getUsername()))
            ->subject($subject)
            ->html($this->twig->render($template, $templateData));

        $this->mailer->send($email);
    }

    /**
     * @throws RfcComplianceException if the recipient or the mail address of the account is invalid
     * @throws TransportExceptionInterface
     */
    public function sendCustomFeedbackToSystem(Account $account, string $mailTo, string $customText): void
    {
        $subject = 'RUNALYZE - ' . $this->translator->trans('Customer feedback');
        $content = 'Username: ' . $account->getUsername() . "\n";
        $content .= 'Mail: ' . $account->getMail() . "\n";
        $content .= 'Message:' . "\n" . $customText;

        $email = (new Email())
            ->subject($subject)
            ->from($this->sender)
            ->to($mailTo)
            ->replyTo(new Address($account->getMail(), $account->getUsername()))
            ->text($content);

        $this->mailer->send($email);
    }

    protected function customLanguageTemplates(string $template, string $language): string
    {
        $template = str_replace('html.twig', '', $template);
        $languageTemplate = $template . $language . '.html.twig';

        if ($this->twig->getLoader()->exists($languageTemplate)) {
            return $languageTemplate;
        }

        return $template . 'html.twig';
    }

    /**
     * @throws RfcComplianceException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendActivationLinkTo(Account $account): void
    {
        $this->sendMailTo(
            $account,
            $this->translator->trans('Please activate your RUNALYZE account'),
            'mail/account/registration.html.twig',
            [
                'username' => $account->getUsername(),
                'activationHash' => $account->getActivationHash(),
            ]
        );
    }

    /**
     * @throws RfcComplianceException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendRecoverPasswordLinkTo(Account $account): void
    {
        $this->sendMailTo(
            $account,
            $this->translator->trans('Reset your RUNALYZE password'),
            'mail/account/recoverPassword.html.twig',
            [
                'username' => $account->getUsername(),
                'changepw_hash' => $account->getChangepwHash(),
            ]
        );
    }

    /**
     * @throws RfcComplianceException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendDeleteLinkTo(Account $account): void
    {
        $this->sendMailTo(
            $account,
            $this->translator->trans('Deletion request of your RUNALYZE account'),
            'mail/account/deleteAccountRequest.html.twig',
            [
                'username' => $account->getUsername(),
                'deletion_hash' => $account->getDeletionHash(),
            ]
        );
    }

    /**
     * @throws RfcComplianceException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendPosterGeneratedTo(Account $account, int $state): void
    {
        $this->sendMailTo(
            $account,
            $this->translator->trans('Your RUNALYZE posters are ready'),
            'mail/tools/posterGenerated.html.twig',
            [
                'username' => $account->getUsername(),
                'partial' => $state === PosterGeneratedMessage::STATE_PARTIAL,
            ]
        );
    }

    /**
     * @throws RfcComplianceException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendBackupGeneratedTo(Account $account): void
    {
        $this->sendMailTo(
            $account,
            $this->translator->trans('Your RUNALYZE backup is ready'),
            'mail/tools/backupGenerated.html.twig',
            [
                'username' => $account->getUsername(),
            ]
        );
    }
}
