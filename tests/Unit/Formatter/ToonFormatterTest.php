<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Formatter;

use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Parser\TestResult;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ToonFormatterTest extends TestCase
{
    private ToonFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ToonFormatter();
    }

    public function testFormatThrowsExceptionForUnknownMode(): void
    {
        $testResult = $this->createSuccessfulResult();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown format mode: invalid');

        $this->formatter->format($testResult, 'invalid');
    }

    public function testFormatDefaultModeReturnsValidEncodedString(): void
    {
        $testResult = $this->createSuccessfulResult();

        $output = $this->formatter->format($testResult, 'default');

        $this->assertIsString($output);
        $this->assertStringContainsString('summary', $output);
        $this->assertStringContainsString('status', $output);
        $this->assertStringContainsString('OK', $output);
    }

    public function testFormatDefaultModeWithFailures(): void
    {
        $testResult = new TestResult(
            summary: [
                'tests' => 10,
                'failures' => 2,
                'errors' => 0,
                'warnings' => 0,
                'skipped' => 0,
                'time' => 5.5,
            ],
            failures: [
                [
                    'class' => 'App\\Tests\\UserTest',
                    'method' => 'testCreate',
                    'message' => 'Expected 200 got 404',
                    'file' => '/path/to/UserTest.php',
                    'line' => 45,
                ],
            ],
            errors: []
        );

        $output = $this->formatter->format($testResult, 'default');

        $this->assertStringContainsString('FAILED', $output);
        // Failures are reported as groups now: one entry per cause, with the
        // number of tests that hit it, instead of one entry per failing test.
        $this->assertStringContainsString('groups', $output);
        $this->assertStringContainsString('g1', $output);
    }

    public function testFormatSummaryModeReturnsCompactOutput(): void
    {
        $testResult = $this->createSuccessfulResult();

        $output = $this->formatter->format($testResult, 'summary');

        $this->assertIsString($output);
        $this->assertStringContainsString('status', $output);
        $this->assertStringNotContainsString('failures', $output);
    }

    public function testFormatDetailedModeIncludesFullPaths(): void
    {
        $testResult = new TestResult(
            summary: [
                'tests' => 10,
                'failures' => 1,
                'errors' => 0,
                'warnings' => 0,
                'skipped' => 0,
                'time' => 5.5,
            ],
            failures: [
                [
                    'class' => 'App\\Tests\\UserTest',
                    'method' => 'testCreate',
                    'message' => 'Expected 200 got 404',
                    'file' => '/path/to/UserTest.php',
                    'line' => 45,
                ],
            ],
            errors: []
        );

        $output = $this->formatter->format($testResult, 'detailed');

        $this->assertStringContainsString('UserTest', $output); // Class name present
        $this->assertStringContainsString('/path/to/UserTest.php', $output); // Full path
    }

    public function testFormatWithErrors(): void
    {
        $testResult = new TestResult(
            summary: [
                'tests' => 10,
                'failures' => 0,
                'errors' => 1,
                'warnings' => 0,
                'skipped' => 0,
                'time' => 5.5,
            ],
            failures: [],
            errors: [
                [
                    'class' => 'App\\Tests\\UserTest',
                    'method' => 'testCreate',
                    'message' => 'Call to undefined method',
                    'file' => '/path/to/UserTest.php',
                    'line' => 45,
                ],
            ]
        );

        $output = $this->formatter->format($testResult, 'default');

        $this->assertStringContainsString('FAILED', $output);
        $this->assertStringContainsString('errors', $output);
    }

    public function testFormatCalculatesTimeWithCorrectPrecision(): void
    {
        $testResult = new TestResult(
            summary: [
                'tests' => 10,
                'failures' => 0,
                'errors' => 0,
                'warnings' => 0,
                'skipped' => 0,
                'time' => 5.5678,
            ],
            failures: [],
            errors: []
        );

        $output = $this->formatter->format($testResult, 'default');

        $this->assertStringContainsString('5.568', $output); // Rounded to 3 decimals
    }

    public function testALargeGroupDoesNotPrintEveryMemberName(): void
    {
        $failures = [];
        for ($i = 0; $i < 200; ++$i) {
            $failures[] = [
                'class' => 'App\\Tests\\InvoiceTest',
                'method' => 'testThing'.$i,
                'type' => \PHPUnit\Framework\ExpectationFailedException::class,
                'file' => '/app/tests/InvoiceTest.php',
                'line' => 42,
                'message' => 'Failed asserting that two arrays are identical.',
            ];
        }

        $output = $this->formatter->format(
            new TestResult(
                ['tests' => 200, 'failures' => 200, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 1.0],
                $failures,
                []
            ),
            'detailed'
        );

        // A group exists because its members are interchangeable. Listing all
        // two hundred puts back the cost the grouping just removed.
        $this->assertStringContainsString('and 195 more', $output);
        $this->assertLessThan(2000, \strlen($output));
    }

    public function testTheDetailPointerIsOmittedWithoutARunId(): void
    {
        $output = $this->formatter->format($this->createFailingResult(), 'default');

        $this->assertStringNotContainsString('phpunit-run-detail', $output);
        $this->assertStringNotContainsString('"run"', $output);
    }

    public function testTheDetailPointerCarriesTheRunId(): void
    {
        $output = $this->formatter->format($this->createFailingResult(), 'default', 'run-42');

        $this->assertStringContainsString('phpunit-run-detail --id=run-42', $output);
    }

    /**
     * The first call an agent makes has to be worth making. A response saying
     * only "17 failures in 3 groups" forces a second call to learn anything
     * actionable, and a round trip costs a whole turn.
     */
    public function testDefaultCarriesAWorkedExampleForTheLargestGroups(): void
    {
        $decoded = json_decode($this->formatter->format($this->manyCauses(5), 'default', 'run-1'), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertCount(5, $decoded['groups']);
        $this->assertArrayHasKey('message', $decoded['groups'][0]);
        $this->assertArrayHasKey('file', $decoded['groups'][0]);
        $this->assertStringContainsString('changed line', (string) $decoded['groups'][0]['message']);
    }

    public function testTheLongTailKeepsItsSummaryAndIsFetchedById(): void
    {
        $decoded = json_decode($this->formatter->format($this->manyCauses(5), 'default', 'run-1'), true, 512, \JSON_THROW_ON_ERROR);

        $withExample = array_filter($decoded['groups'], static fn (array $g): bool => isset($g['message']));
        $this->assertCount(3, $withExample, 'only the largest groups carry an example');

        foreach (\array_slice($decoded['groups'], 3) as $tail) {
            $this->assertArrayNotHasKey('message', $tail);
            $this->assertNotSame('', $tail['summary']);
        }

        $this->assertStringContainsString('phpunit-run-detail --id=run-1', $decoded['next']);
    }

    /**
     * The response is rendered as a table padded to its widest column, so it
     * costs roughly the largest value times the row count. Past about 30KB an
     * agent harness stops passing it through at all.
     */
    public function testInliningExamplesCannotPushTheResponsePastTheRenderingLimit(): void
    {
        $decoded = json_decode($this->formatter->format($this->manyCauses(5), 'default', 'run-1'), true, 512, \JSON_THROW_ON_ERROR);

        $sizes = array_map(static fn ($v): int => \strlen((string) json_encode($v)), $decoded);
        $this->assertNotSame([], $sizes);
        $rendered = max($sizes) * (\count($sizes) + 2);

        $this->assertLessThan(29000, $rendered);
    }

    public function testALongSingleLineMessageIsCappedEvenInTheTail(): void
    {
        $failures = [];
        foreach (range(1, 5) as $i) {
            $failures[] = [
                'class' => 'App\\Tests\\T'.$i,
                'method' => 't',
                'type' => \PHPUnit\Framework\ExpectationFailedException::class,
                'file' => '/app/tests/T'.$i.'.php',
                'line' => 42,
                // No newline: distinct per group (so each lands in its own
                // group) but no second line for anything to cut on.
                'message' => str_repeat('x'.$i, 20000),
            ];
        }

        $decoded = json_decode(
            $this->formatter->format(
                new TestResult(['tests' => 5, 'failures' => 5, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 1.0], $failures, []),
                'default',
                'run-1'
            ),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );

        foreach ($decoded['groups'] as $group) {
            $this->assertLessThan(1000, \strlen((string) $group['summary']));
        }
    }

    public function testALongMessageIsCappedInDetailedMode(): void
    {
        $testResult = new TestResult(
            summary: ['tests' => 1, 'failures' => 1, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 1.0],
            failures: [[
                'class' => 'App\\Tests\\UserTest',
                'method' => 'testCreate',
                'message' => str_repeat('x', 5000),
                'file' => '/path/to/UserTest.php',
                'line' => 45,
            ]],
            errors: []
        );

        $decoded = json_decode($this->formatter->format($testResult, 'detailed'), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertLessThan(1000, \strlen((string) $decoded['groups'][0]['message']));
    }

    public function testSummaryStaysCountsOnly(): void
    {
        $output = $this->formatter->format($this->manyCauses(5), 'summary');

        $this->assertStringNotContainsString('changed line', $output);
        $this->assertStringNotContainsString('groups', $output);
    }

    private function createFailingResult(): TestResult
    {
        return new TestResult(
            ['tests' => 2, 'failures' => 1, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 1.0],
            [[
                'class' => 'App\\Tests\\InvoiceTest',
                'method' => 'testOne',
                'type' => \PHPUnit\Framework\ExpectationFailedException::class,
                'file' => '/app/tests/InvoiceTest.php',
                'line' => 42,
                'message' => 'Failed asserting that two arrays are identical.',
            ]],
            []
        );
    }

    private function manyCauses(int $causes): TestResult
    {
        $failures = [];
        foreach (range(1, $causes) as $i) {
            foreach (range(1, 6 - $i) as $j) {
                $failures[] = [
                    'class' => 'App\\Tests\\T'.$i,
                    'method' => 't'.$j,
                    'type' => \PHPUnit\Framework\ExpectationFailedException::class,
                    'file' => '/app/tests/T'.$i.'.php',
                    'line' => 42,
                    'message' => 'Failed asserting that cause '.str_repeat('x', $i).' holds.'."\n".str_repeat("-    changed line\n", 200),
                ];
            }
        }

        return new TestResult(
            ['tests' => \count($failures), 'failures' => \count($failures), 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 1.0],
            $failures,
            []
        );
    }

    private function createSuccessfulResult(): TestResult
    {
        return new TestResult(
            summary: [
                'tests' => 10,
                'failures' => 0,
                'errors' => 0,
                'warnings' => 0,
                'skipped' => 0,
                'time' => 5.5,
            ],
            failures: [],
            errors: []
        );
    }
}
