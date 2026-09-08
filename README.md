# PHPUnit Extension for Symfony Mate

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

In current Mate setups, extension discovery is handled automatically after Composer install and update. Run `vendor/bin/mate discover` when you want to refresh discovery artifacts such as `mate/AGENT_INSTRUCTIONS.md`.

Useful Mate commands:

```bash
vendor/bin/mate debug:extensions
vendor/bin/mate debug:capabilities
vendor/bin/mate tools:list --extension=matesofmate/phpunit-extension
```

Run a tool directly:

```bash
vendor/bin/mate tools:call phpunit-run --mode=summary
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
- Symfony Mate 0.13+ required
- PHPUnit available locally, or a custom command configured

## Available Tools

- `phpunit-run`
- `phpunit-run-detail`
- `phpunit-list-tests`

All tools return encoded strings through Mate's core `ResponseEncoder`. Install the suggested `helgesverre/toon` package if you want TOON responses; otherwise the same payload falls back to JSON.

## Agent Skills

The extension ships Agent Skills that Mate installs into the project as `mate-<name>`:

- `phpunit-test-run`: choosing the scope of a run, reading failures and errors, and the limits of test discovery

```bash
vendor/bin/mate skills:list
```

## Output Modes

- `default`
- `summary`
- `detailed`

## Grouped Failures

A single broken method usually fails every test that touches it, so `phpunit-run`
reports failures grouped by cause instead of one entry per failing test. Each
group carries the number of tests it accounts for and one example test:

```
groups  [{"id":"g1","count":12,"type":"ExpectationFailedException",
          "summary":"Failed asserting that two arrays are identical.",
          "example":"InvoiceTest::testFormat"}]
next    phpunit-run-detail --id=20260906-105400-123456-a1b2c3 [--group=g1]
```

Grouping is by failure type and a normalised form of the message, so the same
assertion about different values or symbols lands in one group.

The full messages are kept on disk under Mate's cache directory, addressable by
the run id, and `phpunit-run-detail` reads them back without re-running the
suite. The last 20 runs are kept; storing the twenty-first drops the oldest.
Messages are shortened by removing unchanged diff context and vendor stack
frames rather than by cutting at a byte offset, which would keep the preamble
and discard the changed lines.

## Development

```bash
composer install
composer test
composer lint
composer fix
```

## License

MIT
