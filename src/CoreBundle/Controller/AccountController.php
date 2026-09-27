<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Form\RecoverPasswordType;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/{_locale}/account")
 */
class AccountController extends AbstractController
{
    use AppServiceSubscriberTrait;

    protected function getAccountRepository(): AccountRepository
    {
        return $this->getDoctrine()
            ->getRepository(Account::class);
    }

    /**
     * @Route("/delete/{hash}/confirmed", name="account_delete_confirmed", requirements={"hash"="[[:xdigit:]]{32}"})
     */
    public function deleteAccountConfirmedAction(string $hash, Request $request): Response
    {
        $accountToDelete = $this->getAccountRepository()
            ->findOneBy([
                'deletionHash' => $hash,
            ]);

        if ($accountToDelete === null) {
            return $this->render('account/delete/problem.html.twig');
        }

        $currentUser = $this->getUser();
        $isCurrentUser = $currentUser instanceof Account && $currentUser->getId() === $accountToDelete->getId();

        $this->getAccountRepository()
            ->deleteByHash($hash);

        if ($isCurrentUser) {
            $this->get('security.token_storage')
                ->setToken(null);
            $request->getSession()
                ->invalidate();
        }

        return $this->render('account/delete/success.html.twig');
    }

    /**
     * @Route("/delete/{hash}", name="account_delete", requirements={"hash"="[[:xdigit:]]{32}"})
     */
    public function deleteAccountAction(string $hash): Response
    {
        $account = $this->getAccountRepository()
            ->findOneBy([
                'deletionHash' => $hash,
            ]);

        if ($account === null) {
            return $this->render('account/delete/problem.html.twig');
        }

        return $this->render('account/delete/please_confirm.html.twig', [
            'deletionHash' => $hash,
            'username' => $account->getUsername(),
        ]);
    }

    /**
     * @Route("/recover/{hash}", name="account_recover_hash", requirements={"hash"="[[:xdigit:]]{32}"})
     */
    public function recoverForHashAction(string $hash, Request $request, Sha256SaltedEncoder $sha256SaltedEncoder): Response
    {
        $account = $this->getAccountRepository()
            ->findOneBy([
                'changepwHash' => $hash,
            ]);

        if ($account === null) {
            return $this->render('account/recover/hash_invalid.html.twig', [
                'recoverHash' => $hash,
            ]);
        }

        $form = $this->createForm(RecoverPasswordType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $account->setNewSalt();
            $account->setPassword($sha256SaltedEncoder->encodePassword($account->getPlainPassword(), $account->getSalt()));
            $account->removeChangePasswordHash();

            $this->getAccountRepository()
                ->save($account);

            return $this->render('account/recover/success.html.twig');
        }

        return $this->render('account/recover/form_new_password.html.twig', [
            'recoverHash' => $hash,
            'form' => $form->createView(),
            'username' => $account->getUsername(),
        ]);
    }

    /**
     * @Route("/recover", name="account_recover")
     */
    public function recoverAction(Request $request, TranslatorInterface $translator): Response
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'required' => false,
                'attr' => [
                    'autofocus' => true,
                ],
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $account = $this->getAccountRepository()
                ->loadUserByUsername($request->request->get($form->getName())['username']);

            if (! $account instanceof \Runalyze\Bundle\CoreBundle\Entity\Account) {
                $form->get('username')
                    ->addError(new FormError($translator->trans('The username is not known.')));
            } else {
                $account->setNewChangePasswordHash();
                $this->getAccountRepository()
                    ->save($account);

                $this->get('app.mailer.account')
                    ->sendRecoverPasswordLinkTo($account);

                return $this->render('account/recover/mail_delivered.html.twig');
            }
        }

        return $this->render('account/recover/form_send_mail.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/activate/{hash}", requirements={"hash"="[[:xdigit:]]{32}"})
     * @Route("/activate/{hash}/{username}", name="account_activate", requirements={"hash"="[[:xdigit:]]{32}"})
     */
    public function activateAccountAction(string $hash, ?string $username = null): Response
    {
        if ($this->getAccountRepository()->activateByHash($hash)) {
            return $this->render('account/activate/success.html.twig');
        }

        if ($username !== null && $this->getAccountRepository()->loadUserByUsername($username) != null) {
            return $this->render('account/activate/success.html.twig', [
                'username' => $username,
                'alreadyActivated' => true,
            ]);
        }

        return $this->render('account/activate/problem.html.twig');
    }
}
