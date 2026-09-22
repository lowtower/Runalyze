<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Poster;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\PosterGenerationStateResolver;

class PosterGenerationStateResolverTest extends TestCase
{
    private PosterGenerationStateResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PosterGenerationStateResolver();
    }

    public function testResolveReturnsFailedWhenNoFilesWereGenerated(): void
    {
        $this->assertSame(
            PosterGeneratedMessage::STATE_FAILED,
            $this->resolver->resolve(0, 5)
        );
    }

    public function testResolveReturnsFailedWhenZeroFilesWereRequestedAndGenerated(): void
    {
        $this->assertSame(
            PosterGeneratedMessage::STATE_FAILED,
            $this->resolver->resolve(0, 0)
        );
    }

    public function testResolveReturnsPartialWhenFewerFilesGeneratedThanRequested(): void
    {
        $this->assertSame(
            PosterGeneratedMessage::STATE_PARTIAL,
            $this->resolver->resolve(3, 5)
        );
    }

    public function testResolveReturnsPartialWhenMoreFilesGeneratedThanRequested(): void
    {
        $this->assertSame(
            PosterGeneratedMessage::STATE_PARTIAL,
            $this->resolver->resolve(7, 5)
        );
    }

    public function testResolveReturnsSuccessWhenAllRequestedFilesWereGenerated(): void
    {
        $this->assertSame(
            PosterGeneratedMessage::STATE_SUCCESS,
            $this->resolver->resolve(5, 5)
        );
    }

    #[DataProvider('provideBoundaryScenarios')]
    public function testResolveBoundaryScenarios(int $generated, int $requested, int $expected): void
    {
        $this->assertSame($expected, $this->resolver->resolve($generated, $requested));
    }

    /**
     * @return array<string, int[]>
     */
    public static function provideBoundaryScenarios(): array
    {
        return [
            'single file, requested and generated' => [1, 1, PosterGeneratedMessage::STATE_SUCCESS],
            'single file generated out of many requested' => [1, 10, PosterGeneratedMessage::STATE_PARTIAL],
        ];
    }
}
