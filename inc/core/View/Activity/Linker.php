<?php

/**
 * This file contains class::Linker
 * @package Runalyze\View\Activity
 */

namespace Runalyze\View\Activity;

use Ajax;
use DataBrowserLinker;
use DB;
use Icon;
use Runalyze\Activity\Duration;
use Runalyze\Model\Activity;
use Runalyze\Util\LocalTime;
use SessionAccountHandler;
use SharedLinker;
use System;

/**
 * Linker for activities
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity
 */
class Linker
{
    /**
     * Construct linker
     */
    public function __construct(
        protected Activity\Entity $Activity
    ) {
    }

    /**
     * Get public url
     */
    public function publicUrl(): string
    {
        if ($this->Activity->isPublic()) {
            return System::getFullDomainWithProtocol() . SharedLinker::getUrlFor($this->Activity->id());
        }

        return '';
    }

    /**
     * Get edit url
     */
    public function editUrl(): string
    {
        return 'activity/' . $this->Activity->id() . '/edit';
    }

    /**
     * Get link
     * @param string $name displayed link name
     * @return string HTML-link to this training
     */
    public function link(string $name): string
    {
        return Ajax::trainingLink($this->Activity->id(), $name);
    }

    /**
     * Get link with icon as text
     * @param string $tooltipCssClass optional, e.g. 'atRight'
     * @return string HTML-link to this training
     */
    public function linkWithSportIcon(string $tooltipCssClass = ''): string
    {
        return $this->link($this->codeWithSportIcon($tooltipCssClass));
    }

    /**
     * @param string $tooltipCssClass optional, e.g. 'atRight'
     * @return string HTML-code that can be linked to this training
     */
    public function codeWithSportIcon(string $tooltipCssClass = ''): string
    {
        $Time = new Duration($this->Activity->duration());
        $Factory = new \Runalyze\Model\Factory(\SessionAccountHandler::getId());
        $Sport = $Factory->sport($this->Activity->sportid());
        $code = $Sport->icon()
            ->code();

        $Tooltip = new \Runalyze\View\Tooltip($Sport->name() . (! $Time->isZero() ? ': ' . $Time->string() : ''));
        $Tooltip->setPosition($tooltipCssClass);
        $Tooltip->wrapAround($code);

        return $code;
    }

    public function weekLink(string $name = ''): string
    {
        if ($name === '') {
            $name = (new LocalTime($this->Activity->timestamp()))->format('d.m.Y');
        }

        return DataBrowserLinker::weekLink($name, $this->Activity->timestamp());
    }

    public function urlToElevationCorrection(): string
    {
        return 'activity/' . $this->Activity->id() . '/elevation-correction';
    }

    public function urlToElevationInfo(string $data = ''): string
    {
        return 'activity/' . $this->Activity->id() . '/elevation-info' . ($data !== '' ? '?' . $data : '');
    }

    public function urlToClimbScore(): string
    {
        return 'activity/' . $this->Activity->id() . '/climb-score';
    }

    public function urlToVO2maxinfo(string $data = ''): string
    {
        return 'activity/' . $this->Activity->id() . '/vo2max-info' . ($data !== '' ? '?' . $data : '');
    }

    public function urlToRoundsInfo(string $data = ''): string
    {
        return 'activity/' . $this->Activity->id() . '/splits-info' . ($data !== '' ? '?' . $data : '');
    }

    /**
     * Link to editor
     * @param int $id id of training
     * @param string $text by default: Icon::$EDIT
     * @return string link to editor window
     */
    public static function editLink(int $id, string $text = '', string $linkId = '', string $linkClass = ''): string
    {
        if ($text === '') {
            $text = Icon::$EDIT;
        }

        if ($linkId !== '') {
            $linkId = ' id="' . $linkId . '"';
        }

        if ($linkClass !== '') {
            $linkId .= ' class="' . $linkClass . '"';
        }

        return Ajax::window('<a' . $linkId . ' href="activity/' . $id . '/edit">' . $text . '</a>', 'small');
    }

    public function smallEditLink(): string
    {
        return self::editLink($this->Activity->id());
    }

    /**
     * @param int $id activity id
     */
    public static function prevId(int $id, int $timestampInNoTimezone): bool|int
    {
        $PrevTraining = DB::getInstance()->query('SELECT `id` FROM `' . PREFIX . 'training` WHERE ((`time`<"' . $timestampInNoTimezone . '" AND `id`!=' . $id . ') OR (`time`="' . $timestampInNoTimezone . '" AND `id`<' . $id . ')) AND `accountid` = ' . SessionAccountHandler::getId() . ' ORDER BY `time` DESC, `id` DESC LIMIT 1')->fetch();

        return $PrevTraining['id'] ?? false;
    }

    /**
     * @param int $id activity id
     */
    public static function nextId(int $id, int $timestampInNoTimezone): bool|int
    {
        $NextTraining = DB::getInstance()->query('SELECT `id` FROM `' . PREFIX . 'training` WHERE ((`time`>"' . $timestampInNoTimezone . '" AND `id`!=' . $id . ') OR (`time`="' . $timestampInNoTimezone . '" AND `id`>' . $id . ')) AND `accountid` = ' . SessionAccountHandler::getId() . ' ORDER BY `time` ASC, `id` ASC LIMIT 1')->fetch();

        return $NextTraining['id'] ?? false;
    }
}
