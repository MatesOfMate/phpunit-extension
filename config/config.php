<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use MatesOfMate\Common\Process\ProcessExecutor;
use MatesOfMate\Common\Truncator\MessageTruncator;
use MatesOfMate\PHPUnitExtension\Capability\ListTestsTool;
use MatesOfMate\PHPUnitExtension\Capability\RunTool;
use MatesOfMate\PHPUnitExtension\Config\ConfigurationDetector;
use MatesOfMate\PHPUnitExtension\Discovery\TestDiscovery;
use MatesOfMate\PHPUnitExtension\Formatter\ToonFormatter;
use MatesOfMate\PHPUnitExtension\Parser\JunitXmlParser;
use MatesOfMate\PHPUnitExtension\Runner\PhpunitRunner;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $container->parameters()->set('matesofmate_phpunit.custom_command', []);

    // Core infrastructure
    $services->set('matesofmate_phpunit.process_executor', ProcessExecutor::class)
        ->arg('$vendorPaths', ['%mate.root_dir%/vendor/bin/phpunit']);
    $services->set(PhpunitRunner::class)
        ->arg('$executor', service('matesofmate_phpunit.process_executor'))
        ->arg('$projectRoot', '%mate.root_dir%')
        ->arg('$customCommand', '%matesofmate_phpunit.custom_command%');

    $services->set(JunitXmlParser::class);
    $services->set(ToonFormatter::class);
    $services->set(MessageTruncator::class)
        ->arg('$prefixes', [
            'Failed asserting that ',
            'Expectation failed for ',
        ]);

    $services->set(ConfigurationDetector::class)
        ->arg('$projectRoot', '%mate.root_dir%');

    $services->set(TestDiscovery::class)
        ->arg('$projectRoot', '%mate.root_dir%');

    // Tools - automatically discovered by #[McpTool] attribute
    $services->set(RunTool::class);
    $services->set(ListTestsTool::class);
};
