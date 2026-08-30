<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\ConfRepository;
use Runalyze\Bundle\CoreBundle\Form\Settings\PrivacyData;
use Runalyze\Bundle\CoreBundle\Form\Settings\PrivacyType;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PrivacyController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    use AppServiceSubscriberTrait;

    private ConfigurationUpdater $configurationUpdater;

    private TranslatorInterface $translator;

    public function __construct(
        ConfigurationUpdater $configurationUpdater,
        TranslatorInterface $translator
    ) {
        $this->configurationUpdater = $configurationUpdater;
        $this->translator = $translator;
    }

    protected function getConfRepository(): ConfRepository
    {
        return $this->getDoctrine()
            ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Conf::class);
    }

    /**
     * @Route("/settings/privacy", name="settings-privacy")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsAccountAction(Request $request, Account $account): \Symfony\Component\HttpFoundation\Response
    {
        $privacyConfig = $this->get('app.configuration_manager')
            ->getList()
            ->getPrivacy();

        $privacy = new PrivacyData();
        $privacy->setDataFrom($privacyConfig);

        $form = $this->createForm(PrivacyType::class, $privacy, [
            'action' => $this->generateUrl('settings-privacy'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->configurationUpdater->updatePrivacyDetails($account, $privacy->getDataForConfiguration());

            $this->addFlash('success', $this->translator->trans('Your changes have been saved!'));
        }

        return $this->render('settings/privacy.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
