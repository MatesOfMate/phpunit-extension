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
use Symfony\Component\Process\Process;

/**
 * Runs PHPUnit tests and generates JUnit XML output.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class PhpunitRunner
{
    /**
     * @param array<int, string> $customCommand
     */
    public function __construct(
        private readonly ProcessExecutor $executor,
        private readonly ?string $projectRoot = null,
        private readonly array $customCommand = [],
    ) {
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): RunResult
    {
        if ([] !== $this->customCommand) {
            return $this->runCustomCommand($args);
        }

        return $this->runDefault($args);
    }

    /**
     * @param array<int, string> $args
     */
    private function runDefault(array $args): RunResult
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

    /**
     * @param array<int, string> $args
     */
    private function runCustomCommand(array $args): RunResult
    {
        $projectRoot = $this->projectRoot ?? (getcwd() ?: '.');

        $varDir = $projectRoot.'/var';
        if (file_exists($varDir) && !is_dir($varDir)) {
            throw new \RuntimeException(\sprintf('Failed to create PHPUnit JUnit directory: %s', $varDir));
        }

        if (!is_dir($varDir) && !mkdir($varDir, 0777, true) && !is_dir($varDir)) {
            throw new \RuntimeException(\sprintf('Failed to create PHPUnit JUnit directory: %s', $varDir));
        }

        $filename = 'phpunit_junit_'.bin2hex(random_bytes(8)).'.xml';
        $hostPath = $varDir.'/'.$filename;
        $relativePath = 'var/'.$filename;

        $command = [...$this->customCommand, '--log-junit', $relativePath, ...$args];

        $process = new Process($command, $projectRoot);
        $process->setTimeout(300);
        $process->run();

        return new RunResult(
            exitCode: $process->getExitCode() ?? 1,
            output: $process->getOutput(),
            errorOutput: $process->getErrorOutput(),
            junitXmlPath: $hostPath
        );
    }
}
