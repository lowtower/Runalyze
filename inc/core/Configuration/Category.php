<?php

/**
 * This file contains class::Category
 * @package Runalyze\Configuration
 */

namespace Runalyze\Configuration;

use DB;
use FrontendShared;
use Runalyze\Parameter;
use SharedLinker;

/**
 * Configuration category
 * @author Hannes Christiansen
 * @package Runalyze\Configuration
 */
abstract class Category
{
    /**
     * Handles
     * @var \Runalyze\Configuration\Handle[]
     */
    private ?array $Handles = null;

    /**
     * User id
     * @todo use instead a user object
     */
    private ?int $UserID = null;

    /**
     * To load values from database, make sure to call
     * <code>$Category->setUserID($id);</code>
     *
     * Otherwise this object will only contain default values
     *
     * @todo require database as parameter
     */
    public function __construct()
    {
        $this->createHandles();
        $this->registerOnchangeEvents();
    }

    /**
     * @param array $data values from database
     */
    final public function setUserID(?int $id, ?array $data = null)
    {
        if ($id !== $this->UserID && $id >= 0) {
            $this->UserID = $id;
            $this->loadValues($data);
        }
    }

    public function setValues(array $data): void
    {
        foreach ($data as $key => $value) {
            if (isset($this->Handles[$key])) {
                $this->Handles[$key]->object()->setFromString($value);
            }
        }
    }

    /**
     * Has user ID?
     */
    private function hasUserID(): bool
    {
        return is_numeric($this->UserID);
    }

    private function userID(): ?int
    {
        return $this->UserID;
    }

    /**
     * Keys
     */
    final public function keys(): array
    {
        return array_keys($this->Handles);
    }

    /**
     * Internal key
     */
    abstract protected function key(): string;

    /**
     * Create values
     */
    abstract protected function createHandles();

    protected function registerOnchangeEvents()
    {
    }

    public function Fieldset(): ?\Runalyze\Configuration\Fieldset
    {
        return null;
    }

    final protected function addHandle(Handle $Handle): void
    {
        $this->Handles[$Handle->key()] = $Handle;
    }

    /**
     * Add handle
     */
    final protected function createHandle(string $key, Parameter $Parameter)
    {
        $this->Handles[$key] = new Handle($key, $Parameter);
    }

    /**
     * Get value
     * @return mixed
     * @throws \InvalidArgumentException
     */
    final protected function get(string $key)
    {
        return $this->object($key)
            ->value();
    }

    /**
     * Get value object
     * @throws \InvalidArgumentException
     */
    final protected function object(string $key): Parameter
    {
        if (isset($this->Handles[$key])) {
            return $this->Handles[$key]->object();
        }

        throw new \InvalidArgumentException('Asked for unknown value key "' . $key . '" in configuration category.');
    }

    /**
     * Get handle object
     * @throws \InvalidArgumentException
     */
    final protected function handle(string $key): \Runalyze\Configuration\Handle
    {
        if (isset($this->Handles[$key])) {
            return $this->Handles[$key];
        }

        throw new \InvalidArgumentException('Asked for unknown value key "' . $key . '" in configuration category.');
    }

    /**
     * Update all values from post
     */
    final public function updateFromPost(): void
    {
        foreach ($this->Handles as $Handle) {
            $this->updateValueFromPost($Handle);
        }
    }

    final protected function updateValue(Handle $Handle): void
    {
        if ($this->hasUserID() && ! SharedLinker::isOnSharedPage()) {
            $where = '`accountid`=' . $this->userID();
            $where .= ' AND `key`=' . DB::getInstance()->escape($Handle->key());

            DB::getInstance()->updateWhere('conf', $where, 'value', $Handle->object()->valueAsString());
        }
    }

    private function updateValueFromPost(Handle $Handle): void
    {
        $key = $Handle->key();

        if (isset($_POST[$key]) || isset($_POST[$key . '_sent'])) {
            $value = $Handle->value();

            if ($Handle->object() instanceof Parameter\Boolean) {
                $Handle->object()
                    ->set(isset($_POST[$key]));
            } else {
                $Handle->object()
                    ->setFromString($_POST[$key] ?? '');
            }

            if ($value != $Handle->value()) {
                $this->updateValue($Handle);
                $Handle->processOnchangeEvents();
            }
        }
    }

    /**
     * @param array $data values from database
     */
    private function loadValues(?array $data = null)
    {
        $KeysInDatabase = [];
        $Values = $data ?? $this->fetchValues();

        foreach ($Values as $Value) {
            if (! isset($Value['category']) || $Value['category'] == $this->key()) {
                $KeysInDatabase[] = $Value['key'];

                if (isset($this->Handles[$Value['key']])) {
                    $this->Handles[$Value['key']]->object()->setFromString($Value['value']);
                }
            }
        }

        if (! FrontendShared::$IS_SHOWN && $this->hasUserID()) {
            $this->correctDatabaseFor($KeysInDatabase);
        }
    }

    private function fetchValues(): array
    {
        return DB::getInstance()->query('SELECT `key`,`value` FROM ' . PREFIX . 'conf WHERE `accountid`="' . $this->userID() . '" AND `category`="' . $this->key() . '"')->fetchAll();
    }

    /**
     * Correct database
     */
    private function correctDatabaseFor(array $KeysInDatabase): void
    {
        $WantedKeys = array_keys($this->Handles);
        $UnusedKeys = array_diff($KeysInDatabase, $WantedKeys);
        $MissingKeys = array_diff($WantedKeys, $KeysInDatabase);

        foreach ($UnusedKeys as $Key) {
            $this->deleteKeyFromDatabase($Key);
        }

        foreach ($MissingKeys as $Key) {
            $this->insertKeyToDatabase($Key);
        }
    }

    private function deleteKeyFromDatabase(string $Key): void
    {
        DB::getInstance()->exec('DELETE FROM ' . PREFIX . 'conf WHERE `accountid`="' . $this->userID() . '" AND `category`="' . $this->key() . '" AND `key`="' . $Key . '"');
    }

    private function insertKeyToDatabase(string $Key): void
    {
        DB::getInstance()->insert(
            'conf',
            ['key', 'value', 'category', 'accountid'],
            [
                $this->Handles[$Key]->key(),
                $this->Handles[$Key]->object()->valueAsString(),
                $this->key(),
                $this->userID(),
            ]
        );
    }
}
