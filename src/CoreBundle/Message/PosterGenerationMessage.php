<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Message;

final readonly class PosterGenerationMessage
{
    public function __construct(
        private int $accountId,
        private int $year,
        private array $types,
        private int $sportId,
        private string $title,
        private string $size,
        private string $backgroundColor,
        private string $trackColor,
        private string $textColor,
        private string $raceColor
    ) {
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getSportId(): int
    {
        return $this->sportId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function getTrackColor(): string
    {
        return $this->trackColor;
    }

    public function getTextColor(): string
    {
        return $this->textColor;
    }

    public function getRaceColor(): string
    {
        return $this->raceColor;
    }
}
