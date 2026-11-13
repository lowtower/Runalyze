<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Ajax;
use ConfigTabPlugins;
use Frontend;
use HTML;
use Icon;
use PluginFactory;
use PluginInstaller;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
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

        $legacyContent = $this->captureLegacyOutput(function () use ($request): void {
            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

            $Pluginkey = $request->query->get('key');

            $factory = $this->pluginFactory;

            $installableKeys = array_column($factory->notInstalledPlugins(), 'key');

            if (! in_array($Pluginkey, $installableKeys, true)) {
                throw $this->createNotFoundException('Unknown or already installed plugin.');
            }

            $Installer = new PluginInstaller($Pluginkey);

            echo '<h1>' . __('Install') . ' ' . $Pluginkey . '</h1>';

            if ($Installer->install()) {
                $Plugin = $factory->newInstance($Pluginkey);

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

        $legacyContent = $this->captureLegacyOutput(function () use ($id): void {
            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);
            $Factory = $this->pluginFactory;

            if ($id !== null && is_numeric($id)) {
                $Plugin = $Factory->newInstanceFor((int) $id);
                $Plugin->displayConfigWindow();
            } else {
                echo '<em>' . __('Something went wrong ...') . '</em>';
            }
        })->getContent();

        return $this->wrapLegacyContent($legacyContent, $this->requestStack->getCurrentRequest());
    }
}
