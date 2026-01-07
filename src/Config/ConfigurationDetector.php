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

class ConfigurationDetector
{
    public function __construct(
        private readonly string $projectRoot,
    ) {
    }

    public function detect(): ?string
    {
        $candidates = [
            'phpunit.xml',
            'phpunit.xml.dist',
            'phpunit.dist.xml',
        ];

        foreach ($candidates as $file) {
            $path = $this->projectRoot.'/'.$file;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
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

        $xml = simplexml_load_file($configPath);
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
