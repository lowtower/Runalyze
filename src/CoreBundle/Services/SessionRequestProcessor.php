<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionRequestProcessor
{
    private ?string $sessionId = null;

    public function __construct(
        private SessionInterface $session
    ) {
    }

    /**
     * @param array<string, mixed> $record
     */
    public function processRecord(array $record): array
    {
        if (! $this->session->isStarted()) {
            return $record;
        }

        if (! $this->sessionId) {
            $this->sessionId = substr($this->session->getId(), 0, 8) ?: '????????';
        }

        $record['extra']['token'] = $this->sessionId;

        return $record;
    }
}
