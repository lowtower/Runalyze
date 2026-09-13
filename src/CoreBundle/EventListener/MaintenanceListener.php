<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceListener
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event)
    {
        $maintenance = $this->container->hasParameter('app.maintenance') ? $this->container->getParameter('app.maintenance') : false;

        $debug = in_array($this->container->get('kernel')->getEnvironment(), ['test', 'dev']);
        $request = $event->getRequest();
        $routes = ['update', 'install', 'install_start', 'install_finish', 'update_start', 'admin'];

        if ($maintenance && ! $debug && ! in_array($request->get('_route'), $routes)) {
            $engine = $this->container->get('templating');
            $content = $engine->render('maintenance.html.twig');
            $event->setResponse(new Response($content, 503));
            $event->stopPropagation();
        }
    }
}
