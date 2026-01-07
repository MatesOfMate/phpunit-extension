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
        return 0 === $this->summary['failures'] && 0 === $this->summary['errors'];
    }

    public function getPassed(): int
    {
        return (int) ($this->summary['tests']
            - $this->summary['failures']
            - $this->summary['errors']
            - $this->summary['skipped']);
    }
}
