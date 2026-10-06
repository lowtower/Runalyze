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
use Throwable;

class PluginController extends AbstractPluginsAwareController
{
    /**
     * @throws NotFoundHttpException if "key" does not match an installable plugin
     * @throws Throwable if the legacy plugin installer throws
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/call/call.Plugin.install.php')]
    public function pluginInstallAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        ob_start();

        try {
            $frontend = new Frontend(false, $this->tokenStorage, $this->parameterBag, $this->requestStack);

            $Pluginkey = $request->query->get('key');

            $factory = new PluginFactory();

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

            $legacyContent = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return new Response($legacyContent);
    }

    /**
     * @throws Throwable if the legacy plugin throws
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/my/plugin/{id}', name: 'plugin-display', requirements: [
        'id' => '\d+',
    ])]
    public function pluginDisplayAction(int $id, Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag, $this->requestStack);

        return $this->getResponseFor($id, $request, $account);
    }

    /**
     * @throws Throwable if the legacy plugin config window throws
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/call/call.Plugin.config.php', name: 'plugin-config')]
    public function pluginConfigAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $id = $request->query->get('id');

        ob_start();

        try {
            $frontend = new Frontend(false, $this->tokenStorage, $this->parameterBag, $this->requestStack);
            $Factory = new PluginFactory();

            if ($id !== null && is_numeric($id)) {
                $Plugin = $Factory->newInstanceFor((int) $id);
                $Plugin->displayConfigWindow();
            } else {
                echo '<em>' . __('Something went wrong ...') . '</em>';
            }

            $legacyContent = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return new Response($legacyContent);
    }
}
