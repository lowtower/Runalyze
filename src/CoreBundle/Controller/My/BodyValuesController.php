<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Entity\UserRepository;
use Runalyze\Bundle\CoreBundle\Form\BodyValuesType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Metrics\HeartRate\Unit\BeatsPerMinute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[\Symfony\Component\Routing\Attribute\Route(path: '/my/body-values')]
class BodyValuesController extends AbstractController
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        public AutomaticReloadFlagSetter $reloadFlagSetter,
        private readonly UserRepository $userRepository
    ) {
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/add', name: 'body-values-add')]
    public function addAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $oldUser */
        $oldUser = $this->userRepository
            ->getLatestEntryFor($account);
        $user = $oldUser ? $oldUser->cloneObjectForForm() : new User()->setAccount($account)
            ->setCurrentTimestamp();

        $form = $this->createForm(BodyValuesType::class, $user, [
            'action' => $this->generateUrl('body-values-add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository
                ->save($user, $account);
            $this->reloadFlagSetter
                ->set(AutomaticReloadFlagSetter::FLAG_ALL);
            return $this->redirectToRoute('body-values-table');
        }

        return $this->render('my/body-values/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => true,
            'reloadRequested' => true,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/edit', name: 'body-values-edit')]
    public function editAction(Request $request, int $id, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->userRepository->find($id);
        if ($user === null || $user->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('No user entry found.');
        }

        $form = $this->createForm(BodyValuesType::class, $user, [
            'action' => $this->generateUrl('body-values-edit', [
                'id' => $user->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user, $account);
            $this->reloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);
            return $this->redirectToRoute('body-values-table');
        }

        return $this->render('my/body-values/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => false,
            'reloadRequested' => true,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/delete', name: 'body-values-delete')]
    public function deleteAction(int $id, ?Account $account = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->userRepository->find($id);
        if ($user === null || $user->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('No user entry found.');
        }

        $this->userRepository->remove($user);
        $this->reloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_ALL);

        return $this->redirectToRoute('body-values-table');
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/table', name: 'body-values-table')]
    public function tableAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('my/body-values/table.html.twig', [
            'values' => $this->userRepository
                ->findAllFor($account),
            'unitWeight' => $this->configurationManager
                ->getList()
                ->getUnitSystem()
                ->getWeightUnit(),
            'unitHeartRate' => new BeatsPerMinute(),
            'reloadRequested' => false,
        ]);
    }
}
