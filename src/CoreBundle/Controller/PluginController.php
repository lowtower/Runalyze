<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PluginController extends AbstractPluginsAwareController
{
    /**
     * @Route("/call/call.Plugin.install.php")
     */
    public function pluginInstallAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);
        $Pluginkey = filter_input(INPUT_GET, 'key');

        $Installer = new \PluginInstaller($Pluginkey);

        echo '<h1>' . __('Install') . ' ' . $Pluginkey . '</h1>';

        if ($Installer->install()) {
            $Factory = new \PluginFactory();
            $Plugin = $Factory->newInstance($Pluginkey);

            echo \HTML::okay(__('The plugin has been successfully installed.'));

            echo '<ul class="blocklist"><li>';
            echo $Plugin->getConfigLink(\Icon::$CONF . ' ' . __('Configuration'));
            echo '</li></ul>';

            \Ajax::setReloadFlag(\Ajax::$RELOAD_ALL);
            echo \Ajax::getReloadCommand();
        } else {
            echo \HTML::error(__('There was a problem, the plugin could not be installed.'));
        }

        echo '<ul class="blocklist"><li>';
        echo \Ajax::window('<a href="' . \ConfigTabPlugins::getExternalUrl() . '">' . \Icon::$TABLE . ' ' . __('back to list') . '</a>');
        echo '</li></ul>';

        return new Response();
    }

    /**
     * @Route("/my/plugin/{id}", requirements={"id"="\d+"}, name="plugin-display")
     */
    public function pluginDisplayAction(int $id, Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);

        return $this->getResponseFor($id, $request, $account);
    }

    /**
     * @Route("/call/call.Plugin.config.php", name="plugin-config")
     */
    public function pluginConfigAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);
        $Factory = new \PluginFactory();

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $Plugin = $Factory->newInstanceFor($_GET['id']);
            $Plugin->displayConfigWindow();
        } else {
            echo '<em>' . __('Something went wrong ...') . '</em>';
        }

        return new Response();
    }
}
