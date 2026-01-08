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

use MatesOfMate\PHPUnitExtension\DTO\RunResult;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunResultTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $result = new RunResult(
            exitCode: 0,
            output: 'test output',
            errorOutput: 'error output',
            junitXmlPath: '/tmp/junit.xml'
        );

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('test output', $result->output);
        $this->assertSame('error output', $result->errorOutput);
        $this->assertSame('/tmp/junit.xml', $result->junitXmlPath);
    }

    public function testWasSuccessfulReturnsTrueWhenExitCodeIsZero(): void
    {
        $result = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: '/tmp/junit.xml'
        );

        $this->assertTrue($result->wasSuccessful());
    }

    public function testWasSuccessfulReturnsFalseWhenExitCodeIsNonZero(): void
    {
        $result = new RunResult(
            exitCode: 1,
            output: '',
            errorOutput: '',
            junitXmlPath: '/tmp/junit.xml'
        );

        $this->assertFalse($result->wasSuccessful());
    }

    public function testGetJunitXmlReturnsFileContents(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuite></testsuite>');

        $result = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $this->assertStringContainsString('<testsuite></testsuite>', $result->getJunitXml());

        @unlink($tempFile);
    }

    public function testGetJunitXmlReturnsEmptyStringWhenFileDoesNotExist(): void
    {
        $result = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: '/non/existent/path.xml'
        );

        $this->assertSame('', $result->getJunitXml());
    }

    public function testCleanupDeletesJunitXmlFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
        if (false === $tempFile) {
            $this->fail('Could not create temporary file');
        }
        file_put_contents($tempFile, '<?xml version="1.0"?><testsuite></testsuite>');

        $result = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: $tempFile
        );

        $this->assertFileExists($tempFile);

        $result->cleanup();

        $this->assertFileDoesNotExist($tempFile);
    }

    public function testCleanupDoesNotThrowWhenFileDoesNotExist(): void
    {
        $result = new RunResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
            junitXmlPath: '/non/existent/path.xml'
        );

        $result->cleanup();

        $this->addToAssertionCount(1); // No exception thrown
    }
}
