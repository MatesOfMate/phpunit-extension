# CLAUDE.md

Guidance for working on the PHPUnit extension.

## Overview

This package provides PHPUnit execution and discovery tools for Symfony AI Mate using Mate's core response encoder.

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
- `src/Formatter/` produces encoded MCP output
- `src/Discovery/` lists tests
- `config/config.php` registers services

## Output Strategy

- This package returns encoded strings through Mate's core `ResponseEncoder`.
- Describe TOON as optional runtime behavior with JSON fallback.

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
