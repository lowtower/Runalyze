<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\EventListener\MaintenanceListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

#[Group('default')]
final class MaintenanceListenerTest extends TestCase
{
    public function testMaintenanceModeReturnsServiceUnavailableAndStopsPropagation(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->with('maintenance.html.twig')->willReturn('Maintenance');

        $event = $this->requestEvent();
        $this->listener(true, 'prod', $twig)->onKernelRequest($event);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $event->getResponse()?->getStatusCode());
        $this->assertSame('Maintenance', $event->getResponse()?->getContent());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDisabledMaintenanceDoesNotRenderResponse(): void
    {
        $event = $this->requestEvent();
        $this->listener(false, 'prod', $this->unusedTwig())->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    #[DataProvider('debugEnvironments')]
    public function testDebugEnvironmentsBypassMaintenance(string $environment): void
    {
        $event = $this->requestEvent();
        $this->listener(true, $environment, $this->unusedTwig())->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public static function debugEnvironments(): iterable
    {
        yield 'development' => ['dev'];
        yield 'test' => ['test'];
    }

    #[DataProvider('allowedRoutes')]
    public function testAllowedRoutesBypassMaintenance(string $route): void
    {
        $event = $this->requestEvent($route);
        $this->listener(true, 'prod', $this->unusedTwig())->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public static function allowedRoutes(): iterable
    {
        yield 'update' => ['update'];
        yield 'install' => ['install'];
        yield 'install start' => ['install_start'];
        yield 'install finish' => ['install_finish'];
        yield 'update start' => ['update_start'];
        yield 'admin' => ['admin'];
    }

    public function testMissingMaintenanceParameterDefaultsToDisabled(): void
    {
        $parameters = $this->createMock(ParameterBagInterface::class);
        $parameters->expects($this->once())->method('has')->with('app.maintenance')->willReturn(false);
        $parameters->expects($this->never())->method('get');

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('prod');

        $event = $this->requestEvent();
        new MaintenanceListener($parameters, $kernel, $this->unusedTwig())->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    private function listener(bool $maintenance, string $environment, Environment $twig): MaintenanceListener
    {
        $parameters = $this->createStub(ParameterBagInterface::class);
        $parameters->method('has')->willReturn(true);
        $parameters->method('get')->willReturn($maintenance);

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn($environment);

        return new MaintenanceListener($parameters, $kernel, $twig);
    }

    private function unusedTwig(): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->never())->method('render');

        return $twig;
    }

    private function requestEvent(?string $route = null): RequestEvent
    {
        $request = new Request();

        if ($route !== null) {
            $request->attributes->set('_route', $route);
        }

        return new RequestEvent($this->createStub(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);
    }
}
