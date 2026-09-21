<?php

declare(strict_types=1);

/**
 * This file contains class::FormularFieldset
 * @package Runalyze\HTML\Formular
 */

use Runalyze\Configuration;

/**
 * Class for a fieldset as part of a formular
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
class FormularFieldset extends HtmlTag
{
    public static string $LAYOUT_FIELD_W100 = 'w100 block';

    public static string $LAYOUT_FIELD_W100_CHECKBOX = 'w100 checkbox-first';

    public static string $LAYOUT_FIELD_W100_IN_W50 = 'w100 with50erLabel';

    public static string $LAYOUT_FIELD_W100_IN_W33 = 'w100 with33erLabel';

    public static string $LAYOUT_FIELD_W50_IN_W33 = 'w50 with33erLabel';

    public static string $LAYOUT_FIELD_W50 = 'w50';

    public static string $LAYOUT_FIELD_W50_AS_W100 = 'w50 marginr50';

    public static string $LAYOUT_FIELD_W33 = 'w33';

    public static string $LAYOUT_FIELD_W25 = 'w25';

    public static string $LAYOUT_FIELD_INLINE = 'inline';

    public static string $LAYOUT_FIELD_SMALL = 'small';

    public static string $LAYOUT_FIELD_SMALL_INLINE = 'inline small';

    /**
     * Title for this fieldset, if empty, no header is shown
     */
    protected string $title = '';

    /**
     * Array with all fields
     * @var \FormularField[]
     */
    protected array $fields = [];

    /**
     * Boolean flag: collapsed
     */
    protected bool $collapsed = false;

    /**
     * Array with extra messages to display below the fields
     * @var array<mixed, array<string, string>>
     */
    private array $messages = [];

    /**
     * Individual HTML code
     */
    private string $HtmlCode = '';

    private string $HtmlCodeBeforeFields = '';

    /**
     * Boolean flag: only one opened fieldset allowed
     */
    private bool $allowOnlyOneOpenedFieldset = false;

    /**
     * Name of configuration value to save current status (collapsed or not)
     */
    private string $confValueToSaveStatus = '';

    /**
     * Constructor for a new fieldset
     */
    public function __construct(string $title = '')
    {
        if (! empty($title)) {
            $this->setTitle($title);
        }
    }

    /**
     * Set title for this fieldset
     */
    final public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set this fieldset as collapsed
     */
    final public function setCollapsed(): void
    {
        $this->collapsed = true;
    }

    /**
     * Set conf value to save current status
     */
    final public function setConfValueToSaveStatus(string $confValue): void
    {
        $this->confValueToSaveStatus = $confValue;

        if (! Configuration::ActivityForm()->show($confValue)) {
            $this->setCollapsed();
        }
    }

    /**
     * Set a specific layout for all fields
     */
    final public function setLayoutForFields(string $layout): void
    {
        foreach ($this->fields as &$Field) {
            if (is_object($Field)) {
                $Field->setLayoutIfEmpty($layout);
            }
        }
    }

    /**
     * For toggle-function: only one opened fieldset
     */
    final public function allowOnlyOneOpenedFieldset(): void
    {
        $this->allowOnlyOneOpenedFieldset = true;
    }

    /**
     * Add a given field to this fieldset
     */
    final public function addField(?FormularField $Field): void
    {
        if ($Field instanceof \FormularField) {
            $this->fields[] = $Field;
        }
    }

    /**
     * Add given fields to this fieldset
     * @param FormularField[] $Fields
     */
    final public function addFields(array $Fields): void
    {
        foreach ($Fields as $Field) {
            $this->fields[] = $Field;
        }
    }

    final public function validateAllFields(): void
    {
        foreach ($this->fields as &$Field) {
            $Field->validate();
        }
    }

    /**
     * Display this fieldset
     */
    public function display(): void
    {
        if ($this->collapsed) {
            $this->addCSSclass('collapsed');
        }

        if (empty($this->title)) {
            $this->addCSSclass('without-legend');
        }

        echo '<fieldset ' . $this->attributes() . '>';

        $this->displayLegend();
        $this->displayHtmlCodeBeforeFields();
        $this->displayFields();
        $this->displayMessages();
        $this->displayHtmlCode();

        echo '</fieldset>';
    }

    /**
     * Display all fields
     */
    private function displayLegend(): void
    {
        if (! empty($this->title)) {
            echo '<legend onclick="' . $this->getLegendOnclick() . '">' . $this->title . '</legend>';
        }
    }

    /**
     * Get onclick attribute for legend
     */
    private function getLegendOnclick(): string
    {
        $onlyOne = $this->allowOnlyOneOpenedFieldset ? 'true' : 'false';

        return "Runalyze.toggleFieldset(this, '" . $this->Id . "', " . $onlyOne . ", '" . $this->confValueToSaveStatus . "')";
    }

    /**
     * Display all fields
     */
    private function displayFields(): void
    {
        foreach ($this->fields as $Field) {
            if (is_object($Field)) {
                $Field->display();
            } else {
                echo '<div class="w50"></div>';
            }
        }
    }

    /**
     * Set individual HTML code
     */
    public function setHtmlCode(string $Code): void
    {
        $this->HtmlCode = $Code;
    }

    /**
     * Display individual html code
     */
    private function displayHtmlCode(): void
    {
        if (! empty($this->HtmlCode)) {
            echo '<div>' . $this->HtmlCode . '</div>';
        }
    }

    public function setHtmlCodeBeforeFields(string $code): void
    {
        $this->HtmlCodeBeforeFields = $code;
    }

    private function displayHtmlCodeBeforeFields(): void
    {
        if (! empty($this->HtmlCodeBeforeFields)) {
            echo '<div>' . $this->HtmlCodeBeforeFields . '</div>';
        }
    }

    /**
     * Display all messages
     */
    private function displayMessages(): void
    {
        echo '<div class="fieldset-messages">';

        foreach ($this->messages as $message) {
            if ($message['type'] == 'block') {
                echo '<div>' . $message['message'] . '</div>';
            } else {
                echo '<p class="' . $message['type'] . '">' . $message['message'] . '</p>';
            }
        }

        echo '</div>';
    }

    private function addMessage(string $message, string $type): void
    {
        $this->messages[] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * Add okay to fieldset
     */
    final public function addOkay(string $message): void
    {
        $this->addMessage($message, 'okay');
    }

    /**
     * Add warning to fieldset
     */
    final public function addWarning(string $message): void
    {
        $this->addMessage($message, 'warning');
    }

    /**
     * Add error to fieldset
     */
    final public function addError(string $message): void
    {
        $this->addMessage($message, 'error');
    }

    /**
     * Add info to fieldset
     */
    final public function addInfo(string $message): void
    {
        $this->addMessage($message, 'info');
    }

    /**
     * Add small info to fieldset
     */
    final public function addSmallInfo(string $message): void
    {
        $this->addMessage($message, 'info small');
    }

    /**
     * Add block to fieldset
     */
    final public function addBlock(string $message): void
    {
        $this->addMessage($message, 'block');
    }

    /**
     * Add text to fieldset
     */
    final public function addText(string $message): void
    {
        $this->addMessage($message, 'text');
    }

    /**
     * Add block to fieldset
     */
    final public function addFileBlock(string $message): void
    {
        $this->addMessage($message, 'file');
    }
}
