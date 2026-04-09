# PHPUnit Extension for Symfony AI Mate

Token-efficient PHPUnit tools for AI assistants. This package runs tests and returns TOON-formatted responses designed for debugging and iteration.

## Features

- run the full suite, a file, or a single method
- list discoverable tests
- TOON output with summary and grouping modes
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
- Symfony AI Mate 0.6+ recommended
- PHPUnit available locally, or a custom command configured

## Available Tools

- `phpunit-run-suite`
- `phpunit-run-file`
- `phpunit-run-method`
- `phpunit-list-tests`

All tools return TOON-formatted strings in this package. That remains the intended package behavior even though upstream `symfony/ai` is exploring optional TOON with JSON fallback in PR `#1439`.

## Output Modes

- `default`
- `summary`
- `detailed`
- `by-file`
- `by-class`

## Development

```bash
composer install
composer test
composer lint
composer fix
```

## License

MIT
