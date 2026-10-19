<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportTypeType;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Repository\TypeRepository;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Profile\View\DataBrowserRowProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/settings/sport')]
class SportController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private AutomaticReloadFlagSetter $automaticReloadFlagSetter
    ) {
    }

    protected function getSportRepository(): SportRepository
    {
        return $this->doctrine
            ->getRepository(Sport::class);
    }

    protected function getTypeRepository(): TypeRepository
    {
        return $this->doctrine
            ->getRepository(Type::class);
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->doctrine
            ->getRepository(Training::class);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '', name: 'settings-sports', methods: ['GET'])]
    public function overviewAction(?Account $account = null): Response
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
     * @throws AccessDeniedException
     */
    #[Route(path: '/{sportid}/type/add', name: 'sport-type-add', requirements: [
        'sportid' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function typeAddAction(Request $request, int $sportid, ?Account $account = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine
            ->getManager();
        $type = new Type();
        $type->setAccount($account);

        $form = $this->createForm(SportTypeType::class, $type, [
            'action' => $this->generateUrl('sport-type-add', [
                'sportid' => $sportid,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type->setSport($em->getReference(Sport::class, $sportid));
            $this->getTypeRepository()
                ->save($type);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

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
     * @throws AccessDeniedException
     */
    #[Route(path: '/type/{id}/edit', name: 'sport-type-edit', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function typeEditAction(Request $request, int $id, ?Account $account = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $type = $this->getTypeRepository()
            ->find($id);
        if ($type === null || $type->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Type not found or access denied.');
        }

        $form = $this->createForm(SportTypeType::class, $type, [
            'action' => $this->generateUrl('sport-type-edit', [
                'id' => $type->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getTypeRepository()
                ->save($type);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

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
     * @throws AccessDeniedException
     */
    #[Route(path: '/type/{id}/delete', name: 'sport-type-delete', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function deleteSportTypeAction(Request $request, int $id, ?Account $account = null, ?TranslatorInterface $translator = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $token = $request->query->get('t') ?? $request->request->get('t');
        if (! $this->isCsrfTokenValid('deleteSportType', $token)) {
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
            $em = $this->doctrine
                ->getManager();
            $em->remove($type);
            $em->flush();
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
            $this->addFlash('success', $translator->trans('The object has been deleted.'));
        } else {
            $this->addFlash('error', $translator->trans('Object cannot be deleted.') . ' ' . $translator->trans('You have activities associated with this type.'));
        }

        return $this->redirectToRoute('settings-sports');
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '/add/{internalType}', name: 'sport-add', requirements: [
        'internalType' => '\d+',
    ], methods: ['GET', 'POST'])]
    #[Route(path: '/add/custom', name: 'sport-add-custom', methods: ['GET', 'POST'])]
    public function sportAddAction(Request $request, ?Account $account = null, ?string $internalType = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sport = new Sport();
        $sport->setAccount($account);

        if ($internalType !== null && $this->getSportRepository()->isInternalTypeFree((int) $internalType, $account)) {
            $sport->setDataFrom(SportProfile::get((int) $internalType));
            $this->getSportRepository()
                ->save($sport);

            return $this->redirectToRoute('sport-edit', [
                'id' => $sport->getId(),
            ]);
        }

        $form = $this->createForm(SportType::class, $sport, [
            'action' => $this->generateUrl('sport-add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSportRepository()
                ->save($sport);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-edit', [
                'id' => $sport->getId(),
            ]);
        }

        return $this->render('settings/sport/form-sport.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '/{id}/edit', name: 'sport-edit', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function sportEditAction(Request $request, int $id, ?Account $account = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sport = $this->getSportRepository()
            ->find($id);
        if ($sport === null || $sport->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Sport not found or access denied.');
        }

        $form = $this->createForm(SportType::class, $sport, [
            'action' => $this->generateUrl('sport-edit', [
                'id' => $sport->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSportRepository()
                ->save($sport);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

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
     * @throws AccessDeniedException
     */
    #[Route(path: '/{id}/delete', name: 'sport-delete', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function sportDeleteAction(Request $request, int $id, ?Account $account = null, ?TranslatorInterface $translator = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $token = $request->query->get('t') ?? $request->request->get('t');
        if (! $this->isCsrfTokenValid('deleteSport', $token)) {
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
            $em = $this->doctrine
                ->getManager();
            $em->remove($sport);
            $em->flush();
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
            $this->addFlash('success', $translator->trans('The object has been deleted.'));
        } else {
            $this->addFlash('error', $translator->trans('Object cannot be deleted.') . ' ' . $translator->trans('You have activities associated with this sport.'));
        }

        return $this->redirectToRoute('settings-sports');
    }
}
