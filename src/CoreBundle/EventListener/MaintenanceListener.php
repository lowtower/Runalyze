<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class MaintenanceListener
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly KernelInterface $kernel,
        private readonly Environment $twig
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $maintenance = $this->parameterBag->has('app.maintenance') ? $this->parameterBag->get('app.maintenance') : false;

        $debug = in_array($this->kernel->getEnvironment(), ['test', 'dev']);
        $request = $event->getRequest();
        $routes = ['update', 'install', 'install_start', 'install_finish', 'update_start', 'admin'];

        if ($maintenance && ! $debug && ! in_array($request->get('_route'), $routes)) {
            $content = $this->twig->render('maintenance.html.twig');
            $event->setResponse(new Response($content, 503));
            $event->stopPropagation();
        }
    }
}
