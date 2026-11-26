<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Ajax;
use ConfigTabPlugins;
use Frontend;
use HTML;
use Icon;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
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
     * @throws Throwable if plugin installation throws
     */
    #[Route(path: '/call/call.Plugin.install.php', methods: ['GET', 'POST'])]
    public function pluginInstallAction(Request $request, PluginDiscovery $pluginDiscovery, LegacyPluginOutputRenderer $outputRenderer): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $legacyContent = $outputRenderer->render(function () use ($request, $pluginDiscovery): void {
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

                if ($this->modernPanelRegistry->has($pluginKey)
                    && $this->modernPanelRegistry->get($pluginKey) instanceof ModernPanelConfigurationInterface
                ) {
                    $installations = $this->pluginSettingsProvider->getInstalledPluginData();

                    foreach ($installations as $installationData) {
                        if ($installationData['key'] === $pluginKey) {
                            echo '<ul class="blocklist"><li>';
                            echo Ajax::window('<a href="call/call.Plugin.config.php?id=' . $installationData['id'] . '">'
                                . Icon::$CONF . ' ' . __('Configuration') . '</a>', 'small');
                            echo '</li></ul>';

                            break;
                        }
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
     * @throws Throwable if a plugin throws
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
     * @throws Throwable if a plugin configuration handler throws
     */
    #[Route(path: '/call/call.Plugin.config.php', name: 'plugin-config', methods: ['GET', 'POST'])]
    public function pluginConfigAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $id = $request->query->get('id');

        if (is_scalar($id) && ctype_digit((string) $id) && $account instanceof Account) {
            $installation = $this->pluginInstallationManager->getInstallationForAccount($account, (int) $id);

            if ($installation instanceof PluginInstallation && $installation->isStatistic()) {
                $statistic = $this->modernStatisticsRegistry->find($installation->getKey());

                if ($statistic instanceof ModernStatisticsInterface) {
                    return $this->getStatisticsConfigurationResponse($request, $account, $installation, $statistic);
                }
            }

            if ($installation instanceof PluginInstallation && $installation->isPanel()
                && $this->modernPanelRegistry->has($installation->getKey())
            ) {
                $panel = $this->modernPanelRegistry->get($installation->getKey());

                if ($panel instanceof ModernPanelConfigurationInterface) {
                    new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);
                    $reloadRequired = false;

                    $active = $request->query->get('active');

                    if ($active !== null) {
                        if (! is_string($active) || ! ctype_digit($active)) {
                            throw new BadRequestHttpException('Invalid plugin activation state.');
                        }

                        try {
                            $reloadRequired = $this->pluginInstallationManager->setInstalledPluginActive(
                                (int) $id,
                                (int) $active
                            );
                        } catch (InvalidArgumentException $exception) {
                            throw new BadRequestHttpException('Invalid plugin activation state.', $exception);
                        }
                    }

                    if ($request->isMethod('POST') && $request->request->get('edit') === 'true') {
                        $panel->updateConfiguration($account, $installation, $request->request->all());
                        $reloadRequired = true;
                    }

                    $content = $this->renderView($panel->getConfigurationTemplate(), [
                        'plugin_id' => $installation->getId(),
                        'config_data' => $panel->getConfigurationData($account, $installation),
                    ]);

                    if ($reloadRequired) {
                        Ajax::setPluginIDtoReload((int) $id);
                        Ajax::setReloadFlag(Ajax::$RELOAD_PLUGINS);
                        $content .= Ajax::getReloadCommand();
                    }

                    return $this->legacyContentResponseFactory->create($content, $request);
                }

                new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

                return $this->legacyContentResponseFactory->create(
                    HTML::info(__('This plugin has no configurable options.')),
                    $request
                );
            }
        }

        return $this->legacyContentResponseFactory->create('<em>' . __('Something went wrong ...') . '</em>', $request);
    }

    private function getStatisticsConfigurationResponse(
        Request $request,
        Account $account,
        PluginInstallation $installation,
        ModernStatisticsInterface $statistic
    ): Response {
        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $id = $installation->getId();
        assert($id !== null);

        $active = $request->query->get('active');
        if ($active !== null) {
            if (! is_string($active) || ! ctype_digit($active)) {
                throw new BadRequestHttpException('Invalid plugin activation state.');
            }

            try {
                $this->pluginInstallationManager->setInstalledPluginActive($id, (int) $active);
            } catch (InvalidArgumentException $exception) {
                throw new BadRequestHttpException('Invalid plugin activation state.', $exception);
            }

            Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
        }

        $template = 'my/statistics/configuration.html.twig';
        $view = [
            'plugin_id' => $id,
            'name' => $statistic->getName(),
            'description' => $statistic->getDescription($account),
            'inactive' => $installation->isInactive(),
        ];
        $reloadRequired = $active !== null;

        if ($statistic instanceof ModernStatisticsConfigurationInterface) {
            if ($request->isMethod('POST') && $request->request->get('edit') === 'true') {
                $statistic->updateConfiguration($account, $installation, $request->request->all());
                if (! $reloadRequired) {
                    Ajax::setPluginIDtoReload($id);
                    Ajax::setReloadFlag(Ajax::$RELOAD_PLUGINS);
                    $reloadRequired = true;
                }
            }

            $template = $statistic->getConfigurationTemplate();
            $view['config_data'] = $statistic->getConfigurationData($account, $installation);
        }

        $content = $this->renderView($template, $view);

        if ($reloadRequired) {
            $content .= Ajax::getReloadCommand();
        }

        return $this->legacyContentResponseFactory->create($content, $request);
    }
}
