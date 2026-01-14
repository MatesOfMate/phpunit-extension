<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Discovery;

use MatesOfMate\PHPUnitExtension\Discovery\TestDiscovery;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class TestDiscoveryTest extends TestCase
{
    private string $tempDir;
    private string $testDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/phpunit_discovery_test_'.uniqid();
        $this->testDir = $this->tempDir.'/tests';
        mkdir($this->testDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testDiscoverTestsFindsTestFiles(): void
    {
        $this->createTestFile('ExampleTest.php', 'ExampleTest', 'testExample');

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        $this->assertCount(1, $tests);
        $this->assertSame('App\\Tests\\ExampleTest', $tests[0]['class']);
        $this->assertSame('testExample', $tests[0]['method']);
        $this->assertStringContainsString('tests/ExampleTest.php', $tests[0]['file']);
    }

    public function testDiscoverTestsFindsMultipleTestMethods(): void
    {
        $this->createTestFile('UserTest.php', 'UserTest', ['testCreate', 'testUpdate', 'testDelete']);

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        $this->assertCount(3, $tests);
        $this->assertSame('testCreate', $tests[0]['method']);
        $this->assertSame('testUpdate', $tests[1]['method']);
        $this->assertSame('testDelete', $tests[2]['method']);
    }

    public function testDiscoverTestsHandlesMultipleTestFiles(): void
    {
        $this->createTestFile('UserTest.php', 'UserTest', 'testCreate');
        $this->createTestFile('ProductTest.php', 'ProductTest', 'testList');

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        $this->assertCount(2, $tests);
    }

    public function testDiscoverTestsReturnsEmptyArrayWhenNoTestsFound(): void
    {
        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        $this->assertSame([], $tests);
    }

    public function testDiscoverTestsHandlesNonExistentDirectory(): void
    {
        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['nonexistent']);

        $this->assertSame([], $tests);
    }

    public function testDiscoverTestsHandlesMultipleDirectories(): void
    {
        mkdir($this->tempDir.'/tests/Unit', 0777, true);
        mkdir($this->tempDir.'/tests/Integration', 0777, true);

        $this->createTestFile('Unit/UserTest.php', 'UserTest', 'testCreate');
        $this->createTestFile('Integration/ApiTest.php', 'ApiTest', 'testEndpoint');

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests/Unit', 'tests/Integration']);

        $this->assertCount(2, $tests);
    }

    public function testDiscoverTestsIgnoresNonTestFiles(): void
    {
        // Create a non-test file
        $nonTestFile = $this->testDir.'/Helper.php';
        file_put_contents($nonTestFile, '<?php namespace App\\Tests; class Helper {}');

        // Create a test file
        $this->createTestFile('ExampleTest.php', 'ExampleTest', 'testExample');

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        // Should only find the test file, not Helper.php
        $this->assertCount(1, $tests);
        $this->assertStringContainsString('ExampleTest', $tests[0]['class']);
    }

    public function testDiscoverTestsHandlesFileWithoutTestMethods(): void
    {
        $testFile = $this->testDir.'/EmptyTest.php';
        file_put_contents($testFile, <<<'PHP_WRAP'
        <?php
        namespace App\Tests;
        use PHPUnit\Framework\TestCase;
        class EmptyTest extends TestCase {}
        PHP_WRAP
        );

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        $this->assertSame([], $tests);
    }

    public function testDiscoverTestsHandlesFileWithoutNamespace(): void
    {
        $testFile = $this->testDir.'/NoNamespaceTest.php';
        file_put_contents($testFile, <<<'PHP_WRAP'
        <?php
        use PHPUnit\Framework\TestCase;
        class NoNamespaceTest extends TestCase {
            public function testSomething() {}
        }
        PHP_WRAP
        );

        $discovery = new TestDiscovery($this->tempDir);
        $tests = $discovery->discoverTests(['tests']);

        $this->assertCount(1, $tests);
        $this->assertSame('NoNamespaceTest', $tests[0]['class']);
    }

    /**
     * @param string|array<string> $methods
     */
    private function createTestFile(string $filename, string $className, string|array $methods): void
    {
        $filepath = $this->testDir.'/'.$filename;
        $dir = \dirname($filepath);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $methodsArray = \is_array($methods) ? $methods : [$methods];
        $methodsCode = '';

        foreach ($methodsArray as $method) {
            $methodsCode .= "    public function {$method}() {}\n";
        }

        $content = <<<PHP
<?php
namespace App\\Tests;
use PHPUnit\\Framework\\TestCase;
class {$className} extends TestCase {
{$methodsCode}
}
PHP;

        file_put_contents($filepath, $content);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
