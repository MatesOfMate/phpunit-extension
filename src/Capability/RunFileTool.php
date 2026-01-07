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

class RunFileTool
{
    public function __construct(
        private readonly PhpunitRunner $runner,
        private readonly JunitXmlParser $parser,
        private readonly ToonFormatter $formatter,
        private readonly ConfigurationDetector $configDetector,
    ) {
    }

    #[McpTool(
        name: 'phpunit_run_file',
        description: 'Run PHPUnit tests from a specific file. Returns TOON-formatted results. Use for: testing changes to a single test file, debugging specific test class, focused test execution. Example: phpunit_run_file("tests/Service/UserServiceTest.php")'
    )]
    public function execute(
        string $file,
        ?string $filter = null,
        bool $stopOnFailure = false,
    ): string {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Test file not found: {$file}");
        }

        $args = $this->buildArgs($file, $filter, $stopOnFailure);

        $runResult = $this->runner->run($args);
        $testResult = $this->parser->parse($runResult->getJunitXml());
        $output = $this->formatter->format($testResult);

        $runResult->cleanup();

        return $output;
    }

    /**
     * @return array<string>
     */
    private function buildArgs(string $file, ?string $filter, bool $stopOnFailure): array
    {
        $args = [];

        $configPath = $this->configDetector->detect();
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

        $args[] = $file;

        return $args;
    }
}
