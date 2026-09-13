<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Activity\Distance;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\FeedbackType;
use Runalyze\Bundle\CoreBundle\Form\RegistrationType;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @package Runalyze\Bundle\CoreBundle\Controller
 */
class DefaultController extends AbstractPluginsAwareController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private AuthenticationUtils $authenticationUtils,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
        private AccountMailer $accountMailer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
    }

    protected function getAccountRepository(): AccountRepository
    {
        return $this->getDoctrine()
            ->getRepository(Account::class);
    }

    protected function includeOldScript(string $file, bool $initFrontend = true): Response
    {
        if ($initFrontend) {
            $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);
        }

        include $file;

        return $this->render('legacy_end.html.twig');
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboardAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

        $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)
            ->getContent();

        include __DIR__ . '/../../../dashboard.php';

        return $this->render('legacy_end.html.twig');
    }

    /**
     * @Route("/", name="base_url")
     */
    public function indexAction(Request $request): RedirectResponse
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/{_locale}/register", name="register")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, Environment $twig, TranslatorInterface $translator): Response
    {
        if (! $this->parameterBag->get('app.user_can_register')) {
            return $this->render('register/disabled.html.twig');
        }

        $account = new Account();
        $form = $this->createForm(RegistrationType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registration = new Registration($this->getDoctrine()->getManager(), $account, $passwordEncoder);
            $formdata = $request->request->get($form->getName());

            $registration->setLocale($request->getLocale());
            $registration->setTimezoneByName($formdata['textTimezone']);

            if (! $this->parameterBag->get('app.user_disable_account_activation')) {
                $registration->requireAccountActivation();
            }

            $registration->setPassword($account->getPlainPassword());
            $account = $registration->registerAccount();

            if ($this->parameterBag->get('app.user_disable_account_activation')) {
                return $this->render('account/activate/success.html.twig', [
                    'username' => $account->getUsername(),
                    'alreadyActivated' => false,
                ]);
            }

            $this->accountMailer->sendActivationLinkTo($account);

            return $this->render('register/mail_delivered.html.twig', [
                'num' => $this->collectStatistics(),
            ]);
        }

        return $this->render('register/form.html.twig', [
            'form' => $form->createView(),
            'num' => $this->collectStatistics(),
        ]);
    }

    /**
     * @Route("/{_locale}/login", name="login")
     */
    public function loginAction(
        Request $request
    ): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('dashboard');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('login/ajax_not_logged_in.html.twig');
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();

        return $this->render('login/form.html.twig', [
            'error' => $error,
            'num' => $this->collectStatistics(),
        ]);
    }

    /**
     * @Route("/{_locale}/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(): RedirectResponse
    {
        return $this->redirectToRoute('login');
    }

    /**
     * @return array<string, int|string> ['user' => (int)..., 'distance' => (string)...]
     */
    protected function collectStatistics(): array
    {
        $repository = $this->getAccountRepository();
        $numUser = $repository->getAmountOfActivatedUsers();

        /** @var TrainingRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(Training::class);
        $numDistance = (float) $repository->getAmountOfLoggedKilometers();

        return [
            'user' => (int) $numUser,
            'distance' => Distance::format($numDistance),
        ];
    }

    /**
     * @Route("/plugin/{plugin}/{file}")
     */
    public function pluginAction(string $plugin, string $file): Response
    {
        return $this->includeOldScript('../plugin/' . $plugin . '/' . $file);
    }

    /**
     * @Route("/index.php")
     */
    public function indexPhpAction(Request $request, Account $account): \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        if ($request->isXmlHttpRequest()) {
            $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

            $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)
                ->getContent();

            include $this->parameterBag->get('kernel.root_dir') . '/../dashboard.php';

            return new Response();
        }

        return $this->redirectToRoute('base_url');
    }

    /**
     * @Route("/login.php")
     */
    public function loginPhpAction(): RedirectResponse
    {
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/unsubscribe/{mail}/{hash}", name="unsubscribe-mail")
     */
    public function unsubscribeMailAction(string $mail, string $hash): Response
    {
        $repository = $this->getAccountRepository();
        $account = $repository->findOneBy([
            'mail' => $mail,
        ]);

        if ($account !== null && $hash === md5($account->getUsername())) {
            return $this->render('account/unsubscribe_info.html.twig', [
                'mail' => $mail,
                'hash' => $hash,
            ]);
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    /**
     * @Route("/unsubscribe/{mail}/{hash}/confirm", name="unsubscribe-mail-confirm")
     */
    public function unsubscribeMailConfirmAction(string $mail, string $hash): Response
    {
        $repository = $this->getAccountRepository();
        $account = $repository->findOneBy([
            'mail' => $mail,
        ]);

        if ($account !== null && $hash === md5($account->getUsername())) {
            $account->setAllowMails(false);
            $repository->save($account);

            return $this->render('account/unsubscribe_success.html.twig');
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    /**
     * @Route("/feedback", name="feedback")
     */
    public function feedbackAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! empty($this->parameterBag->get('app.feedback_mail'))) {
            $form = $this->createForm(FeedbackType::class, null, [
                'action' => $this->generateUrl('feedback'),
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->accountMailer->sendCustomFeedbackToSystem($account, $this->parameterBag->get('app.feedback_mail'), $form->getData()['message']);
                return $this->render('feedback.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            return $this->render('feedback.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        throw $this->createNotFoundException();
    }
}
