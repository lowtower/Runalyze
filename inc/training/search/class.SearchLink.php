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
     * @var string
     */
    public static $WINDOW_URL = 'my/search';

    /**
     * Parameter
     * @var array
     */
    protected $Params = [];

    public function __construct()
    {
        $this->Params = [];
        $this->Signs = [];
    }

    /**
     * Link to search-window
     * @param string $name [optional]
     * @return string
     */
    final public function link($name)
    {
        return Ajax::window('<a href="' . $this->url() . '">' . $name . '</a>', 'big');
    }

    /**
     * @return string url to search window
     */
    final public function url()
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
     * @param int $fromTimestamp
     * @param int $toTimestamp
     */
    public function fromTo($fromTimestamp, $toTimestamp): void
    {
        $this->Params['date-from'] = date('d.m.Y', $fromTimestamp);
        $this->Params['date-to'] = date('d.m.Y', $toTimestamp);
    }

    /**
     * @param string $key key in database
     * @param mixed $value may be an array for select types
     * @param string $sign [optional] possible signs: is (default), gt, ge, le, lt, ne, like
     */
    public function addParam($key, $value, $sign = ''): void
    {
        $this->Params[$key] = $value;

        if ((string) $sign !== '') {
            $this->Signs[$key] = $sign;
        }
    }

    /*+
     * @param string $sort key in database
     * @param boolean $asc [optional] sort ascending, default false
     */
    public function sortBy($sort, $asc = false): void
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
     * @var array
     */
    protected $Signs = [];

    /**
     * URL to search
     * @param string $key key in database
     * @param mixed $value can be an array
     * @param string $text
     * @param string $sign optional equality sign
     * @return string
     */
    public static function to($key, $value, $text, $sign = '')
    {
        $Link = new self();
        $Link->addParam($key, $value, $sign);

        return $Link->link($text);
    }

    /**
     * URL to search
     * @param string $key key in database
     * @param mixed $value can be an array
     * @return string
     */
    public static function urlFor($key, $value)
    {
        $Link = new self();
        $Link->addParam($key, $value);

        return $Link->url();
    }
}
