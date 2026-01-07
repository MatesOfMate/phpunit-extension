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
use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use Mcp\Capability\Attribute\McpTool;

class RunMethodTool
{
    public function __construct(
        private readonly PhpunitRunner $runner,
        private readonly JunitXmlParser $parser,
        private readonly ToonFormatter $formatter,
        private readonly ConfigurationDetector $configDetector,
    ) {
    }

    #[McpTool(
        name: 'phpunit_run_method',
        description: 'Run a single PHPUnit test method. Returns TOON-formatted results. Use for: debugging a specific failing test, verifying a single test fix, isolated test execution. Example: phpunit_run_method("App\\Tests\\UserServiceTest", "testCreateUser")'
    )]
    public function execute(
        string $class,
        string $method,
    ): string {
        $filter = \sprintf('%s::%s$', preg_quote($class, '/'), preg_quote($method, '/'));

        $args = $this->buildArgs($filter);

        $runResult = $this->runner->run($args);
        $testResult = $this->parser->parse($runResult->getJunitXml());
        $output = $this->formatter->format($testResult);

        $runResult->cleanup();

        return $output;
    }

    /**
     * @return array<string>
     */
    private function buildArgs(string $filter): array
    {
        $args = [];

        $configPath = $this->configDetector->detect();
        if ($configPath) {
            $args[] = '--configuration';
            $args[] = $configPath;
        }

        $args[] = '--filter';
        $args[] = $filter;

        return $args;
    }
}
