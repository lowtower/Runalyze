<?php

/**
 * This file contains class::ConfigTabGeneral
 * @package Runalyze\System\Config
 */

use Runalyze\Configuration;

/**
 * @author Hannes Christiansen
 * @package Runalyze\System\Config
 */
class ConfigTabGeneral extends ConfigTab
{
    /**
     * Set key and title for form
     */
    protected function setKeyAndTitle()
    {
        $this->key = 'config_tab_general';
        $this->title = __('General settings');
    }

    /**
     * @return \Runalyze\Configuration\Category[]
     */
    private function allCategories(): array
    {
        return [
            Configuration::General(),
            Configuration::ActivityView(),
            Configuration::ActivityForm(),
            Configuration::Design(),
            Configuration::DataBrowser(),
            Configuration::VO2max(),
            Configuration::Trimp(),
            Configuration::BasicEndurance(),
        ];
    }

    /**
     * Set all fieldsets and fields
     */
    public function setFieldsetsAndFields()
    {
        $IsFirst = true;
        $Categories = $this->allCategories();

        foreach ($Categories as $Category) {
            $Fieldset = $Category->Fieldset();

            if ($Fieldset !== null) {
                if ($IsFirst) {
                    $IsFirst = false;
                } else {
                    $Fieldset->setCollapsed();
                }

                $this->Formular->addFieldset($Fieldset);
                $this->Formular->setLayoutForFields(FormularFieldset::$LAYOUT_FIELD_W100);
            }
        }

        $this->Formular->allowOnlyOneOpenedFieldset();
    }

    /**
     * Parse all post values
     */
    public function parsePostData()
    {
        $Categories = $this->allCategories();

        foreach ($Categories as $Category) {
            $Category->updateFromPost();
        }
    }
}
