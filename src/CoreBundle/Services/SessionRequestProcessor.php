<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionRequestProcessor
{
    private ?string $sessionId = null;

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * @param array<string, mixed> $record
     */
    public function processRecord(array $record): array
    {
        $request = $this->requestStack->getMainRequest();

        if (! $request instanceof Request || ! $request->hasSession()) {
            return $record;
        }

        $session = $request->getSession();

        if (! $session->isStarted()) {
            return $record;
        }

        if (! $this->sessionId) {
            $this->sessionId = substr($session->getId(), 0, 8) ?: '????????';
        }

        $record['extra']['token'] = $this->sessionId;

        return $record;
    }
}
