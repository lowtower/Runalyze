<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use HTML;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRequestHandlerInterface;
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
        protected PanelRegistry $panelRegistry,
        protected ConfigurationManager $configurationManager,
        protected StatisticsRegistry $statisticsRegistry,
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
                || ! $this->panelRegistry->has($pluginData['key'])
            ) {
                continue;
            }

            $installation = $this->pluginInstallationManager->getInstallationForAccount($account, $pluginData['id']);

            if ($installation instanceof PluginInstallation) {
                $panel = $this->panelRegistry->get($pluginData['key']);
                $panelContent = $this->getResponseForPanel($account, $installation, $request)->getContent();
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
            && $this->panelRegistry->has($installation->getKey())
        ) {
            return $this->getResponseForPanel($account, $installation, $request);
        }

        if ($installation instanceof PluginInstallation && $installation->isStatistic()) {
            $statistic = $this->statisticsRegistry->find($installation->getKey());

            if ($statistic instanceof StatisticsInterface) {
                if ($statistic instanceof StatisticsRequestHandlerInterface) {
                    $statistic->handleRequest($account, $installation, $request->query);
                }

                return $this->render($statistic->getTemplate(), $statistic->present($account, $installation, $request->query));
            }
        }

        return new Response(HTML::error(__('The plugin could not be found.')));
    }

    /**
     * @throws InvalidArgumentException if a panel rejects the installation or request parameters
     */
    private function getResponseForPanel(Account $account, PluginInstallation $installation, Request $request): Response
    {
        $panel = $this->panelRegistry->get($installation->getKey());

        if ($panel instanceof PanelRequestHandlerInterface) {
            $panel->handleRequest($account, $installation, $request->query->all());
        }

        return $this->render($panel->getTemplate(), [
            'plugin_id' => $installation->getId(),
            'is_hidden' => $installation->isHidden(),
            'panel_data' => $panel->present($account, $installation),
        ]);
    }
}
