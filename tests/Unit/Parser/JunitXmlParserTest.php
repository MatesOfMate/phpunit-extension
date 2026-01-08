<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Parser;

use MatesOfMate\Common\Truncator\MessageTruncator;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class JunitXmlParserTest extends TestCase
{
    private JunitXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new JunitXmlParser();
    }

    public function testParseThrowsExceptionForEmptyXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty JUnit XML provided');

        $this->parser->parse('');
    }

    public function testParseSuccessfulTestSuite(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="5" assertions="10" failures="0" errors="0" warnings="0" skipped="0" time="2.5">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="2" time="0.5"/>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(5, $result->summary['tests']);
        $this->assertSame(10, $result->summary['assertions']);
        $this->assertSame(0, $result->summary['failures']);
        $this->assertSame(0, $result->summary['errors']);
        $this->assertSame(0, $result->summary['warnings']);
        $this->assertSame(0, $result->summary['skipped']);
        $this->assertSame(2.5, $result->summary['time']);
        $this->assertEmpty($result->failures);
        $this->assertEmpty($result->errors);
        $this->assertTrue($result->wasSuccessful());
    }

    public function testParseTestSuiteWithFailures(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="5" assertions="10" failures="2" errors="0" warnings="0" skipped="0" time="2.5">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="2" time="0.5">
            <failure type="PHPUnit\Framework\ExpectationFailedException">Failed asserting that false is true.</failure>
        </testcase>
        <testcase name="testAnother" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="20" assertions="1" time="0.3">
            <failure type="PHPUnit\Framework\ExpectationFailedException">Expected 200 but got 404.</failure>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(2, $result->summary['failures']);
        $this->assertCount(2, $result->failures);
        $this->assertFalse($result->wasSuccessful());

        $firstFailure = $result->failures[0];
        $this->assertSame('App\Tests\ExampleTest', $firstFailure['class']);
        $this->assertSame('testExample', $firstFailure['method']);
        $this->assertSame('/path/to/ExampleTest.php', $firstFailure['file']);
        $this->assertSame(10, $firstFailure['line']);
        $this->assertSame(ExpectationFailedException::class, $firstFailure['type']);
        $this->assertIsString($firstFailure['message']);
    }

    public function testParseTestSuiteWithErrors(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="5" assertions="10" failures="0" errors="1" warnings="0" skipped="0" time="2.5">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="2" time="0.5">
            <error type="Error">Call to undefined method</error>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(1, $result->summary['errors']);
        $this->assertCount(1, $result->errors);
        $this->assertFalse($result->wasSuccessful());

        $error = $result->errors[0];
        $this->assertSame('App\Tests\ExampleTest', $error['class']);
        $this->assertSame('testExample', $error['method']);
        $this->assertSame('/path/to/ExampleTest.php', $error['file']);
        $this->assertSame(10, $error['line']);
        $this->assertSame('Error', $error['type']);
        $this->assertIsString($error['message']);
    }

    public function testParseTestSuiteWithWarningsAndSkipped(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="5" assertions="10" failures="0" errors="0" warnings="1" skipped="2" time="2.5">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="2" time="0.5"/>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(1, $result->summary['warnings']);
        $this->assertSame(2, $result->summary['skipped']);
    }

    public function testParseHandlesMissingAttributes(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php"/>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(0, $result->summary['tests']);
        $this->assertSame(0, $result->summary['assertions']);
        $this->assertSame(0.0, $result->summary['time']);
    }

    public function testParseTruncatesLongFailureMessages(): void
    {
        $longMessage = str_repeat('Failed asserting that this is a very long error message that should be truncated. ', 10);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="1" assertions="1" failures="1" errors="0" warnings="0" skipped="0" time="1.0">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="1" time="0.5">
            <failure type="PHPUnit\Framework\ExpectationFailedException">{$longMessage}</failure>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertLessThanOrEqual(200, \strlen((string) $result->failures[0]['message']));
    }

    public function testParseWithCustomTruncator(): void
    {
        $truncator = $this->createMock(MessageTruncator::class);
        $truncator->expects($this->atLeastOnce())
            ->method('truncate')
            ->willReturn('Truncated message');

        $parser = new JunitXmlParser($truncator);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="1" assertions="1" failures="1" errors="0" warnings="0" skipped="0" time="1.0">
        <testcase name="testExample" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="1" time="0.5">
            <failure type="PHPUnit\Framework\ExpectationFailedException">Long failure message</failure>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $result = $parser->parse($xml);

        $this->assertSame('Truncated message', $result->failures[0]['message']);
    }

    public function testParseHandlesMixedFailuresAndErrors(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Test Suite" tests="3" assertions="5" failures="1" errors="1" warnings="0" skipped="0" time="2.5">
        <testcase name="testFailure" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="10" assertions="2" time="0.5">
            <failure type="PHPUnit\Framework\ExpectationFailedException">Assertion failed</failure>
        </testcase>
        <testcase name="testError" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="20" assertions="1" time="0.3">
            <error type="Error">Runtime error</error>
        </testcase>
        <testcase name="testSuccess" class="App\Tests\ExampleTest" file="/path/to/ExampleTest.php" line="30" assertions="2" time="0.4"/>
    </testsuite>
</testsuites>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(1, $result->summary['failures']);
        $this->assertSame(1, $result->summary['errors']);
        $this->assertCount(1, $result->failures);
        $this->assertCount(1, $result->errors);
        $this->assertFalse($result->wasSuccessful());
    }
}
