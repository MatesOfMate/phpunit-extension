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

use MatesOfMate\Common\Process\ProcessExecutor as CommonProcessExecutor;
use MatesOfMate\Common\Process\ProcessExecutorInterface;
use MatesOfMate\Common\Process\ProcessResult;

/**
 * Executes PHPUnit commands with automatic binary detection.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class PhpunitProcessExecutor implements ProcessExecutorInterface
{
    private readonly CommonProcessExecutor $executor;

    public function __construct()
    {
        $cwd = getcwd();
        $vendorPaths = false !== $cwd ? [
            $cwd.'/vendor/bin/phpunit',
        ] : [];

        $this->executor = new CommonProcessExecutor($vendorPaths);
    }

    public function execute(string $binaryName, array $args = [], int $timeout = 300, bool $usePhpBinary = true): ProcessResult
    {
        return $this->executor->execute($binaryName, $args, $timeout, $usePhpBinary);
    }
}
