<?php

declare(strict_types=1);

namespace Runalyze\Tests\Parser\Activity;

use Runalyze\Parser\Activity\Common\ParserInterface;
use Runalyze\Parser\Activity\FileExtensionToParserMapping;
use Runalyze\Parser\Activity\FileType\Tcx;

final class FileExtensionToParserMappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FileExtensionToParserMapping
     */
    protected $Mapping;

    protected function setUp(): void
    {
        $this->Mapping = new FileExtensionToParserMapping();
    }

    public function testInvalidExtension(): void
    {
        $this->assertNull($this->Mapping->getParserClassFor('foobar'));
    }

    public function testThatMappingIsCaseInsensitive(): void
    {
        $this->assertEquals(Tcx::class, $this->Mapping->getParserClassFor('tcx'));
        $this->assertEquals(Tcx::class, $this->Mapping->getParserClassFor('TCX'));
    }

    public function testThatAllMappedParserExist(): void
    {
        foreach (FileExtensionToParserMapping::MAPPING as $class) {
            $this->assertInstanceOf(ParserInterface::class, new $class());
        }
    }
}
