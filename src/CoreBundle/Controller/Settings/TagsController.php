<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\TagRepository;
use Runalyze\Bundle\CoreBundle\Form\Settings\TagType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/settings/tags")
 */
class TagsController extends AbstractController
{
    protected function getTagRepository(): TagRepository
    {
        return $this->getDoctrine()
            ->getRepository(Tag::class);
    }

    /**
     * @Route("", name="settings-tags")
     * @throws AccessDeniedException
     */
    public function overviewAction(Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('settings/tag/overview.html.twig', [
            'tags' => $this->getTagRepository()
                ->findAllFor($account),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="settings-tags-edit")
     * @throws AccessDeniedException
     */
    public function tagEditAction(Request $request, int $id, Account $account): Response
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
     * @Route("/{id}/delete", name="settings-tags-delete")
     * @throws AccessDeniedException
     */
    public function tagDeleteAction(Request $request, int $id, Account $account, TranslatorInterface $translator): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $this->isCsrfTokenValid('deleteTag', $request->get('t'))) {
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
