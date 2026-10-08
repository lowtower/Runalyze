<?php

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Runalyze\Activity\Duration;
use Runalyze\Activity\LapIntensity;
use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

class RunalyzeRoundArray extends Type
{
    public const string RUNALYZE_ROUND_ARRAY = 'runalyze_round_array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! ($value instanceof RoundCollection) || $value->isEmpty()) {
            return null;
        }

        $rounds = [];

        foreach ($value->getElements() as $round) {
            $rounds[] = $this->getNativeValueForRound($round);
        }

        return implode('-', $rounds);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): RoundCollection
    {
        $collection = new RoundCollection();

        if ($value !== null && trim($value) !== '') {
            $rounds = explode('-', $value);

            foreach ($rounds as $round) {
                $collection->add($this->getRoundForNativeValue($round));
            }
        }

        return $collection;
    }

    protected function getNativeValueForRound(Round $round): string
    {
        $distance = number_format($round->getDistance(), 3, '.', '');
        $duration = Duration::format($round->getDuration());

        $intensity = $round->getIntensity();
        return $intensity->getValue() . $distance . '|' . $duration;
    }

    protected function getRoundForNativeValue(string $data): Round
    {
        $intensity = LapIntensity::fromValue(substr($data, 0, 1));

        $data = $intensity instanceof LapIntensity ? substr($data, 1) : $data;

        $duration = new Duration(substr(strrchr($data, '|'), 1))->seconds();
        $distance = (float) strstr($data, '|', true);

        $r = new Round($distance, $duration);
        if ($intensity instanceof LapIntensity) {
            $r->setIntensity($intensity);
        } else {
            // default is active if no special char exits
            $r->setIntensity(LapIntensity::getInstanceActive());
        }

        return $r;
    }
}
