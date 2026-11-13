<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Translation;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class MonthlyStatsTranslationTest extends KernelTestCase
{
    public function testMonthlyStatsAnalysisLabelsAreTranslatedIntoGerman(): void
    {
        $translator = self::getContainer()->get('translator');

        self::assertInstanceOf(TranslatorInterface::class, $translator);
        self::assertSame('nach Höhenmetern', $translator->trans('by elevation', [], 'messages', 'de'));
        self::assertSame('nach Energie', $translator->trans('by energy', [], 'messages', 'de'));
        self::assertSame('nach TRIMP', $translator->trans('by trimp', [], 'messages', 'de'));
        self::assertSame('nach Anzahl', $translator->trans('by number', [], 'messages', 'de'));
    }
}
