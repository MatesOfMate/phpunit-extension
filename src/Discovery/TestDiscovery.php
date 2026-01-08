<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Discovery;

use Symfony\Component\Finder\Finder;

/**
 * Discovers PHPUnit test files and methods in a project.
 *
 * @internal
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class TestDiscovery
{
    public function __construct(
        private readonly string $projectRoot,
    ) {
    }

    /**
     * @param array<string> $directories
     *
     * @return array<int, array<string, string>>
     */
    public function discoverTests(array $directories): array
    {
        $tests = [];

        foreach ($directories as $dir) {
            $fullPath = $this->projectRoot.'/'.ltrim((string) $dir, '/');

            if (!is_dir($fullPath)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->name('*Test.php')->in($fullPath);

            foreach ($finder as $file) {
                $tests = array_merge($tests, $this->parseTestFile($file->getRealPath()));
            }
        }

        return $tests;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseTestFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            return [];
        }

        $tests = [];

        if (!preg_match('/class\s+(\w+)\s+extends/', $content, $classMatch)) {
            return [];
        }

        $className = $classMatch[1];

        $namespace = '';
        if (preg_match('/namespace\s+([\w\\\\]+);/', $content, $nsMatch)) {
            $namespace = $nsMatch[1];
        }

        $fqcn = '' !== $namespace && '0' !== $namespace ? $namespace.'\\'.$className : $className;

        preg_match_all('/public\s+function\s+(test\w+)\s*\(/', $content, $methodMatches);

        foreach ($methodMatches[1] as $method) {
            $tests[] = [
                'file' => str_replace($this->projectRoot.'/', '', $filePath),
                'class' => $fqcn,
                'method' => $method,
            ];
        }

        return $tests;
    }
}
