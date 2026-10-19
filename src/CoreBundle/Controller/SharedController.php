<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use DataBrowserShared;
use Doctrine\Persistence\ManagerRegistry;
use FrontendShared;
use FrontendSharedStatistics;
use PlotMonthSumData;
use PlotSumData;
use PlotWeekSumData;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PrivacyGuard;
use Runalyze\Export\Share\Facebook;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Feed;
use Runalyze\View\Activity\Linker;
use Runalyze\View\Leaflet\Activity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TrainingView;

class SharedController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag,
        private ConfigurationManager $configurationManager,
        private ActivityContextFactory $activityContextFactory,
        private PrivacyGuard $privacyGuard,
        private Feed $activityFeed,
        private RequestStack $requestStack
    ) {
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->doctrine
            ->getRepository(Training::class);
    }

    #[Route(path: '/shared/{activityHash}&{foo}&{bar}&{baz}', requirements: [
        'activityHash' => '[a-zA-Z0-9]+',
    ], methods: ['GET'])]
    #[Route(path: '/shared/{activityHash}&{foo}&{bar}', requirements: [
        'activityHash' => '[a-zA-Z0-9]+',
    ], methods: ['GET'])]
    #[Route(path: '/shared/{activityHash}&{foo}', requirements: [
        'activityHash' => '[a-zA-Z0-9]+',
    ], methods: ['GET'])]
    #[Route(path: '/shared/{activityHash}', name: 'shared-activity', requirements: [
        'activityHash' => '[a-zA-Z0-9]+',
    ], methods: ['GET'])]
    public function sharedTrainingAction(string $activityHash, Request $request): Response
    {
        $activityId = (int) base_convert($activityHash, 35, 10);
        /** @var ?Training $activity */
        $activity = $activityId > 0
            ? $this->doctrine
                ->getRepository(Training::class)->find($activityId)
            : null;

        if ($activity === null || ! $activity->isPublic()) {
            return $this->render('shared/invalid_activity.html.twig');
        }

        $request->query->set('user', $activity->getAccount()->getUsername());

        /** @var AccountRepository $repository */
        $repository = $this->doctrine
            ->getRepository(Account::class);
        $account = $repository->findForUsername($activity->getAccount()->getUsername());
        $publicList = $this->configurationManager
            ->getList($account)
            ->getPrivacy()
            ->isListPublic();

        new FrontendShared(true, $this->tokenStorage, $this->parameterBag, $this->requestStack);

        $activityContext = $this->activityContextFactory
            ->getContext($activity);
        $activityContextLegacy = new Context($activity->getId(), $activity->getAccount()->getId());
        $hasRoute = $activityContext->canShowMap() && $this->privacyGuard
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
            'activityUrl' => new Linker($activityContextLegacy->activity())->publicUrl(),
            'activityHasRoute' => $hasRoute,
            'metaTitle' => new Facebook($activityContextLegacy)->metaTitle(),
            'view' => new TrainingView($activityContextLegacy),
        ]);
    }

    #[Route(path: '/shared/{username}/', methods: ['GET'])]
    public function oldSharedUserAction(string $username): Response
    {
        return $this->redirectToRoute('shared-athlete', [
            'username' => $username,
        ], 301);
    }

    #[Route(path: '/athlete/{username}', name: 'shared-athlete', methods: ['GET'])]
    public function sharedUserAction(string $username, Request $request): Response
    {
        /** @var AccountRepository $repository */
        $repository = $this->doctrine
            ->getRepository(Account::class);
        $account = $repository->findForUsername($username);
        $privacy = $this->configurationManager
            ->getList($account)
            ->getPrivacy();

        if ($account === null || ! $privacy->isListPublic()) {
            return $this->render('shared/invalid_athlete.html.twig');
        }

        $request->query->set('user', $username);

        new FrontendShared(true, $this->tokenStorage, $this->parameterBag, $this->requestStack);

        if ($request->query->has('type')) {
            return $this->render('shared/athlete/base_plot_sum_data.html.twig', [
                'username' => $username,
                'plot' => $this->getPlotSumData($request),
            ]);
        }

        if ($privacy->isListWithStatistics()) {
            $accountStatistics = $this->getTrainingRepository()
                ->getAccountStatistics($account);
            $legacyStatistics = new FrontendSharedStatistics();
            $publicList = $this->configurationManager
                ->getList($account)
                ->getPrivacy()
                ->isListPublic();
        } else {
            $accountStatistics = null;
            $legacyStatistics = null;
            $publicList = false;
        }

        return $this->render('shared/athlete/base.html.twig', [
            'account' => $account,
            'accountStatistics' => $accountStatistics,
            'legacyStatistics' => $legacyStatistics,
            'dataBrowser' => new DataBrowserShared(),
            'username' => $username,
            'hasPublicList' => $publicList,
        ]);
    }

    #[Route(path: '/athlete/{username}/feed', name: 'shared-athlete-feed', methods: ['GET'])]
    public function publicUserFeedAction(string $username): Response
    {
        /** @var AccountRepository $repository */
        $repository = $this->doctrine
            ->getRepository(Account::class);
        $account = $repository->findForUsername($username);
        $privacy = $this->configurationManager
            ->getList($account)
            ->getPrivacy();

        $feed = $this->activityFeed;
        $feed->setFeedTitle('RUNALYZE athlete ' . $username)
            ->setFeedUrl($this->generateUrl('shared-athlete-feed', [
                'username' => $username,
            ], UrlGeneratorInterface::ABSOLUTE_URL))
            ->setSiteUrl($this->generateUrl('shared-athlete', [
                'username' => $username,
            ], UrlGeneratorInterface::ABSOLUTE_URL))
            ->setFeedAuthor($username);
        $feed->setActivities(
            $account === null || ! $privacy->isListPublic()
                ? []
                : $this->getTrainingRepository()
                    ->getLatestActivities($account, 20, ! $privacy->isListShowingAllActivities())
        );

        return new Response(
            $feed->buildFeed(),
            Response::HTTP_OK,
            [
                'content-type' => 'text/xml',
            ]
        );
    }

    protected function getPlotSumData(Request $request): PlotSumData
    {
        if ($request->query->get('y') === null) {
            $request->query->set('y', PlotSumData::LAST_12_MONTHS);
        }

        return $request->query->get('type', 'month') == 'week' ? new PlotWeekSumData() : new PlotMonthSumData();
    }
}
