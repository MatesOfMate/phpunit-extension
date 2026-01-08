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

/**
 * Runs the full PHPUnit test suite with token-optimized output.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunSuiteTool
{
    use BuildsPhpunitArguments;

    public function __construct(
        private readonly PhpunitRunner $runner,
        private readonly JunitXmlParser $parser,
        private readonly ToonFormatter $formatter,
        private readonly ConfigurationDetector $configDetector,
    ) {
    }

    #[McpTool(
        name: 'phpunit-run-suite',
        description: 'Run the full PHPUnit test suite. Returns token-optimized TOON format. Available modes: "default" (summary + failures/errors with truncated messages), "summary" (just totals and status), "detailed" (full error messages without truncation), "by-file" (errors grouped by file path), "by-class" (errors grouped by test class). Use for: running all tests, CI validation, checking overall test health.'
    )]
    public function execute(
        ?string $configuration = null,
        ?string $filter = null,
        bool $stopOnFailure = false,
        string $mode = 'default',
    ): string {
        $args = $this->buildPhpunitArgs(
            configuration: $configuration,
            filter: $filter,
            stopOnFailure: $stopOnFailure,
        );

        $runResult = $this->runner->run($args);
        $testResult = $this->parser->parse($runResult->getJunitXml());
        $output = $this->formatter->format($testResult, $mode);

        $runResult->cleanup();

        return $output;
    }
}
