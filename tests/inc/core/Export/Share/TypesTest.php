<?php

namespace Runalyze\Export\Share;

use Runalyze\View\Activity\Context;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldFactory')]
class TypesTest extends \PHPUnit\Framework\TestCase
{
    public function testAllConstructors(): void
    {
        $context = new Context(0, 0);

        foreach (Types::getEnum() as $typeid) {
            $instance = Types::get($typeid, $context);

            $this->assertInstanceOf(AbstractSharer::class, $instance, sprintf('Types::get(%d) did not return an instance of AbstractSharer.', $typeid));
        }
    }
}
