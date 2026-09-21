<?php

/**
 * This file contains class::SearchLink
 * @package Runalyze\Search
 */
/**
 * Search link
 *
 * @author Hannes Christiansen
 * @package Runalyze\Search
 */
class SearchLink
{
    /**
     * Window url
     */
    public static string $WINDOW_URL = 'my/search';

    /**
     * Parameter
     */
    protected array $Params;

    public function __construct()
    {
        $this->Params = [];
        $this->Signs = [];
    }

    /**
     * Link to search-window
     */
    final public function link(string $name): string
    {
        return Ajax::window('<a href="' . $this->url() . '">' . $name . '</a>', 'big');
    }

    /**
     * @return string url to search window
     */
    final public function url(): string
    {
        $var = '';
        foreach ($this->Params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $var .= '&' . $key . '=' . $val;
                }
            } else {
                $var .= '&' . $key . '=' . $value;

                if (isset($this->Signs[$key])) {
                    $var .= '&opt[' . $key . ']=' . $this->Signs[$key];
                }
            }
        }

        return self::$WINDOW_URL . '?get=true' . str_replace(' ', '+', $var);
    }

    /**
     * Set dates
     */
    public function fromTo(int $fromTimestamp, int $toTimestamp): void
    {
        $this->Params['date-from'] = date('d.m.Y', $fromTimestamp);
        $this->Params['date-to'] = date('d.m.Y', $toTimestamp);
    }

    /**
     * @param string $key key in database
     * @param mixed $value may be an array for select types
     * @param string $sign possible signs: is (default), gt, ge, le, lt, ne, like
     */
    public function addParam(string $key, $value, string $sign = ''): void
    {
        $this->Params[$key] = $value;

        if ($sign !== '') {
            $this->Signs[$key] = $sign;
        }
    }

    /*+
     * @param string $sort key in database
     * @param bool $asc ,sort ascending, default false
     */
    public function sortBy(string $sort, bool $asc = false): void
    {
        $this->addParam('search-sort-by', $sort);
        $this->addParam('search-sort-order', $asc ? 'ASC' : 'DESC');
    }

    /**
     * Send results to multi editor
     */
    public function sendToMultiEditor(): void
    {
        $this->addParam('send-to-multi-editor', true);
    }

    /**
     * Parameter signs
     */
    protected array $Signs;

    /**
     * URL to search
     * @param string $key key in database
     * @param mixed $value can be an array
     * @param string $sign optional equality sign
     */
    public static function to(string $key, $value, string $text, string $sign = ''): string
    {
        $Link = new self();
        $Link->addParam($key, $value, $sign);

        return $Link->link($text);
    }

    /**
     * URL to search
     * @param string $key key in database
     * @param mixed $value can be an array
     */
    public static function urlFor(string $key, $value): string
    {
        $Link = new self();
        $Link->addParam($key, $value);

        return $Link->url();
    }
}
