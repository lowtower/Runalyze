<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class MessengerTransportsTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    private function transport(string $name): InMemoryTransport
    {
        $transport = self::getContainer()->get('messenger.transport.' . $name);
        $this->assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    private function createPosterMessage(): PosterGenerationMessage
    {
        return new PosterGenerationMessage(
            accountId: 1,
            year: 2024,
            types: ['heatmap'],
            sportId: 1,
            title: 'Title',
            size: '4000',
            backgroundColor: '#222222',
            trackColor: '#4DD2FF',
            textColor: '#FFFFFF',
            raceColor: '#FFFF00'
        );
    }

    #[DataProvider('transportNames')]
    public function testTransportDoesNotTouchADatabaseInTheTestEnvironment(string $name): void
    {
        $this->assertInstanceOf(InMemoryTransport::class, $this->transport($name));
    }

    public static function transportNames(): iterable
    {
        yield 'async' => ['async'];
        yield 'failed' => ['failed'];
    }

    public function testListenerThatMovesFailedMessagesToTheFailureTransportIsRegistered(): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $listenerClasses = array_map(
            static fn (mixed $listener): string => is_array($listener) && is_object($listener[0]) ? $listener[0]::class : get_debug_type($listener),
            $dispatcher->getListeners(WorkerMessageFailedEvent::class)
        );

        $this->assertContains(SendFailedMessageToFailureTransportListener::class, $listenerClasses);
    }

    public function testPosterMessageIsSentToTheAsyncTransportAndNotToTheFailureTransport(): void
    {
        $async = $this->transport('async');
        $failed = $this->transport('failed');
        $async->reset();
        $failed->reset();

        $bus = self::getContainer()->get(MessageBusInterface::class);
        $this->assertInstanceOf(MessageBusInterface::class, $bus);

        $bus->dispatch($this->createPosterMessage());

        $sent = $async->getSent();
        $this->assertCount(1, $sent);
        $this->assertInstanceOf(PosterGenerationMessage::class, $sent[0]->getMessage());
        $this->assertSame([], $failed->getSent());
    }
}
