<?php

/**
 * This file contains the class of the RunalyzePluginStat "Wetter".
 * @package Runalyze\Plugins\Stats
 */
$PLUGINKEY = 'RunalyzePluginStat_Wetter';

use Runalyze\Activity\Temperature;
use Runalyze\Data\Weather\Humidity;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Util\LocalTime;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats
 */
class RunalyzePluginStat_Wetter extends PluginStat
{
    protected int $EquipmentTypeId;

    /**
     * @var array array(id => name)
     */
    protected array $AllTypes = [];

    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Weather');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('Statistics about weather conditions and temperatures.');
    }

    /**
     * @codeCoverageIgnore
     */
    final protected function displayLongDescription(): void
    {
        echo HTML::p(__('There is no bad weather, there is only bad clothing.'));
        echo HTML::p(__('Are you a wimp or a tough runner? '
                        . 'Have a look at these statistics about the weather conditions.'));
    }

    protected function initConfiguration()
    {
        $this->AllTypes = [
            0 => __('none'),
        ];
        $AllTypes = DB::getInstance()->query('SELECT `id`, `name` FROM `' . PREFIX . 'equipment_type` WHERE `accountid`="' . SessionAccountHandler::getId() . '" ORDER BY `name` ASC')->fetchAll();

        foreach ($AllTypes as $data) {
            $this->AllTypes[$data['id']] = $data['name'];
        }

        $Types = new PluginConfigurationValueSelect('equipment_type', __('Equipment type to display'));
        $Types->setOptions($this->AllTypes);

        $Configuration = new PluginConfiguration($this->id());
        $Configuration->addValue($Types);

        if (isset($_GET['dat']) && isset($this->AllTypes[$_GET['dat']])) {
            $Configuration->object('equipment_type')
                ->setValue($_GET['dat']);
            $Configuration->update('equipment_type');
            Cache::delete(PluginConfiguration::CACHE_KEY);
        }

        $this->setConfiguration($Configuration);
        $this->EquipmentTypeId = (int) $this->Configuration()
            ->value('equipment_type');
    }

    /**
     * @return string[]
     */
    protected function getToolbarNavigationLinks(): array
    {
        $LinkList = [];
        $LinkList[] = '<li>' . Ajax::window('<a href="plugin/' . $this->key() . '/window.php">' . Ajax::tooltip(Icon::$LINE_CHART, __('Show temperature plots')) . '</a>') . '</li>';

        return array_merge($LinkList, $this->getEquipmentTypeSubmenuLinks());
    }

    /**
     * @return string[]
     */
    private function getEquipmentTypeSubmenuLinks(): array
    {
        if (count($this->AllTypes) <= 1) {
            return [];
        }

        $LinkList = [];
        $LinkList[] = '<li class="with-submenu"><span class="link">' . $this->AllTypes[$this->EquipmentTypeId] . '</span><ul class="submenu">';

        foreach ($this->AllTypes as $id => $name) {
            $active = ($id == $this->EquipmentTypeId);
            $LinkList[] = '<li' . ($active ? ' class="active"' : '') . '>' . $this->getInnerLink("{$name}", 0, 0, $id) . '</li>';
        }

        $LinkList[] = '</ul></li>';

        return $LinkList;
    }

    /**
     * @param bool $addTableName must be used if query contains joins
     */
    protected function getTimerForOrderingInQuery(bool $addTableName = false): string
    {
        $time = $addTableName ? '`' . PREFIX . 'training`.`time`' : '`time`';

        if ($this->showsAllYears()) {
            // Ensure month-wise data
            return 'MONTH(FROM_UNIXTIME(' . $time . '))';
        }

        return parent::getTimerForOrderingInQuery($addTableName);
    }

    protected function prepareForDisplay()
    {
        $this->setSportsNavigation(true, true);
        $this->setYearsNavigation(true, true, true);
        $this->setToolbarNavigationLinks($this->getToolbarNavigationLinks());

        $this->setHeaderWithSportAndYear();
    }

    /**
     * @see PluginStat::displayContent()
     */
    protected function displayContent(): void
    {
        $this->displayExtremeTrainings();
        $this->displayMonthwiseTable();

        if ($this->knowsEquipmentType()) {
            $this->displayEquipmentTable();
        }
    }

    protected function displayExtremeTrainings()
    {
        $hot = DB::getInstance()->query('SELECT `temperature`, `id`, `time` FROM `' . PREFIX . 'training` WHERE `temperature` IS NOT NULL ' . $this->getSportAndYearDependenceForQuery() . ' AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `temperature` DESC LIMIT 5')->fetchAll();
        $cold = DB::getInstance()->query('SELECT `temperature`, `id`, `time` FROM `' . PREFIX . 'training` WHERE `temperature` IS NOT NULL ' . $this->getSportAndYearDependenceForQuery() . ' AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `temperature` ASC LIMIT 5')->fetchAll();
        $windiest = DB::getInstance()->query('SELECT `wind_speed`, `id`, `time` FROM `' . PREFIX . 'training` WHERE `wind_speed` IS NOT NULL ' . $this->getSportAndYearDependenceForQuery() . ' AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `wind_speed` DESC LIMIT 5')->fetchAll();
        $maxhumidity = DB::getInstance()->query('SELECT `humidity`, `id`, `time` FROM `' . PREFIX . 'training` WHERE `humidity` IS NOT NULL ' . $this->getSportAndYearDependenceForQuery() . ' AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `humidity` DESC LIMIT 5')->fetchAll();

        foreach ($hot as $i => $h) {
            $hot[$i] = Temperature::format($h['temperature'], true) . ' ' . __('on') . ' ' . Ajax::trainingLink($h['id'], new LocalTime($h['time'])->format('d.m.Y'));
        }

        foreach ($cold as $i => $c) {
            $cold[$i] = Temperature::format($c['temperature'], true) . ' ' . __('on') . ' ' . Ajax::trainingLink($c['id'], new LocalTime($c['time'])->format('d.m.Y'));
        }

        foreach ($windiest as $i => $w) {
            $windiest[$i] = new WindSpeed($w['wind_speed'])->string() . ' ' . __('on') . ' ' . Ajax::trainingLink($w['id'], new LocalTime($w['time'])->format('d.m.Y'));
        }

        foreach ($maxhumidity as $i => $h) {
            $maxhumidity[$i] = new Humidity($h['humidity'])->string() . ' ' . __('on') . ' ' . Ajax::trainingLink($h['id'], new LocalTime($h['time'])->format('d.m.Y'));
        }

        echo $this->buildExtremeTrainingsHtml($hot, $cold, $windiest, $maxhumidity);
    }

    /**
     * @param string[] $hot
     * @param string[] $cold
     * @param string[] $windiest
     * @param string[] $maxhumidity
     */
    private function buildExtremeTrainingsHtml(array $hot, array $cold, array $windiest, array $maxhumidity): string
    {
        $html = '<p>';
        $html .= '<strong>' . __('Hottest activities') . ':</strong> ';
        $html .= ($hot === [] ? __('none') : implode(', ', $hot)) . '<br>';
        $html .= '<strong>' . __('Coldest activities') . ':</strong> ';
        $html .= ($cold === [] ? __('none') : implode(', ', $cold)) . '<br>';
        $html .= '<strong>' . __('Most windy activities') . ':</strong> ';
        $html .= ($windiest === [] ? __('none') : implode(', ', $windiest)) . '<br>';
        $html .= '<strong>' . __('Highest humidity activities') . ':</strong> ';
        $html .= ($maxhumidity === [] ? __('none') : implode(', ', $maxhumidity)) . '<br>';
        $html .= '</p>';

        return $html;
    }

    protected function knowsEquipmentType(): bool
    {
        return ($this->EquipmentTypeId > 0) && isset($this->AllTypes[$this->EquipmentTypeId]);
    }

    protected function displayMonthwiseTable()
    {
        require_once __DIR__ . '/MonthwiseTable.php';

        $num = $this->showsLast6Months() ? 6 : 12;
        $offset = $this->showsTimeRange() ? date('m') - $num - 1 + 12 : -1;

        $Table = new \Runalyze\Plugin\Stat\Wetter\MonthwiseTable(
            DB::getInstance(),
            SessionAccountHandler::getId(),
            $this->EquipmentTypeId
        );
        $Table->setDependency($this->getSportAndYearDependenceForQuery(true));
        $Table->setGroupBy($this->getTimerIndexForQuery(true));
        $Table->setOrderBy($this->getTimerForOrderingInQuery(true));
        $Table->setMonthOffset($offset);
        $Table->display();
    }

    protected function displayEquipmentTable()
    {
        require_once __DIR__ . '/MinMaxTableForEquipment.php';

        $Table = new \Runalyze\Plugin\Stat\Wetter\MinMaxTableForEquipment(
            DB::getInstance(),
            SessionAccountHandler::getId(),
            $this->EquipmentTypeId
        );
        $Table->setDependency($this->getSportAndYearDependenceForQuery(true));
        $Table->display();
    }
}
