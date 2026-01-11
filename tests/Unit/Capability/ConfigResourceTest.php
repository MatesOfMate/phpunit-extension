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

use MatesOfMate\PHPUnitExtension\Capability\ConfigResource;
use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use PHPUnit\Framework\TestCase;

class ConfigResourceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/phpunit-config-resource-test-'.uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            if (false !== $files) {
                array_map(unlink(...), $files);
            }
            rmdir($this->tempDir);
        }
    }

    public function testGetConfigurationWithExistingConfig(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml';
        file_put_contents($configPath, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
XML
        );

        $originalCwd = getcwd();
        chdir($this->tempDir);

        try {
            $detector = new ConfigurationDetector($this->tempDir);
            $resource = new ConfigResource($detector);

            $result = $resource->getConfiguration();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('uri', $result);
            $this->assertArrayHasKey('mimeType', $result);
            $this->assertArrayHasKey('text', $result);

            $this->assertSame('phpunit://config', $result['uri']);
            $this->assertSame('text/plain', $result['mimeType']);
            $this->assertIsString($result['text']);

            // Verify the text contains expected data
            $this->assertStringContainsString('project_root', $result['text']);
            $this->assertStringContainsString('config_file', $result['text']);
            $this->assertStringContainsString('config_exists', $result['text']);
            $this->assertStringContainsString('test_directories', $result['text']);
            $this->assertStringContainsString('bootstrap_file', $result['text']);
            $this->assertStringContainsString('config_content', $result['text']);
            $this->assertStringContainsString('vendor/autoload.php', $result['text']);
        } finally {
            if (false !== $originalCwd) {
                chdir($originalCwd);
            }
        }
    }

    public function testGetConfigurationWithoutConfig(): void
    {
        $originalCwd = getcwd();
        chdir($this->tempDir);

        try {
            $detector = new ConfigurationDetector($this->tempDir);
            $resource = new ConfigResource($detector);

            $result = $resource->getConfiguration();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('uri', $result);
            $this->assertArrayHasKey('mimeType', $result);
            $this->assertArrayHasKey('text', $result);

            $this->assertSame('phpunit://config', $result['uri']);
            $this->assertSame('text/plain', $result['mimeType']);
            $this->assertIsString($result['text']);

            // Verify the text contains expected data
            $this->assertStringContainsString('project_root', $result['text']);
            $this->assertStringContainsString('config_file', $result['text']);
            $this->assertStringContainsString('config_exists', $result['text']);
        } finally {
            if (false !== $originalCwd) {
                chdir($originalCwd);
            }
        }
    }

    public function testReturnStructure(): void
    {
        $originalCwd = getcwd();
        chdir($this->tempDir);

        try {
            $detector = new ConfigurationDetector($this->tempDir);
            $resource = new ConfigResource($detector);

            $result = $resource->getConfiguration();

            $this->assertIsArray($result);
            $this->assertCount(3, $result);
            $this->assertArrayHasKey('uri', $result);
            $this->assertArrayHasKey('mimeType', $result);
            $this->assertArrayHasKey('text', $result);
        } finally {
            if (false !== $originalCwd) {
                chdir($originalCwd);
            }
        }
    }

    public function testToonFormatting(): void
    {
        $originalCwd = getcwd();
        chdir($this->tempDir);

        try {
            $detector = new ConfigurationDetector($this->tempDir);
            $resource = new ConfigResource($detector);

            $result = $resource->getConfiguration();

            // TOON format should be compact and structured
            $this->assertIsString($result['text']);
            $this->assertNotEmpty($result['text']);

            // TOON format produces human-readable text, not JSON
            $this->assertStringNotContainsString('{', $result['text']);
            $this->assertStringNotContainsString('[', $result['text']);
        } finally {
            if (false !== $originalCwd) {
                chdir($originalCwd);
            }
        }
    }

    public function testBootstrapExtraction(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml';
        file_put_contents($configPath, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
XML
        );

        $originalCwd = getcwd();
        chdir($this->tempDir);

        try {
            $detector = new ConfigurationDetector($this->tempDir);
            $resource = new ConfigResource($detector);

            $result = $resource->getConfiguration();

            $this->assertStringContainsString('tests/bootstrap.php', $result['text']);
        } finally {
            if (false !== $originalCwd) {
                chdir($originalCwd);
            }
        }
    }
}
