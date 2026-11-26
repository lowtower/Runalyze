<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use HTML;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
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

    public function __construct(
        protected ManagerRegistry $doctrine,
        protected PluginInstallationManager $pluginInstallationManager,
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
     * @throws Throwable if a plugin throws
     */
    protected function getResponseForAllEnabledPanels(Request $request, Account $account): Response
    {
        $content = '';

        foreach ($this->pluginSettingsProvider->getInstalledPluginData() as $pluginData) {
            if ($pluginData['type'] !== PluginInstallation::TYPE_PANEL || $pluginData['active'] === PluginInstallation::STATE_INACTIVE
                || ! $this->modernPanelRegistry->has($pluginData['key'])
            ) {
                continue;
            }

            $installation = $this->pluginInstallationManager->getInstallationForAccount($account, $pluginData['id']);

            if ($installation instanceof PluginInstallation) {
                $panel = $this->modernPanelRegistry->get($pluginData['key']);
                $panelContent = $this->getResponseForModernPanel($account, $installation, $request)->getContent();
                $panelClass = $panel->shouldReloadAfterTraining() ? 'panel' : 'panel dont-reload-for-training';
                $content .= '<div class="' . $panelClass . '" id="panel-' . $pluginData['id'] . '">' . $panelContent . '</div>';
            }
        }

        return new Response($content);
    }

    /**
     * @throws Throwable if a plugin throws
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

        return new Response(HTML::error(__('The plugin could not be found.')));
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
}
