<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class HttpSmokeTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testPublicRoutesRespondWithout500(): void
    {
        $routes = [
            '/',
            '/login',
            '/dashboard',
            '/api/health',
            '/login/check',
        ];

        foreach ($routes as $url) {
            $this->client->request('GET', $url);
            $statusCode = $this->client->getResponse()->getStatusCode();

            $this->assertLessThan(
                500,
                $statusCode,
                "Route {$url} returned status code {$statusCode}"
            );
        }
    }

    public function testAllRoutesAreConfiguredWithHttpMethods(): void
    {
        $router = self::getContainer()->get('router');
        $routeCollection = $router->getRouteCollection();

        foreach ($routeCollection->all() as $name => $route) {
            if (str_starts_with($name, '_')) {
                continue;
            }

            $methods = $route->getMethods();

            $this->assertNotEmpty(
                $methods,
                "Route '{$name}' is missing HTTP method configuration. "
                . 'This triggers E_USER_DEPRECATED in modern Symfony.'
            );
        }
    }
}
