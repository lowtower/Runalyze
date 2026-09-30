<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use LogicException;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route as RouteEntity;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Form\MultiImporterType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityCache;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResultCollection;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Runalyze\Util\LocalTime;
use Runalyze\Util\ServerParams;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private FileImporter $fileImporter,
        private ActivityDataContainerFilter $activityDataContainerFilter,
        private ConfigurationManager $configurationManager,
        private ActivityDataContainerToActivityContextConverter $activityDataContainerToActivityContextConverter,
        private ActivityCache $activityCache,
        private DuplicateFinder $duplicateFinder,
        private TranslatorInterface $translator,
        private ActivityContextAdapterFactory $activityContextAdapterFactory,
        private AutomaticReloadFlagSetter $automaticReloadFlagSetter
    ) {
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->getDoctrine()
            ->getRepository(Training::class);
    }

    /**
     * @Route("/activity/add", name="activity-add")
     */
    public function createAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $defaultUploadMode = $this->configurationManager
            ->getList()
            ->getActivityForm()
            ->get('TRAINING_CREATE_MODE');

        if ($defaultUploadMode == 'garmin') {
            return $this->forward(self::class . '::communicatorAction');
        }

        if ($defaultUploadMode == 'form') {
            return $this->forward(self::class . '::newAction');
        }

        return $this->getUploadFormResponse();
    }

    /**
     * @Route("/activity/communicator", name="activity-communicator")
     */
    public function communicatorAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('activity/import_garmin_communicator.html.twig');
    }

    /**
     * @Route("/activity/communicator/iframe", name="activity-communicator-iframe")
     */
    public function communicatorIFrameAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('import/garmin_communicator.html.twig', [
            'garminAPIKey' => $this->getParameter('app.garmin_api_key'),
        ]);
    }

    /**
     * @Route("/activity/upload", name="activity-upload")
     */
    public function uploadAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $importResult = null;
        $importDir = $this->getParameter('app.data_directory') . '/import/';

        if ($request->query->has('file')) {
            $importer = $this->fileImporter;

            $importResult = $importer->importSingleFile($importDir . $request->query->get('file'));
        } elseif ($request->query->has('files')) {
            $importer = $this->fileImporter;
            $importResult = $importer->importFiles(
                array_map(fn ($file): string => $importDir . $file, explode(';', $request->query->get('files')))
            );
        }

        if ($importResult instanceof FileImportResultCollection) {
            return $this->getResponseForImportResults($importResult, $account, $request);
        }

        return $this->getUploadFormResponse();
    }

    protected function getResponseForImportResults(FileImportResultCollection $results, ?Account $account = null, ?Request $request = null): Response
    {
        $results->completeAndFilterResults($this->activityDataContainerFilter);

        foreach ($results as $result) {
            if ($result->isFailed()) {
                $this->addFlash('error', sprintf('%s: %s', pathinfo($result->getOriginalFileName(), PATHINFO_BASENAME), $result->getException()->getMessage()));
            }
        }

        $numActivities = $results->getTotalNumberOfActivities();

        if ($numActivities === 1) {
            return $this->getResponseForNewSingleActivity(
                $this->containerToActivity($this->findFirstActivityDataContainer($results), $account),
                $request
            );
        }

        if ($numActivities > 1) {
            return $this->getResponseForMultipleNewActivities($results, $request, $account);
        }

        return $this->getUploadFormResponse();
    }

    /**
     * @throws LogicException
     */
    private function findFirstActivityDataContainer(FileImportResultCollection $results): ActivityDataContainer
    {
        foreach ($results as $result) {
            foreach ($result->getContainer() as $container) {
                return $container;
            }
        }

        throw new LogicException('Expected at least one activity data container to be present when getTotalNumberOfActivities() reported at least one activity.');
    }

    protected function getUploadFormResponse(): Response
    {
        $serverParams = new ServerParams();
        $maxFileSize = min($serverParams->getPostMaxSizeInBytes(), $serverParams->getUploadMaxFilesize());

        return $this->render('activity/import_upload.html.twig', [
            'maxFileSize' => $maxFileSize < PHP_INT_MAX ? $maxFileSize : false,
        ]);
    }

    protected function containerToActivity(ActivityDataContainer $container, ?Account $account = null): Training
    {
        return $this->activityDataContainerToActivityContextConverter->getActivityFor($container, $account);
    }

    protected function getResponseForMultipleNewActivities(FileImportResultCollection $results, Request $request, ?Account $account = null): Response
    {
        $activityHashes = [];
        $errors = [];
        $previews = [];

        foreach ($results as $result) {
            if ($result->isFailed()) {
                $errors[] = sprintf('%s: %s', $result->getOriginalFileName(), $result->getException()->getMessage());
            } else {
                foreach ($result->getContainer() as $container) {
                    $activity = $this->containerToActivity($container, $account);
                    $previews[] = new ActivityPreview($activity, $this->duplicateFinder->isPossibleDuplicate($activity));
                    $activityHashes[] = $this->activityCache->save($activity);
                }
            }
        }

        $form = $this->createForm(MultiImporterType::class, $activityHashes, [
            'action' => $this->generateUrl('activity-multi-importer'),
        ]);
        $form->handleRequest($request);

        return $this->render('activity/multi_importer.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
            'previews' => $previews,
        ]);
    }

    /**
     * @Route("/activity/multi-import", name="activity-multi-importer")
     */
    public function multiImporterAction(Request $request, ?Account $account = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(MultiImporterType::class, [], [
            'action' => $this->generateUrl('activity-multi-importer'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $hashes = $form->get('activity')
                ->getData();

            if (! is_array($hashes)) {
                return $this->redirectToRoute('activity-upload');
            }

            $repository = $this->getTrainingRepository();
            $defaultLocation = $this->configurationManager
                ->getList()
                ->getActivityForm()
                ->getDefaultLocationForWeatherForecast();
            $activityIds = [];

            foreach ($hashes as $hash) {
                $activity = $this->activityCache->get($hash, null, true);
                $activity->setAccount($account);
                $activity->getAdapter()
                    ->setAccountToRelatedEntities();

                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $this->activityContextAdapterFactory->getAdapterFor($context)
                    ->guessWeatherConditions($defaultLocation, $account);

                $activityIds[] = $repository->save($activity, true);
            }

            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

            if ($form->get('show_multi_editor')->getData()) {
                return $this->redirectToRoute('multi-editor', [
                    'ids' => implode(',', $activityIds),
                ]);
            }

            $this->addFlash('success', $this->translator->trans('The activities have been successfully imported.'));

            return $this->render('util/close_overlay.html.twig');
        }

        return $this->redirectToRoute('activity-upload');
    }

    /**
     * @Route("/activity/new", name="activity-new")
     */
    public function newAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->query->has('date')) {
            $time = LocalTime::fromString($request->query->get('date'))->getTimestamp();
        } else {
            $time = null;
        }

        return $this->getResponseForNewSingleActivity(
            $this->getDefaultNewActivity($account, $time),
            $request,
            false
        );
    }

    protected function getResponseForNewSingleActivity(Training $activity, ?Request $request = null, bool $setCache = true): Response
    {
        $form = $this->createForm(ActivityType::class, $activity, [
            'action' => $this->generateUrl('activity-new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleSubmitOfNewActivityForm($activity, $form);

            $this->addFlash('success', $this->translator->trans('The activity has been successfully created.'));
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

            return $this->render('util/close_overlay.html.twig');
        }

        if (! $form->isSubmitted() && $setCache) {
            $form->get('temporaryHash')
                ->setData($this->activityCache->save($activity));
        }

        return $this->render('activity/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => true,
            'isDuplicate' => $this->duplicateFinder->isPossibleDuplicate($activity),
            'isPowerLocked' => $activity->isPowerCalculated() !== null,
            'prev_activity_id' => null,
            'next_activity_id' => null,
            'activity_id' => null,
            'isMulti' => (bool) $request?->get('multi', false),
        ]);
    }

    protected function handleSubmitOfNewActivityForm(Training $newActivity, FormInterface $form): void
    {
        $activity = $this->activityCache->get($form->get('temporaryHash')->getData(), $newActivity, true);

        if ($activity->getRouteName() != '') {
            if (! $activity->hasRoute()) {
                $activity->setRoute((new RouteEntity())->setAccount($activity->getAccount()));
            }

            $activity->getRoute()
                ->setName($activity->getRouteName());

            if ((int) $activity->getElevation() !== 0 && $activity->getRoute()->getElevation() === 0) {
                $activity->getRoute()
                    ->setElevation($activity->getElevation());
            }
        }

        if ($form->get('is_race')->getData()) {
            $raceResult = (new Raceresult())->fillFromActivity($activity);
            $activity->setRaceresult($raceResult);
        }

        $this->getTrainingRepository()
            ->save($activity);
    }

    protected function getDefaultNewActivity(?Account $account = null, ?int $time = null): Training
    {
        $activity = new Training();
        $activity->setAccount($account);
        $activity->setTime($time ?: LocalTime::now());
        $activity->setSport($this->getMainSport($account));
        $activity->setPublic(! $activity->getSport()->getDefaultPrivacy());
        $activity->setType($activity->getSport()->getDefaultType());

        return $activity;
    }

    protected function getMainSport(?Account $account = null): ?Sport
    {
        $mainSportId = $this->configurationManager
            ->getList()
            ->getGeneral()
            ->getMainSport();
        /** @var SportRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(Sport::class);
        $sport = $repository->findThisOrAny($mainSportId, $account);

        if ($sport === null || $account->getId() != $sport->getAccount()->getId()) {
            return null;
        }

        return $sport;
    }
}
