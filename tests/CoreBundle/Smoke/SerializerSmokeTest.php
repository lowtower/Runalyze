<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class SerializerSmokeTest extends KernelTestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->serializer = self::getContainer()->get('serializer');
    }

    public function testSerializerInstantiatesCorrectly(): void
    {
        $this->assertNotNull($this->serializer);
    }

    public function testSerializerCanSerializeSimpleObject(): void
    {
        $testData = new class() {
            public string $id = 'test-id';

            public string $name = 'test-name';
        };

        $json = $this->serializer->serialize($testData, 'json');

        $this->assertIsString($json);
        $this->assertNotEmpty($json);
        $this->assertStringContainsString('test-id', $json);
    }

    public function testSerializerCanDeserializeSimpleJson(): void
    {
        $json = '{"id":"test-id","name":"test-name"}';

        $testData = new class() {
            public string $id = '';

            public string $name = '';
        };

        $deserialized = $this->serializer->deserialize(
            $json,
            $testData::class,
            'json'
        );

        $this->assertSame('test-id', $deserialized->id);
        $this->assertSame('test-name', $deserialized->name);
    }
}
