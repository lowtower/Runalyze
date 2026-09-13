<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Entity\TypeRepository;
use Runalyze\Bundle\CoreBundle\Form;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Profile\View\DataBrowserRowProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/settings/sport")
 */
class SportController extends AbstractController
{
    use AppServiceSubscriberTrait;

    protected function getSportRepository(): SportRepository
    {
        return $this->getDoctrine()
            ->getRepository(Sport::class);
    }

    protected function getTypeRepository(): TypeRepository
    {
        return $this->getDoctrine()
            ->getRepository(Type::class);
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->getDoctrine()
            ->getRepository(Training::class);
    }

    /**
     * @Route("", name="settings-sports")
     */
    public function overviewAction(Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('settings/sport/overview.html.twig', [
            'sports' => $this->getSportRepository()
                ->findAllFor($account),
            'hasTrainings' => array_flip($this->getTrainingRepository()->getSportsWithTraining($account)),
            'freeInternalTypes' => $this->getSportRepository()
                ->getFreeInternalTypes($account),
            'calendarView' => new DataBrowserRowProfile(),
        ]);
    }

    /**
     * @Route("/{sportid}/type/add", name="sport-type-add", requirements={"sportid"="\d+"})
     */
    public function typeAddAction(Request $request, int $sportid, Account $account): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()
            ->getManager();
        $type = new Type();
        $type->setAccount($account);

        $form = $this->createForm(Form\Settings\SportTypeType::class, $type, [
            'action' => $this->generateUrl('sport-type-add', [
                'sportid' => $sportid,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type->setSport($em->getReference(Sport::class, $sportid));
            $this->getTypeRepository()
                ->save($type);
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
            return $this->redirectToRoute('sport-edit', [
                'id' => $sportid,
            ]);
        }

        return $this->render('settings/sport/form-type.html.twig', [
            'form' => $form->createView(),
            'sport_id' => $sportid,
        ]);
    }

    /**
     * @Route("/type/{id}/edit", name="sport-type-edit", requirements={"id"="\d+"})
     */
    public function typeEditAction(Request $request, int $id, Account $account): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $type = $this->getTypeRepository()
            ->find($id);
        if ($type === null || $type->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Type not found or access denied.');
        }

        $form = $this->createForm(Form\Settings\SportTypeType::class, $type, [
            'action' => $this->generateUrl('sport-type-edit', [
                'id' => $type->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getTypeRepository()
                ->save($type);
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-type-edit', [
                'id' => $type->getId(),
            ]);
        }

        return $this->render('settings/sport/form-type.html.twig', [
            'form' => $form->createView(),
            'sport_id' => $type->getSport()
                ->getId(),
        ]);
    }

    /**
     * @Route("/type/{id}/delete", name="sport-type-delete", requirements={"id"="\d+"})
     */
    public function deleteSportTypeAction(Request $request, int $id, Account $account, TranslatorInterface $translator): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $this->isCsrfTokenValid('deleteSportType', $request->get('t'))) {
            $this->addFlash('error', $translator->trans('Invalid token.'));
            return $this->redirectToRoute('settings-sports');
        }

        $type = $this->getTypeRepository()
            ->find($id);
        if ($type === null || $type->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Type not found or access denied.');
        }

        if ($type->getTrainings()->count() == 0) {
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()
                ->getManager();
            $em->remove($type);
            $em->flush();
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
            $this->addFlash('success', $translator->trans('The object has been deleted.'));
        } else {
            $this->addFlash('error', $translator->trans('Object cannot be deleted.') . ' ' . $translator->trans('You have activities associated with this type.'));
        }

        return $this->redirectToRoute('settings-sports');
    }

    /**
     * @Route("/add/{internalType}", name="sport-add", requirements={"internalType"="\d+"})
     * @Route("/add/custom", name="sport-add-custom")
     */
    public function sportAddAction(Request $request, Account $account, $internalType = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sport = new Sport();
        $sport->setAccount($account);

        if ($internalType !== null && $this->getSportRepository()->isInternalTypeFree($internalType, $account)) {
            $sport->setDataFrom(SportProfile::get($internalType));
            $this->getSportRepository()
                ->save($sport);

            return $this->redirectToRoute('sport-edit', [
                'id' => $sport->getId(),
            ]);
        }

        $form = $this->createForm(Form\Settings\SportType::class, $sport, [
            'action' => $this->generateUrl('sport-add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSportRepository()
                ->save($sport);
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-edit', [
                'id' => $sport->getId(),
            ]);
        }

        return $this->render('settings/sport/form-sport.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="sport-edit", requirements={"id"="\d+"})
     */
    public function sportEditAction(Request $request, int $id, Account $account): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sport = $this->getSportRepository()
            ->find($id);
        if ($sport === null || $sport->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Sport not found or access denied.');
        }

        $form = $this->createForm(Form\Settings\SportType::class, $sport, [
            'action' => $this->generateUrl('sport-edit', [
                'id' => $sport->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSportRepository()
                ->save($sport);
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-edit', [
                'id' => $sport->getId(),
            ]);
        }

        return $this->render('settings/sport/form-sport.html.twig', [
            'form' => $form->createView(),
            'types' => $this->getTypeRepository()
                ->findAllFor($account, $sport),
            'calendarView' => new DataBrowserRowProfile(),
            'hasTrainings' => array_flip($this->getTrainingRepository()->getTypesWithTraining($account)),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="sport-delete", requirements={"id"="\d+"})
     */
    public function sportDeleteAction(Request $request, int $id, Account $account, TranslatorInterface $translator): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $this->isCsrfTokenValid('deleteSport', $request->get('t'))) {
            $this->addFlash('error', $translator->trans('Invalid token.'));
            return $this->redirectToRoute('settings-sports');
        }

        $sport = $this->getSportRepository()
            ->find($id);
        if ($sport === null || $sport->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Sport not found or access denied.');
        }

        if ($sport->getTrainings()->count() === 0) {
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()
                ->getManager();
            $em->remove($sport);
            $em->flush();
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
            $this->addFlash('success', $translator->trans('The object has been deleted.'));
        } else {
            $this->addFlash('error', $translator->trans('Object cannot be deleted.') . ' ' . $translator->trans('You have activities associated with this sport.'));
        }

        return $this->redirectToRoute('settings-sports');
    }
}
