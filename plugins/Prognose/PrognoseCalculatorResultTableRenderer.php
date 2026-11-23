<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Twig\Environment;

final readonly class PrognoseCalculatorResultTableRenderer
{
    public function __construct(
        private Environment $twig
    ) {}

    /**
     * @param array<array-key, array<string, mixed>> $prognoses
     */
    public function render(array $prognoses, bool $isValid): string
    {
        return $this->twig->render('@Prognose/calculator_result_table.html.twig', [
            'prognoses' => $prognoses,
            'is_valid' => $isValid,
            'headings' => [
                'distance' => __('Distance'),
                'prognosis' => __('Prognosis'),
                'pace' => __('Pace'),
                'difference' => __('Difference'),
                'personal_best' => __('Personal best'),
                'date' => __('Date'),
                'invalid_input' => __('Your input is invalid'),
            ],
        ]);
    }
}
