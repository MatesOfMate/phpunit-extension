<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Parser;

/**
 * Structured test result data parsed from JUnit XML.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
readonly class TestResult
{
    /**
     * @param array<string, int|float>         $summary
     * @param array<int, array<string, mixed>> $failures
     * @param array<int, array<string, mixed>> $errors
     */
    public function __construct(
        public array $summary,
        public array $failures,
        public array $errors,
    ) {
    }

    public function wasSuccessful(): bool
    {
        return 0 === $this->getFailed() && 0 === $this->getErrors();
    }

    public function getPassed(): int
    {
        return $this->getTests() - $this->getFailed() - $this->getErrors() - $this->getSkipped();
    }

    public function getTests(): int
    {
        return (int) $this->summary['tests'];
    }

    public function getFailed(): int
    {
        return (int) $this->summary['failures'];
    }

    public function getErrors(): int
    {
        return (int) $this->summary['errors'];
    }

    public function getWarnings(): int
    {
        return (int) $this->summary['warnings'];
    }

    public function getSkipped(): int
    {
        return (int) $this->summary['skipped'];
    }

    public function getTime(): float
    {
        return (float) $this->summary['time'];
    }
}
