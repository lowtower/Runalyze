<?php

/**
 * This file contains class::PluginConfigurationWindow
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration window
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationWindow
{
    public function __construct(
        protected \Plugin $Plugin
    ) {
        $this->handlePostData();
    }

    protected function handlePostData()
    {
        if (! empty($_POST)) {
            $this->updateConfiguration();
            $this->setReloadCommand();
        }
    }

    protected function updateConfiguration()
    {
        if (isset($_POST['edit']) && $_POST['edit'] == 'true') {
            $this->Plugin->Configuration()
                ->updateFromPost();
        }
    }

    protected function setReloadCommand()
    {
        Ajax::setPluginIDtoReload($this->Plugin->id());
        Ajax::setReloadFlag(Ajax::$RELOAD_PLUGINS);
        echo Ajax::getReloadCommand();
    }

    public function display(): void
    {
        $this->displayHeader();
        $this->displayForm();
    }

    protected function displayHeader()
    {
        $Links = [];
        $Links[] = [
            'tag' => Ajax::window('<a href="' . ConfigTabPlugins::getExternalUrl() . '">' . __('back to overview') . '</a>'),
        ];

        echo '<div class="panel-heading">';
        echo '<div class="panel-menu">';
        echo Ajax::toolbarNavigation($Links);
        echo '</div>';
        echo '<h1>' . __('Plugin configuration') . ': ' . $this->Plugin->name() . '</h1>';
        echo '</div>';
    }

    protected function displayForm()
    {
        $Formular = new Formular(Plugin::$CONFIG_URL . '?id=' . $this->Plugin->id(), 'post');
        $Formular->addCSSclass('ajax');
        $Formular->addCSSclass('no-automatic-reload');
        $Formular->addHiddenValue('edit', 'true');
        $Formular->addFieldset($this->getFieldsetForDescription());
        $Formular->addFieldset($this->getFieldsetForValues());
        $Formular->addFieldset($this->getFieldsetForActivation());
        $Formular->setLayoutForFields(FormularFieldset::$LAYOUT_FIELD_W100);
        $Formular->display();
    }

    /**
     * Get fieldset: description
     */
    protected function getFieldsetForDescription(): \FormularFieldset
    {
        $Fieldset = new FormularFieldset(__('Description'));
        $Fieldset->addText($this->Plugin->description());

        if ($this->Plugin->isInActive()) {
            $Fieldset->addWarning(__('The plugin is deactivated.'));
        }

        return $Fieldset;
    }

    /**
     * Get fieldset: values
     */
    protected function getFieldsetForValues(): \FormularFieldset
    {
        $Fieldset = new FormularFieldset(__('Configuration'));

        if ($this->Plugin->Configuration()->isEmpty()) {
            $Fieldset->addInfo(__('There are no settings.'));
        } else {
            foreach ($this->Plugin->Configuration()->objects() as $Value) {
                $Fieldset->addField($Value->getFormField());
            }

            $Fieldset->addField(new FormularSubmit(__('Save'), ''));
        }

        return $Fieldset;
    }

    /**
     * Get fieldset: activation
     */
    protected function getFieldsetForActivation(): \FormularFieldset
    {
        $activationLink = $this->Plugin->isInActive()
            ? $this->Plugin->getConfigLink(__('Activate plugin'), '&active=' . Plugin::ACTIVE)
            : $this->Plugin->getConfigLink(__('Deactivate plugin'), '&active=' . Plugin::ACTIVE_NOT);

        $Fieldset = new FormularFieldset(__('Activation'));
        $Fieldset->addInfo($activationLink);

        return $Fieldset;
    }
}
