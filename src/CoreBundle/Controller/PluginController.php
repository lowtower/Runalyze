<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Ajax;
use ConfigTabPlugins;
use Frontend;
use HTML;
use Icon;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Response;
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
    public function pluginInstallAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $legacyContent = $this->legacyPluginOutputRenderer->render(function () use ($request): void {
            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

            $Pluginkey = $request->query->get('key');

            $availablePluginKeys = $this->pluginDiscovery->findAvailablePluginKeys();
            $installedPluginKeys = $this->pluginInstallationManager->getInstalledPluginKeys();
            $installableKeys = array_diff($availablePluginKeys, $installedPluginKeys);

            if (! is_string($Pluginkey) || ! in_array($Pluginkey, $installableKeys, true)) {
                throw $this->createNotFoundException('Unknown or already installed plugin.');
            }

            echo '<h1>' . __('Install') . ' ' . $Pluginkey . '</h1>';

            if ($this->pluginInstallationManager->install($Pluginkey)) {
                $Plugin = $this->instantiatePluginByKey($Pluginkey);

                echo HTML::okay(__('The plugin has been successfully installed.'));

                echo '<ul class="blocklist"><li>';
                echo $Plugin->getConfigLink(Icon::$CONF . ' ' . __('Configuration'));
                echo '</li></ul>';

                Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
                echo Ajax::getReloadCommand();
            } else {
                echo HTML::error(__('There was a problem, the plugin could not be installed.'));
            }

            echo '<ul class="blocklist"><li>';
            echo Ajax::window('<a href="' . ConfigTabPlugins::getExternalUrl() . '">' . Icon::$TABLE . ' ' . __('back to list') . '</a>');
            echo '</li></ul>';
        })->getContent();

        return $this->wrapLegacyContent($legacyContent, $this->requestStack->getCurrentRequest());
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

        return $this->wrapLegacyContent(
            $this->getResponseFor($id, $request, $account)->getContent(),
            $request
        );
    }

    /**
     * @throws Throwable if the legacy plugin config window throws
     */
    #[Route(path: '/call/call.Plugin.config.php', name: 'plugin-config', methods: ['GET', 'POST'])]
    public function pluginConfigAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $id = $request->query->get('id');

        $legacyContent = $this->legacyPluginOutputRenderer->render(function () use ($id, $request): void {
            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

            if ($id !== null && is_numeric($id)) {
                $plugin = $this->instantiatePluginById((int) $id);
                $active = $request->query->get('active');

                if ($active !== null) {
                    if (! is_string($active) || ! ctype_digit($active)) {
                        throw new BadRequestHttpException('Invalid plugin activation state.');
                    }

                    $this->pluginInstallationManager->setInstalledPluginActive($plugin->id(), (int) $active);

                    Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
                    echo Ajax::getReloadCommand();
                }

                $plugin->displayConfigWindow();
            } else {
                echo '<em>' . __('Something went wrong ...') . '</em>';
            }
        })->getContent();

        return $this->wrapLegacyContent($legacyContent, $this->requestStack->getCurrentRequest());
    }
}
