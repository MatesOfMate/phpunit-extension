<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Runner;

use MatesOfMate\Common\Process\ProcessResult;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitProcessExecutor;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class PhpunitRunnerTest extends TestCase
{
    public function testRunExecutesPhpunitAndReturnsRunResult(): void
    {
        $executor = $this->createMock(PhpunitProcessExecutor::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with(
                'phpunit',
                $this->callback(
                    static fn (array $args): bool => \in_array('--log-junit', $args, true)),
                300,
                true
            )
            ->willReturn(new ProcessResult(0, 'output', ''));

        $runner = new PhpunitRunner($executor);
        $result = $runner->run(['--version']);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('output', $result->output);
        $this->assertFileExists($result->junitXmlPath);

        // Cleanup
        $result->cleanup();
    }

    public function testRunIncludesProvidedArguments(): void
    {
        $executor = $this->createMock(PhpunitProcessExecutor::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with(
                'phpunit',
                $this->callback(static fn (array $args): bool => \in_array('--filter', $args, true)
                    && \in_array('TestClass', $args, true)),
                300,
                true
            )
            ->willReturn(new ProcessResult(0, '', ''));

        $runner = new PhpunitRunner($executor);
        $result = $runner->run(['--filter', 'TestClass']);

        $this->assertIsInt($result->exitCode);

        // Cleanup
        $result->cleanup();
    }

    public function testRunCreatesTemporaryJunitFile(): void
    {
        $executor = $this->createMock(PhpunitProcessExecutor::class);
        $executor->expects($this->once())
            ->method('execute')
            ->willReturn(new ProcessResult(0, '', ''));

        $runner = new PhpunitRunner($executor);
        $result = $runner->run([]);

        $tempDir = sys_get_temp_dir();
        $this->assertNotEmpty($tempDir);
        $this->assertStringStartsWith($tempDir, $result->junitXmlPath);
        $this->assertStringContainsString('phpunit_junit_', $result->junitXmlPath);

        // Cleanup
        $result->cleanup();
    }

    public function testRunHandlesExecutionFailure(): void
    {
        $executor = $this->createMock(PhpunitProcessExecutor::class);
        $executor->expects($this->once())
            ->method('execute')
            ->willReturn(new ProcessResult(1, '', 'error'));

        $runner = new PhpunitRunner($executor);
        $result = $runner->run([]);

        $this->assertSame(1, $result->exitCode);
        $this->assertSame('error', $result->errorOutput);
        $this->assertFalse($result->wasSuccessful());

        // Cleanup
        $result->cleanup();
    }
}
