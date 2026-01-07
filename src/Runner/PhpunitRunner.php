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

use Symfony\Component\Process\Process;

class PhpunitRunner
{
    private readonly string $phpBinary;
    private readonly string $phpunitBinary;
    private readonly string $projectRoot;

    public function __construct(
        ?string $phpBinary = null,
        ?string $phpunitBinary = null,
        ?string $projectRoot = null,
    ) {
        $this->phpBinary = $phpBinary ?? \PHP_BINARY;
        $this->projectRoot = $projectRoot ?? (string) getcwd();
        $this->phpunitBinary = $phpunitBinary ?? $this->findPhpunitBinary();
    }

    /**
     * @param array<string> $args
     */
    public function run(array $args): RunResult
    {
        $tempJunitFile = tempnam(sys_get_temp_dir(), 'phpunit_junit_');

        $fullArgs = array_merge(
            [$this->phpBinary, $this->phpunitBinary],
            $args,
            ['--log-junit', $tempJunitFile]
        );

        $process = new Process(
            $fullArgs,
            $this->projectRoot,
            timeout: 300
        );

        $process->run();

        return new RunResult(
            exitCode: $process->getExitCode() ?? 1,
            output: $process->getOutput(),
            errorOutput: $process->getErrorOutput(),
            junitXmlPath: $tempJunitFile
        );
    }

    private function findPhpunitBinary(): string
    {
        $candidates = [
            $this->projectRoot.'/vendor/bin/phpunit',
            $this->projectRoot.'/vendor/phpunit/phpunit/phpunit',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('PHPUnit binary not found in vendor directory');
    }
}
