<?php

/**
 * This file contains the class of the RunalyzePluginPanel "Prognose".
 * @package Runalyze\Plugins\Panels
 */
$PLUGINKEY = 'RunalyzePluginPanel_Prognose';
/**
 * Class: RunalyzePluginPanel_Prognose
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Panels
 */
class RunalyzePluginPanel_Prognose extends PluginPanel
{
    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Prognosis');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('Predict your race performance on various distances.');
    }

    /**
     * @codeCoverageIgnore
     */
    final protected function displayLongDescription(): void
    {
        echo HTML::p(__('There are different models that can be used to predict your race performances:'));
        echo HTML::fileBlock('<strong>VO2max</strong><br>'
                    . __('Your current effective VO2max is estimated based on the ratio of heart rate and pace. '
                        . 'This value is equivalent to specific performances.'));
        echo HTML::fileBlock("<strong>Robert Bock (CPP, 'Competitive Performance Predictor')</strong><br>"
                    . __('Robert Bock uses an individual coefficient for your fatigue over time/distance. '
                        . 'This model uses your two best results.') . '<br>'
                    . '<small>see <a href="http://www.robert-bock.de/Sport_0/lauf_7/cpp/cpp.html">http://www.robert-bock.de/Sport_0/lauf_7/cpp/cpp.html</a></small>');
        echo HTML::fileBlock("<strong>Herbert Steffny ('Das gro&szlig;e Laufbuch')</strong><br>"
                    . __('Herbert Steffny uses fixed factors to transform performances from one distance to another. '
                        . 'This model uses your best result.'));
        echo HTML::fileBlock('<strong>David Cameron</strong><br>'
                    . __('David Cameron uses a fixed coefficient for the fatigue over time/distance and slightly different formulas than Robert Bock. '
                        . 'This model uses your best result.') . '<br>'
                    . '<small>see <a href="http://www.infobarrel.com/Runners_Math_How_to_Predict_Your_Race_Time">http://www.infobarrel.com/Runners_Math_How_to_Predict_Your_Race_Time</a></small>');
        echo HTML::info(__('The VO2max model is the only one which considers your current shape. '
                            . 'The other models are based on your previous race results.'));
    }

    protected function initConfiguration()
    {
        $Distances = new PluginConfigurationValueDistances('distances', __('Distances to predict'), '', [1, 3, 5, 10, 21.1, 42.2]);

        $Model = new PluginConfigurationValueSelect('model', __('Prediction model'));
        $Model->setOptions([
            'vo2max' => __('Effective VO2max'),
            'cpp' => 'Robert Bock (CPP)',
            'steffny' => 'Herbert Steffny',
            'cameron' => 'David Cameron',
        ]);
        $Model->setDefaultValue('vo2max');

        $BasicEndurance = new PluginConfigurationValueBool('use_be', __('Use marathon shape'), __('Use marathon shape factor to adapt prognosis for long distances (VO2max only).'));
        $BasicEndurance->setDefaultValue(true);

        $Configuration = new PluginConfiguration($this->id());
        $Configuration->addValue($Distances);
        $Configuration->addValue($Model);
        $Configuration->addValue($BasicEndurance);

        $this->setConfiguration($Configuration);
    }

    /**
     * @see PluginPanel::displayContent()
     */
    protected function displayContent(): void
    {
        // deprecated, see Runalyze\Bundle\CoreBundle\Controller\AbstractPluginsAwareController::getResponseForPrognosePanel
    }

    public function getDistances(): array
    {
        return array_filter($this->Configuration()->value('distances'), is_numeric(...));
    }
}
