<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use MatesOfMate\PHPUnitExtension\Capability\ListTestsTool;
use MatesOfMate\PHPUnitExtension\Capability\RunFileTool;
use MatesOfMate\PHPUnitExtension\Capability\RunMethodTool;
use MatesOfMate\PHPUnitExtension\Capability\RunSuiteTool;
use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use MatesOfMate\PHPUnitExtension\Discovery\TestDiscovery;
use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Core infrastructure
    $services->set(PhpunitRunner::class)
        ->arg('$projectRoot', '%kernel.project_dir%');

    $services->set(JunitXmlParser::class);
    $services->set(ToonFormatter::class);

    $services->set(ConfigurationDetector::class)
        ->arg('$projectRoot', '%kernel.project_dir%');

    $services->set(TestDiscovery::class)
        ->arg('$projectRoot', '%kernel.project_dir%');

    // Tools - automatically discovered by #[McpTool] attribute
    $services->set(RunSuiteTool::class);
    $services->set(RunFileTool::class);
    $services->set(RunMethodTool::class);
    $services->set(ListTestsTool::class);
};
