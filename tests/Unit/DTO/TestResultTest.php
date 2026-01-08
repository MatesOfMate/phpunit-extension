<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\DTO;

use MatesOfMate\PHPUnitExtension\DTO\TestResult;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class TestResultTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 2,
            'errors' => 1,
            'skipped' => 1,
            'time' => 5.5,
        ];
        $failures = [
            ['class' => 'TestClass', 'method' => 'testMethod', 'message' => 'Failed'],
        ];
        $errors = [
            ['class' => 'ErrorClass', 'method' => 'testError', 'message' => 'Error'],
        ];

        $result = new TestResult($summary, $failures, $errors);

        $this->assertSame($summary, $result->summary);
        $this->assertSame($failures, $result->failures);
        $this->assertSame($errors, $result->errors);
    }

    public function testWasSuccessfulReturnsTrueWhenNoFailuresOrErrors(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertTrue($result->wasSuccessful());
    }

    public function testWasSuccessfulReturnsFalseWhenHasFailures(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 2,
            'errors' => 0,
            'skipped' => 0,
            'time' => 5.5,
        ];
        $failures = [
            ['class' => 'TestClass', 'method' => 'testMethod', 'message' => 'Failed'],
        ];

        $result = new TestResult($summary, $failures, []);

        $this->assertFalse($result->wasSuccessful());
    }

    public function testWasSuccessfulReturnsFalseWhenHasErrors(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 1,
            'skipped' => 0,
            'time' => 5.5,
        ];
        $errors = [
            ['class' => 'ErrorClass', 'method' => 'testError', 'message' => 'Error'],
        ];

        $result = new TestResult($summary, [], $errors);

        $this->assertFalse($result->wasSuccessful());
    }

    public function testGetPassedCalculatesCorrectly(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 2,
            'errors' => 1,
            'skipped' => 1,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        // 10 - 2 - 1 - 1 = 6
        $this->assertSame(6, $result->getPassed());
    }

    public function testGetPassedReturnsZeroWhenAllTestsFailed(): void
    {
        $summary = [
            'tests' => 5,
            'failures' => 3,
            'errors' => 2,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(0, $result->getPassed());
    }

    public function testGetPassedReturnsAllWhenNoFailures(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(10, $result->getPassed());
    }

    public function testGetTestsReturnsCorrectValue(): void
    {
        $summary = [
            'tests' => 42,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(42, $result->getTests());
    }

    public function testGetFailedReturnsCorrectValue(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 3,
            'errors' => 0,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(3, $result->getFailed());
    }

    public function testGetErrorsReturnsCorrectValue(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 2,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(2, $result->getErrors());
    }

    public function testGetWarningsReturnsCorrectValue(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 0,
            'warnings' => 5,
            'skipped' => 0,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(5, $result->getWarnings());
    }

    public function testGetSkippedReturnsCorrectValue(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 4,
            'time' => 5.5,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(4, $result->getSkipped());
    }

    public function testGetTimeReturnsCorrectValue(): void
    {
        $summary = [
            'tests' => 10,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'time' => 12.456,
        ];

        $result = new TestResult($summary, [], []);

        $this->assertSame(12.456, $result->getTime());
    }
}
