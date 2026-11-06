<?php

/**
 * This file contains class::TrainingView
 * @package Runalyze\DataObjects\Training\View
 */

use Runalyze\Configuration;
use Runalyze\Export;
use Runalyze\Model;
use Runalyze\View\Activity\Linker;

/**
 * Display a training
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View
 */
class TrainingView
{
    /**
     * Sections
     * @var TrainingViewSection[]
     */
    protected array $Sections = [];

    /**
     * Toolbar links
     */
    protected array $ToolbarLinks = [];

    public function __construct(
        protected \Runalyze\View\Activity\Context $Context
    ) {
        $this->initToolbarLinks();
        $this->initSections();
    }

    private function initToolbarLinks(): void
    {
        $Linker = new Linker($this->Context->activity());

        if (! Request::isOnSharedPage()) {
            $this->initShareLinks($Linker);
            $this->initExportLinks();
            $this->initToolsLinks();
            $this->initEditLinks($Linker);
        }

        $this->ToolbarLinks[] = Ajax::tooltip($Linker->weekLink(), '<em>' . __('Show week') . '</em><br>' . $this->Context->dataview()->weekday() . ', ' . $this->Context->dataview()->dateAndDaytime());
    }

    /**
     * Init social share links
     */
    protected function initShareLinks(Linker $linker)
    {
        $this->ToolbarLinks[] = '<li class="with-submenu"><span class="link"><i class="fa fa-fw fa-share-alt"></i> ' . __('Share') . '</span><ul class="submenu">';

        if ($this->Context->activity()->isPublic()) {
            $this->ToolbarLinks[] = '<li><a href="' . $linker->publicUrl() . '" target="_blank">' . Icon::$ATTACH . ' ' . __('Public link') . '</a></li>';
        }

        foreach (Export\Share\Types::getEnum() as $typeid) {
            $Exporter = Export\Share\Types::get($typeid, $this->Context);

            if ($Exporter->isPossible()) {
                $link = '<li><a href="' . $Exporter->url() . '"' . ($Exporter->isExternalLink() ? ' target="_blank"' : '') . '><i class="fa fa-fw ' . $Exporter->iconClass() . '"></i> ' . $Exporter->name() . '</a></li>';
                $this->ToolbarLinks[] = ! $Exporter->isExternalLink() ? Ajax::window($link) : $link;
            }
        }

        $this->ToolbarLinks[] = '</ul></li>';
    }

    /**
     * Init download links
     */
    protected function initExportLinks()
    {
        $this->ToolbarLinks[] = '<li class="with-submenu"><span class="link"><i class="fa fa-fw fa-download"></i> ' . __('Export') . '</span><ul class="submenu">';

        foreach (Export\File\Types::getEnum() as $typeid) {
            $Exporter = Export\File\Types::get($typeid, $this->Context);

            if ($Exporter->isPossible()) {
                $this->ToolbarLinks[] = '<li><a href="' . $Exporter->url() . '"><i class="fa fa-fw ' . $Exporter->iconClass() . '"></i> ' . sprintf(__('as %s'), strtoupper($Exporter->extension())) . '</a></li>';
            }
        }

        $this->ToolbarLinks[] = '</ul></li>';
    }

