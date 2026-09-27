<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal\Service;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Runalyze\Bundle\CoreBundle\Controller\Internal\Service\WeatherController;
use Runalyze\Service\WeatherForecast\Exception\TooOldForCurrentConditionsException;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[\PHPUnit\Framework\Attributes\Group('default')]
class WeatherControllerClassifyExceptionTest extends TestCase
{
    private function classify(Throwable $exception): string
    {
        $controller = new WeatherController($this->createMock(TranslatorInterface::class));

        $reflectionMethod = new ReflectionMethod(WeatherController::class, 'classifyException');

        return $reflectionMethod->invoke($controller, $exception);
    }

    private function requestExceptionWithStatus(int $statusCode): RequestException
    {
        return new RequestException(
            'request failed',
            new Request('GET', 'https://example.test/weather'),
            new Response($statusCode)
        );
    }

    public function testTooOldForCurrentConditionsExceptionIsClassifiedAsTooOld(): void
    {
        $this->assertSame(
            'too_old',
            $this->classify(new TooOldForCurrentConditionsException('activity is too old'))
        );
    }

    public function testRequestExceptionWithStatusCode401IsClassifiedAsAuthFailed(): void
    {
        $this->assertSame('auth_failed', $this->classify($this->requestExceptionWithStatus(401)));
    }

    public function testRequestExceptionWithStatusCode403IsClassifiedAsAuthFailed(): void
    {
        $this->assertSame('auth_failed', $this->classify($this->requestExceptionWithStatus(403)));
    }

    public function testRequestExceptionWithStatusCode429IsClassifiedAsRateLimited(): void
    {
        $this->assertSame('rate_limited', $this->classify($this->requestExceptionWithStatus(429)));
    }

    public function testRequestExceptionWithOtherStatusCodeIsClassifiedAsRequestFailed(): void
    {
        $this->assertSame('request_failed', $this->classify($this->requestExceptionWithStatus(500)));
    }

    public function testRequestExceptionWithoutAResponseIsClassifiedAsRequestFailed(): void
    {
        $exception = new RequestException(
            'connection failed',
            new Request('GET', 'https://example.test/weather')
        );

        $this->assertSame('request_failed', $this->classify($exception));
    }

    public function testGenericThrowableIsClassifiedAsRequestFailed(): void
    {
        $this->assertSame('request_failed', $this->classify(new RuntimeException('unexpected failure')));
    }
}
