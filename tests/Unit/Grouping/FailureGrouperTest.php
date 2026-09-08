<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Grouping;

use MatesOfMate\PHPUnitExtension\Grouping\FailureGrouper;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class FailureGrouperTest extends TestCase
{
    private FailureGrouper $grouper;

    protected function setUp(): void
    {
        $this->grouper = new FailureGrouper();
    }

    public function testGroupReturnsNothingForAPassingRun(): void
    {
        $this->assertSame([], $this->grouper->group([]));
    }

    /**
     * PHPUnit writes "Class::method with data set ..." on the first line of the
     * failure text. That line names the failing test, not the cause, so using
     * it as the key would put every failure in a group of its own.
     */
    public function testHeadlineSkipsTheLeadingTestIdentifierLine(): void
    {
        $message = "App\\Tests\\InvoiceTest::testFormat with data set \"INV-1200\"\n"
            ."Failed asserting that two arrays are identical.\n--- Expected\n+++ Actual";

        $this->assertSame('Failed asserting that two arrays are identical.', $this->grouper->headline($message));
    }

    public function testFailuresWithOneCauseCollapseIntoOneGroup(): void
    {
        $entries = [];
        foreach (range(1200, 1211) as $number) {
            $entries[] = $this->failure("testFormat with data set \"INV-{$number}\"", 'Failed asserting that two arrays are identical.');
        }

        $groups = $this->grouper->group($entries);

        $this->assertCount(1, $groups);
        $this->assertSame(12, $groups[0]['count']);
        $this->assertSame('g1', $groups[0]['id']);
    }

    public function testGroupsAreOrderedBySizeAndCarryEveryMember(): void
    {
        $entries = [
            $this->failure('testOne', 'Failed asserting that null is of type int.'),
            $this->failure('testTwo', 'Failed asserting that two arrays are identical.'),
            $this->failure('testThree', 'Failed asserting that two arrays are identical.'),
        ];

        $groups = $this->grouper->group($entries);

        $this->assertCount(2, $groups);
        $this->assertSame([2, 1], array_column($groups, 'count'));
        $this->assertSame(['InvoiceTest::testTwo', 'InvoiceTest::testThree'], $groups[0]['tests']);
        $this->assertCount(2, $groups[0]['members']);
    }

    public function testDifferentCausesStaySeparate(): void
    {
        $groups = $this->grouper->group([
            $this->failure('testOne', 'Failed asserting that two arrays are identical.'),
            $this->failure('testTwo', 'Failed asserting that null is of type int.'),
        ]);

        $this->assertCount(2, $groups);
    }

    public function testTheSameSentenceAboutDifferentValuesIsOneCause(): void
    {
        $groups = $this->grouper->group([
            $this->failure('testOne', 'Failed asserting that 7957.53 is identical to 6558.86.'),
            $this->failure('testTwo', 'Failed asserting that 15702.17 is identical to 13330.87.'),
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('Failed asserting that <num> is identical to <num>.', $groups[0]['fingerprint']);
    }

    public function testTheSameFailureInDifferentSymbolsIsOneCause(): void
    {
        $groups = $this->grouper->group([
            $this->failure('testOne', 'Method A::x() returned an unexpected value.'),
            $this->failure('testTwo', 'Method B::y() returned an unexpected value.'),
        ]);

        $this->assertCount(1, $groups);
    }

    public function testVolatileTokensDoNotSplitAGroup(): void
    {
        $groups = $this->grouper->group([
            $this->failure('testOne', 'Failed writing to /tmp/run-a1b2/report.xml'),
            $this->failure('testTwo', 'Failed writing to /tmp/run-c3d4/report.xml'),
            $this->failure('testThree', 'Failed for 550e8400-e29b-41d4-a716-446655440000'),
            $this->failure('testFour', 'Failed for 6ba7b810-9dad-11d1-80b4-00c04fd430c8'),
        ]);

        $this->assertCount(2, $groups);
    }

    public function testTheFailureTypeSeparatesOtherwiseIdenticalMessages(): void
    {
        $groups = $this->grouper->group([
            $this->failure('testOne', 'Something went wrong.', \PHPUnit\Framework\ExpectationFailedException::class),
            $this->failure('testTwo', 'Something went wrong.', 'RuntimeException'),
        ]);

        $this->assertCount(2, $groups);
        $this->assertSame(['ExpectationFailedException', 'RuntimeException'], array_column($groups, 'type'));
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $method, string $message, string $type = \PHPUnit\Framework\ExpectationFailedException::class): array
    {
        return [
            'class' => 'App\\Tests\\InvoiceTest',
            'method' => $method,
            'type' => $type,
            'file' => '/app/tests/InvoiceTest.php',
            'line' => 42,
            'message' => "App\\Tests\\InvoiceTest::{$method}\n".$message,
        ];
    }
}
