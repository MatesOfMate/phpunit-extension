<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Capability;

use MatesOfMate\PHPUnitExtension\Capability\RunSuiteTool;
use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use MatesOfMate\PHPUnitExtension\DTO\RunResult;
use MatesOfMate\PHPUnitExtension\DTO\TestResult;
use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunSuiteToolTest extends TestCase
{
    public function testExecuteRunsTestsAndReturnsFormattedOutput(): void
    {
        $runner = $this->createMock(PhpunitRunner::class);
        $parser = $this->createMock(JunitXmlParser::class);
        $formatter = $this->createMock(ToonFormatter::class);
        $configDetector = $this->createMock(ConfigurationDetector::class);

        // Create real RunResult instance (readonly class cannot be mocked)
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuites></testsuites>');

        $runResult = new RunResult(
            exitCode: 0,
            output: 'test output',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $testResult = new TestResult(
            ['tests' => 10, 'failures' => 0, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [],
            []
        );

        $runner->expects($this->once())
            ->method('run')
            ->willReturn($runResult);

        $parser->expects($this->once())
            ->method('parse')
            ->willReturn($testResult);

        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'default')
            ->willReturn('formatted output');

        $configDetector->method('detect')->willReturn(null);

        $tool = new RunSuiteTool($runner, $parser, $formatter, $configDetector);
        $output = $tool->execute();

        $this->assertSame('formatted output', $output);

        // Cleanup
        @unlink($tempFile);
    }

    public function testExecutePassesConfigurationToRunner(): void
    {
        $runner = $this->createMock(PhpunitRunner::class);
        $parser = $this->createMock(JunitXmlParser::class);
        $formatter = $this->createMock(ToonFormatter::class);
        $configDetector = $this->createMock(ConfigurationDetector::class);

        // Create real RunResult instance
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuites></testsuites>');

        $runResult = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $testResult = new TestResult(
            ['tests' => 10, 'failures' => 0, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [],
            []
        );

        $configDetector->method('detect')->willReturn('/path/to/phpunit.xml');

        $runner->expects($this->once())
            ->method('run')
            ->with($this->callback(static fn (array $args): bool => \in_array('--configuration', $args, true)
                && \in_array('/path/to/phpunit.xml', $args, true)))
            ->willReturn($runResult);

        $parser->method('parse')->willReturn($testResult);
        $formatter->method('format')->willReturn('output');

        $tool = new RunSuiteTool($runner, $parser, $formatter, $configDetector);
        $tool->execute();

        @unlink($tempFile);
    }

    public function testExecutePassesFilterArgument(): void
    {
        $runner = $this->createMock(PhpunitRunner::class);
        $parser = $this->createMock(JunitXmlParser::class);
        $formatter = $this->createMock(ToonFormatter::class);
        $configDetector = $this->createMock(ConfigurationDetector::class);

        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuites></testsuites>');

        $runResult = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $testResult = new TestResult(
            ['tests' => 10, 'failures' => 0, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [],
            []
        );

        $configDetector->method('detect')->willReturn(null);

        $runner->expects($this->once())
            ->method('run')
            ->with($this->callback(static fn (array $args): bool => \in_array('--filter', $args, true)
                && \in_array('UserTest', $args, true)))
            ->willReturn($runResult);

        $parser->method('parse')->willReturn($testResult);
        $formatter->method('format')->willReturn('output');

        $tool = new RunSuiteTool($runner, $parser, $formatter, $configDetector);
        $tool->execute(filter: 'UserTest');

        @unlink($tempFile);
    }

    public function testExecutePassesStopOnFailureFlag(): void
    {
        $runner = $this->createMock(PhpunitRunner::class);
        $parser = $this->createMock(JunitXmlParser::class);
        $formatter = $this->createMock(ToonFormatter::class);
        $configDetector = $this->createMock(ConfigurationDetector::class);

        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuites></testsuites>');

        $runResult = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $testResult = new TestResult(
            ['tests' => 10, 'failures' => 0, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [],
            []
        );

        $configDetector->method('detect')->willReturn(null);

        $runner->expects($this->once())
            ->method('run')
            ->with($this->callback(static fn (array $args): bool => \in_array('--stop-on-failure', $args, true)))
            ->willReturn($runResult);

        $parser->method('parse')->willReturn($testResult);
        $formatter->method('format')->willReturn('output');

        $tool = new RunSuiteTool($runner, $parser, $formatter, $configDetector);
        $tool->execute(stopOnFailure: true);

        @unlink($tempFile);
    }

    public function testExecuteSupportsMultipleFormatterModes(): void
    {
        $runner = $this->createMock(PhpunitRunner::class);
        $parser = $this->createMock(JunitXmlParser::class);
        $formatter = $this->createMock(ToonFormatter::class);
        $configDetector = $this->createMock(ConfigurationDetector::class);

        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuites></testsuites>');

        $runResult = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $testResult = new TestResult(
            ['tests' => 10, 'failures' => 0, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [],
            []
        );

        $configDetector->method('detect')->willReturn(null);
        $runner->method('run')->willReturn($runResult);
        $parser->method('parse')->willReturn($testResult);

        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'summary')
            ->willReturn('summary output');

        $tool = new RunSuiteTool($runner, $parser, $formatter, $configDetector);
        $output = $tool->execute(mode: 'summary');

        $this->assertSame('summary output', $output);

        @unlink($tempFile);
    }
}
