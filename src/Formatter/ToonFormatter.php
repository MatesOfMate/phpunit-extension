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

use MatesOfMate\PHPUnitExtension\Parser\TestResult;

class ToonFormatter
{
    public function format(TestResult $result): string
    {
        $data = [
            'summary' => [
                'tests' => $result->summary['tests'],
                'passed' => $result->getPassed(),
                'failed' => $result->summary['failures'],
                'errors' => $result->summary['errors'],
                'warnings' => $result->summary['warnings'],
                'skipped' => $result->summary['skipped'],
                'time' => round($result->summary['time'], 3).'s',
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

    private function shortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
