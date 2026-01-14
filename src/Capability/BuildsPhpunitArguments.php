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

/**
 * Provides helper methods for building PHPUnit command arguments.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
trait BuildsPhpunitArguments
{
    private readonly ConfigurationDetector $configDetector;

    /**
     * @return array<string>
     */
    private function buildPhpunitArgs(
        ?string $configuration = null,
        ?string $filter = null,
        ?string $file = null,
        bool $stopOnFailure = false,
    ): array {
        $args = [];

        $config = $configuration ?? $this->configDetector->detect();
        if ($config) {
            $args[] = '--configuration';
            $args[] = $config;
        }

        if ($filter) {
            $args[] = '--filter';
            $args[] = $filter;
        }

        if ($stopOnFailure) {
            $args[] = '--stop-on-failure';
        }

        if ($file) {
            $args[] = $file;
        }

        return $args;
    }
}
