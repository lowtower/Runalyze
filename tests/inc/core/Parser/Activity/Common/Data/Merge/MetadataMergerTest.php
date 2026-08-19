<?php

namespace Runalyze\Tests\Parser\Activity\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\Merge\MetadataMerger;
use Runalyze\Parser\Activity\Common\Data\Metadata;

class MetadataMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Metadata
     */
    protected $FirstMetadata;

    /**
     * @var Metadata
     */
    protected $SecondMetadata;

    protected function setUp(): void
    {
        $this->FirstMetadata = new Metadata();
        $this->SecondMetadata = new Metadata();
    }

    /**
     * @group review-later
     * TODO: This test needs a deeper look later. It currently fails because the $result might be null.
     */
    public function testThatMergeWorksWithEmptyObjects()
    {
        $merger = new MetadataMerger($this->FirstMetadata, $this->SecondMetadata);
        $result = $merger->merge();

        $this->assertInstanceOf(
            Metadata::class,
            $result,
            'merge() should return an instance of Metadata'
        );
    }

    public function testThatTimestampIsNotOverridden()
    {
        $this->FirstMetadata->setTimestamp(123456789);
        $this->SecondMetadata->setTimestamp(987654321, 120);

        (new MetadataMerger($this->FirstMetadata, $this->SecondMetadata))->merge();

        $this->assertEquals(123456789, $this->FirstMetadata->getTimestamp());
    }

    public function testMergingCreatorDetails()
    {
        $this->FirstMetadata->setCreator('foo');
        $this->SecondMetadata->setCreator('', 'bar');

        (new MetadataMerger($this->FirstMetadata, $this->SecondMetadata))->merge();

        $this->assertEquals('foo', $this->FirstMetadata->getCreator());
        $this->assertEquals('bar', $this->FirstMetadata->getCreatorDetails());
    }

    public function testMergingEquipment()
    {
        $this->FirstMetadata->addEquipment('Foo');
        $this->SecondMetadata->addEquipment('Bar');

        (new MetadataMerger($this->FirstMetadata, $this->SecondMetadata))->merge();

        $this->assertEquals(['Foo', 'Bar'], $this->FirstMetadata->getEquipmentNames());
    }
}