    protected function initToolsLinks()
    {
        $toolsLinks = [];

        if (
            $this->Context->hasTrackdata()
            && $this->Context->trackdata()
                ->has(Model\Trackdata\Entity::DISTANCE)
            && $this->Context->trackdata()
                ->has(Model\Trackdata\Entity::TIME)
        ) {
            $toolsLinks[] = '<li><a class="window link" data-size="big" href="activity/' . $this->Context->activity()->id() . '/splits-info"><i class="fa far fa-fw fa-chart-bar"></i> ' . __('Analyze splits') . '</a></li>';
        }

        if ($this->Context->activity()->vo2maxByHeartRate() > 0) {
            $toolsLinks[] = '<li><a class="window link" data-size="small" href="activity/' . $this->Context->activity()->id() . '/vo2max-info"><i class="fa fa-fw fa-calculator"></i> ' . __('Show VO<sub>2</sub>max calculation') . '</a></li>';
        }

        if ($this->Context->hasRoute() && $this->Context->route()->hasElevations()) {
            $toolsLinks[] = '<li><a class="window link" data-size="normal" href="activity/' . $this->Context->activity()->id() . '/elevation-info"><i class="fa far fa-fw fa-chart-area"></i> ' . __('More about elevation') . '</a></li>';
        }

        if ($this->Context->hasTrackdata() && $this->Context->trackdata()->has(Model\Trackdata\Entity::TIME) && $this->Context->trackdata()->has(Model\Trackdata\Entity::DISTANCE) && $this->Context->hasRoute() && $this->Context->route()->hasElevations()) {
            $toolsLinks[] = '<li><a class="window link" data-size="normal" href="activity/' . $this->Context->activity()->id() . '/climb-score"><i class="fa far fa-fw fa-chart-area"></i> ' . __('Climb score') . '</a></li>';
        }

        if ($this->Context->hasTrackdata() && $this->Context->trackdata()->has(Model\Trackdata\Entity::TIME)) {
            $toolsLinks[] = '<li><a class="window link" data-size="big" href="activity/' . $this->Context->activity()->id() . '/time-series-info"><i class="fa far fa-fw fa-chart-line"></i> ' . __('Analyze time series') . '</a></li>';
        }

        if ($this->Context->hasTrackdata() && $this->Context->trackdata()->has(Model\Trackdata\Entity::TIME) && $this->Context->trackdata()->has(Model\Trackdata\Entity::DISTANCE)) {
            $toolsLinks[] = '<li><a class="window link" data-size="big" href="activity/' . $this->Context->activity()->id() . '/sub-segments-info"><i class="fa far fa-fw fa-chart-bar"></i> ' . __('Find best sub segments') . '</a></li>';
        }

        if ($toolsLinks !== []) {
            $this->ToolbarLinks = array_merge(
                $this->ToolbarLinks,
                ['<li class="with-submenu"><span class="link"><i class="fa fa-fw fa-magic"></i> ' . __('Tools') . '</span><ul class="submenu">'],
                $toolsLinks,
                ['</ul></li>']
            );
        }
    }

    protected function initEditLinks(Linker $linker)
    {
        if ($this->Context->activity()->isPublic()) {
            $privacyLabel = __('Make private');
            $privacyIcon = 'fa-lock';
        } else {
            $privacyLabel = __('Make public');
            $privacyIcon = 'fa-unlock';
        }

        if ($this->Context->hasRaceResult()) {
            $raceResultLabel = __('Edit race result');
        } else {
            $raceResultLabel = __('Add race result');
        }

        $this->ToolbarLinks[] = '<li class="with-submenu"><span class="link"><i class="fa fa-fw fa-wrench"></i></span><ul class="submenu">';
        $this->ToolbarLinks[] = '<li>' . Ajax::window('<a class="link" href="' . $linker->editUrl() . '">' . Icon::$EDIT . ' ' . __('Edit activity') . '</a> ', 'small') . '</li>';
        $this->ToolbarLinks[] = '<li>' . Ajax::window('<a class="link" href="my/raceresult/' . $this->Context->activity()->id() . '"><i class="fa fa-fw fa-trophy"></i> ' . $raceResultLabel . '</a></li></a> ', 'normal') . '</li>';
        $this->ToolbarLinks[] = '<li><a class="ajax" target="statistics-inner" href="activity/' . $this->Context->activity()->id() . '?action=changePrivacy"><i class="fa fa-fw ' . $privacyIcon . '"></i> ' . $privacyLabel . '</a></li>';
        $this->ToolbarLinks[] = '<li><a class="ajax" target="statistics-inner" href="activity/' . $this->Context->activity()->id() . '/delete" data-confirm="' . __('Do you really want to delete this activity?') . '"><i class="fa fa-fw fa-trash"></i> ' . __('Delete activity') . '</a></li>';
        $this->ToolbarLinks[] = '</ul></li>';
    }

