<?php

declare(strict_types=1);

function readCoverage(string $path): array
{
    if (!is_file($path)) {
        fwrite(STDERR, "Coverage report not found: $path\n");
        exit(1);
    }

    $xml = simplexml_load_file($path);
    $metrics = $xml?->project?->metrics;

    if ($metrics === null || !isset($metrics['statements'], $metrics['coveredstatements'])) {
        fwrite(STDERR, "Coverage report has no statement metrics: $path\n");
        exit(1);
    }

    $total = (int) $metrics['statements'];
    $covered = (int) $metrics['coveredstatements'];

    if ($total === 0) {
        fwrite(STDERR, "Coverage report contains no executable statements: $path\n");
        exit(1);
    }

    return [$covered, $total];
}

[$baseCovered, $baseTotal] = readCoverage((string) getenv('BASELINE_COVERAGE'));
[$headCovered, $headTotal] = readCoverage((string) getenv('PULL_REQUEST_COVERAGE'));

// Exakt ohne Float-Rundung: head% < base%  <=>  head * baseTotal < base * headTotal
$failed = $headCovered * $baseTotal < $baseCovered * $headTotal;

$basePercentage = 100 * $baseCovered / $baseTotal;
$headPercentage = 100 * $headCovered / $headTotal;
$percentageDelta = $headPercentage - $basePercentage;
$coveredDelta = $headCovered - $baseCovered;

$status = $failed
    ? sprintf('Statement coverage decreased by %.4f percentage points', -$percentageDelta)
    : 'Statement coverage did not decrease';

$report = sprintf(
    "<!-- runalyze-coverage-report -->\n### Code coverage\n\n"
    . "| Revision | Statement coverage | Covered statements |\n"
    . "| --- | ---: | ---: |\n"
    . "| Base branch | %.4f%% | %d/%d |\n"
    . "| Pull request | %.4f%% | %d/%d |\n\n"
    . "**Change:** %+d covered statements (%+.4f pp)  \n"
    . "**Result:** %s\n\n"
    . "**Gate:** the statement coverage percentage must not decrease. The absolute number of covered statements may decrease when executable code is removed.",
    $basePercentage, $baseCovered, $baseTotal,
    $headPercentage, $headCovered, $headTotal,
    $coveredDelta, $percentageDelta,
    $status
);

file_put_contents((string) getenv('GITHUB_STEP_SUMMARY'), $report . "\n", FILE_APPEND);

$outputs = "report<<COVERAGE_REPORT\n" . $report . "\nCOVERAGE_REPORT\n"
    . 'failed=' . ($failed ? 'true' : 'false') . "\n";
file_put_contents((string) getenv('GITHUB_OUTPUT'), $outputs, FILE_APPEND);

if ($failed) {
    fwrite(STDERR, sprintf(
        "Statement coverage decreased by %.4f percentage points (from %.4f%% to %.4f%%).\n",
        -$percentageDelta,
        $basePercentage,
        $headPercentage
    ));
} else {
    echo "Covered statements did not decrease.\n";
}
