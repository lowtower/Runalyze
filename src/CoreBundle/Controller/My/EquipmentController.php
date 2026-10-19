<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Form;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/my/equipment')]
class EquipmentController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private ConfigurationManager $configurationManager,
        private AutomaticReloadFlagSetter $automaticReloadFlagSetter
    ) {
    }

    protected function getEquipmentRepository(): EquipmentRepository
    {
        return $this->doctrine
            ->getRepository(Equipment::class);
    }

    protected function getEquipmentTypeRepository(): EquipmentTypeRepository
    {
        return $this->doctrine
            ->getRepository(EquipmentType::class);
    }

    #[Route(path: '/category/{typeid}/table', name: 'equipment-category-table', requirements: [
        'typeid' => '\d+',
    ], methods: ['GET'])]
    public function categoryTableAction(int $typeid, ?Account $account = null, ?TranslatorInterface $translator = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var EquipmentType $equipmentType */
        $equipmentType = $this->getEquipmentTypeRepository()
            ->findOneBy([
                'id' => $typeid,
                'account' => $account->getId(),
            ]);
        $equipmentStatistics = $this->getEquipmentRepository()
            ->getStatisticsForType($typeid, $account);

        if ($equipmentType === null) {
            $this->addFlash('error', $translator->trans('EquipmentType not found or access denied.'));
            return $this->redirectToRoute('equipment-overview');
        }

        $unitSystem = $this->configurationManager
            ->getList()
            ->getUnitSystem();

        if ($equipmentType->getSport()->count() == 1) {
            $unitSystem->setPaceUnitFromSport($equipmentType->getSport()->first());
        }

        return $this->render('my/equipment/category/table.html.twig', [
            'unitSystem' => $unitSystem,
            'category' => $equipmentType,
            'statistics' => $equipmentStatistics,
        ]);
    }

    #[Route(path: '/overview', name: 'equipment-overview', methods: ['GET'])]
    public function overviewAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('my/equipment/overview.html.twig', [
            'equipmentTypes' => $this->getEquipmentTypeRepository()
                ->findAllFor($account),
            'unitSystem' => $this->configurationManager
                ->getList()
                ->getUnitSystem(),
        ]);
    }

    #[Route(path: '/category/add', name: 'equipment-category-add', methods: ['GET', 'POST'])]
    public function typeAddAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $equipmentType = new EquipmentType();
        $equipmentType->setAccount($account);

        $form = $this->createForm(Form\EquipmentCategoryType::class, $equipmentType, [
            'action' => $this->generateUrl('equipment-category-add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getEquipmentTypeRepository()
                ->save($equipmentType);
            $this->automaticReloadFlagSetter
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipmentType->getId(),
            ]);
        }

        return $this->render('my/equipment/form-category.html.twig', [
            'form' => $form->createView(),
            'unitSystem' => $this->configurationManager
                ->getList()
                ->getUnitSystem(),
        ]);
    }

    #[Route(path: '/category/{id}/edit', name: 'equipment-category-edit', methods: ['GET', 'POST'])]
    public function typeEditAction(Request $request, int $id, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $equipmentType = $this->getEquipmentTypeRepository()
            ->find($id);
        if ($equipmentType === null || $equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('EquipmentType not found or access denied.');
        }

        $form = $this->createForm(Form\EquipmentCategoryType::class, $equipmentType, [
            'action' => $this->generateUrl('equipment-category-edit', [
                'id' => $equipmentType->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getEquipmentTypeRepository()
                ->save($equipmentType);
            $this->automaticReloadFlagSetter
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipmentType->getId(),
            ]);
        }

        return $this->render('my/equipment/form-category.html.twig', [
            'form' => $form->createView(),
            'equipment' => $this->getEquipmentRepository()
                ->findForTypeId($equipmentType->getId(), $account),
            'unitSystem' => $this->configurationManager
                ->getList()
                ->getUnitSystem(),
        ]);
    }

    #[Route(path: '/category/{id}/delete', name: 'equipment-category-delete', methods: ['GET', 'POST'])]
    public function deleteEquipmentTypeAction(Request $request, int $id, ?Account $account = null, ?TranslatorInterface $translator = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $token = $request->query->get('t') ?? $request->request->get('t');
        if (! $this->isCsrfTokenValid('deleteEquipmentCategory', $token)) {
            $this->addFlash('error', $translator->trans('Invalid token.'));

            return $this->redirectToRoute('equipment-overview');
        }

        $equipmentType = $this->getEquipmentTypeRepository()
            ->find($id);
        if ($equipmentType === null || $equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('EquipmentType not found or access denied.');
        }

        $this->getEquipmentTypeRepository()
            ->remove($equipmentType);

        $this->automaticReloadFlagSetter
            ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
        $this->addFlash('success', $translator->trans('The category has been deleted.'));

        return $this->redirectToRoute('equipment-overview');
    }

    #[Route(path: '/add/{id}', name: 'equipment-add', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function equipmentAddAction(Request $request, int $id, ?Account $account = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $equipmentType = $this->getEquipmentTypeRepository()
            ->find($id);

        if ($equipmentType === null || $equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('EquipmentType not found or access denied.');
        }

        $equipment = new Equipment();
        $equipment->setAccount($account);
        $equipment->setType($equipmentType);
        $equipment->setDateStart(new \DateTime('now'));

        $form = $this->createForm(Form\EquipmentType::class, $equipment, [
            'action' => $this->generateUrl('equipment-add', [
                'id' => $equipmentType->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getEquipmentRepository()
                ->save($equipment);
            $this->automaticReloadFlagSetter
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipment->getType()
                    ->getId(),
            ]);
        }

        return $this->render('my/equipment/form-equipment.html.twig', [
            'form' => $form->createView(),
            'category_id' => $equipmentType->getId(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'equipment-edit', methods: ['GET', 'POST'])]
    public function equipmentEditAction(Request $request, int $id, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $equipment = $this->getEquipmentRepository()
            ->find($id);
        if ($equipment === null || $equipment->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Equipment not found or access denied.');
        }

        $form = $this->createForm(Form\EquipmentType::class, $equipment, [
            'action' => $this->generateUrl('equipment-edit', [
                'id' => $equipment->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getEquipmentRepository()
                ->save($equipment);
            $this->automaticReloadFlagSetter
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipment->getType()
                    ->getId(),
            ]);
        }

        return $this->render('my/equipment/form-equipment.html.twig', [
            'form' => $form->createView(),
            'category_id' => $equipment->getType()
                ->getId(),
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'equipment-delete', methods: ['GET', 'POST'])]
    public function deleteEquipmentAction(Request $request, int $id, ?Account $account = null, ?TranslatorInterface $translator = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $token = $request->query->get('t') ?? $request->request->get('t');
        if (! $this->isCsrfTokenValid('deleteEquipment', $token)) {
            $this->addFlash('error', $translator->trans('Invalid token.'));

            return $this->redirectToRoute('equipment-overview');
        }

        $equipment = $this->getEquipmentRepository()
            ->find($id);
        if ($equipment === null || $equipment->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Equipment not found or access denied.');
        }

        $this->getEquipmentRepository()
            ->remove($equipment);
        $this->automaticReloadFlagSetter
            ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
        $this->addFlash('success', $translator->trans('The object has been deleted.'));

        return $this->redirectToRoute('equipment-category-edit', [
            'id' => $equipment->getType()
                ->getId(),
        ]);
    }
}
