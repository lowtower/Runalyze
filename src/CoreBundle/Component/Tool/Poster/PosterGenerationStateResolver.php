<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;

final class PosterGenerationStateResolver
{
    public function resolve(int $generatedFiles, int $requestedFiles): int
    {
        if ($generatedFiles === 0) {
            return PosterGeneratedMessage::STATE_FAILED;
        }

        if ($requestedFiles !== $generatedFiles) {
            return PosterGeneratedMessage::STATE_PARTIAL;
        }

        return PosterGeneratedMessage::STATE_SUCCESS;
    }
}
