<?php

declare(strict_types=1);

namespace Runalyze\Configuration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter;
use Runalyze\Parameter\Boolean;
use Runalyze\Parameter\Integer;
use Runalyze\Parameter\Textline;

class Category_MockTester extends Category
{
    protected function key(): string
    {
        return 'key';
    }

    protected function createHandles()
    {
        $this->addHandle(new Handle('TEST', new Integer(42)));
        $this->addHandle(new Handle('SECOND', new Textline('foobar')));
        $this->addHandle(new Handle('FLAG', new Boolean(true)));
    }

    public function test(): mixed
    {
        return $this->get('TEST');
    }

    public function Second(): Parameter
    {
        return $this->object('SECOND');
    }

    public function flag(): bool
    {
        return $this->get('FLAG');
    }

    public function rawObject(string $key): Parameter
    {
        return $this->object($key);
    }

    public function rawHandle(string $key): Handle
    {
        return $this->handle($key);
    }
}

#[Group('default')]
class CategoryTest extends TestCase
{
    private Category_MockTester $object;

    private array $postBackup;

    protected function setUp(): void
    {
        $this->object = new Category_MockTester();
        $this->postBackup = $_POST;
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
    }

    public function testAccessReturnsDefaultValuesAndAllowsUpdatingThem(): void
    {
        self::assertSame(42, $this->object->test());
        self::assertSame('foobar', $this->object->Second()->value());

        $this->object->Second()
            ->set('test');

        self::assertSame('test', $this->object->Second()->value());
    }

    public function testKeysReturnsAllRegisteredHandleKeysInDeclarationOrder(): void
    {
        self::assertSame(['TEST', 'SECOND', 'FLAG'], $this->object->keys());
    }

    public function testObjectThrowsExceptionForUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->object->rawObject('UNKNOWN_KEY');
    }

    public function testHandleThrowsExceptionForUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->object->rawHandle('UNKNOWN_KEY');
    }

    public function testDefaultFieldsetImplementationReturnsNull(): void
    {
        self::assertNull($this->object->Fieldset());
    }

    public function testSetValuesUpdatesRegisteredHandleFromString(): void
    {
        $this->object->setValues([
            'SECOND' => 'updated value',
        ]);

        self::assertSame('updated value', $this->object->Second()->value());
    }

    public function testSetValuesIgnoresUnknownKeys(): void
    {
        $this->object->setValues([
            'UNKNOWN_KEY' => 'irrelevant',
        ]);

        self::assertSame('foobar', $this->object->Second()->value());
    }

    public function testUpdateFromPostSetsBooleanToTrueWhenCheckboxIsPresent(): void
    {
        $_POST = [
            'FLAG' => '1',
            'FLAG_sent' => '1',
        ];

        $this->object->updateFromPost();

        self::assertTrue($this->object->flag());
    }

    public function testUpdateFromPostSetsBooleanToFalseWhenCheckboxIsAbsentButFormWasSent(): void
    {
        $_POST = [
            'FLAG_sent' => '1',
        ];

        $this->object->updateFromPost();

        self::assertFalse($this->object->flag());
    }

    public function testUpdateFromPostUpdatesTextValueWhenKeyIsPresent(): void
    {
        $_POST = [
            'SECOND' => 'from post',
        ];

        $this->object->updateFromPost();

        self::assertSame('from post', $this->object->Second()->value());
    }

    public function testUpdateFromPostLeavesValuesUnchangedWhenKeyIsMissing(): void
    {
        $_POST = [];

        $this->object->updateFromPost();

        self::assertSame(42, $this->object->test());
        self::assertSame('foobar', $this->object->Second()->value());
    }

    public function testUpdateFromPostFallsBackToEmptyStringWhenOnlySentMarkerIsPresent(): void
    {
        $_POST = [
            'SECOND_sent' => '1',
        ];

        $this->object->updateFromPost();

        self::assertSame('', $this->object->Second()->value());
    }
}
