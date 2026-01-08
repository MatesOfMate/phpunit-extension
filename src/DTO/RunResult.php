<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\DTO;

/**
 * Result of a PHPUnit test run with JUnit XML output path.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
readonly class RunResult
{
    public function __construct(
        public int $exitCode,
        public string $output,
        public string $errorOutput,
        public string $junitXmlPath,
    ) {
    }

    public function wasSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }

    public function getJunitXml(): string
    {
        if (!file_exists($this->junitXmlPath)) {
            return '';
        }

        return (string) file_get_contents($this->junitXmlPath);
    }

    public function cleanup(): void
    {
        if (file_exists($this->junitXmlPath)) {
            @unlink($this->junitXmlPath);
        }
    }
}
