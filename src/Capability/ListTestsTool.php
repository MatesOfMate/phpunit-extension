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
use MatesOfMate\PHPUnitExtension\Discovery\TestDiscovery;
use Mcp\Capability\Attribute\McpTool;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

/**
 * Lists all available PHPUnit tests in the project.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ListTestsTool
{
    public function __construct(
        private readonly TestDiscovery $discovery,
        private readonly ConfigurationDetector $configDetector,
    ) {
    }

    /**
     * @param string|null $directory Limit discovery to a specific directory. Defaults to detected test directories.
     */
    #[McpTool(
        name: 'phpunit-list-tests',
        description: 'List discoverable PHPUnit tests so the AI can find files, classes, and methods to run.'
    )]
    public function execute(?string $directory = null): string
    {
        $directories = $directory ? [$directory] : $this->configDetector->getTestDirectories();

        $tests = $this->discovery->discoverTests($directories);

        return ResponseEncoder::encode(['tests' => $tests]);
    }
}
