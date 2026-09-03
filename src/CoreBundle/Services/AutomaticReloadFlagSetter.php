<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class AutomaticReloadFlagSetter
{
    /**
     * @var string
     */
    public const FLASH_BAG_KEY = 'ajax.reload.command';

    /**
     * @var int enum
     */
    public const FLAG_DATA_BROWSER = 1;

    /**
     * @var int enum
     */
    public const FLAG_TRAINING = 2;

    /**
     * @var int enum
     */
    public const FLAG_TRAINING_AND_DATA_BROWSER = 3;

    /**
     * @var int enum
     */
    public const FLAG_PLUGINS = 4;

    /**
     * @var int enum
     */
    public const FLAG_ALL = 5;

    /**
     * @var int enum
     */
    public const FLAG_PAGE = 6;

    protected FlashBagInterface $FlashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->FlashBag = $flashBag;
    }

    public function set(int $flag): int
    {
        $currentFlag = $this->FlashBag->get(self::FLASH_BAG_KEY);

        if (! empty($currentFlag)) {
            $flag = $this->mergeFlags([$currentFlag[0], $flag]);
        }

        $this->FlashBag->set(self::FLASH_BAG_KEY, (string) $flag);

        return $flag;
    }

    /**
     * @param int[] $flags
     */
    protected function mergeFlags(array $flags): int
    {
        if (min($flags) == self::FLAG_DATA_BROWSER) {
            if (max($flags) == self::FLAG_PLUGINS) {
                return self::FLAG_ALL;
            }

            if (max($flags) == self::FLAG_TRAINING) {
                return self::FLAG_TRAINING_AND_DATA_BROWSER;
            }
        }

        return max($flags);
    }
}
