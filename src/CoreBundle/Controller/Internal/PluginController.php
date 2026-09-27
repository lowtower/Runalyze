<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Frontend;
use PluginFactory;
use Runalyze\Bundle\CoreBundle\Controller\AbstractPluginsAwareController;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/_internal/plugin")
 */
class PluginController extends AbstractPluginsAwareController
{
    protected function getPluginRepository(): PluginRepository
    {
        return $this->getDoctrine()
            ->getRepository(Plugin::class);
    }

    /**
     * @throws AccessDeniedException if no plugin with the given id exists for the current account
     */
    private function getPluginOwnedByAccountOrFail(int $id, Account $account): Plugin
    {
        $plugin = $this->getPluginRepository()
            ->find($id);

        if ($plugin === null || $plugin->getAccount()->getId() != $account->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        new Frontend(true, $this->tokenStorage, $this->parameterBag);
        PluginFactory::clearCache();

        return $plugin;
    }

    /**
     * @Route("/toggle/{id}", name="internal-plugin-toggle")
     * @throws AccessDeniedException
     */
    public function togglePanelAction(int $id, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginOwnedByAccountOrFail($id, $account);

        $this->getPluginRepository()
            ->toggleHidden($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/up", name="internal-plugin-move-up")
     * @throws AccessDeniedException
     */
    public function movePanelUpAction(int $id, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginOwnedByAccountOrFail($id, $account);

        $this->getPluginRepository()
            ->movePanelUp($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/down", name="internal-plugin-move-down")
     * @throws AccessDeniedException
     */
    public function movePanelDownAction(int $id, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginOwnedByAccountOrFail($id, $account);

        $this->getPluginRepository()
            ->movePanelDown($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/all-panels", name="internal-plugin-all-panels")
     * @throws AccessDeniedException
     */
    public function contentPanelsAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag);

        return $this->getResponseForAllEnabledPanels($request, $account);
    }
}
