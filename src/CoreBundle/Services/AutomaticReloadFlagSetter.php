<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use LogicException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class AutomaticReloadFlagSetter
{
    public const string FLASH_BAG_KEY = 'ajax.reload.command';

    /**
     * @var int enum
     */
    public const int FLAG_DATA_BROWSER = 1;

    /**
     * @var int enum
     */
    public const int FLAG_TRAINING = 2;

    /**
     * @var int enum
     */
    public const int FLAG_TRAINING_AND_DATA_BROWSER = 3;

    /**
     * @var int enum
     */
    public const int FLAG_PLUGINS = 4;

    /**
     * @var int enum
     */
    public const int FLAG_ALL = 5;

    /**
     * @var int enum
     */
    public const int FLAG_PAGE = 6;

    public function __construct(
        protected RequestStack $RequestStack
    ) {}

    /**
     * @throws SessionNotFoundException if there is no active request with a session
     * @throws LogicException if the active session does not register a flash bag
     */
    public function set(int $flag): int
    {
        $flashBag = $this->getFlashBag();
        $currentFlag = $flashBag->get(self::FLASH_BAG_KEY);

        if ($currentFlag !== []) {
            $flag = $this->mergeFlags([(int) $currentFlag[0], $flag]);
        }

        $flashBag->set(self::FLASH_BAG_KEY, (string) $flag);

        return $flag;
    }

    /**
     * @param int[] $flags
     */
    protected function mergeFlags(array $flags): int
    {
        if (min($flags) === self::FLAG_DATA_BROWSER) {
            if (max($flags) === self::FLAG_PLUGINS) {
                return self::FLAG_ALL;
            }

            if (max($flags) === self::FLAG_TRAINING) {
                return self::FLAG_TRAINING_AND_DATA_BROWSER;
            }
        }

        return max($flags);
    }

    /**
     * @throws SessionNotFoundException if there is no active request with a session
     * @throws LogicException if the active session does not register a flash bag
     */
    private function getFlashBag(): FlashBagInterface
    {
        $flashBag = $this->RequestStack->getSession()
            ->getBag('flashes');

        if (! $flashBag instanceof FlashBagInterface) {
            throw new LogicException(sprintf(
                'Expected instance of %s, got %s.',
                FlashBagInterface::class,
                get_debug_type($flashBag)
            ));
        }

        return $flashBag;
    }
}
