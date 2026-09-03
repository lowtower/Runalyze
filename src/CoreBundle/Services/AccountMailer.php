<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AccountMailer
{
    protected MailerInterface $mailer;

    protected Environment $twig;

    protected TranslatorInterface $translator;

    /**
     * @var string|Address|null
     */
    protected $sender;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        TranslatorInterface $translator
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    public function setFrom(string $senderMail, string $senderName): void
    {
        $this->sender = new Address($senderMail, $senderName);
    }

    /**
     * @param array<string, scalar|object|null> $templateData
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
            ->replyTo($account->getMail(), $account->getUsername())
            ->text($content);

        $this->mailer->send($email);
    }

    protected function customLanguageTemplates(string $template, string $language): string
    {
        $template = str_replace('html.twig', '', $template);
        try {
            $this->twig->render($template . $language . '.html.twig');
            return $template . $language . '.html.twig';
        } catch (\Exception $exception) {
            return $template . 'html.twig';
        }
    }

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
