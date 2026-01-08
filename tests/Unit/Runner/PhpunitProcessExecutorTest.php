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

use MatesOfMate\PHPUnitExtension\Runner\PhpunitProcessExecutor;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class PhpunitProcessExecutorTest extends TestCase
{
    private PhpunitProcessExecutor $executor;

    protected function setUp(): void
    {
        $this->executor = new PhpunitProcessExecutor();
    }

    public function testExecuteReturnsProcessResult(): void
    {
        $result = $this->executor->execute('phpunit', ['--version']);

        $this->assertIsInt($result->exitCode);
        $this->assertIsString($result->output);
        $this->assertIsString($result->errorOutput);
    }

    public function testExecuteWithPhpunitVersionCommand(): void
    {
        $result = $this->executor->execute('phpunit', ['--version']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('PHPUnit', $result->output);
    }

    public function testExecuteRespectsTimeout(): void
    {
        $startTime = microtime(true);
        $result = $this->executor->execute('phpunit', ['--help'], timeout: 1);
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(2.0, $duration);
        $this->assertIsInt($result->exitCode);
    }

    public function testExecuteUsesPhpBinaryByDefault(): void
    {
        $result = $this->executor->execute('phpunit', ['--version'], usePhpBinary: true);

        // Should use PHP_BINARY to execute phpunit
        $this->assertStringContainsString('PHPUnit', $result->output);
    }

    public function testExecuteWithInvalidCommand(): void
    {
        $result = $this->executor->execute('nonexistent-command-12345', []);

        $this->assertNotSame(0, $result->exitCode);
    }

    public function testExecuteWithArguments(): void
    {
        $result = $this->executor->execute('phpunit', ['--version', '--no-colors']);

        $this->assertStringContainsString('PHPUnit', $result->output);
    }
}
