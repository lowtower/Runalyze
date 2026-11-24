<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use HTML;
use InvalidArgumentException;
use Plugin as LegacyPlugin;
use PluginPanel;
use PluginType;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

abstract class AbstractPluginsAwareController extends AbstractController
{
    use AppServiceSubscriberTrait;

    protected bool $IsShowingAllPanels = false;

    public function __construct(
        protected ManagerRegistry $doctrine,
        protected PluginInstallationManager $pluginInstallationManager,
        protected LegacyPluginOutputRenderer $legacyPluginOutputRenderer,
        protected LegacyContentResponseFactory $legacyContentResponseFactory,
        protected PluginSettingsProvider $pluginSettingsProvider,
        protected ModernPanelRegistry $modernPanelRegistry,
        protected ConfigurationManager $configurationManager,
        protected ModernStatisticsRegistry $modernStatisticsRegistry,
        protected TokenStorageInterface $tokenStorage,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $requestStack
    ) {}

    /**
     * @throws Throwable if a modern panel or legacy plugin throws
     */
    protected function getResponseForAllEnabledPanels(Request $request, Account $account): Response
    {
        $this->IsShowingAllPanels = true;
        $content = '';

        foreach ($this->pluginSettingsProvider->getInstalledPluginData() as $pluginData) {
            if ($pluginData['type'] !== PluginType::string(PluginType::PANEL) || $pluginData['active'] === 0) {
                continue;
            }

            if ($this->modernPanelRegistry->has($pluginData['key'])) {
                $installation = $this->pluginInstallationManager->getInstallationForAccount($account, $pluginData['id']);

                if ($installation instanceof PluginInstallation) {
                    $panel = $this->modernPanelRegistry->get($pluginData['key']);
                    $panelContent = $this->getResponseForModernPanel($account, $installation, $request)->getContent();
                    $panelClass = $panel->shouldReloadAfterTraining() ? 'panel' : 'panel dont-reload-for-training';
                    $content .= '<div class="' . $panelClass . '" id="panel-' . $pluginData['id'] . '">' . $panelContent . '</div>';

                    continue;
                }
            }

            $panel = $this->pluginSettingsProvider->getInstalledPluginById($pluginData['id']);
            $panelContent = $this->getResponseForPlugin($panel)->getContent();

            $content .= $panelContent;
        }

        return new Response($content);
    }

    /**
     * @throws Throwable if a modern panel or legacy plugin throws
     */
    protected function getResponseFor(int $pluginId, Request $request, Account $account): Response
    {
        $installation = $this->pluginInstallationManager->getInstallationForAccount($account, $pluginId);

        if (
            $installation instanceof PluginInstallation
            && $installation->isPanel()
            && ! $installation->isInactive()
            && $this->modernPanelRegistry->has($installation->getKey())
        ) {
            return $this->getResponseForModernPanel($account, $installation, $request);
        }

        if ($installation instanceof PluginInstallation && $installation->isStatistic()) {
            $statistic = $this->modernStatisticsRegistry->find($installation->getKey());

            if ($statistic instanceof ModernStatisticsInterface) {
                if ($statistic instanceof ModernStatisticsRequestHandlerInterface) {
                    $statistic->handleRequest($account, $installation, $request->query);
                }

                return $this->render($statistic->getTemplate(), $statistic->present($account, $installation, $request->query));
            }
        }

        $content = '';

        try {
            $plugin = $this->instantiatePluginById($pluginId);
        } catch (Exception) {
            $plugin = null;
            $content = HTML::error(__('The plugin could not be found.'));
        }

        if (! $plugin instanceof LegacyPlugin) {
            return new Response()->setContent($content);
        }

        return $this->getResponseForPlugin($plugin);
    }

    /**
     * @throws InvalidArgumentException if a modern panel rejects the installation or request parameters
     */
    private function getResponseForModernPanel(Account $account, PluginInstallation $installation, Request $request): Response
    {
        $panel = $this->modernPanelRegistry->get($installation->getKey());

        if ($panel instanceof ModernPanelRequestHandlerInterface) {
            $panel->handleRequest($account, $installation, $request->query->all());
        }

        return $this->render($panel->getTemplate(), [
            'plugin_id' => $installation->getId(),
            'is_hidden' => $installation->isHidden(),
            'panel_data' => $panel->present($account, $installation),
        ]);
    }

    /**
     * @throws Throwable if a legacy plugin throws
     */
    protected function getResponseForPlugin(LegacyPlugin $plugin): Response
    {
        if ($plugin instanceof PluginPanel) {
            $plugin->setSurroundingDivVisible($this->IsShowingAllPanels);
        }

        return $this->legacyPluginOutputRenderer->render(function () use ($plugin): void {
            $plugin->display();
        });
    }

    /**
     * @throws InvalidArgumentException if no plugin is installed with the given id
     */
    protected function instantiatePluginById(int $pluginId): LegacyPlugin
    {
        return $this->pluginSettingsProvider->getInstalledPluginById($pluginId);
    }

    /**
     * @throws InvalidArgumentException if no plugin is installed with the given key
     */
    protected function instantiatePluginByKey(string $pluginKey): LegacyPlugin
    {
        return $this->pluginSettingsProvider->getInstalledPluginByKey($pluginKey);
    }
}
