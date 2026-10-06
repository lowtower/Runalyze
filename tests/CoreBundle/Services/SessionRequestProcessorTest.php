<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\SessionRequestProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[\PHPUnit\Framework\Attributes\Group('default')]
class SessionRequestProcessorTest extends TestCase
{
    private RequestStack $requestStack;

    private SessionRequestProcessor $processor;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->processor = new SessionRequestProcessor($this->requestStack);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function createRecord(array $extra = [
        'existing' => 'value',
    ]): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable('2024-01-01 12:00:00'),
            channel: 'app',
            level: Level::Info,
            message: 'A message',
            context: [],
            extra: $extra
        );
    }

    private function pushRequestWithSession(bool $startSession): Session
    {
        $session = new Session(new MockArraySessionStorage());

        if ($startSession) {
            $session->start();
        }

        $request = new Request();
        $request->setSession($session);

        $this->requestStack->push($request);

        return $session;
    }

    public function testRecordIsUnchangedWithoutRequest(): void
    {
        $processedRecord = $this->processor->processRecord($this->createRecord());

        self::assertSame([
            'existing' => 'value',
        ], $processedRecord->extra);
    }

    public function testRecordIsUnchangedWhenRequestHasNoSession(): void
    {
        $this->requestStack->push(new Request());

        $processedRecord = $this->processor->processRecord($this->createRecord());

        self::assertSame([
            'existing' => 'value',
        ], $processedRecord->extra);
    }

    public function testRecordIsUnchangedWhenSessionIsNotStarted(): void
    {
        $this->pushRequestWithSession(startSession: false);

        $processedRecord = $this->processor->processRecord($this->createRecord());

        self::assertSame([
            'existing' => 'value',
        ], $processedRecord->extra);
    }

    public function testRecordGetsFirstEightCharactersOfSessionIdAsToken(): void
    {
        $session = $this->pushRequestWithSession(startSession: true);

        $processedRecord = $this->processor->processRecord($this->createRecord());

        self::assertSame(substr($session->getId(), 0, 8), $processedRecord->extra['token']);
        self::assertSame(8, strlen($processedRecord->extra['token']));
    }

    public function testExistingExtraValuesAndOtherRecordFieldsAreKept(): void
    {
        $this->pushRequestWithSession(startSession: true);

        $processedRecord = $this->processor->processRecord($this->createRecord());

        self::assertSame('value', $processedRecord->extra['existing']);
        self::assertSame('A message', $processedRecord->message);
        self::assertSame('app', $processedRecord->channel);
    }

    public function testRecordWithoutExtraGetsExtraWithToken(): void
    {
        $this->pushRequestWithSession(startSession: true);

        $processedRecord = $this->processor->processRecord($this->createRecord(extra: []));

        self::assertArrayHasKey('token', $processedRecord->extra);
    }

    public function testTokenIsKeptWhenSessionIdChangesAfterFirstUse(): void
    {
        $session = $this->pushRequestWithSession(startSession: true);

        $firstToken = $this->processor->processRecord($this->createRecord())
            ->extra['token'];
        $session->migrate();
        $secondToken = $this->processor->processRecord($this->createRecord())
            ->extra['token'];

        self::assertSame($firstToken, $secondToken);
    }

    public function testSubRequestsUseSessionOfMainRequest(): void
    {
        $session = $this->pushRequestWithSession(startSession: true);
        $this->requestStack->push(new Request());

        $processedRecord = $this->processor->processRecord($this->createRecord());

        self::assertSame(substr($session->getId(), 0, 8), $processedRecord->extra['token']);
    }
}
