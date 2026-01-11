<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Capability;

use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use Mcp\Capability\Attribute\McpResource;

/**
 * Provides PHPUnit configuration information as an MCP resource.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ConfigResource
{
    public function __construct(
        private readonly ConfigurationDetector $configDetector,
    ) {
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    #[McpResource(
        uri: 'phpunit://config',
        name: 'phpunit-configuration',
        description: 'PHPUnit project configuration details in TOON format. Provides project root, config file path, test directories, bootstrap file, and full config content. Use for: understanding project setup, checking test configuration, locating test directories, troubleshooting configuration issues.',
        mimeType: 'text/plain',
    )]
    public function getConfiguration(): array
    {
        $projectRoot = getcwd();
        if (false === $projectRoot) {
            throw new \RuntimeException('Unable to determine current working directory');
        }

        $configPath = $this->configDetector->detect($projectRoot);

        $data = [
            'project_root' => $projectRoot,
            'config_file' => $configPath,
            'config_exists' => null !== $configPath,
        ];

        if (null !== $configPath) {
            $data['test_directories'] = $this->configDetector->getTestDirectories();
            $data['bootstrap_file'] = $this->extractBootstrapFile($configPath);
            $data['config_content'] = file_exists($configPath) ? file_get_contents($configPath) : null;
        }

        return [
            'uri' => 'phpunit://config',
            'mimeType' => 'text/plain',
            'text' => toon($data),
        ];
    }

    private function extractBootstrapFile(string $configPath): ?string
    {
        $xml = @simplexml_load_file($configPath);
        if (false === $xml) {
            return null;
        }

        $bootstrap = $xml->attributes()['bootstrap'] ?? null;

        return null !== $bootstrap ? (string) $bootstrap : null;
    }
}
