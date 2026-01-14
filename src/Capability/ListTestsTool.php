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

    #[McpTool(
        name: 'phpunit-list-tests',
        description: 'List all available PHPUnit tests in the project. Returns TOON-formatted list of test files, classes, and methods. Use for: discovering available tests, understanding test structure, finding tests to run. Optionally filter by directory.'
    )]
    public function execute(?string $directory = null): string
    {
        $directories = $directory ? [$directory] : $this->configDetector->getTestDirectories();

        $tests = $this->discovery->discoverTests($directories);

        return toon(['tests' => $tests]);
    }
}
