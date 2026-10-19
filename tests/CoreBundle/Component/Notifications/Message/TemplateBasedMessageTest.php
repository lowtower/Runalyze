<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Notifications\Message;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;

final class TemplateBasedMessageTest extends \PHPUnit\Framework\TestCase
{
    public function testNonExistantTemplate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TemplateBasedMessage('nonexistant.yml');
    }

    public function testSimpleTemplate(): void
    {
        $message = new TemplateBasedMessage('../../../tests/CoreBundle/DataFixtures/messages/test-message.yml');

        $this->assertTrue($message->hasLink());
        $this->assertSame('foobar', $message->getText());
        $this->assertSame('http://runalyze.com/', $message->getLink());
        $this->assertFalse($message->isLinkInternal());
    }
}
