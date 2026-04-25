# PHPUnit Extension for Symfony AI Mate

Token-efficient PHPUnit tools for AI assistants. This package runs tests and returns encoded structured responses designed for debugging and iteration.

## Features

- run the full suite, a file, a class, or a single method through one tool
- list discoverable tests
- encoded output with three consistent detail modes
- custom command support for containerized setups

## Installation

```bash
composer require --dev matesofmate/phpunit-extension
vendor/bin/mate init
```

In current AI Mate setups, extension discovery is handled automatically after Composer install and update. Run `vendor/bin/mate discover` when you want to refresh discovery artifacts such as `mate/AGENT_INSTRUCTIONS.md`.

Useful Mate commands:

```bash
vendor/bin/mate debug:extensions
vendor/bin/mate debug:capabilities
vendor/bin/mate mcp:tools:list --extension=matesofmate/phpunit-extension
```

Use the generated wrapper for Codex:

```bash
./bin/codex
```

## Custom Command Configuration

If PHPUnit must run through Docker or another wrapper command, configure `matesofmate_phpunit.custom_command`.

```php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('matesofmate_phpunit.custom_command', [
        'docker', 'compose', 'exec', 'php-test', 'vendor/bin/phpunit',
    ]);
};
```

## Requirements

- PHP 8.2+
- Symfony AI Mate 0.8+ required
- PHPUnit available locally, or a custom command configured

## Available Tools

- `phpunit-run`
- `phpunit-list-tests`

All tools return encoded strings through Mate's core `ResponseEncoder`. Install the suggested `helgesverre/toon` package if you want TOON responses; otherwise the same payload falls back to JSON.

## Output Modes

- `default`
- `summary`
- `detailed`

## Development

```bash
composer install
composer test
composer lint
composer fix
```

## License

MIT
