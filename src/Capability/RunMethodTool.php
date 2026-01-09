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
 * Runs a single PHPUnit test method with token-optimized output.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunMethodTool
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
        name: 'phpunit-run-method',
        description: 'Run a single PHPUnit test method. Returns token-optimized TOON format. Available modes: "default" (summary + failure/error details), "summary" (just totals and status), "detailed" (full error messages without truncation). Use for: debugging a specific failing test, verifying a single test fix, isolated test execution.'
    )]
    public function execute(
        ?string $class = null,
        ?string $method = null,
        string $mode = 'default',
    ): string {
        if (null === $class) {
            throw new \InvalidArgumentException('The "class" parameter is required for phpunit-run-method tool.');
        }
        if (null === $method) {
            throw new \InvalidArgumentException('The "method" parameter is required for phpunit-run-method tool.');
        }

        $filter = \sprintf('%s::%s$', preg_quote($class, '/'), preg_quote($method, '/'));

        $args = $this->buildPhpunitArgs(filter: $filter);

        $runResult = $this->runner->run($args);
        $testResult = $this->parser->parse($runResult->getJunitXml());
        $output = $this->formatter->format($testResult, $mode);

        $runResult->cleanup();

        return $output;
    }
}
