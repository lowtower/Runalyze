<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\ConfRepository;
use Runalyze\Bundle\CoreBundle\Form\Settings\PrivacyData;
use Runalyze\Bundle\CoreBundle\Form\Settings\PrivacyType;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PrivacyController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private ConfigurationUpdater $configurationUpdater,
        private TranslatorInterface $translator
    ) {
    }

    protected function getConfRepository(): ConfRepository
    {
        return $this->getDoctrine()
            ->getRepository(Conf::class);
    }

    /**
     * @Route("/settings/privacy", name="settings-privacy")
     */
    public function settingsAccountAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

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
