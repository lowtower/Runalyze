<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;

class DuplicateFinder
{
    /**
     * @var TrainingRepository
     */
    protected $TrainingRepository;

    public function __construct(TrainingRepository $repository)
    {
        $this->TrainingRepository = $repository;
    }

    public function isPossibleDuplicate(Training $activity): bool
    {
        $activity->getAdapter()
            ->setActivityIdIfEmpty();

        return $this->TrainingRepository->isPossibleDuplicate($activity);
    }
}
