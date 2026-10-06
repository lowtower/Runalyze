<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionRequestProcessor
{
    private ?string $sessionId = null;

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function processRecord(LogRecord $record): LogRecord
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

        $record->extra['token'] = $this->sessionId;

        return $record;
    }
}
