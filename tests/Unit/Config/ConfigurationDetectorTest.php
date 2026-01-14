<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Config;

use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ConfigurationDetectorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/phpunit_config_test_'.uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testDetectFindsPhpunitXml(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml';
        file_put_contents($configPath, '<?xml version="1.0"?><phpunit></phpunit>');

        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame($configPath, $detector->detect());
    }

    public function testDetectFindsPhpunitXmlDist(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml.dist';
        file_put_contents($configPath, '<?xml version="1.0"?><phpunit></phpunit>');

        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame($configPath, $detector->detect());
    }

    public function testDetectFindsPhpunitDistXml(): void
    {
        $configPath = $this->tempDir.'/phpunit.dist.xml';
        file_put_contents($configPath, '<?xml version="1.0"?><phpunit></phpunit>');

        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame($configPath, $detector->detect());
    }

    public function testDetectPrefersPhpunitXmlOverDist(): void
    {
        $phpunitXml = $this->tempDir.'/phpunit.xml';
        $phpunitXmlDist = $this->tempDir.'/phpunit.xml.dist';

        file_put_contents($phpunitXml, '<?xml version="1.0"?><phpunit></phpunit>');
        file_put_contents($phpunitXmlDist, '<?xml version="1.0"?><phpunit></phpunit>');

        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame($phpunitXml, $detector->detect());
    }

    public function testDetectReturnsNullWhenNoConfigFound(): void
    {
        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertNull($detector->detect());
    }

    public function testGetTestDirectoriesReturnsDefaultWhenNoConfigFound(): void
    {
        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame(['tests'], $detector->getTestDirectories());
    }

    public function testGetTestDirectoriesExtractsFromConfig(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml';
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpunit>
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
XML;
        file_put_contents($configPath, $xml);

        $detector = new ConfigurationDetector($this->tempDir);
        $directories = $detector->getTestDirectories();

        $this->assertContains('tests/Unit', $directories);
        $this->assertContains('tests/Integration', $directories);
    }

    public function testGetTestDirectoriesReturnsDefaultWhenXmlParsingFails(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml';
        file_put_contents($configPath, 'invalid xml content');

        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame(['tests'], $detector->getTestDirectories());
    }

    public function testGetTestDirectoriesReturnsDefaultWhenNoDirectoriesFound(): void
    {
        $configPath = $this->tempDir.'/phpunit.xml';
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpunit>
    <testsuites>
        <testsuite name="unit">
        </testsuite>
    </testsuites>
</phpunit>
XML;
        file_put_contents($configPath, $xml);

        $detector = new ConfigurationDetector($this->tempDir);

        $this->assertSame(['tests'], $detector->getTestDirectories());
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
