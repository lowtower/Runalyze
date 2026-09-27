<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Settings;

use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\Privacy;

class PrivacyData
{
    public bool $AthletePageActive = false;

    public bool $ShowPrivateInList = false;

    public bool $ShowStatisticsInList = false;

    /**
     * @var string never|race|always
     */
    public string $MapPrivacy = 'always';

    public function setDataFrom(Privacy $privacy): void
    {
        $this->AthletePageActive = $privacy->isListPublic();
        $this->ShowPrivateInList = $privacy->isListShowingAllActivities();
        $this->ShowStatisticsInList = $privacy->isListWithStatistics();
        $this->MapPrivacy = $privacy->get('TRAINING_MAP_PUBLIC_MODE');
    }

    /**
     * @return array<string, string>
     */
    public function getDataForConfiguration(): array
    {
        return [
            'TRAINING_LIST_PUBLIC' => $this->AthletePageActive ? 'true' : 'false',
            'TRAINING_LIST_ALL' => $this->ShowPrivateInList ? 'true' : 'false',
            'TRAINING_LIST_STATISTICS' => $this->ShowStatisticsInList ? 'true' : 'false',
            'TRAINING_MAP_PUBLIC_MODE' => $this->MapPrivacy,
        ];
    }
}
