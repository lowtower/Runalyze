<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Entity\Training;

class DuplicateFinder
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\TrainingRepository $TrainingRepository
    ) {
    }

    public function isPossibleDuplicate(Training $activity): bool
    {
        $activity->getAdapter()
            ->setActivityIdIfEmpty();

        return $this->TrainingRepository->isPossibleDuplicate($activity);
    }
}