    protected function initSections()
    {
        if (Configuration::ActivityView()->mapFirst() && Configuration::ActivityView()->plotMode()->showCollection()) {
            $this->Sections[] = new SectionComposite($this->Context);
            $this->Sections[] = new SectionLaps($this->Context);
        } else {
            $this->Sections[] = new SectionOverview($this->Context);

            if (Configuration::ActivityView()->mapFirst()) {
                $this->Sections[] = new SectionRouteOnlyMap($this->Context);
            } else {
                $this->Sections[] = new SectionLaps($this->Context);
            }

            $this->Sections[] = new SectionSwimLane($this->Context);

            if (Configuration::ActivityView()->plotMode()->showSeperated()) {
                $this->Sections[] = new SectionHeartrate($this->Context);
                $this->Sections[] = new SectionPace($this->Context);

                if (Configuration::ActivityView()->mapFirst()) {
                    $this->Sections[] = new SectionRouteOnlyElevation($this->Context);
                } else {
                    $this->Sections[] = new SectionRoute($this->Context);
                }
            } else {
                $this->Sections[] = new SectionComposite($this->Context);

                if (Configuration::ActivityView()->plotMode()->showPaceAndHR()) {
                    if (Configuration::ActivityView()->mapFirst()) {
                        $this->Sections[] = new SectionRouteOnlyElevation($this->Context);
                    } else {
                        $this->Sections[] = new SectionRoute($this->Context);
                    }
                } else {
                    if (! Configuration::ActivityView()->mapFirst()) {
                        $this->Sections[] = new SectionRouteOnlyMap($this->Context);
                    }
                }
            }

            if (Configuration::ActivityView()->mapFirst()) {
                $this->Sections[] = new SectionLaps($this->Context);
            }
        }

        if (
            (
                $this->Context->trackdata()
                    ->has(\Runalyze\Model\Trackdata\Entity::CADENCE)
                && $this->Context->sport()
                    ->getInternalProfileEnum() === \Runalyze\Profile\Sport\SportProfile::RUNNING
            )
            || $this->Context->trackdata()
                ->has(\Runalyze\Model\Trackdata\Entity::GROUNDCONTACT)
            || $this->Context->trackdata()
                ->has(\Runalyze\Model\Trackdata\Entity::VERTICAL_OSCILLATION)
        ) {
            $this->Sections[] = new SectionRunningDynamics($this->Context);
            $this->Sections[] = new SectionRunScribeData($this->Context);
            $this->Sections[] = new SectionMiscellaneous($this->Context, false);
        } else {
            $this->Sections[] = new SectionRunScribeData($this->Context);
            $this->Sections[] = new SectionMiscellaneous($this->Context, true);
        }

        if ($this->Context->hasHRV()) {
            $this->Sections[] = new SectionHRV($this->Context);
        }
    }

    /**
     * Data for the twig template `activity/training_view.html.twig`
     *
     * The sections are still rendered by the legacy section classes and are
     * captured as html string. They will be ported to twig templates step
     * by step.
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $isShared = Request::isOnSharedPage();

        return [
            'is_shared' => $isShared,
            'title' => $this->Context->dataview()->titleWithComment(),
            'toolbar_links' => $isShared ? $this->sharedToolbarLinks() : $this->ToolbarLinks,
            'navigation_prev' => $isShared ? '' : $this->navigationLink(Linker::prevId($this->Context->activity()->id(), $this->Context->activity()->timestamp()), 'left'),
            'navigation_next' => $isShared ? '' : $this->navigationLink(Linker::nextId($this->Context->activity()->id(), $this->Context->activity()->timestamp()), 'right'),
            'refresh_icon' => Icon::$REFRESH_SMALL,
            'sections_html' => $this->sectionsHtml(),
            'plots_js' => Ajax::wrapJSforDocumentReady('RunalyzePlot.resizeTrainingCharts();'),
        ];
    }

    /**
     * Toolbar links for the shared activity view
     *
     * @return string[]
     */
    private function sharedToolbarLinks(): array
    {
        $User = DB::getInstance()->query('SELECT * FROM `' . PREFIX . 'account` WHERE `id`="' . SharedLinker::getUserId() . '" LIMIT 1')->fetch();
        $username = is_array($User) ? (string) ($User['username'] ?? '') : '';

        return [
            SharedLinker::getStandardLinkTo($this->Context->activity()->id(), Icon::$ATTACH),
            '<a href="athlete/' . $username . '" target="_blank">' . Icon::$TABLE . '</a>',
        ];
    }

    /**
     * Link to the previous/next activity or a transparent icon if there is none
     *
     * @param bool|int $id id of the neighbouring activity or false
     */
    private function navigationLink(bool|int $id, string $direction): string
    {
        if ($id !== false) {
            return Ajax::trainingLink($id, '<i class="fa fa-fw fa-chevron-' . $direction . '"></i>');
        }

        return '<i class="transparent-70 fa-grey fa fa-fw fa-chevron-' . $direction . '"></i>';
    }

    /**
     * Rendered sections
     *
     * The section classes still echo their content directly, therefore their
     * output is captured here. This is transitional and will be removed when
     * the sections are ported to twig templates.
     */
    private function sectionsHtml(): string
    {
        ob_start();

        try {
            foreach ($this->Sections as $Section) {
                $Section->display();
            }
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return (string) ob_get_clean();
    }
}
