<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Message;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;

final class PosterGenerationMessageTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $message = new PosterGenerationMessage(
            accountId: 1,
            year: 2024,
            types: ['circular', 'linear'],
            sportId: 5,
            title: 'My Running Year',
            size: 'A2',
            backgroundColor: '#000000',
            trackColor: '#FF0000',
            textColor: '#FFFFFF',
            raceColor: '#00FF00'
        );

        $this->assertSame(1, $message->getAccountId());
        $this->assertSame(2024, $message->getYear());
        $this->assertSame(['circular', 'linear'], $message->getTypes());
        $this->assertSame(5, $message->getSportId());
        $this->assertSame('My Running Year', $message->getTitle());
        $this->assertSame('A2', $message->getSize());
        $this->assertSame('#000000', $message->getBackgroundColor());
        $this->assertSame('#FF0000', $message->getTrackColor());
        $this->assertSame('#FFFFFF', $message->getTextColor());
        $this->assertSame('#00FF00', $message->getRaceColor());
    }

    public function testGetTypesReturnsEmptyArrayWhenNoTypesAreSelected(): void
    {
        $message = new PosterGenerationMessage(
            accountId: 1,
            year: 2024,
            types: [],
            sportId: 5,
            title: '',
            size: 'A2',
            backgroundColor: '#000000',
            trackColor: '#FF0000',
            textColor: '#FFFFFF',
            raceColor: '#00FF00'
        );

        $this->assertSame([], $message->getTypes());
    }

    public function testGetTypesReturnsSingleType(): void
    {
        $message = new PosterGenerationMessage(
            accountId: 1,
            year: 2024,
            types: ['circular'],
            sportId: 5,
            title: 'Title',
            size: 'A3',
            backgroundColor: '#111111',
            trackColor: '#222222',
            textColor: '#333333',
            raceColor: '#444444'
        );

        $this->assertSame(['circular'], $message->getTypes());
    }
}
