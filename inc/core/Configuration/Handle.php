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
     */
    private static array $TableHandles = [];

    /**
     * All table handles
     * @return array array('key' => 'table')
     */
    public static function tableHandles(): array
    {
        return self::$TableHandles;
    }

    /**
     * Construct
     */
    public function __construct(
        protected string $Key,
        /**
         * Parameter
         */
        protected Parameter $Parameter
    ) {
        if ($this->Parameter instanceof SelectRow) {
            self::$TableHandles[$this->Key] = $this->Parameter->table();
        }
    }

    final public function key(): string
    {
        return $this->Key;
    }

    /**
     * Parameter object
     */
    final public function object(): \Runalyze\Parameter
    {
        return $this->Parameter;
    }

    final public function value(): mixed
    {
        return $this->Parameter->value();
    }

    /**
     * @param callable $callback
     */
    final public function registerOnchangeEvent($callback): void
    {
        $this->OnchangeCallback = $callback;
    }

    final public function registerOnchangeFlag(string $flag): void
    {
        $this->OnchangeReloadFlag = $flag;
    }

    /**
     * Process onchange events
     */
    final public function processOnchangeEvents(): void
    {
        if (! empty($this->OnchangeCallback)) {
            call_user_func($this->OnchangeCallback);
        }

        if (! empty($this->OnchangeReloadFlag)) {
            Ajax::setReloadFlag($this->OnchangeReloadFlag);
        }
    }
}
