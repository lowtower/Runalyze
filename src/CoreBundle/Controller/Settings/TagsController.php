<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\TagRepository;
use Runalyze\Bundle\CoreBundle\Form\Settings\TagType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\Symfony\Component\Routing\Attribute\Route(path: '/settings/tags')]
class TagsController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $doctrine
    ) {
    }

    protected function getTagRepository(): TagRepository
    {
        return $this->doctrine
            ->getRepository(Tag::class);
    }

    /**
     * @throws AccessDeniedException
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '', name: 'settings-tags')]
    public function overviewAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('settings/tag/overview.html.twig', [
            'tags' => $this->getTagRepository()
                ->findAllFor($account),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/edit', name: 'settings-tags-edit')]
    public function tagEditAction(Request $request, int $id, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $tag = $this->getTagRepository()
            ->find($id);
        if ($tag === null || $tag->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Tag not found or access denied.');
        }

        $form = $this->createForm(TagType::class, $tag, [
            'action' => $this->generateUrl('settings-tags-edit', [
                'id' => $tag->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getTagRepository()
                ->save($tag);

            return $this->redirectToRoute('settings-tags');
        }

        return $this->render('settings/tag/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/delete', name: 'settings-tags-delete')]
    public function tagDeleteAction(Request $request, TranslatorInterface $translator, int $id, ?Account $account = null): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $token = $request->query->get('t') ?? $request->request->get('t');
        if (! $this->isCsrfTokenValid('deleteTag', $token)) {
            $this->addFlash('notice', $translator->trans('Invalid token.'));

            return $this->redirectToRoute('settings-tags');
        }

        $tag = $this->getTagRepository()
            ->find($id);
        if ($tag === null || $tag->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException('Tag not found or access denied.');
        }

        $this->getTagRepository()
            ->remove($tag);

        $this->addFlash('notice', $translator->trans('Tag has been deleted.'));

        return $this->redirectToRoute('settings-tags');
    }
}
