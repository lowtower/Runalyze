<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Poster;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\PosterGenerationStateResolver;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PosterGenerationStateResolverTest extends TestCase
{
    private PosterGenerationStateResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PosterGenerationStateResolver();
    }

    #[DataProvider('provideScenarios')]
    public function testResolve(int $generated, int $requested, int $expected): void
    {
        $this->assertSame($expected, $this->resolver->resolve($generated, $requested));
    }

    /**
     * @return array<string, int[]>
     */
    public static function provideScenarios(): array
    {
        return [
            'no files generated' => [0, 5, PosterGeneratedMessage::STATE_FAILED],
            'nothing requested and nothing generated' => [0, 0, PosterGeneratedMessage::STATE_FAILED],
            'fewer files generated than requested' => [3, 5, PosterGeneratedMessage::STATE_PARTIAL],
            'more files generated than requested' => [7, 5, PosterGeneratedMessage::STATE_PARTIAL],
            'all requested files generated' => [5, 5, PosterGeneratedMessage::STATE_SUCCESS],
            'single file, requested and generated' => [1, 1, PosterGeneratedMessage::STATE_SUCCESS],
            'single file generated out of many requested' => [1, 10, PosterGeneratedMessage::STATE_PARTIAL],
        ];
    }
}
