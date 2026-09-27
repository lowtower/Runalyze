<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;

#[\PHPUnit\Framework\Attributes\Group('default')]
class UnsubscribeHashGeneratorTest extends TestCase
{
    public function testGenerateIsDeterministicForTheSameUsernameAndSecret(): void
    {
        $generator = new UnsubscribeHashGenerator('secret-a');

        $this->assertSame($generator->generate('alice'), $generator->generate('alice'));
    }

    public function testGenerateDiffersForDifferentUsernames(): void
    {
        $generator = new UnsubscribeHashGenerator('secret-a');

        $this->assertNotSame($generator->generate('alice'), $generator->generate('bob'));
    }

    public function testGenerateDiffersForDifferentSecrets(): void
    {
        $generatorA = new UnsubscribeHashGenerator('secret-a');
        $generatorB = new UnsubscribeHashGenerator('secret-b');

        $this->assertNotSame($generatorA->generate('alice'), $generatorB->generate('alice'));
    }

    public function testGenerateDoesNotMatchThePlainMd5OfTheUsername(): void
    {
        $generator = new UnsubscribeHashGenerator('secret-a');

        $this->assertNotSame(md5('alice'), $generator->generate('alice'));
    }

    public function testIsValidAcceptsAMatchingHash(): void
    {
        $generator = new UnsubscribeHashGenerator('secret-a');

        $this->assertTrue($generator->isValid('alice', $generator->generate('alice')));
    }

    public function testIsValidRejectsAHashGeneratedWithADifferentSecret(): void
    {
        $generatorA = new UnsubscribeHashGenerator('secret-a');
        $generatorB = new UnsubscribeHashGenerator('secret-b');

        $this->assertFalse($generatorA->isValid('alice', $generatorB->generate('alice')));
    }

    public function testIsValidRejectsAHashComputedForADifferentUsername(): void
    {
        $generator = new UnsubscribeHashGenerator('secret-a');

        $this->assertFalse($generator->isValid('alice', $generator->generate('bob')));
    }

    public function testIsValidRejectsThePlainMd5OfTheUsername(): void
    {
        $generator = new UnsubscribeHashGenerator('secret-a');

        $this->assertFalse($generator->isValid('alice', md5('alice')));
    }
}
