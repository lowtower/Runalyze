<?php

declare(strict_types=1);

namespace Runalyze\Tests\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

trait HttpClientAwareTestCaseTrait
{
    protected function getMockForResponses(array $responses): \GuzzleHttp\Client
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);

        return new Client([
            'handler' => $handler,
        ]);
    }
}
