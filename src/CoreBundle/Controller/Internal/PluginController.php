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
use Throwable;

#[Route(path: '/_internal/plugin')]
class PluginController extends AbstractPluginsAwareController
{
    protected function getPluginRepository(): PluginRepository
    {
        return $this->doctrine
            ->getRepository(Plugin::class);
    }

    /**
     * @throws AccessDeniedException if no plugin with the given id exists for the current account
     */
    private function getPluginOwnedByAccountOrFail(int $id, ?Account $account = null): Plugin
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
     * @throws AccessDeniedException
     */
    #[Route(path: '/toggle/{id}', name: 'internal-plugin-toggle')]
    public function togglePanelAction(int $id, ?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginOwnedByAccountOrFail($id, $account);

        $this->getPluginRepository()
            ->toggleHidden($plugin);

        return new JsonResponse();
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '/move/{id}/up', name: 'internal-plugin-move-up')]
    public function movePanelUpAction(int $id, ?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginOwnedByAccountOrFail($id, $account);

        $this->getPluginRepository()
            ->movePanelUp($plugin);

        return new JsonResponse();
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '/move/{id}/down', name: 'internal-plugin-move-down')]
    public function movePanelDownAction(int $id, ?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $plugin = $this->getPluginOwnedByAccountOrFail($id, $account);

        $this->getPluginRepository()
            ->movePanelDown($plugin);

        return new JsonResponse();
    }

    /**
     * @throws AccessDeniedException
     * @throws Throwable if a legacy plugin throws while building the panels' content
     */
    #[Route(path: '/all-panels', name: 'internal-plugin-all-panels')]
    public function contentPanelsAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        ob_start();

        try {
            $frontend = new Frontend(false, $this->tokenStorage, $this->parameterBag);
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        ob_end_clean();

        return $this->getResponseForAllEnabledPanels($request, $account);
    }
}
