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
 * Runs PHPUnit tests from a specific file with token-optimized output.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunFileTool
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
        name: 'phpunit-run-file',
        description: 'Run PHPUnit tests from a specific file. Returns token-optimized TOON format. Available modes: "default" (summary + failures/errors), "summary" (just totals and status), "detailed" (full error messages without truncation). Use for: testing changes to a single test file, debugging specific test class, focused test execution.'
    )]
    public function execute(
        ?string $file = null,
        ?string $filter = null,
        bool $stopOnFailure = false,
        string $mode = 'default',
    ): string {
        if (null === $file) {
            throw new \InvalidArgumentException('The "file" parameter is required for phpunit-run-file tool.');
        }

        $args = $this->buildPhpunitArgs(
            filter: $filter,
            file: $file,
            stopOnFailure: $stopOnFailure,
        );

        $runResult = $this->runner->run($args);
        $testResult = $this->parser->parse($runResult->getJunitXml());
        $output = $this->formatter->format($testResult, $mode);

        $runResult->cleanup();

        return $output;
    }
}
