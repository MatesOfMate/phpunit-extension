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

use MatesOfMate\Common\Cache\RunCache;
use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Grouping\FailureGrouper;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use Symfony\AI\Mate\Attribute\MateTool;

/**
 * Runs PHPUnit using a single flexible entrypoint.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunTool
{
    use BuildsPhpunitArguments;

    public function __construct(
        private readonly PhpunitRunner $runner,
        private readonly JunitXmlParser $parser,
        private readonly ToonFormatter $formatter,
        private readonly ConfigurationDetector $configDetector,
        private readonly FailureGrouper $grouper,
        private readonly RunCache $cache,
    ) {
    }

    /**
     * @param string|null $file          run only a specific test file when provided
     * @param string|null $class         run a specific test class when provided
     * @param string|null $method        Run a specific test method. Requires the class parameter.
     * @param string|null $filter        apply a raw PHPUnit filter expression
     * @param string|null $configuration Path to the PHPUnit configuration file. Defaults to auto-detection.
     * @param bool        $stopOnFailure stop after the first failure
     * @param string      $mode          output detail level: default, summary, or detailed
     */
    #[MateTool(name: 'phpunit-run', title: 'PHPUnit Run', description: 'Run PHPUnit tests. Use this for the full suite, a single test file, a class, or a specific test method.')]
    public function execute(
        ?string $file = null,
        ?string $class = null,
        ?string $method = null,
        ?string $filter = null,
        ?string $configuration = null,
        bool $stopOnFailure = false,
        string $mode = 'default',
    ): string {
        if (null !== $method && null === $class) {
            throw new \InvalidArgumentException('The "class" parameter is required when "method" is provided.');
        }

        if (null !== $file && !file_exists($file)) {
            throw new \InvalidArgumentException("Test file not found: {$file}");
        }

        $resolvedFilter = $this->buildFilter(
            class: $class,
            method: $method,
            filter: $filter,
        );

        $args = $this->buildPhpunitArgs(
            configuration: $configuration,
            filter: $resolvedFilter,
            file: $file,
            stopOnFailure: $stopOnFailure,
        );

        $runResult = $this->runner->run($args);

        try {
            try {
                $testResult = $this->parser->parse($runResult->getJunitXml());

                $groups = $this->grouper->group($this->entriesOf($testResult));
                $runId = $this->remember($groups, $testResult);

                return $this->formatter->format($testResult, $mode, $runId, $groups);
            } catch (\Throwable) {
                $output = $runResult->output;
                if ('' !== $runResult->errorOutput) {
                    $output .= "\n\n".$runResult->errorOutput;
                }

                return $output;
            }
        } finally {
            $runResult->cleanup();
        }
    }

    private function buildFilter(?string $class, ?string $method, ?string $filter): ?string
    {
        if (null !== $class && null !== $method) {
            $specificMethodFilter = \sprintf('%s::%s$', preg_quote($class, '/'), preg_quote($method, '/'));

            return null !== $filter && '' !== $filter
                ? \sprintf('(%s)|(%s)', $specificMethodFilter, $filter)
                : $specificMethodFilter;
        }

        if (null !== $class) {
            $classFilter = \sprintf('%s(::.*)?$', preg_quote($class, '/'));

            return null !== $filter && '' !== $filter
                ? \sprintf('(%s)|(%s)', $classFilter, $filter)
                : $classFilter;
        }

        return $filter;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entriesOf(\MatesOfMate\PHPUnitExtension\Parser\TestResult $result): array
    {
        return array_merge($result->failures, $result->errors);
    }

    /**
     * Caching is a convenience, not part of the answer. A cache that cannot be
     * written must cost the agent a run id, not the whole formatted response:
     * the catch-all above would otherwise turn an unwritable directory into a
     * raw dump of the entire PHPUnit output.
     *
     * @param array<int, array<string, mixed>> $groups
     */
    private function remember(array $groups, \MatesOfMate\PHPUnitExtension\Parser\TestResult $result): ?string
    {
        if ([] === $groups) {
            return null;
        }

        try {
            return $this->cache->store([
                'summary' => $result->summary,
                'groups' => $groups,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
