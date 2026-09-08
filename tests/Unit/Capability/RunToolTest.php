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

use MatesOfMate\Common\Cache\RunCache;
use MatesOfMate\PHPUnitExtension\Capability\RunTool;
use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Grouping\FailureGrouper;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use MatesOfMate\PHPUnitExtension\Parser\TestResult;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use MatesOfMate\PHPUnitExtension\Runner\RunResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunToolTest extends TestCase
{
    public function testExecuteRunsSuiteAndReturnsFormattedOutput(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult, $testResult] = $this->createDependencies();

        $runner->expects($this->once())
            ->method('run')
            ->with([])
            ->willReturn($runResult);

        $parser->expects($this->once())
            ->method('parse')
            ->with($runResult->getJunitXml())
            ->willReturn($testResult);

        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'default')
            ->willReturn('formatted output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);

        $this->assertSame('formatted output', $tool->execute());
    }

    public function testExecutePassesConfigurationAndFileToRunner(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult, $testResult] = $this->createDependencies();
        $testFile = $this->createTemporaryTestFile();

        $runner->expects($this->once())
            ->method('run')
            ->with(['--configuration', 'phpunit.xml', $testFile])
            ->willReturn($runResult);

        $parser->method('parse')->willReturn($testResult);
        $formatter->method('format')->willReturn('output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);
        $tool->execute(
            file: $testFile,
            configuration: 'phpunit.xml',
        );
    }

    public function testExecuteBuildsClassAndMethodFilter(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult, $testResult] = $this->createDependencies();
        $testFile = $this->createTemporaryTestFile();

        $runner->expects($this->once())
            ->method('run')
            ->with(['--filter', 'App\\\\Tests\\\\UserTest::testCreate$', $testFile])
            ->willReturn($runResult);

        $parser->method('parse')->willReturn($testResult);
        $formatter->method('format')->willReturn('output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);
        $tool->execute(
            file: $testFile,
            class: 'App\\Tests\\UserTest',
            method: 'testCreate',
        );
    }

    public function testExecuteCombinesClassFilterWithExplicitFilter(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult, $testResult] = $this->createDependencies();

        $runner->expects($this->once())
            ->method('run')
            ->with($this->callback(static fn (array $args): bool => \in_array('--filter', $args, true)
                && \in_array('(App\\\\Tests\\\\UserTest(::.*)?$)|(failing)', $args, true)))
            ->willReturn($runResult);

        $parser->method('parse')->willReturn($testResult);
        $formatter->method('format')->willReturn('output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);
        $tool->execute(class: 'App\\Tests\\UserTest', filter: 'failing');
    }

    public function testExecuteThrowsWhenMethodIsProvidedWithoutClass(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache] = $this->createDependencies();

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "class" parameter is required when "method" is provided.');

        $tool->execute(method: 'testCreate');
    }

    public function testExecuteThrowsWhenFileDoesNotExist(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache] = $this->createDependencies();

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Test file not found: tests/MissingTest.php');

        $tool->execute(file: 'tests/MissingTest.php');
    }

    public function testExecuteFallsBackToRawOutputWhenParserFails(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult] = $this->createDependencies(output: 'PHPUnit output', errorOutput: 'No tests executed!');

        $runner->expects($this->once())
            ->method('run')
            ->willReturn($runResult);

        $parser->expects($this->once())
            ->method('parse')
            ->willThrowException(new \RuntimeException('Broken XML'));

        $formatter->expects($this->never())->method('format');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);
        $result = $tool->execute();

        $this->assertStringContainsString('PHPUnit output', $result);
        $this->assertStringContainsString('No tests executed!', $result);
    }

    public function testExecuteStoresTheRunAndPassesItsIdToTheFormatter(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult] = $this->createDependencies();
        $testResult = $this->createFailingResult();

        $runner->method('run')->willReturn($runResult);
        $parser->method('parse')->willReturn($testResult);

        $cache->expects($this->once())
            ->method('store')
            ->willReturn('20260906-105400-123456-abcdef');

        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'default', '20260906-105400-123456-abcdef', $this->callback(
                static fn (array $groups): bool => 1 === \count($groups) && 2 === $groups[0]['count']
            ))
            ->willReturn('formatted output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);

        $this->assertSame('formatted output', $tool->execute());
    }

    public function testExecuteDoesNotTouchTheCacheWhenNothingFailed(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult, $testResult] = $this->createDependencies();

        $runner->method('run')->willReturn($runResult);
        $parser->method('parse')->willReturn($testResult);

        $cache->expects($this->never())->method('store');
        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'default', null, [])
            ->willReturn('formatted output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);

        $this->assertSame('formatted output', $tool->execute());
    }

    /**
     * The catch-all around parsing exists to surface PHPUnit's own output when
     * the XML cannot be read. An unwritable cache directory is not that, and
     * must not cost the agent the formatted response.
     */
    public function testAnUnwritableCacheCostsTheRunIdAndNothingElse(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult] = $this->createDependencies(output: 'PHPUnit output');
        $testResult = $this->createFailingResult();

        $runner->method('run')->willReturn($runResult);
        $parser->method('parse')->willReturn($testResult);
        $cache->method('store')->willThrowException(new \RuntimeException('read-only file system'));

        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'default', null, $this->anything())
            ->willReturn('formatted output');

        $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);
        $result = $tool->execute();

        $this->assertSame('formatted output', $result);
        $this->assertStringNotContainsString('PHPUnit output', $result);
    }

    /**
     * The mock above proves RunTool degrades when store() throws, but that
     * only holds if RunCache actually throws on a real write failure. This
     * exercises a genuine one, a cache directory that exists but is not
     * writable, through RunTool's own catch, not RunCache in isolation.
     */
    public function testARealUnwritableCacheDirectoryDegradesTheSameWay(): void
    {
        [$runner, $parser, $formatter, $configDetector, $grouper, , $runResult] = $this->createDependencies(output: 'PHPUnit output');
        $testResult = $this->createFailingResult();

        $runner->method('run')->willReturn($runResult);
        $parser->method('parse')->willReturn($testResult);

        $formatter->expects($this->once())
            ->method('format')
            ->with($testResult, 'default', null, $this->anything())
            ->willReturn('formatted output');

        $dir = sys_get_temp_dir().'/phpunit-run-tool-test-'.bin2hex(random_bytes(4));
        mkdir($dir.'/phpunit-runs', 0o700, true);
        chmod($dir.'/phpunit-runs', 0o500);
        $cache = new RunCache($dir, 'phpunit-runs', 20);

        try {
            if (is_writable($dir.'/phpunit-runs')) {
                $this->markTestSkipped('the directory is still writable despite the permission change, likely running as root.');
            }

            $tool = new RunTool($runner, $parser, $formatter, $configDetector, $grouper, $cache);
            $result = $tool->execute();

            $this->assertSame('formatted output', $result);
            $this->assertStringNotContainsString('PHPUnit output', $result);
        } finally {
            chmod($dir.'/phpunit-runs', 0o700);
            @rmdir($dir.'/phpunit-runs');
            @rmdir($dir);
        }
    }

    private function createFailingResult(): TestResult
    {
        $failure = static fn (string $method): array => [
            'class' => 'App\\Tests\\InvoiceTest',
            'method' => $method,
            'type' => \PHPUnit\Framework\ExpectationFailedException::class,
            'file' => '/app/tests/InvoiceTest.php',
            'line' => 42,
            'message' => 'Failed asserting that two arrays are identical.',
        ];

        return new TestResult(
            ['tests' => 10, 'failures' => 2, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [$failure('testOne'), $failure('testTwo')],
            []
        );
    }

    /**
     * @return array{
     *     MockObject&PhpunitRunner,
     *     MockObject&JunitXmlParser,
     *     MockObject&ToonFormatter,
     *     MockObject&ConfigurationDetector,
     *     FailureGrouper,
     *     MockObject&RunCache,
     *     RunResult,
     *     TestResult
     * }
     */
    private function createDependencies(string $output = '', string $errorOutput = ''): array
    {
        $runner = $this->createMock(PhpunitRunner::class);
        $parser = $this->createMock(JunitXmlParser::class);
        $formatter = $this->createMock(ToonFormatter::class);
        $configDetector = $this->createMock(ConfigurationDetector::class);
        $configDetector->method('detect')->willReturn(null);

        $grouper = new FailureGrouper();
        $cache = $this->createMock(RunCache::class);

        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }

        file_put_contents($tempFile, '<?xml version="1.0"?><testsuites></testsuites>');

        $runResult = new RunResult(
            exitCode: '' === $errorOutput ? 0 : 1,
            output: $output,
            errorOutput: $errorOutput,
            junitXmlPath: $tempFile
        );

        $testResult = new TestResult(
            ['tests' => 10, 'failures' => 0, 'errors' => 0, 'warnings' => 0, 'skipped' => 0, 'time' => 5.5],
            [],
            []
        );

        return [$runner, $parser, $formatter, $configDetector, $grouper, $cache, $runResult, $testResult];
    }

    private function createTemporaryTestFile(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_case_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary test file');
        }

        file_put_contents($tempFile, '<?php // test');

        return $tempFile;
    }
}
