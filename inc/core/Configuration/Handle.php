<?php

/**
 * This file contains class::Handle
 * @package Runalyze\Configuration
 */

namespace Runalyze\Configuration;

use Ajax;
use Runalyze\Parameter;
use Runalyze\Parameter\SelectRow;

/**
 * Configuration handle
 * @author Hannes Christiansen
 * @package Runalyze\Configuration
 */
class Handle
{
    /**
     * Key
     * @var string
     */
    protected $Key;

    /**
     * Parameter
     * @var Parameter
     */
    protected $Parameter;

    /**
     * Onchange callback
     * @var callable|string
     */
    protected $OnchangeCallback = '';

    /**
     * Onchange reload flag
     * @var mixed enum
     */
    protected $OnchangeReloadFlag = '';

    /**
     * Array with all values
     * @var array
     */
    private static $TableHandles = [];

    /**
     * All table handles
     * @return array array('key' => 'table')
     */
    public static function tableHandles()
    {
        return self::$TableHandles;
    }

    /**
     * Construct
     * @param string $Key
     */
    public function __construct($Key, Parameter $Parameter)
    {
        $this->Key = $Key;
        $this->Parameter = $Parameter;

        if ($Parameter instanceof SelectRow) {
            self::$TableHandles[$Key] = $Parameter->table();
        }
    }

    /**
     * @return string
     */
    final public function key()
    {
        return $this->Key;
    }

    /**
     * Parameter object
     * @return \Runalyze\Parameter
     */
    final public function object()
    {
        return $this->Parameter;
    }

    /**
     * @return mixed
     */
    final public function value()
    {
        return $this->Parameter->value();
    }

    /**
     * @param callable $callback
     */
    final public function registerOnchangeEvent($callback)
    {
        $this->OnchangeCallback = $callback;
    }

    /**
     * @param string $flag
     */
    final public function registerOnchangeFlag($flag)
    {
        $this->OnchangeReloadFlag = $flag;
    }

    /**
     * Process onchange events
     */
    final public function processOnchangeEvents()
    {
        if (! empty($this->OnchangeCallback)) {
            call_user_func($this->OnchangeCallback);
        }

        if (! empty($this->OnchangeReloadFlag)) {
            Ajax::setReloadFlag($this->OnchangeReloadFlag);
        }
    }
}
