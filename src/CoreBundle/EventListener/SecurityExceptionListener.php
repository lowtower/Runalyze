<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\DisabledException;

class SecurityExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof DisabledException) {
            $response = new Response();
            $response->setContent('<html><body><h1>Custom disabled page!</h1></body></html>');
            $event->setResponse($response);
        }
    }
}
