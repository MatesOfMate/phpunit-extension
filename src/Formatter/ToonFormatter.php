<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Formatter;

use MatesOfMate\PHPUnitExtension\DTO\TestResult;

/**
 * Formats test results using TOON (Token-Oriented Object Notation) for token-efficient output.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ToonFormatter
{
    public function format(TestResult $result, string $mode = 'default'): string
    {
        return match ($mode) {
            'default' => $this->formatDefault($result),
            'summary' => $this->formatSummary($result),
            'detailed' => $this->formatDetailed($result),
            'by-file' => $this->formatByFile($result),
            'by-class' => $this->formatByClass($result),
            default => throw new \InvalidArgumentException("Unknown format mode: {$mode}"),
        };
    }

    private function formatDefault(TestResult $result): string
    {
        $data = [
            'summary' => [
                'tests' => $result->getTests(),
                'passed' => $result->getPassed(),
                'failed' => $result->getFailed(),
                'errors' => $result->getErrors(),
                'warnings' => $result->getWarnings(),
                'skipped' => $result->getSkipped(),
                'time' => round($result->getTime(), 3).'s',
            ],
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ];

        if ([] !== $result->failures) {
            $data['failures'] = array_map(
                fn (array $f): array => [
                    'class' => $this->shortClassName($f['class']),
                    'method' => $f['method'],
                    'message' => $f['message'],
                    'file' => basename((string) $f['file']),
                    'line' => $f['line'],
                ],
                $result->failures
            );
        }

        if ([] !== $result->errors) {
            $data['errors'] = array_map(
                fn (array $e): array => [
                    'class' => $this->shortClassName($e['class']),
                    'method' => $e['method'],
                    'exception' => $e['message'],
                    'file' => basename((string) $e['file']),
                    'line' => $e['line'],
                ],
                $result->errors
            );
        }

        return toon($data);
    }

    private function formatSummary(TestResult $result): string
    {
        return toon([
            'tests' => $result->getTests(),
            'passed' => $result->getPassed(),
            'failed' => $result->getFailed(),
            'errors' => $result->getErrors(),
            'time' => round($result->getTime(), 3).'s',
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ]);
    }

    private function formatDetailed(TestResult $result): string
    {
        $data = [
            'summary' => [
                'tests' => $result->getTests(),
                'passed' => $result->getPassed(),
                'failed' => $result->getFailed(),
                'errors' => $result->getErrors(),
                'time' => round($result->getTime(), 3).'s',
            ],
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ];

        if ([] !== $result->failures) {
            $data['failures'] = array_map(
                fn (array $f): array => [
                    'class' => $f['class'],
                    'method' => $f['method'],
                    'message' => $f['message'],
                    'file' => $f['file'],
                    'line' => $f['line'],
                ],
                $result->failures
            );
        }

        if ([] !== $result->errors) {
            $data['errors'] = array_map(
                fn (array $e): array => [
                    'class' => $e['class'],
                    'method' => $e['method'],
                    'exception' => $e['message'],
                    'file' => $e['file'],
                    'line' => $e['line'],
                ],
                $result->errors
            );
        }

        return toon($data);
    }

    private function formatByFile(TestResult $result): string
    {
        $allIssues = [...$result->failures, ...$result->errors];
        $grouped = [];

        foreach ($allIssues as $issue) {
            $file = basename((string) $issue['file']);
            $grouped[$file][] = $issue;
        }

        ksort($grouped);

        $data = [
            'summary' => [
                'tests' => $result->getTests(),
                'passed' => $result->getPassed(),
                'failed' => $result->getFailed(),
                'errors' => $result->getErrors(),
                'time' => round($result->getTime(), 3).'s',
            ],
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
            'by_file' => $grouped,
        ];

        return toon($data);
    }

    private function formatByClass(TestResult $result): string
    {
        $allIssues = [...$result->failures, ...$result->errors];
        $grouped = [];

        foreach ($allIssues as $issue) {
            $class = $this->shortClassName($issue['class']);
            $grouped[$class][] = $issue;
        }

        ksort($grouped);

        $data = [
            'summary' => [
                'tests' => $result->getTests(),
                'passed' => $result->getPassed(),
                'failed' => $result->getFailed(),
                'errors' => $result->getErrors(),
                'time' => round($result->getTime(), 3).'s',
            ],
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
            'by_class' => $grouped,
        ];

        return toon($data);
    }

    private function shortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
