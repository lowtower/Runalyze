<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Frontend;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Dataset;
use Runalyze\Bundle\CoreBundle\Entity\DatasetRepository;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Form\Settings\AccountType;
use Runalyze\Bundle\CoreBundle\Form\Settings\ChangeMailType;
use Runalyze\Bundle\CoreBundle\Form\Settings\ChangePasswordType;
use Runalyze\Bundle\CoreBundle\Form\Settings\DatasetCollectionType;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Configuration;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\DefaultConfiguration;
use Runalyze\Dataset\Keys;
use Runalyze\Language;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingsController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private TranslatorInterface $translator,
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag,
        private UserPasswordHasherInterface $passwordHasher,
        private AutomaticReloadFlagSetter $automaticReloadFlagSetter,
        private RequestStack $requestStack,
        private AccountMailer $accountMailer,
        private ConfigurationManager $configurationManager
    ) {
    }

    protected function getAccountRepository(): AccountRepository
    {
        return $this->getDoctrine()
            ->getRepository(Account::class);
    }

    protected function getDatasetRepository(): DatasetRepository
    {
        return $this->getDoctrine()
            ->getRepository(Dataset::class);
    }

    /**
     * @Route("/settings/account", name="settings-account")
     * @throws AccessDeniedException
     */
    public function settingsAccountAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        $currentLanguage = $account->getLanguage();
        $form = $this->createForm(AccountType::class, $account, [
            'action' => $this->generateUrl('settings-account'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->all($form->getName());

            if (isset($formdata['reset_configuration'])) {
                Configuration::resetConfiguration($account->getId());
                $this->addFlash('success', $this->translator->trans('Default configuration has been restored!'));

                $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);
            }

            if (isset($formdata['language'])) {
                $this->requestStack->getSession()
                    ->set('_locale', $formdata['language']);
                Language::setLanguage($formdata['language']);

                if ($account->getLanguage() !== $currentLanguage) {
                    $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PAGE);
                }
            }

            $this->getAccountRepository()
                ->save($account);

            $this->addFlash('success', $this->translator->trans('Your changes have been saved!'));
        }

        return $this->render('settings/account.html.twig', [
            'form' => $form->createView(),
            'language' => $account->getLanguage(),
        ]);
    }

    /**
     * @Route("/settings/password", name="settings-password")
     * @throws AccessDeniedException
     */
    public function settingsPasswordAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(ChangePasswordType::class, $account, [
            'action' => $this->generateUrl('settings-password'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->all($form->getName());
            $account->setPlainPassword($formdata['plainPassword']['first']);
            $this->hashPassword($account);
            $this->getAccountRepository()
                ->save($account);

            $this->addFlash('success', $this->translator->trans('Your new password has been saved!'));
        }

        return $this->render('settings/account-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/settings/mail", name="settings-mail")
     * @throws AccessDeniedException
     */
    public function settingsMailAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(ChangeMailType::class, $account, [
            'action' => $this->generateUrl('settings-mail'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getAccountRepository()
                ->save($account);
            $this->addFlash('success', $this->translator->trans('Your mail address has been changed!'));
        }

        return $this->render('settings/account-mail.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function hashPassword(?Account $account = null): void
    {
        $account->setNewSalt();
        $account->setPassword($this->passwordHasher->hashPassword($account, $account->getPlainPassword()));
    }

    /**
     * @Route("/settings/account/delete", name="settings-account-delete")
     * @throws AccessDeniedException
     */
    public function windowDeleteAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $account->setNewDeletionHash();
        $this->getAccountRepository()
            ->save($account);

        $this->accountMailer->sendDeleteLinkTo($account);

        return $this->render('settings/account-delete.html.twig');
    }

    /**
     * @Route("/settings/dataset", name="settings-dataset-update", methods={"POST"})
     * @throws AccessDeniedException
     */
    public function datasetPostAction(?Account $account = null, ?Request $request = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()
            ->getManager();

        $dataset = $this->getDatasetRepository()
            ->findAllFor($account);

        $form = $this->createForm(DatasetCollectionType::class, [
            'datasets' => $dataset,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('datasets')->getData() as $datasetObject) {
                /** @var Dataset $datasetObject */
                $datasetObject->setAccount($account);
                $em->persist($datasetObject);
            }

            $em->flush();
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
        }

        return $this->redirectToRoute('settings-dataset');
    }

    /**
     * @Route("/settings/dataset", name="settings-dataset", methods={"GET"})
     * @throws AccessDeniedException
     */
    public function datasetAction(?Account $account = null, ?Request $request = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        $dataset = $this->getDatasetRepository()
            ->findAllFor($account);
        $missingKeyObjects = array_flip(Keys::getEnum());
        $numberOfExistingKeys = count($dataset);

        foreach ($dataset as $datasetObject) {
            unset($missingKeyObjects[$datasetObject->getKeyId()]);
        }

        foreach ($missingKeyObjects as $key => $missingDataset) {
            $dataset[] = (new Dataset())->setActive(false)->setKeyId($key)->setAccount($account);
        }

        $form = $this->createForm(DatasetCollectionType::class, [
            'datasets' => $dataset,
        ], [
            'action' => $this->generateUrl('settings-dataset'),
        ]);
        $form->handleRequest($request);

        return $this->render('settings/dataset.html.twig', [
            'form' => $form->createView(),
            'datasetKeys' => new Keys(),
            'missingKeys' => $missingKeyObjects,
            'defaultConfiguration' => (new DefaultConfiguration())->data(),
            'numberOfExistingKeys' => $numberOfExistingKeys,
            'context' => new Context($this->getExampleTraining($account), $account->getId()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getExampleTraining(?Account $account = null): array
    {
        $configuration = $this->configurationManager
            ->getList();

        return [
            'id' => 0,
            'sportid' => $configuration->getGeneral()
                ->getRunningSport(),
            'typeid' => __('race'),
            'time' => time(),
            'created' => time(),
            'edited' => time(),
            'is_public' => 1,
            'is_track' => 1,
            'distance' => 10,
            's' => 51 * 60 + 27,
            'elevation' => 57,
            'climb_score' => 1.3,
            'percentage_hilly' => 0.68,
            'kcal' => 691,
            'pulse_avg' => 186,
            'pulse_max' => 193,
            'vo2max_with_elevation' => $configuration->getData()
                ->getCurrentVO2maxShape() + 1,
            'vo2max' => $configuration->getData()
                ->getCurrentVO2maxShape() + 2,
            'use_vo2max' => 0,
            'fit_vo2max_estimate' => round($configuration->getData()->getCurrentVO2maxShape()),
            'fit_recovery_time' => 800,
            'fit_hrv_analysis' => 800,
            'fit_training_effect' => 3.1,
            'fit_anaerobic_training_effect' => 3.1,
            'fit_performance_condition' => 101,
            'fit_performance_condition_end' => 96,
            'jd_intensity' => 27,
            'rpe' => 13,
            'trimp' => 121,
            'cadence' => 90,
            'stride_length' => 108,
            'groundcontact' => 220,
            'vertical_oscillation' => 76,
            'power' => 520,
            'temperature' => 17,
            'wind_speed' => 27,
            'wind_deg' => 219,
            'pressure' => 1025,
            'humidity' => 63,
            'weatherid' => 5,
            'splits' => '5|26:51-5|24:36',
            'comment' => str_replace(' ', '&nbsp;', __('Test activity')),
            'partner' => 'Peter',
            'notes' => str_replace(' ', '&nbsp;', __('Great run!')),
            'accountid' => $account->getId(),
            'creator' => '',
            'creator_details' => '',
            'activity_id' => '',
            'elevation_corrected' => 1,
            'swolf' => 29,
            'total_strokes' => 1250,
            'vertical_ratio' => 79,
            'groundcontact_balance' => 4980,
            'avg_impact_gs_left' => 10.3,
            'avg_impact_gs_right' => 10.7,
            'avg_braking_gs_left' => 9.8,
            'avg_braking_gs_right' => 9.6,
            'avg_footstrike_type_left' => 12,
            'avg_footstrike_type_right' => 13,
            'avg_pronation_excursion_left' => -9.4,
            'avg_pronation_excursion_right' => -15.0,
            'pulse_avg_active' => 156,
            'total_cycles' => 203,
            'fit_load_peak' => 78,
            'fit_training_effect_benefit' => 1, // #TSC Recovery
            'with_goal' => '1',
            Keys\Tags::CONCAT_TAGIDS_KEY => $this->exampleTagID($account),
            Keys\CompleteEquipment::CONCAT_EQUIPMENT_KEY => $this->exampleEquipmentIDs($account),
        ];
    }

    protected function exampleTagID(?Account $account = null): string
    {
        $tag = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->findBy([
                'account' => $account->getId(),
            ], null, 1);

        if ($tag) {
            return (string) $tag[0]->getId();
        }

        return '';
    }

    protected function exampleEquipmentIDs(?Account $account = null): string
    {
        $ids = [];
        $equipment = $this->getDoctrine()
            ->getRepository(Equipment::class)
            ->findBy([
                'account' => $account->getId(),
            ], null, 2);

        if (is_array($equipment)) {
            foreach ($equipment as $element) {
                $ids[] = $element->getId();
            }
        }

        return implode(',', $ids);
    }
}
