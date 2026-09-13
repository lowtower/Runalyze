<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Profile\Mapping\FitSdkExercise;

/**
 * Parser for parsing the FIT details for the laps/splits and store it as JSON.
 * Example JSON structure see at end.
 * #TSC
 */
class FitSplitsAdditionals
{
    // additional infos for strength-training
    private ?FitSdkExercise $fitExcerciseMapping = null;

    /**
     * Every element is one lap/round with a sub-array of key/values
     *
     * @var list<array<string, mixed>>
     */
    private array $data = [];

    public function hasData(): bool
    {
        return $this->data !== [] && ! empty($this->getUniqueKeys());
    }

    /**
     * Gets the split additionals as a JSON-compatible structure.
     *
     * @return array{
     *     keys: list<string>,
     *     data: list<object>
     * }|null
     */
    public function getFinishedSplitsAdditional(): ?array
    {
        if (! $this->hasData()) {
            return null;
        }

        // #TSC: Save additional infos of splits (f.e. strong-training) as JSON
        return [
            'keys' => array_values($this->getUniqueKeys()),
            'data' => array_map(
                static fn (array $row): object => (object) $row,
                $this->data
            ),
        ];
    }

    /**
     * #TSC read the sets of a strength-training as "normal" laps
     * @param array<string, mixed> $values
     */
    public function getSetRoundAndCollect(array &$values): ?Round
    {
        $round = null;

        // duration in seconds
        if (isset($values['duration']) && round($values['duration'][0] / 1000) > 0) {
            $active = isset($values['type']) && $values['type'][0] == 1;

            // create a lap/round
            $round = new Round(
                0,
                $values['duration'][0] / 1000,
                $active
            );

            if ($active) {
                $set = [];
                if (isset($values['category'])) {
                    // category and sub-category can be a array of 3 values (seperated by ,)
                    $cat = $values['category'][1];
                    if (isset($values['category_subtype'])) {
                        $sub = $values['category_subtype'][1];
                    } else {
                        $sub = null;
                    }

                    // init the mapper the first time
                    if ($this->fitExcerciseMapping == null) {
                        $this->fitExcerciseMapping = new FitSdkExercise();
                    }

                    // map the categories to a string name
                    $set['excercise'] = $this->fitExcerciseMapping->getFullMapping($cat, $sub);
                }

                if (isset($values['repetitions'])) {
                    $set['repetitions'] = (int) ($values['repetitions'][0]);
                }

                if (isset($values['weight']) && $values['weight'][0] > 0) {
                    $set['weight'] = (int) ($values['weight'][0]) / 16;
                }

                $this->data[] = $set;
            } else {
                // its a rest, use an empty element
                $this->data[] = [];
            }
        }

        return $round;
    }

    /**
     * #TSC collect additional informations of the lap.
     * @param array<string, mixed> $values
     */
    public function collectLap(array &$values, bool $isActiveLap, bool $isSwimming, ?array $adds = null): void
    {
        $i = [];

        if ($isActiveLap && $isSwimming) {
            if (isset($values['total_strokes']) && $values['total_strokes'][0] > 0) {
                $i['FIT strokes'] = (int) ($values['total_strokes'][0]);
            }
        }

        if (isset($values['total_calories']) && $values['total_calories'][0] > 0) {
            $i['FIT calories'] = (int) ($values['total_calories'][0]);
        }

        if (isset($adds)) {
            $i = array_merge($i, $adds);
        }

        if ($i !== []) {
            $this->data[] = $i;
        } else {
            // its a rest, use an empty element
            $this->data[] = [];
        }
    }

    private function getUniqueKeys(): array
    {
        $keys = [];
        foreach ($this->data as $row) {
            foreach ($row as $key => $value) {
                $keys[] = $key;
            }
        }

        // make all the keys unique
        return array_values(array_unique($keys));
    }
}

/*
{
    "keys": ["excercise","repetitions"],                            <== all keys (not every key is available in the data) used for UI table headers
    "data": [
        {"excercise":"curl, bench_press","repetitions":35},         <== data of lap/split/round 1
        {},                                                         <== rest lap without data
        {"excercise":"curl, bench_press","repetitions":16},
        {},
        {"excercise":"row, curl","repetitions":40}
    ]
}
*/
