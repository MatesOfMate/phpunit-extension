<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Capability;

use MatesOfMate\Common\Cache\RunCache;
use MatesOfMate\PHPUnitExtension\Grouping\MessageStripper;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

/**
 * Returns the detail behind a grouped `phpunit-run` response, without re-running
 * the suite.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunDetailTool
{
    /** Applies to everything except a single named `test`, which is returned in full. */
    private const MAX_MESSAGE_LENGTH = 800;

    public function __construct(
        private readonly RunCache $cache,
        private readonly MessageStripper $stripper,
    ) {
    }

    /**
     * @param string      $id    the run id returned by phpunit-run
     * @param string|null $group return every failure in one group, for example g1
     * @param string|null $test  return one test in full, for example InvoiceFormatterTest::testFormatsInvoice
     * @param bool        $raw   keep the noise: unchanged diff context and vendor stack frames
     */
    #[MateTool(name: 'phpunit-run-detail', title: 'PHPUnit Run Detail', description: 'Show the failure messages behind a grouped phpunit-run result, by run id. Use the test argument for one test in full; without it, or with only group, each message is capped to keep a wide result small.')]
    public function execute(
        string $id,
        ?string $group = null,
        ?string $test = null,
        bool $raw = false,
    ): string {
        $run = $this->cache->load($id);

        if (null === $run) {
            $known = $this->cache->ids();

            return ResponseEncoder::encode([
                'error' => "Unknown run id: {$id}",
                'hint' => [] === $known
                    ? 'No runs are cached yet. Run phpunit-run first.'
                    : 'Cached runs, newest first: '.implode(', ', \array_slice($known, 0, 5)),
            ]);
        }

        $groups = $run['groups'] ?? [];
        $matched = $this->select($groups, $group, $test);

        if ([] === $matched) {
            return ResponseEncoder::encode([
                'error' => 'Nothing matched in this run.',
                'groups' => array_map(static fn (array $g): string => $g['id'].' ('.$g['count'].')', $groups),
            ]);
        }

        return ResponseEncoder::encode([
            'run' => $id,
            'entries' => $this->entries($matched, $test, $raw),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<int, array<string, mixed>>
     */
    private function select(array $groups, ?string $group, ?string $test): array
    {
        if (null !== $group) {
            return array_values(array_filter($groups, static fn (array $g): bool => $g['id'] === $group));
        }

        if (null !== $test) {
            return array_values(array_filter(
                $groups,
                static fn (array $g): bool => \in_array($test, $g['tests'], true)
                    || [] !== array_filter($g['tests'], static fn (string $t): bool => str_contains($t, $test))
            ));
        }

        return $groups;
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<int, array<string, mixed>>
     */
    private function entries(array $groups, ?string $test, bool $raw): array
    {
        $entries = [];

        foreach ($groups as $g) {
            foreach ($g['members'] ?? [] as $member) {
                $name = ($member['test'] ?? '');
                if (null !== $test && $name !== $test && !str_contains((string) $name, $test)) {
                    continue;
                }

                $message = (string) ($member['message'] ?? '');
                if (!$raw) {
                    $message = $this->stripper->strip($message);
                }
                if (null === $test) {
                    $message = $this->stripper->truncate($message, self::MAX_MESSAGE_LENGTH);
                }

                $entries[] = [
                    'group' => $g['id'],
                    'test' => $name,
                    'file' => $member['file'] ?? null,
                    'line' => $member['line'] ?? null,
                    'message' => $message,
                ];
            }
        }

        return $entries;
    }
}
