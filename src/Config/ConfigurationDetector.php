<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Config;

use MatesOfMate\Common\Config\ConfigurationDetector as CommonConfigurationDetector;

/**
 * Detects PHPUnit configuration files and extracts test directories.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ConfigurationDetector
{
    private readonly CommonConfigurationDetector $detector;

    public function __construct(
        private readonly string $projectRoot,
    ) {
        $this->detector = new CommonConfigurationDetector([
            'phpunit.xml',
            'phpunit.xml.dist',
            'phpunit.dist.xml',
        ]);
    }

    public function detect(?string $projectRoot = null): ?string
    {
        return $this->detector->detect($projectRoot ?: $this->projectRoot);
    }

    /**
     * @return array<string>
     */
    public function getTestDirectories(): array
    {
        $configPath = $this->detect();

        if (!$configPath) {
            return ['tests'];
        }

        $xml = @simplexml_load_file($configPath);
        if (false === $xml) {
            return ['tests'];
        }

        $directories = [];
        $result = $xml->xpath('//directory');

        if (!\is_array($result)) {
            return ['tests'];
        }

        foreach ($result as $dir) {
            $directories[] = (string) $dir;
        }

        return $directories ?: ['tests'];
    }
}
