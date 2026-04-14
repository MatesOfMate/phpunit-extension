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
        $this->assertStringContainsString('failures', $output);
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
