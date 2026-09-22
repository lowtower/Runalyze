<?php

namespace Runalyze;

/**
 * @deprecated since v3.1
 */
class Configuration
{
    /**
     * @var \Runalyze\Configuration\Category[]
     */
    private static array $Categories = [];

    private static ?array $ValuesFromDB = null;

    private static ?int $AccountID = null;

    /**
     * @param mixed $accountid
     */
    public static function loadAll($accountid = 'auto'): void
    {
        if ($accountid === 'auto') {
            self::$AccountID = self::loadAccountID();
        } else {
            self::$AccountID = $accountid;
        }

        self::fetchAllValues();
        self::initAllCategories();
    }

    private static function initAllCategories(): void
    {
        self::ActivityForm();
        self::ActivityView();
        self::Data();
        self::DataBrowser();
        self::Design();
        self::General();
        self::Privacy();
        self::Trimp();
        self::VO2max();
        self::BasicEndurance();
    }

    /**
     * @param mixed $accountid
     * @throws \InvalidArgumentException
     */
    public static function resetConfiguration($accountid = 'auto'): void
    {
        if ($accountid !== 'auto' && ! is_numeric($accountid)) {
            throw new \InvalidArgumentException('Invalid accountid: ' . $accountid);
        }

        if ($accountid === 'auto') {
            if (self::$AccountID === null) {
                throw new \InvalidArgumentException('Configuration does not know any accountid.');
            }

            $accountid = self::$AccountID;
        }

        \DB::getInstance()->exec('DELETE FROM `' . PREFIX . 'conf` WHERE `accountid`="' . $accountid . '" AND `category` != "general" AND `category` != "data"');

        self::initAllCategories();
    }

    private static function fetchAllValues(): void
    {
        self::$Categories = [];

        if (self::$AccountID !== null) {
            self::$ValuesFromDB = \DB::getInstance()->query('SELECT `key`,`value`,`category` FROM ' . PREFIX . 'conf WHERE `accountid`="' . self::$AccountID . '"')->fetchAll();
        } else {
            self::$ValuesFromDB = [];
        }
    }

    private static function loadAccountID(): ?int
    {
        if (defined('RUNALYZE_TEST')) {
            return null;
        }

        return \SessionAccountHandler::getId();
    }

    private static function get(string $categoryName): \Runalyze\Configuration\Category
    {
        if (! isset(self::$Categories[$categoryName])) {
            $className = 'Runalyze\\Configuration\\Category\\' . $categoryName;
            $Category = new $className();
            $Category->setUserID(self::$AccountID, self::$ValuesFromDB);

            self::$Categories[$categoryName] = $Category;
        }

        return self::$Categories[$categoryName];
    }

    /**
     * @return \Runalyze\Configuration\Category\General
     */
    public static function General()
    {
        /** @var \Runalyze\Configuration\Category\General $return */
        $return = self::get('General');
        return $return;
    }

    public static function ActivityView(): \Runalyze\Configuration\Category\ActivityView
    {
        /** @var \Runalyze\Configuration\Category\ActivityView $return */
        $return = self::get('ActivityView');
        return $return;
    }

    public static function ActivityForm(): \Runalyze\Configuration\Category\ActivityForm
    {
        /** @var \Runalyze\Configuration\Category\ActivityForm $return */
        $return = self::get('ActivityForm');
        return $return;
    }

    public static function DataBrowser(): \Runalyze\Configuration\Category\DataBrowser
    {
        /** @var \Runalyze\Configuration\Category\DataBrowser $return */
        $return = self::get('DataBrowser');
        return $return;
    }

    public static function Privacy(): \Runalyze\Configuration\Category\Privacy
    {
        /** @var \Runalyze\Configuration\Category\Privacy $return */
        $return = self::get('Privacy');
        return $return;
    }

    public static function Design(): \Runalyze\Configuration\Category\Design
    {
        /** @var \Runalyze\Configuration\Category\Design $return */
        $return = self::get('Design');
        return $return;
    }

    public static function Data(): \Runalyze\Configuration\Category\Data
    {
        /** @var \Runalyze\Configuration\Category\Data $return */
        $return = self::get('Data');
        return $return;
    }

    public static function VO2max(): \Runalyze\Configuration\Category\VO2max
    {
        /** @var \Runalyze\Configuration\Category\VO2max $return */
        $return = self::get('VO2max');
        return $return;
    }

    public static function Trimp(): \Runalyze\Configuration\Category\Trimp
    {
        /** @var \Runalyze\Configuration\Category\Trimp $return */
        $return = self::get('Trimp');
        return $return;
    }

    public static function BasicEndurance(): \Runalyze\Configuration\Category\BasicEndurance
    {
        /** @var \Runalyze\Configuration\Category\BasicEndurance $return */
        $return = self::get('BasicEndurance');
        return $return;
    }
}
