<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Controller\AbstractPluginsAwareController;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/toggle/{id}", name="internal-plugin-toggle")
     */
    public function togglePanelAction(int $id, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginRepository()
            ->find($id);
        if ($plugin === null || $plugin->getAccount()->getId() != $account->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);
        \PluginFactory::clearCache();

        $this->getPluginRepository()
            ->toggleHidden($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/up", name="internal-plugin-move-up")
     */
    public function movePanelUpAction(int $id, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginRepository()
            ->find($id);

        if ($plugin === null || $plugin->getAccount()->getId() != $account->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);
        \PluginFactory::clearCache();

        $this->getPluginRepository()
            ->movePanelUp($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/move/{id}/down", name="internal-plugin-move-down")
     */
    public function movePanelDownAction(int $id, Account $account): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Plugin manuell aus der Datenbank laden
        $plugin = $this->getPluginRepository()
            ->find($id);

        if ($plugin === null || $plugin->getAccount()->getId() != $account->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Only as long as PluginFactory's cache is in use
        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);
        \PluginFactory::clearCache();

        $this->getPluginRepository()
            ->movePanelDown($plugin);

        return new JsonResponse();
    }

    /**
     * @Route("/all-panels", name="internal-plugin-all-panels")
     */
    public function contentPanelsAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);

        return $this->getResponseForAllEnabledPanels($request, $account);
    }
}
