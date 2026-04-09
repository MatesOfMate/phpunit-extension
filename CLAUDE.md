# CLAUDE.md

Guidance for working on the PHPUnit extension.

## Overview

This package provides TOON-first PHPUnit execution and discovery tools for Symfony AI Mate.

## Current Mate Workflow

- initialize projects with `vendor/bin/mate init`
- current Mate setups auto-discover extensions after install and update
- `vendor/bin/mate discover` refreshes extension state and generated instructions
- `./bin/codex` is the correct Codex entrypoint
- `vendor/bin/mate debug:extensions` and `vendor/bin/mate debug:capabilities` are the primary troubleshooting commands

## Structure

- `src/Capability/` contains the tools
- `src/Runner/` executes PHPUnit
- `src/Parser/` parses JUnit XML
- `src/Formatter/` produces TOON output
- `src/Discovery/` lists tests
- `config/config.php` registers services

## Output Strategy

- This package intentionally returns TOON-formatted strings.
- Upstream `symfony/ai` PR `#1439` introduces an optional encoder direction, but this package currently stays explicitly TOON-first.

## Service Registration

Use `config/config.php`, not `config/services.php`.

## Commands

```bash
composer install
composer test
composer lint
composer fix
vendor/bin/mate mcp:tools:list --extension=matesofmate/phpunit-extension
```

## Standards

- no `declare(strict_types=1)` by project convention
- non-final classes by project convention
- docs must match actual tool names and output modes
