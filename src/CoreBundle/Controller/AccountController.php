<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Form\RecoverPasswordType;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/{_locale}/account')]
class AccountController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AccountMailer $accountMailer
    ) {
    }

    protected function getAccountRepository(): AccountRepository
    {
        return $this->doctrine
            ->getRepository(Account::class);
    }

    #[Route(path: '/delete/{hash}/confirmed', name: 'account_delete_confirmed', requirements: [
        'hash' => '[[:xdigit:]]{32}',
    ])]
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
            $this->tokenStorage->setToken(null);
            $request->getSession()
                ->invalidate();
        }

        return $this->render('account/delete/success.html.twig');
    }

    #[Route(path: '/delete/{hash}', name: 'account_delete', requirements: [
        'hash' => '[[:xdigit:]]{32}',
    ])]
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

    #[Route(path: '/recover/{hash}', name: 'account_recover_hash', requirements: [
        'hash' => '[[:xdigit:]]{32}',
    ])]
    public function recoverForHashAction(string $hash, Request $request, UserPasswordHasherInterface $passwordHasher): Response
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
            $account->setPassword($passwordHasher->hashPassword($account, $account->getPlainPassword()));
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

    #[Route(path: '/recover', name: 'account_recover')]
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
                ->loadUserByUsername($request->request->all($form->getName())['username']);

            if (! $account instanceof Account) {
                $form->get('username')
                    ->addError(new FormError($translator->trans('The username is not known.')));
            } else {
                $account->setNewChangePasswordHash();
                $this->getAccountRepository()
                    ->save($account);

                $this->accountMailer->sendRecoverPasswordLinkTo($account);

                return $this->render('account/recover/mail_delivered.html.twig');
            }
        }

        return $this->render('account/recover/form_send_mail.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/activate/{hash}', requirements: [
        'hash' => '[[:xdigit:]]{32}',
    ])]
    #[Route(path: '/activate/{hash}/{username}', name: 'account_activate', requirements: [
        'hash' => '[[:xdigit:]]{32}',
    ])]
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
