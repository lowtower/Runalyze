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
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class PluginController extends AbstractPluginsAwareController
{
    /**
     * @Route("/call/call.Plugin.install.php")
     *
     * @throws NotFoundHttpException if "key" does not match an installable plugin
     * @throws Throwable if the legacy plugin installer throws
     */
    public function pluginInstallAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag);

        $Pluginkey = $request->query->get('key');

        $factory = new PluginFactory();

        $installableKeys = array_column($factory->notInstalledPlugins(), 'key');

        if (! in_array($Pluginkey, $installableKeys, true)) {
            throw $this->createNotFoundException('Unknown or already installed plugin.');
        }

        return $this->captureLegacyOutput(function () use ($factory, $Pluginkey): void {
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
        });
    }

    /**
     * @Route("/my/plugin/{id}", requirements={"id"="\d+"}, name="plugin-display")
     *
     * @throws Throwable if the legacy plugin throws
     */
    public function pluginDisplayAction(int $id, Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag);

        return $this->getResponseFor($id, $request, $account);
    }

    /**
     * @Route("/call/call.Plugin.config.php", name="plugin-config")
     *
     * @throws Throwable if the legacy plugin config window throws
     */
    public function pluginConfigAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag);
        $Factory = new PluginFactory();

        $id = $request->query->get('id');

        return $this->captureLegacyOutput(function () use ($Factory, $id): void {
            if ($id !== null && is_numeric($id)) {
                $Plugin = $Factory->newInstanceFor((int) $id);
                $Plugin->displayConfigWindow();
            } else {
                echo '<em>' . __('Something went wrong ...') . '</em>';
            }
        });
    }
}
