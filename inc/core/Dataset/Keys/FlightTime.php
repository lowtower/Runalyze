<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

class FlightTime extends AbstractKey
{
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::FLIGHT_TIME;
    }

    public function column(): array
    {
        return ['cadence', 'groundcontact'];
    }

    public function label(): string
    {
        return __('Flight time');
    }

    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->flightTime();
    }

    public function cssClass(): string
    {
        return 'small';
    }
}
