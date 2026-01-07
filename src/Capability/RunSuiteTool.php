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

class RunSuiteTool
{
    public function __construct(
        private readonly PhpunitRunner $runner,
        private readonly JunitXmlParser $parser,
        private readonly ToonFormatter $formatter,
        private readonly ConfigurationDetector $configDetector,
    ) {
    }

    #[McpTool(
        name: 'phpunit_run_suite',
        description: 'Run the full PHPUnit test suite. Returns results in token-optimized TOON format showing summary (tests/passed/failed/errors/time), failures, and errors. Use for: running all tests, CI validation, checking overall test health.'
    )]
    public function execute(
        ?string $configuration = null,
        ?string $filter = null,
        bool $stopOnFailure = false,
    ): string {
        $args = $this->buildArgs($configuration, $filter, $stopOnFailure);

        $runResult = $this->runner->run($args);
        $testResult = $this->parser->parse($runResult->getJunitXml());
        $output = $this->formatter->format($testResult);

        $runResult->cleanup();

        return $output;
    }

    /**
     * @return array<string>
     */
    private function buildArgs(?string $config, ?string $filter, bool $stopOnFailure): array
    {
        $args = [];

        $configPath = $config ?? $this->configDetector->detect();
        if ($configPath) {
            $args[] = '--configuration';
            $args[] = $configPath;
        }

        if ($filter) {
            $args[] = '--filter';
            $args[] = $filter;
        }

        if ($stopOnFailure) {
            $args[] = '--stop-on-failure';
        }

        return $args;
    }
}
