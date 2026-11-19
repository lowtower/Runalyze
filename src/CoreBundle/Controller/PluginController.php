<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Ajax;
use ConfigTabPlugins;
use Frontend;
use HTML;
use Icon;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelMetadataInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class PluginController extends AbstractPluginsAwareController
{
    /**
     * @throws NotFoundHttpException if "key" does not match an installable plugin
     * @throws Throwable if the legacy plugin installer throws
     */
    #[Route(path: '/call/call.Plugin.install.php', methods: ['GET', 'POST'])]
    public function pluginInstallAction(Request $request, PluginDiscovery $pluginDiscovery): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $legacyContent = $this->legacyPluginOutputRenderer->render(function () use ($request, $pluginDiscovery): void {
            $pluginKey = $request->query->get('key');

            $availablePluginKeys = $pluginDiscovery->findAvailablePluginKeys();
            $installedPluginKeys = $this->pluginInstallationManager->getInstalledPluginKeys();
            $installableKeys = array_diff($availablePluginKeys, $installedPluginKeys);

            if (! is_string($pluginKey) || ! in_array($pluginKey, $installableKeys, true)) {
                throw $this->createNotFoundException('Unknown or already installed plugin.');
            }

            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

            echo '<h1>' . __('Install') . ' ' . $pluginKey . '</h1>';

            if ($this->pluginInstallationManager->install($pluginKey)) {
                echo HTML::okay(__('The plugin has been successfully installed.'));

                if (! $this->modernPanelRegistry->has($pluginKey)
                    || ! $this->modernPanelRegistry->get($pluginKey) instanceof ModernPanelMetadataInterface
                ) {
                    $plugin = $this->instantiatePluginByKey($pluginKey);
                    $configLink = $plugin->getConfigLink(Icon::$CONF . ' ' . __('Configuration'));

                    if ($configLink !== null) {
                        echo '<ul class="blocklist"><li>' . $configLink . '</li></ul>';
                    }
                }

                Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
                echo Ajax::getReloadCommand();
            } else {
                echo HTML::error(__('There was a problem, the plugin could not be installed.'));
            }

            echo '<ul class="blocklist"><li>';
            echo Ajax::window('<a href="' . ConfigTabPlugins::getExternalUrl() . '">' . Icon::$TABLE . ' ' . __('back to list') . '</a>');
            echo '</li></ul>';
        })->getContent();

        return $this->legacyContentResponseFactory->create($legacyContent, $this->requestStack->getCurrentRequest());
    }

    /**
     * @throws Throwable if the legacy plugin throws
     */
    #[Route(path: '/my/plugin/{id}', name: 'plugin-display', requirements: [
        'id' => '\d+',
    ], methods: ['GET'])]
    public function pluginDisplayAction(int $id, Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        return $this->legacyContentResponseFactory->create(
            $this->getResponseFor($id, $request, $account)->getContent(),
            $request
        );
    }

    /**
     * @throws BadRequestHttpException if the activation state is invalid
     * @throws Throwable if the legacy plugin config window throws
     */
    #[Route(path: '/call/call.Plugin.config.php', name: 'plugin-config', methods: ['GET', 'POST'])]
    public function pluginConfigAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $id = $request->query->get('id');

        if (is_scalar($id) && ctype_digit((string) $id) && $account instanceof Account) {
            $installation = $this->pluginInstallationManager->getInstallationForAccount($account, (int) $id);

            if ($installation !== null
                && $this->modernPanelRegistry->has($installation->getKey())
                && $this->modernPanelRegistry->get($installation->getKey()) instanceof ModernPanelMetadataInterface
            ) {
                return $this->legacyContentResponseFactory->create(
                    HTML::info(__('This plugin has no configurable options.')),
                    $request
                );
            }
        }

        $legacyContent = $this->legacyPluginOutputRenderer->render(function () use ($id, $request): void {
            if ($id !== null && is_numeric($id)) {
                new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

                $plugin = $this->instantiatePluginById((int) $id);
                $active = $request->query->get('active');

                if ($active !== null) {
                    if (! is_string($active) || ! ctype_digit($active)) {
                        throw new BadRequestHttpException('Invalid plugin activation state.');
                    }

                    try {
                        $this->pluginInstallationManager->setInstalledPluginActive($plugin->id(), (int) $active);
                    } catch (InvalidArgumentException $exception) {
                        throw new BadRequestHttpException('Invalid plugin activation state.', $exception);
                    }

                    $plugin = $this->instantiatePluginById($plugin->id());

                    Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
                    echo Ajax::getReloadCommand();
                }

                $postData = $request->request->all();

                if ($postData !== []) {
                    if (isset($postData['edit']) && $postData['edit'] == 'true') {
                        $plugin->Configuration()->updateFromPost($postData);
                    }

                    Ajax::setPluginIDtoReload($plugin->id());
                    Ajax::setReloadFlag(Ajax::$RELOAD_PLUGINS);
                    echo Ajax::getReloadCommand();
                }

                $plugin->displayConfigWindow();
            } else {
                echo '<em>' . __('Something went wrong ...') . '</em>';
            }
        })->getContent();

        return $this->legacyContentResponseFactory->create($legacyContent, $this->requestStack->getCurrentRequest());
    }
}
