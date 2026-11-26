<?php

use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;

/**
 * This file contains class::ConfigTabPlugins
 * @package Runalyze\System\Config
 */
/**
 * @author Hannes Christiansen
 * @package Runalyze\System\Config
 */
class ConfigTabPlugins extends ConfigTab
{
    public function __construct(
        private readonly PluginInstallationManager $pluginInstallationManager,
        private readonly PluginSettingsProvider $pluginSettingsProvider
    ) {
        parent::__construct();
    }

    /**
     * Set key and title for form
     */
    protected function setKeyAndTitle()
    {
        $this->key = 'config_tab_plugins';
        $this->title = __('Plugins');
    }

    /**
     * Get URL
     */
    public static function getExternalUrl(): string
    {
        return ConfigTabs::$CONFIG_URL . '?key=config_tab_plugins&external=true';
    }

    /**
     * Set all fieldsets and fields
     */
    public function setFieldsetsAndFields(): void
    {
        $Panels = new FormularFieldset(__('Panels'));
        $Panels->addInfo(__('Panels are small statistics always shown on the right side.'));
        $Panels->setHtmlCode($this->getCodeFor(PluginInstallation::TYPE_PANEL));
        $Panels->setCollapsed();

        $Stats = new FormularFieldset(__('Statistics'));
        $Stats->addInfo(__('Normal statistics are shown below the activitiy log.'));
        $Stats->setHtmlCode($this->getCodeFor(PluginInstallation::TYPE_STAT));

        $Install = new FormularFieldset(__('Install a new plugin'));
        $Install->addInfo(__('New plugins can be installed here.'));
        $Install->setHtmlCode($this->getCodeForInstall());
        $Install->setCollapsed();

        $this->Formular->addFieldset($Panels);
        $this->Formular->addFieldset($Stats);
        $this->Formular->addFieldset($Install);
        $this->Formular->allowOnlyOneOpenedFieldset();
    }

    private function getCodeFor(string $PluginType): string
    {
        $Plugins = $this->pluginSettingsProvider->getInstalledPluginAdminEntries($PluginType);

        if ($Plugins === []) {
            return HTML::info(__('No plugins available.'));
        }

        $Code = '
			<table class="zebra-style fullwidth more-padding">
				<thead>
					<tr class="top b">
						<th colspan="3">' . ($PluginType === PluginInstallation::TYPE_PANEL ? __('Panel') : __('Statistics')) . '</th>
						<th>' . __('Mode') . '</th>
						<th>' . __('Order') . '</th>
					</tr>
				</thead>
				<tbody>';

        foreach ($Plugins as $pos => $Plugin) {
            $Code .= '
					<tr id="' . $Plugin['id'] . '_tr" class="a' . (($Plugin['active'] === PluginInstallation::STATE_INACTIVE) ? ' unimportant' : '') . '">
						<td>' . $Plugin['config_link'] . '</td>
						<td class="b">' . $Plugin['name'] . '</td>
						<td>' . $Plugin['description'] . '</td>
						<td><select name="plugin_modus_' . $Plugin['id'] . '">
								<option value="' . PluginInstallation::STATE_ACTIVE . '"' . HTML::Selected(($Plugin['active'] === PluginInstallation::STATE_ACTIVE)) . '>' . __('enabled') . '</option>
								<option value="' . PluginInstallation::STATE_HIDDEN . '"' . HTML::Selected(($Plugin['active'] === PluginInstallation::STATE_HIDDEN)) . '>' . __('hidden*') . '</option>
								<option value="' . PluginInstallation::STATE_INACTIVE . '"' . HTML::Selected(($Plugin['active'] === PluginInstallation::STATE_INACTIVE)) . '>' . __('not enabled') . '</option>
							</select></td>
						<td style="white-space:nowrap;">
							<input class="plugin-position" type="text" name="plugin_order_' . $Plugin['id'] . '" size="3" value="' . ($pos + 1) . '">
							<span class="link" onclick="pluginMove(' . $Plugin['id'] . ', \'up\')">' . Icon::$UP . '</span>
							<span class="link" onclick="pluginMove(' . $Plugin['id'] . ', \'down\')">' . Icon::$DOWN . '</span>
						</td>
					</tr>';
        }

        $Code .= '
				</tbody>
			</table>';

        $Code .= Ajax::wrapJS('
			function pluginMove(id, way) {
				var pos = parseInt($("input[name=\'plugin_order_"+id+"\']").val()),
					tr = $("#"+id+"_tr");

				if (way == "up" && pos > 1) {
					$("#"+id+"_tr .plugin-position").val(pos-1);
					tr.prev().find(".plugin-position").val(pos);
					tr.prev().toggleClass("swapped");
					tr.prev().before(tr);
				} else if (way == "down" && tr.next().find(".plugin-position").val() > 0) {
					$("#"+id+"_tr .plugin-position").val(pos+1);
					tr.next().find(".plugin-position").val(pos);
					tr.next().toggleClass("swapped");
					tr.next().after(tr);
				}

				tr.toggleClass("swapped");
			}
		');

        match ($PluginType) {
            PluginInstallation::TYPE_PANEL => $Code .= HTML::info(__('* Hidden plugins only show their headings.')),
            PluginInstallation::TYPE_STAT => $Code .= HTML::info(__("* Hidden plugins are grouped as 'Miscellaneous'.")),
            default => $Code .= '',
        };

        return $Code;
    }

    private function getCodeForInstall(): string
    {
        $Plugins = $this->pluginSettingsProvider->getAvailablePluginAdminEntries();

        if ($Plugins === []) {
            return HTML::fileBlock(__('There are no new plugins to install.'));
        }

        $Code = '
			<table class="fullwidth zebra-style more-padding">
				<thead>
					<tr class="b">
						<th colspan="3">' . __('Plugin') . '</th>
						<th colspan="2">' . __('Type') . '</th>
					</tr>
				</thead>
				<tbody>';

        foreach ($Plugins as $Plugin) {
            $Code .= '
				<tr>
					<td>' . Icon::$ADD . '</td>
					<td class="b">' . Ajax::window('<a href="call/call.Plugin.install.php?key=' . $Plugin['key'] . '">' . Ajax::tooltip($Plugin['name'], __('Install plugin')) . '</a>') . '</td>
					<td>' . $Plugin['description'] . '</td>
					<td colspan="2">' . ($Plugin['type'] === PluginInstallation::TYPE_PANEL ? __('Panel') : __('Statistics')) . '</td>
				</tr>';
        }

        return $Code . '
				</tbody>
			</table>';
    }

    /**
     * Parse all post values
     */
    public function parsePostData(): void
    {
        $settings = [];

        foreach ($this->pluginSettingsProvider->getInstalledPluginData() as $Plugin) {
            $id = $Plugin['id'];

            if (isset($_POST['plugin_modus_' . $id]) && isset($_POST['plugin_order_' . $id])) {
                $settings[(int) $id] = [
                    'active' => (int) $_POST['plugin_modus_' . $id],
                    'order' => (int) $_POST['plugin_order_' . $id],
                ];
            }
        }

        if ($this->pluginInstallationManager->updateInstalledPluginSettings($settings)) {
            Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
        }
    }
}
