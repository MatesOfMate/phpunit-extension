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

use MatesOfMate\Common\Process\ProcessExecutor;
use MatesOfMate\Common\Process\ProcessResult;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class PhpunitRunnerTest extends TestCase
{
    public function testRunExecutesPhpunitAndReturnsRunResult(): void
    {
        $executor = $this->createMock(ProcessExecutor::class);
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
        $executor = $this->createMock(ProcessExecutor::class);
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
        $executor = $this->createMock(ProcessExecutor::class);
        $executor->expects($this->once())
            ->method('execute')
            ->willReturn(new ProcessResult(0, '', ''));

        $runner = new PhpunitRunner($executor);
        $result = $runner->run([]);

        $tempDir = sys_get_temp_dir();
        $this->assertNotEmpty($tempDir);
        // On macOS, temp paths might start with /private, so we need to normalize the comparison
        $normalizedTempDir = str_replace('/private', '', $tempDir);
        $normalizedPath = str_replace('/private', '', $result->junitXmlPath);
        $this->assertNotEmpty($normalizedTempDir, 'Normalized temp directory should not be empty');
        $this->assertStringStartsWith($normalizedTempDir, $normalizedPath);
        $this->assertStringContainsString('phpunit_junit_', $result->junitXmlPath);

        // Cleanup
        $result->cleanup();
    }

    public function testRunHandlesExecutionFailure(): void
    {
        $executor = $this->createMock(ProcessExecutor::class);
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

    public function testCustomCommandUsesProjectVarDirForJunitPath(): void
    {
        $projectRoot = sys_get_temp_dir().'/phpunit_runner_test_'.bin2hex(random_bytes(4));
        mkdir($projectRoot, 0777, true);

        try {
            $executor = $this->createMock(ProcessExecutor::class);
            $executor->expects($this->never())->method('execute');

            $runner = new PhpunitRunner(
                $executor,
                $projectRoot,
                [\PHP_BINARY, '-r', 'fwrite(STDERR, "custom command failure"); exit(1);'],
            );

            $result = $runner->run(['--filter', 'SomeTest']);

            $this->assertDirectoryExists($projectRoot.'/var');
            $this->assertStringStartsWith($projectRoot.'/var/phpunit_junit_', $result->junitXmlPath);
            $this->assertStringEndsWith('.xml', $result->junitXmlPath);
        } finally {
            // Cleanup
            if (isset($result)) {
                $result->cleanup();
            }
            @rmdir($projectRoot.'/var');
            @rmdir($projectRoot);
        }
    }

    public function testCustomCommandThrowsWhenVarDirectoryCannotBeCreated(): void
    {
        $projectRoot = sys_get_temp_dir().'/phpunit_runner_test_'.bin2hex(random_bytes(4));
        mkdir($projectRoot, 0777, true);
        file_put_contents($projectRoot.'/var', 'blocking file');

        try {
            $executor = $this->createMock(ProcessExecutor::class);
            $executor->expects($this->never())->method('execute');

            $runner = new PhpunitRunner(
                $executor,
                $projectRoot,
                [\PHP_BINARY, '-r', 'exit(1);'],
            );

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to create PHPUnit JUnit directory');

            $runner->run([]);
        } finally {
            @unlink($projectRoot.'/var');
            @rmdir($projectRoot);
        }
    }

    public function testDefaultBehaviorUnchangedWithEmptyCustomCommand(): void
    {
        $executor = $this->createMock(ProcessExecutor::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with(
                'phpunit',
                $this->callback(static fn (array $args): bool => \in_array('--log-junit', $args, true)),
                300,
                true
            )
            ->willReturn(new ProcessResult(0, 'test output', ''));

        $runner = new PhpunitRunner($executor, '/some/root', []);
        $result = $runner->run(['--version']);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('test output', $result->output);

        $result->cleanup();
    }
}
