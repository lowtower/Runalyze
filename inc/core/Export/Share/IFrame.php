<?php

/**
 * This file contains class::IFrame
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

use Formular;
use FormularFieldset;
use FormularInputNumber;
use FormularSubmit;
use FormularTextarea;

/**
 * Exporter for: IFrame
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Export\Share
 */
class IFrame extends AbstractSnippetSharer
{
    public const int DEFAULT_WIDTH = 500;

    public const int DEFAULT_HEIGHT = 500;

    public function enum(): int
    {
        return Types::IFRAME;
    }

    public function isPossible(): bool
    {
        return $this->Context->activity()
            ->isPublic();
    }

    public function name(): string
    {
        return __('Widget');
    }

    /**
     * Current width
     */
    protected function width(): int
    {
        return isset($_POST['width']) && is_numeric($_POST['width']) ? (int) $_POST['width'] : self::DEFAULT_WIDTH;
    }

    /**
     * Current height
     */
    protected function height(): int
    {
        return isset($_POST['height']) && is_numeric($_POST['height']) ? (int) $_POST['height'] : self::DEFAULT_HEIGHT;
    }

    public function display(string $activityUrl, bool $isLocalhost): void
    {
        $Code = str_replace(["\r", "\n", "\t"], ['', '', ''], $this->codeSnippet($activityUrl));

        $CodeField = new FormularTextarea('code', __('Code'), $Code);
        $CodeField->addCSSclass('fullwidth');
        $CodeField->addAttribute('rows', '3');

        $FieldsetCode = new FormularFieldset(__('HTML-Code'));

        if ($isLocalhost) {
            $FieldsetCode->addError(__('Runalyze runs on a local server. Only people in your local network will be able to see the training.'));
        }

        $FieldsetCode->addField($CodeField);
        $FieldsetCode->addInfo(__('Add this code to your blog/website.'));

        $WidthField = new FormularInputNumber('width', __('Width'), (string) $this->width());
        $WidthField->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $WidthField->setMin(200);
        $WidthField->setMax(600);

        $HeightField = new FormularInputNumber('height', __('Height'), (string) $this->height());
        $HeightField->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $HeightField->setMin(200);
        $HeightField->setMax(600);

        $SubmitField = new FormularSubmit(__('Change size'), '');

        $FieldsetCode->addField($WidthField);
        $FieldsetCode->addField($HeightField);
        $FieldsetCode->addField($SubmitField);

        $FieldsetPreview = new FormularFieldset(__('Preview'));
        $FieldsetPreview->addBlock($Code);

        $Formular = new Formular($this->url());
        $Formular->addCSSclass('ajax');
        $Formular->addCSSclass('no-automatic-reload');
        $Formular->addFieldset($FieldsetCode);
        $Formular->addFieldset($FieldsetPreview);
        $Formular->setLayoutForFields(FormularFieldset::$LAYOUT_FIELD_W100);
        $Formular->display();
    }

    /**
     * Get HTML code for snippet
     */
    private function codeSnippet(string $activityUrl): string
    {
        return '<iframe style="padding:0;margin:0 auto;display:block;max-width:100%;" src="' . htmlspecialchars($activityUrl) . '?mode=iframe&amp;utm_medium=referral&amp;utm_source=iframe" width="' . $this->width() . '" height="' . $this->height() . '"></iframe>';
    }
}
