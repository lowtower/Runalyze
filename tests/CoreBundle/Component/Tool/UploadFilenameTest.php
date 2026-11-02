<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\UploadFilename;

final class UploadFilenameTest extends TestCase
{
    public function testPathComponentsAreRemoved(): void
    {
        $this->assertSame('evil.json.gz', UploadFilename::fromClientOriginalName('../../evil.json.gz'));
        $this->assertSame('evil.fit', UploadFilename::fromClientOriginalName('C:\\fakepath\\evil.fit'));
    }

    public function testPlainFileNameIsKept(): void
    {
        $this->assertSame('simple.json.gz', UploadFilename::fromClientOriginalName('simple.json.gz'));
    }

    public function testUnusableFileNamesAreRejected(): void
    {
        $this->assertNull(UploadFilename::fromClientOriginalName(''));
        $this->assertNull(UploadFilename::fromClientOriginalName('.'));
        $this->assertNull(UploadFilename::fromClientOriginalName('..'));
        $this->assertNull(UploadFilename::fromClientOriginalName("bad\0name.fit"));
        $this->assertNull(UploadFilename::fromClientOriginalName("bad\nname.fit"));
    }
}
