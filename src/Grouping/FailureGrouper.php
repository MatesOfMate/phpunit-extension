<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Grouping;

/**
 * Collapses failures that share a cause into groups, the way an error tracker
 * collapses events into issues.
 *
 * A suite with one broken method usually reports one failure per test that
 * touches it. Returning all of them in full costs the agent a large response to
 * learn a single fact. Grouping by (type, fingerprint) turns seventeen
 * assertion diffs into "12 x two arrays are identical, 4 x <num> is identical
 * to <num>, 1 x two strings are equal", which is the shape of the problem.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class FailureGrouper
{
    /**
     * @param array<int, array<string, mixed>> $entries
     *
     * @return array<int, array<string, mixed>> groups, largest first
     */
    public function group(array $entries, string $messageKey = 'message'): array
    {
        $groups = [];

        foreach ($entries as $entry) {
            $type = $this->typeOf($entry);
            $headline = $this->headline((string) ($entry[$messageKey] ?? ''));
            $fingerprint = $this->fingerprint($headline);
            $key = $type.'|'.$fingerprint;

            $groups[$key] ??= [
                'type' => $type,
                'fingerprint' => $fingerprint,
                'summary' => $headline,
                'count' => 0,
                'tests' => [],
                'members' => [],
                'representative' => $entry,
            ];

            ++$groups[$key]['count'];
            $name = $this->testName($entry);
            $groups[$key]['tests'][] = $name;
            $groups[$key]['members'][] = [
                'test' => $name,
                'file' => $entry['file'] ?? null,
                'line' => $entry['line'] ?? null,
                'message' => (string) ($entry[$messageKey] ?? ''),
            ];
        }

        $groups = array_values($groups);
        usort($groups, static fn (array $a, array $b): int => $b['count'] <=> $a['count'] ?: strcmp($a['summary'], $b['summary']));

        foreach ($groups as $i => &$group) {
            $group['id'] = 'g'.($i + 1);
        }

        return $groups;
    }

    /**
     * Normalises the volatile parts of a message so that two reports of the
     * same underlying problem land on the same string.
     *
     * Order matters: UUIDs and hashes are consumed before the generic number
     * rule, and quoted literals before paths, so a quoted path is replaced once
     * rather than twice.
     */
    public function fingerprint(string $message): string
    {
        $s = $message;
        $s = preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i', '<uuid>', $s) ?? $s;
        $s = preg_replace('/\b(?:0x)?[0-9a-f]{12,}\b/i', '<hash>', $s) ?? $s;
        $s = preg_replace('/"[^"]*"|\'[^\']*\'/', '<str>', $s) ?? $s;
        // Symbol names identify the instance, not the rule: "Method A::x()
        // should return int but returns string" and the same sentence about
        // B::y() are one problem reported twice.
        $s = preg_replace('/[A-Za-z_][A-Za-z0-9_\\\\]*::[A-Za-z_][A-Za-z0-9_]*\(\)/', '<method>', $s) ?? $s;
        $s = preg_replace('/\$[A-Za-z_][A-Za-z0-9_]*/', '<var>', $s) ?? $s;
        $s = preg_replace('#\b(?:/|[A-Za-z]:\\\\)[^\s:,)]+(?::\d+)?#', '<path>', $s) ?? $s;
        $s = preg_replace('/\b\d+(?:\.\d+)?(?:[eE][-+]?\d+)?\b/', '<num>', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return trim($s);
    }

    /**
     * The first meaningful line. PHPUnit puts the assertion sentence there and
     * the diff below it, so the sentence is what identifies the cause.
     */
    public function headline(string $message): string
    {
        foreach (explode("\n", $message) as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            // PHPUnit prefixes the failure text with the test it came from
            // ("App\\Tests\\FooTest::testBar with data set #3"). That line names
            // the symptom, not the cause, and is different for every failing
            // test, so grouping on it would put each failure in its own group.
            if ($this->isTestIdentifier($line)) {
                continue;
            }

            return $line;
        }

        return '';
    }

    private function isTestIdentifier(string $line): bool
    {
        return 1 === preg_match('/^[A-Za-z_\\\\][A-Za-z0-9_\\\\]*::\S+/', $line);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function typeOf(array $entry): string
    {
        $type = (string) ($entry['type'] ?? 'unknown');
        $parts = explode('\\', $type);

        return end($parts) ?: 'unknown';
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function testName(array $entry): string
    {
        $parts = explode('\\', (string) ($entry['class'] ?? ''));

        return (end($parts) ?: '?').'::'.($entry['method'] ?? '?');
    }
}
