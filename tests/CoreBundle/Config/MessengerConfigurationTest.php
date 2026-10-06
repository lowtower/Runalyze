<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Symfony\Component\Yaml\Yaml;

#[\PHPUnit\Framework\Attributes\Group('default')]
class MessengerConfigurationTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $configuration;

    protected function setUp(): void
    {
        $this->configuration = Yaml::parseFile(dirname(__DIR__, 3) . '/config/packages/messenger.yaml');
    }

    /**
     * @return array<string, mixed>
     */
    private function messenger(): array
    {
        return $this->configuration['framework']['messenger'];
    }

    /**
     * @return array<string, mixed>
     */
    private function messengerOfTestEnvironment(): array
    {
        return $this->configuration['when@test']['framework']['messenger'];
    }

    /**
     * @return array<string, string|int|null>
     */
    private function optionsOfFailedTransportDsn(): array
    {
        $query = (string) parse_url($this->messenger()['transports']['failed'], PHP_URL_QUERY);
        parse_str($query, $options);

        return $options;
    }

    public function testMessagesThatKeepFailingAreKeptInAnExistingFailureTransport(): void
    {
        $this->assertSame('failed', $this->messenger()['failure_transport']);
        $this->assertArrayHasKey('failed', $this->messenger()['transports']);
    }

    public function testFailureTransportUsesTheDefaultConnectionOfDoctrine(): void
    {
        $dsn = $this->messenger()['transports']['failed'];

        $this->assertSame('doctrine', parse_url($dsn, PHP_URL_SCHEME));
        $this->assertSame('default', parse_url($dsn, PHP_URL_HOST));
    }

    public function testFailureTransportHasItsOwnQueueSoThatTheWorkerDoesNotConsumeIt(): void
    {
        $this->assertSame('failed', $this->optionsOfFailedTransportDsn()['queue_name']);
    }

    public function testFailureTransportDoesNotCreateTheTableAtRuntime(): void
    {
        $this->assertSame('0', $this->optionsOfFailedTransportDsn()['auto_setup']);
    }

    public function testAsyncTransportReadsItsDsnFromTheEnvironment(): void
    {
        $this->assertSame('%env(MESSENGER_TRANSPORT_DSN)%', $this->messenger()['transports']['async']);
    }

    /**
     * @param class-string $messageClass
     */
    #[DataProvider('messagesForTheWorker')]
    public function testMessageIsRoutedToTheAsyncTransport(string $messageClass): void
    {
        $this->assertSame('async', $this->messenger()['routing'][$messageClass]);
    }

    public static function messagesForTheWorker(): iterable
    {
        yield 'backup' => [BackupGenerationMessage::class];
        yield 'poster' => [PosterGenerationMessage::class];
    }

    public function testEveryRoutedTransportExists(): void
    {
        foreach ($this->messenger()['routing'] as $messageClass => $transport) {
            $this->assertArrayHasKey($transport, $this->messenger()['transports'], $messageClass);
        }
    }

    public function testNothingIsRoutedToTheFailureTransport(): void
    {
        $this->assertNotContains('failed', $this->messenger()['routing']);
    }

    public function testKernelTestsUseInMemoryTransportsForBothTransports(): void
    {
        $this->assertSame(
            [
                'async' => 'in-memory://',
                'failed' => 'in-memory://',
            ],
            $this->messengerOfTestEnvironment()['transports']
        );
    }
}
