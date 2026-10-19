<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

final class ParameterSelectFileTest extends \PHPUnit\Framework\TestCase
{
    protected \Runalyze\Parameter\SelectFile $object;

    protected function setUp(): void
    {
        $this->object = new SelectFile('path/to/file.jpg', [
            'folder' => 'dir/',
            'extensions' => ['jpg', 'png', 'gif'],
            'filename_only' => false,
        ]);
    }

    public function testSet(): void
    {
        $this->assertEquals('path/to/file.jpg', $this->object->value());

        $this->object->set('another/path/to/file.png');
        $this->assertEquals('another/path/to/file.png', $this->object->value());
    }

    public function testWrongExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->object->set('path/to/file.php');
    }

    public function testWrongPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->object->set('../private/file.jpg');
    }

    public function testRootPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->object->set('/usr/file.jpg');
    }

    public function testNoExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->object->set('/bin/shell');
    }

    public function testUppercaseVariantsAllowed(): void
    {
        $this->object->set('another/path/to/file.PNG');
        $this->assertEquals('another/path/to/file.PNG', $this->object->value());
    }

    public function testUppercaseVariantsDisallowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->object->allowUppercaseVariants(false);
        $this->object->set('another/path/to/file.PNG');
    }

    public function testFilenameOnly(): void
    {
        $select = new SelectFile('file.jpg', [
            'folder' => 'dir/',
            'extensions' => ['jpg', 'png', 'gif'],
            'filename_only' => true,
        ]);

        $this->assertEquals('file.jpg', $select->value());
    }
}
