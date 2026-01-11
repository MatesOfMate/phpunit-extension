<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Runner;

use MatesOfMate\Common\Process\ProcessExecutor;

/**
 * Runs PHPUnit tests and generates JUnit XML output.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class PhpunitRunner
{
    public function __construct(
        private readonly ProcessExecutor $executor,
    ) {
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): RunResult
    {
        $tempJunitFile = tempnam(sys_get_temp_dir(), 'phpunit_junit_');
        if (false === $tempJunitFile) {
            throw new \RuntimeException('Failed to create temporary file for JUnit XML');
        }

        $result = $this->executor->execute('phpunit', [...$args, '--log-junit', $tempJunitFile], timeout: 300);

        return new RunResult(
            exitCode: $result->exitCode,
            output: $result->output,
            errorOutput: $result->errorOutput,
            junitXmlPath: $tempJunitFile
        );
    }
}
