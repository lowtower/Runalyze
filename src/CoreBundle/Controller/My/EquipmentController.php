<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Form;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/my/equipment")
 * @Security("has_role('ROLE_USER')")
 */
class EquipmentController extends Controller
{
    /**
     * @return EquipmentRepository
     */
    protected function getEquipmentRepository()
    {
        return $this->getDoctrine()
            ->getRepository('CoreBundle:Equipment');
    }

    /**
     * @return EquipmentTypeRepository
     */
    protected function getEquipmentTypeRepository()
    {
        return $this->getDoctrine()
            ->getRepository('CoreBundle:EquipmentType');
    }

    /**
     * @Route("/category/{typeid}/table", name="equipment-category-table", requirements={"typeid" = "\d+"})
     */
    public function categoryTableAction($typeid, Account $account): \Symfony\Component\HttpFoundation\Response
    {
        /** @var EquipmentType $equipmentType */
        $equipmentType = $this->getEquipmentTypeRepository()
            ->findOneBy([
                'id' => $typeid,
                'account' => $account->getId(),
            ]);
        $equipmentStatistics = $this->getEquipmentRepository()
            ->getStatisticsForType($typeid, $account);

        if ($equipmentType === null) {
            throw $this->createAccessDeniedException();
        }

        $unitSystem = $this->get('app.configuration_manager')
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

    /**
     * @Route("/overview", name="equipment-overview")
     */
    public function overviewAction(Account $account): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('my/equipment/overview.html.twig', [
            'equipmentTypes' => $this->getEquipmentTypeRepository()
                ->findAllFor($account),
        ]);
    }

    /**
     * @Route("/category/add", name="equipment-category-add")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typeAddAction(Request $request, Account $account)
    {
        $equipmentType = new EquipmentType();
        $equipmentType->setAccount($account);

        $form = $this->createForm(Form\EquipmentCategoryType::class, $equipmentType, [
            'action' => $this->generateUrl('equipment-category-add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getEquipmentTypeRepository()
                ->save($equipmentType);
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipmentType->getId(),
            ]);
        }

        return $this->render('my/equipment/form-category.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/category/{id}/edit", name="equipment-category-edit")
     * @ParamConverter("equipmentType", class="CoreBundle:EquipmentType")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typeEditAction(Request $request, EquipmentType $equipmentType, Account $account)
    {
        if ($equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
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
            $this->get('app.automatic_reload_flag_setter')
                ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipmentType->getId(),
            ]);
        }

        return $this->render('my/equipment/form-category.html.twig', [
            'form' => $form->createView(),
            'equipment' => $this->getEquipmentRepository()
                ->findByTypeId($equipmentType->getId(), $account),
        ]);
    }

    /**
     * @Route("/category/{id}/delete", name="equipment-category-delete")
     * @ParamConverter("equipmentType", class="CoreBundle:EquipmentType")
     */
    public function deleteEquipmentTypeAction(Request $request, EquipmentType $equipmentType, Account $account): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! $this->isCsrfTokenValid('deleteEquipmentCategory', $request->get('t'))) {
            $this->addFlash('error', $this->get('translator')->trans('Invalid token.'));

            return $this->redirectToRoute('equipment-overview');
        }

        if ($equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->getEquipmentTypeRepository()
            ->remove($equipmentType);

        $this->get('app.automatic_reload_flag_setter')
            ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
        $this->addFlash('success', $this->get('translator')->trans('The category has been deleted.'));

        return $this->redirectToRoute('equipment-overview');
    }

    /**
     * @Route("/add/{id}", name="equipment-add", requirements={"id" = "\d+"})
     * @ParamConverter("equipmentType", class="CoreBundle:EquipmentType")
     */
    public function equipmentAddAction(Request $request, EquipmentType $equipmentType, Account $account)
    {
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
            $this->get('app.automatic_reload_flag_setter')
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

    /**
     * @Route("/{id}/edit", name="equipment-edit")
     * @ParamConverter("equipment", class="CoreBundle:Equipment")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function equipmentEditAction(Request $request, Equipment $equipment, Account $account)
    {
        if ($equipment->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
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
            $this->get('app.automatic_reload_flag_setter')
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

    /**
     * @Route("/{id}/delete", name="equipment-delete")
     * @ParamConverter("equipment", class="CoreBundle:Equipment")
     */
    public function deleteEquipmentAction(Request $request, Equipment $equipment, Account $account): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! $this->isCsrfTokenValid('deleteEquipment', $request->get('t'))) {
            $this->addFlash('error', $this->get('translator')->trans('Invalid token.'));

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipment->getType()
                    ->getId(),
            ]);
        }

        if ($equipment->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->getEquipmentRepository()
            ->remove($equipment);
        $this->get('app.automatic_reload_flag_setter')
            ->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
        $this->addFlash('success', $this->get('translator')->trans('The object has been deleted.'));

        return $this->redirectToRoute('equipment-category-edit', [
            'id' => $equipment->getType()
                ->getId(),
        ]);
    }
}
