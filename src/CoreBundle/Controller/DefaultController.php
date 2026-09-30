<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Frontend;
use Runalyze\Activity\Distance;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\FeedbackType;
use Runalyze\Bundle\CoreBundle\Form\RegistrationType;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;

/**
 * @package Runalyze\Bundle\CoreBundle\Controller
 */
class DefaultController extends AbstractPluginsAwareController
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly AuthenticationUtils $authenticationUtils,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
        private readonly AccountMailer $accountMailer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
    }

    protected function getAccountRepository(): AccountRepository
    {
        return $this->doctrine->getRepository(Account::class);
    }

    /**
     * Includes a legacy script and captures its direct output into the Symfony Response.
     *
     * @throws Throwable if the included legacy file throws
     */
    protected function includeOldScript(string $file, bool $initFrontend = true): Response
    {
        if ($initFrontend) {
            new Frontend(false, $this->tokenStorage, $this->parameterBag);
        }

        ob_start();

        try {
            include $file;
            $legacyContent = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        $response = $this->render('legacy_end.html.twig');
        $response->setContent($legacyContent . $response->getContent());

        return $response;
    }

    /**
     * @Route("/dashboard", name="dashboard")
     *
     * @throws Throwable if the included legacy dashboard throws
     */
    public function dashboardAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)
            ->getContent();

        ob_start();

        try {
            include __DIR__ . '/../../../dashboard.php';
            $legacyContent = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        $response = $this->render('legacy_end.html.twig');
        $response->setContent($legacyContent . $response->getContent());

        return $response;
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
    public function registerAction(Request $request, UserPasswordHasherInterface $passwordEncoder, Environment $twig, TranslatorInterface $translator): Response
    {
        if (! $this->parameterBag->get('app.user_can_register')) {
            return $this->render('register/disabled.html.twig');
        }

        $account = new Account();
        $form = $this->createForm(RegistrationType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registration = new Registration($this->doctrine->getManager(), $account, $passwordEncoder);
            $formdata = $request->request->all($form->getName());

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
    public function loginAction(Request $request): RedirectResponse|Response
    {
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
    public function loginCheckAction(): void
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
        $repository = $this->doctrine->getRepository(Training::class);
        $numDistance = (float) $repository->getAmountOfLoggedKilometers();

        return [
            'user' => (int) $numUser,
            'distance' => Distance::format($numDistance),
        ];
    }

    /**
     * @Route("/plugin/{plugin}/{file}")
     *
     * @throws NotFoundHttpException if $plugin or $file contain a path traversal attempt
     * @throws Throwable if the included legacy plugin file throws
     */
    public function pluginAction(string $plugin, string $file): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (str_contains($plugin, '..') || str_contains($file, '..') || str_starts_with($plugin, '/') || str_starts_with($file, '/')) {
            throw $this->createNotFoundException('Invalid plugin path.');
        }

        return $this->includeOldScript('../plugin/' . $plugin . '/' . $file);
    }

    /**
     * @Route("/index.php")
     *
     * @throws Throwable if the included legacy dashboard throws
     */
    public function indexPhpAction(Request $request, ?Account $account = null): Response|RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->isXmlHttpRequest()) {
            new Frontend(true, $this->tokenStorage, $this->parameterBag);

            $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)
                ->getContent();

            ob_start();

            try {
                include $this->parameterBag->get('kernel.project_dir') . '/dashboard.php';
                $legacyContent = ob_get_clean();
            } catch (Throwable $throwable) {
                ob_end_clean();

                throw $throwable;
            }

            return new Response($legacyContent);
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
    public function unsubscribeMailAction(string $mail, string $hash, UnsubscribeHashGenerator $unsubscribeHashGenerator): Response
    {
        $repository = $this->getAccountRepository();
        $account = $repository->findOneBy([
            'mail' => $mail,
        ]);

        if ($account !== null && $unsubscribeHashGenerator->isValid($account->getUsername(), $hash)) {
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
    public function unsubscribeMailConfirmAction(string $mail, string $hash, UnsubscribeHashGenerator $unsubscribeHashGenerator): Response
    {
        $repository = $this->getAccountRepository();
        $account = $repository->findOneBy([
            'mail' => $mail,
        ]);

        if ($account !== null && $unsubscribeHashGenerator->isValid($account->getUsername(), $hash)) {
            $account->setAllowMails(false);
            $repository->save($account);

            return $this->render('account/unsubscribe_success.html.twig');
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    /**
     * @Route("/feedback", name="feedback")
     *
     * @throws NotFoundHttpException if no feedback recipient is configured
     */
    public function feedbackAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (empty($this->parameterBag->get('app.feedback_mail'))) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(FeedbackType::class, null, [
            'action' => $this->generateUrl('feedback'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->accountMailer->sendCustomFeedbackToSystem($account, $this->parameterBag->get('app.feedback_mail'), $form->getData()['message']);
        }

        return $this->render('feedback.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
