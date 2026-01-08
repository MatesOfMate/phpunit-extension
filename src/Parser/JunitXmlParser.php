<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Parser;

use MatesOfMate\Common\Truncator\MessageTruncator;

/**
 * Parses JUnit XML output into structured test results.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class JunitXmlParser
{
    public function __construct(
        private readonly MessageTruncator $truncator = new MessageTruncator(),
    ) {
    }

    public function parse(string $junitXml): TestResult
    {
        if ('' === $junitXml || '0' === $junitXml) {
            throw new \InvalidArgumentException('Empty JUnit XML provided');
        }

        $xml = new \SimpleXMLElement($junitXml);

        $testsuite = $xml->testsuite ?? $xml;

        $summary = [
            'tests' => (int) ($testsuite['tests'] ?? 0),
            'assertions' => (int) ($testsuite['assertions'] ?? 0),
            'failures' => (int) ($testsuite['failures'] ?? 0),
            'errors' => (int) ($testsuite['errors'] ?? 0),
            'warnings' => (int) ($testsuite['warnings'] ?? 0),
            'skipped' => (int) ($testsuite['skipped'] ?? 0),
            'time' => (float) ($testsuite['time'] ?? 0.0),
        ];

        $failures = $this->parseFailures($xml);
        $errors = $this->parseErrors($xml);

        return new TestResult($summary, $failures, $errors);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFailures(\SimpleXMLElement $xml): array
    {
        $failures = [];

        $testcases = $xml->xpath('//testcase[failure]');
        if (!\is_array($testcases)) {
            return [];
        }

        foreach ($testcases as $testcase) {
            $failure = $testcase->failure;

            $failures[] = [
                'class' => (string) $testcase['class'],
                'method' => (string) $testcase['name'],
                'file' => (string) $testcase['file'],
                'line' => (int) $testcase['line'],
                'type' => (string) ($failure['type'] ?? 'unknown'),
                'message' => $this->truncator->truncate((string) $failure, 200),
            ];
        }

        return $failures;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseErrors(\SimpleXMLElement $xml): array
    {
        $errors = [];

        $testcases = $xml->xpath('//testcase[error]');
        if (!\is_array($testcases)) {
            return [];
        }

        foreach ($testcases as $testcase) {
            $error = $testcase->error;

            $errors[] = [
                'class' => (string) $testcase['class'],
                'method' => (string) $testcase['name'],
                'file' => (string) $testcase['file'],
                'line' => (int) $testcase['line'],
                'type' => (string) ($error['type'] ?? 'unknown'),
                'message' => $this->truncator->truncate((string) $error, 200),
            ];
        }

        return $errors;
    }
}
