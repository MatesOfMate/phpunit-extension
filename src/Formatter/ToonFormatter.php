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

use MatesOfMate\PHPUnitExtension\Grouping\FailureGrouper;
use MatesOfMate\PHPUnitExtension\Grouping\MessageStripper;
use MatesOfMate\PHPUnitExtension\Parser\TestResult;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

/**
 * Formats test results for compact tool responses, leading with grouped
 * failures rather than one entry per failing test.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ToonFormatter
{
    private const TESTS_SHOWN = 5;

    /** Past the usual one-or-two-cause suite, a worked example per group regrows the wall of text grouping removed. */
    private const GROUPS_WITH_EXAMPLE = 3;

    /** Keeps the rendered response under the ~30KB an agent harness will still pass through untruncated. */
    private const EXAMPLE_LENGTH = 800;

    public function __construct(
        private readonly FailureGrouper $grouper = new FailureGrouper(),
        private readonly MessageStripper $stripper = new MessageStripper(),
    ) {
    }

    /**
     * @param array<int, array<string, mixed>>|null $groups pre-computed groups, so the caller that already grouped
     *                                                      them for the cache does not repeat the work
     */
    public function format(TestResult $result, string $mode = 'default', ?string $runId = null, ?array $groups = null): string
    {
        return match ($mode) {
            'default' => $this->formatDefault($result, $runId, $groups ?? $this->groupsOf($result)),
            'summary' => $this->formatSummary($result),
            'detailed' => $this->formatDetailed($result, $runId, $groups ?? $this->groupsOf($result)),
            default => throw new \InvalidArgumentException("Unknown format mode: {$mode}"),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     */
    private function formatDefault(TestResult $result, ?string $runId, array $groups): string
    {
        $data = [
            'summary' => $this->summaryOf($result),
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ];

        if ([] === $groups) {
            return ResponseEncoder::encode($data);
        }

        $data['groups'] = array_map(
            function (array $g, int $index): array {
                $entry = [
                    'id' => $g['id'],
                    'count' => $g['count'],
                    'type' => $g['type'],
                    'summary' => $this->stripper->truncate($this->firstLine((string) $g['summary']), self::EXAMPLE_LENGTH),
                    'example' => $g['tests'][0] ?? '',
                ];

                if ($index < self::GROUPS_WITH_EXAMPLE) {
                    $rep = $g['representative'];
                    $entry['message'] = $this->stripper->truncate(
                        $this->stripper->strip((string) ($rep['message'] ?? '')),
                        self::EXAMPLE_LENGTH
                    );
                    $entry['file'] = basename((string) ($rep['file'] ?? '')).':'.($rep['line'] ?? '');
                }

                return $entry;
            },
            $groups,
            array_keys($groups)
        );

        if (null !== $runId) {
            $data['run'] = $runId;
            $data['next'] = \sprintf(
                'phpunit-run-detail --id=%s [--group=g1|--test=Class::method] for the full messages',
                $runId
            );
        }

        return ResponseEncoder::encode($data);
    }

    private function formatSummary(TestResult $result): string
    {
        return ResponseEncoder::encode([
            'tests' => $result->getTests(),
            'passed' => $result->getPassed(),
            'failed' => $result->getFailed(),
            'errors' => $result->getErrors(),
            'time' => round($result->getTime(), 3).'s',
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     */
    private function formatDetailed(TestResult $result, ?string $runId, array $groups): string
    {
        $data = [
            'summary' => $this->summaryOf($result),
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ];

        if ([] === $groups) {
            return ResponseEncoder::encode($data);
        }

        $data['groups'] = array_map(
            function (array $g): array {
                $rep = $g['representative'];

                return [
                    'id' => $g['id'],
                    'count' => $g['count'],
                    'type' => $g['type'],
                    'example' => $g['tests'][0] ?? '',
                    // unlike default, which shortens this to a basename
                    'file' => (string) ($rep['file'] ?? ''),
                    'line' => $rep['line'] ?? null,
                    'message' => $this->stripper->truncate(
                        $this->stripper->strip((string) ($rep['message'] ?? '')),
                        self::EXAMPLE_LENGTH
                    ),
                    'tests' => $this->sampleTests($g['tests']),
                ];
            },
            $groups
        );
        if (null !== $runId) {
            $data['run'] = $runId;
            $data['next'] = \sprintf(
                'phpunit-run-detail --id=%s --test=Class::method for one test in full',
                $runId
            );
        }

        return ResponseEncoder::encode($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryOf(TestResult $result): array
    {
        return [
            'tests' => $result->getTests(),
            'passed' => $result->getPassed(),
            'failed' => $result->getFailed(),
            'errors' => $result->getErrors(),
            'warnings' => $result->getWarnings(),
            'skipped' => $result->getSkipped(),
            'time' => round($result->getTime(), 3).'s',
        ];
    }

    private function firstLine(string $message): string
    {
        $line = strtok($message, "\n");

        return false === $line ? '' : $line;
    }

    /**
     * @param array<int, string> $tests
     *
     * @return array<int, string>
     */
    private function sampleTests(array $tests): array
    {
        if (\count($tests) <= self::TESTS_SHOWN) {
            return $tests;
        }

        $shown = \array_slice($tests, 0, self::TESTS_SHOWN);
        $shown[] = \sprintf('... and %d more', \count($tests) - self::TESTS_SHOWN);

        return $shown;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupsOf(TestResult $result): array
    {
        return $this->grouper->group(array_merge($result->failures, $result->errors));
    }
}
