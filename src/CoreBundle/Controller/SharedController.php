<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Export\Share\Facebook;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Linker;
use Runalyze\View\Leaflet\Activity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SharedController extends AbstractController
{
    use AppServiceSubscriberTrait;

    private TokenStorageInterface $tokenStorage;

    private ParameterBagInterface $parameterBag;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->getDoctrine()
            ->getRepository(Training::class);
    }

    /**
     * @Route("/shared/{activityHash}&{foo}&{bar}&{baz}", requirements={"activityHash"="[a-zA-Z0-9]+"})
     * @Route("/shared/{activityHash}&{foo}&{bar}", requirements={"activityHash"="[a-zA-Z0-9]+"})
     * @Route("/shared/{activityHash}&{foo}", requirements={"activityHash"="[a-zA-Z0-9]+"})
     * @Route("/shared/{activityHash}", requirements={"activityHash"="[a-zA-Z0-9]+"}, name="shared-activity")
     */
    public function sharedTrainingAction($activityHash, Request $request): Response
    {
        /** @var null|Training $activity */
        $activity = $this->getDoctrine()
            ->getRepository(Training::class)
            ->find((int) base_convert((string) $activityHash, 35, 10));

        if ($activity === null || ! $activity->isPublic()) {
            return $this->render('shared/invalid_activity.html.twig');
        }

        $_GET['user'] = $activity->getAccount()->getUsername();
        /** @var AccountRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(Account::class);
        $account = $repository->findByUsername($activity->getAccount()->getUsername());
        $publicList = $this->get('app.configuration_manager')
            ->getList($account)
            ->getPrivacy()
            ->isListPublic();

        $Frontend = new \FrontendShared(true, $this->tokenStorage, $this->parameterBag);
        $activityContext = $this->get('app.activity_context.factory')
            ->getContext($activity);
        $activityContextLegacy = new Context($activity->getId(), $activity->getAccount()->getId());

        $hasRoute = $activityContext->canShowMap() && $this->get('app.privacy_guard')
            ->isMapVisible($activity, $activityContext->getRaceResult());

        if ($request->query->get('mode') == 'iframe') {
            return $this->render('shared/widget/iframe/base.html.twig', [
                'username' => $activity->getAccount()
                    ->getUsername(),
                'hasPublicList' => $publicList,
                'context' => $activityContext,
                'route' => $hasRoute ? new Activity(
                    'route-' . $activity->getId(),
                    $activityContextLegacy->route(),
                    $activityContextLegacy->trackdata()
                ) : false,
            ]);
        }

        return $this->render('shared/activity/base.html.twig', [
            'username' => $activity->getAccount()
                ->getUsername(),
            'activity' => $activity,
            'hasPublicList' => $publicList,
            'activityUrl' => (new Linker($activityContextLegacy->activity()))->publicUrl(),
            'activityHasRoute' => $hasRoute,
            'metaTitle' => (new Facebook($activityContextLegacy))->metaTitle(),
            'view' => new \TrainingView($activityContextLegacy),
        ]);
    }

    /**
     * @Route("/shared/{username}/")
     */
    public function oldSharedUserAction(string $username): Response
    {
        return $this->redirectToRoute('shared-athlete', [
            'username' => $username,
        ], 301);
    }

    /**
     * @Route("/athlete/{username}", name="shared-athlete")
     */
    public function sharedUserAction(string $username, Request $request): Response
    {
        /** @var AccountRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(Account::class);
        $account = $repository->findByUsername($username);
        $privacy = $this->get('app.configuration_manager')
            ->getList($account)
            ->getPrivacy();

        if ($account === null || ! $privacy->isListPublic()) {
            return $this->render('shared/invalid_athlete.html.twig');
        }

        $_GET['user'] = $username;

        $Frontend = new \FrontendShared(true, $this->tokenStorage, $this->parameterBag);

        if (isset($_GET['type'])) {
            return $this->render('shared/athlete/base_plot_sum_data.html.twig', [
                'username' => $username,
                'plot' => $this->getPlotSumData(),
            ]);
        }

        if ($privacy->isListWithStatistics()) {
            /** @var TrainingRepository $repository */
            $repository = $this->getDoctrine()
                ->getRepository(Training::class);
            $accountStatistics = $repository->getAccountStatistics($account);
            $legacyStatistics = new \FrontendSharedStatistics();
            $publicList = $this->get('app.configuration_manager')
                ->getList($account)
                ->getPrivacy()
                ->isListPublic();
        } else {
            $accountStatistics = null;
            $legacyStatistics = null;
        }

        return $this->render('shared/athlete/base.html.twig', [
            'account' => $account,
            'accountStatistics' => $accountStatistics,
            'legacyStatistics' => $legacyStatistics,
            'dataBrowser' => new \DataBrowserShared(),
            'username' => $username,
            'hasPublicList' => $publicList,
        ]);
    }

    /**
     * @Route("/athlete/{username}/feed", name="shared-athlete-feed")
     */
    public function publicUserFeedAction(string $username): Response
    {
        /** @var AccountRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(Account::class);
        $account = $repository->findByUsername($username);
        $privacy = $this->get('app.configuration_manager')
            ->getList($account)
            ->getPrivacy();

        $feed = $this->get('app.activity.feed');
        $feed->setFeedTitle('RUNALYZE athlete ' . $username)
            ->setFeedUrl($this->generateUrl('shared-athlete-feed', [
                'username' => $username,
            ], UrlGeneratorInterface::ABSOLUTE_URL))
            ->setSiteUrl($this->generateUrl('shared-athlete', [
                'username' => $username,
            ], UrlGeneratorInterface::ABSOLUTE_URL))
            ->setFeedAuthor($username);

        if ($account === null || ! $privacy->isListPublic()) {
            return new Response(
                $feed->buildFeed(),
                Response::HTTP_OK,
                [
                    'content-type' => 'text/xml',
                ]
            );
        }

        $feed->setActivities($this->getTrainingRepository()->getLatestActivities($account, 20, ! $privacy->isListShowingAllActivities()));

        return new Response(
            $feed->buildFeed(),
            Response::HTTP_OK,
            [
                'content-type' => 'text/xml',
            ]
        );
    }

    /**
     * @return \PlotSumData
     */
    protected function getPlotSumData()
    {
        $Request = Request::createFromGlobals();

        if ($Request->query->get('y') === null) {
            $_GET['y'] = \PlotSumData::LAST_12_MONTHS;
        }

        return $Request->query->get('type', 'month') == 'week' ? new \PlotWeekSumData() : new \PlotMonthSumData();
    }
}
