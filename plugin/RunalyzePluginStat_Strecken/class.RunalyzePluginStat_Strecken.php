<?php

/**
 * This file contains the class of the RunalyzePluginStat "Strecken".
 * @package Runalyze\Plugins\Stats
 */
$PLUGINKEY = 'RunalyzePluginStat_Strecken';

use Runalyze\Activity\Distance;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats
 */
class RunalyzePluginStat_Strecken extends PluginStat
{
    public const string CITY_SEPERATOR = ' - ';

    /**
     * Maximum number of routes on routenet.
     * #TSC set the max value from 50 to 250; must set to your preference regarding concurrent user, memory...
     */
    public const int MAX_ROUTES_ON_NET = 250;

    protected array $Cities = [];

    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Routes');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('Some statistics for your most frequent routes.');
    }

    /**
     * @codeCoverageIgnore
     */
    final protected function displayLongDescription(): void
    {
        echo HTML::p(
            __("The input field 'route' expects different places separted by a '-', e.g. 'City A - City B'" .
                'This way the plugin will be able to count how often you visit each city or place.')
        );
    }

    protected function initConfiguration()
    {
        $Configuration = new PluginConfiguration($this->id());
        $Configuration->addValue(new PluginConfigurationValueBool('analyze_cities', __('Show visited cities'), '', true));

        $this->setConfiguration($Configuration);
    }

    protected function prepareForDisplay()
    {
        $text = __('Open route network');
        $Link = Ajax::window('<a class="" href="plugin/' . $this->key() . '/window.routenet.php?sport=' . $this->sportid . '&y=' . $this->year . '"><i class="fa fa-map-marker"></i> ' . $text . '</a>', 'big');

        $this->setToolbarNavigationLinks(['<li>' . $Link . '</li>']);
        $this->setYearsNavigation(true, true, true);
        $this->setSportsNavigation(true, true);

        $this->setHeaderWithSportAndYear();

        if ($this->Configuration()->value('analyze_cities')) {
            $this->initCities();
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function defaultSport(): int
    {
        return -1;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function titleForAllYears(): string
    {
        return __('All years');
    }

    /**
     * @see PluginStat::displayContent()
     */
    protected function displayContent(): void
    {
        $this->displayRoutes();

        if ($this->Configuration()->value('analyze_cities')) {
            $this->displayCities();

            echo HTML::clearBreak();
            echo HTML::clearBreak();

            $this->displayLonelyCities();
        }
    }

    private function displayRoutes(): void
    {
        if ($this->Configuration()->value('analyze_cities')) {
            echo '<table style="width:70%;" class="left zebra-style">';
        } else {
            echo '<table style="width:100%;" class="zebra-style">';
        }

        echo '<thead><tr><th colspan="3">' . __('Most frequent routes') . '</th></tr></thead>';
        echo '<tbody class="r">';

        $i = 0;
        $statement = DB::getInstance()->query(
            '
            SELECT
                `' . PREFIX . 'route`.`name`,
                SUM(`' . PREFIX . 'training`.`distance`) as `km`,
                SUM(1) as `num`
            FROM `' . PREFIX . 'training`
            LEFT JOIN `' . PREFIX . 'route` ON `' . PREFIX . 'training`.`routeid`=`' . PREFIX . 'route`.`id`
            WHERE 1 ' . $this->getSportAndYearDependenceForQuery() . ' AND `' . PREFIX . 'training`.`accountid`=' . SessionAccountHandler::getId() . ' AND `routeid`!=0 AND `name`!=""
            GROUP BY `name`
            ORDER BY `num` DESC
            LIMIT 10'
        );

        while ($data = $statement->fetch()) {
            echo '<tr>
                    <td>' . $data['num'] . 'x</td>
                    <td class="l">' . SearchLink::to('route', $data['name'], Helper::Cut($data['name'], 100)) . '</td>
                    <td>' . Distance::format($data['km']) . '</td>
                </tr>';

            $i++;
        }

        echo '</tbody>';
        echo '</table>';
    }

    private function displayCities(): void
    {
        echo '<table style="width:25%;" class="right zebra-style">';
        echo '<thead><tr><th colspan="2">' . __('Most frequent places') . '</th></tr></thead>';
        echo '<tbody>';

        $i = 1;

        if ($this->Cities === []) {
            echo HTML::emptyTD(2, HTML::em(__('There are no routes.')));
        }

        foreach ($this->Cities as $city => $num) {
            $i++;
            echo '<tr class="a' . ($i % 2 + 1) . '">
                    <td>' . $num . 'x</td>
                    <td>' . SearchLink::to('route', $city, $city, 'like') . '</td>
                </tr>';

            if ($i === 11) {
                break;
            }
        }

        echo '</tbody>';
        echo '</table>';
    }

    private function displayLonelyCities(): void
    {
        echo '<table class="margin-5 fullwidth zebra-style">';
        echo '<thead><tr><th colspan="2">' . __('Rare places') . '</th></tr></thead>';
        echo '<tbody>';

        echo $this->renderLonelyCitiesRows($this->Cities);

        echo '
            <tr class="no-zebra">
                <td colspan="2" class="c">
                    ' . sprintf(__('You have visited %s different places.'), count($this->Cities)) . '
                </td>
            </tr>
        </tbody>
        </table>

        <p class="c"><em>' . __("Everything seperated by a ' - ' is considered as an individual place.") . '</em></p>';
    }

    /**
     * Renders the grouped "Nx: City1, City2, ..." table rows for cities
     * visited 4 times or fewer, grouping consecutive cities with the same
     * visit count onto a single row.
     * @param array<string, int> $cities [city name => visit count], expected
     *                                    to already be sorted ascending by
     *                                    visit count (i.e. array_reverse()'d
     *                                    from the descending $this->Cities)
     */
    private function renderLonelyCitiesRows(array $cities): string
    {
        $html = '';
        $currentGroupCount = null;
        $sortedAscending = array_reverse($cities);

        foreach ($sortedAscending as $city => $num) {
            if ($num > 4) {
                break;
            }

            if ($currentGroupCount !== $num) {
                if ($currentGroupCount !== null) {
                    $html .= '</td></tr>';
                }

                $currentGroupCount = $num;
                $html .= '<tr><td class="b">' . $num . 'x</td><td>';
            } else {
                $html .= ', ';
            }

            $html .= SearchLink::to('route', $city, $city, 'like');
        }

        if ($currentGroupCount !== null) {
            $html .= '</td></tr>';
        }

        return $html;
    }

    private function initCities(): void
    {
        $this->Cities = [];
        $statement = DB::getInstance()->query($this->fetchCitiesQuery());

        while ($string = $statement->fetch(PDO::FETCH_COLUMN)) {
            $cities = explode(' - ', $string);
            foreach ($cities as $city) {
                $city = trim($city);

                if (! isset($this->Cities[$city])) {
                    $this->Cities[$city] = 1;
                } else {
                    $this->Cities[$city]++;
                }
            }
        }

        array_multisort($this->Cities, SORT_DESC);
    }

    private function fetchCitiesQuery(): string
    {
        if ($this->sportid <= 0 && $this->year <= 0) {
            return 'SELECT `cities` FROM `' . PREFIX . 'route` WHERE `cities`!=""';
        }

        $Query = 'SELECT `' . PREFIX . 'route`.`cities` FROM `' . PREFIX . 'training`';
        $Query .= ' RIGHT JOIN `' . PREFIX . 'route` ON `' . PREFIX . 'training`.`routeid` = `' . PREFIX . 'route`.`id`';
        $Query .= ' WHERE ';
        $Query .= '`' . PREFIX . 'training`.`accountid`=' . SessionAccountHandler::getId();
        $Query .= $this->getSportAndYearDependenceForQuery();

        return $Query . ' AND `routeid`!=0 AND `cities`!=""';
    }

    public static function panelMenuForRoutenet(int $positioninterval, int $sportid, int $year): string
    {
        $Code = '<div class="panel-menu"><ul>';
        $Code .= '<li class="with-submenu"><span class="link">Position interval</span><ul class="submenu">' . self::submenuForPositionInterval($positioninterval, $sportid, $year) . '</ul></li>';
        $Code .= '<li class="with-submenu"><span class="link">' . __('Choose sport') . '</span><ul class="submenu">' . self::submenuForSport($positioninterval, $sportid, $year) . '</ul></li>';
        $Code .= '<li class="with-submenu"><span class="link">' . __('Choose year') . '</span><ul class="submenu">' . self::submenuForYear($positioninterval, $sportid, $year) . '</ul></li>';

        return $Code . '</ul></div>';
    }

    private static function submenuForPositionInterval(int $positioninterval, int $sportid, int $year): string
    {
        $i = 1;
        $Code = '<li' . ($positioninterval === $i ? ' class="active"' : '') . '>' . self::linkToRoutenet(__('All'), $i, $sportid, $year) . '</li>';
        for ($i++; $i <= 5; $i++) {
            $Code .= '<li' . ($positioninterval === $i ? ' class="active"' : '') . '>' . self::linkToRoutenet("{$i}", $i, $sportid, $year) . '</li>';
        }

        return $Code;
    }

    private static function submenuForSport(int $positioninterval, int $sportid, int $year): string
    {
        $Code = '<li' . ($sportid === -1 ? ' class="active"' : '') . '>' . self::linkToRoutenet(__('All'), $positioninterval, -1, $year) . '</li>';

        $Sports = SportFactory::NamesAsArray();
        foreach ($Sports as $id => $name) {
            $Code .= '<li' . ($id == $sportid ? ' class="active"' : '') . '>' . self::linkToRoutenet($name, $positioninterval, $id, $year) . '</li>';
        }

        return $Code;
    }

    private static function submenuForYear(int $positioninterval, int $sportid, int $year): string
    {
        $Code = '<li' . ($year === -1 ? ' class="active"' : '') . '>' . self::linkToRoutenet(__('All years'), $positioninterval, $sportid, -1) . '</li>';

        for ($y = date('Y'); $y >= START_YEAR; $y--) {
            $Code .= '<li' . ($y == $year ? ' class="active"' : '') . '>' . self::linkToRoutenet($y, $positioninterval, $sportid, $y) . '</li>';
        }

        return $Code;
    }

    private static function linkToRoutenet(string $text, int $positioninterval, int $sportid, int $year): string
    {
        return Ajax::window('<a class="" href="plugin/RunalyzePluginStat_Strecken/window.routenet.php?posint=' . $positioninterval . '&sport=' . $sportid . '&y=' . $year . '">' . $text . '</a>', 'big');
    }
}
