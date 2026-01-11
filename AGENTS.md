# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHPUnit extension for Symfony AI Mate providing AI assistants with token-optimized test execution tools. This extension executes PHPUnit tests and returns results in TOON (Token-Oriented Object Notation) format, achieving 40-50% token reduction compared to raw PHPUnit output.

## Common Commands

### Development Workflow

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run specific test
vendor/bin/phpunit tests/Unit/Capability/RunSuiteToolTest.php
vendor/bin/phpunit --filter testExecute

# Check code quality (validates composer.json, runs Rector, PHP CS Fixer, PHPStan)
composer lint

# Auto-fix code style and apply automated refactorings
composer fix
```

### Individual Quality Tools

```bash
# PHP CS Fixer (code style)
vendor/bin/php-cs-fixer fix --dry-run --diff  # Check only
vendor/bin/php-cs-fixer fix                   # Apply fixes

# PHPStan (static analysis at level 8)
vendor/bin/phpstan analyse

# Rector (automated refactoring to PHP 8.2)
vendor/bin/rector process --dry-run           # Preview changes
vendor/bin/rector process                     # Apply changes
```

## Architecture

### Component Structure

**MCP Tools** (`src/Capability/`):
- `RunSuiteTool` - Execute full PHPUnit test suite
- `RunFileTool` - Execute tests in specific file
- `RunMethodTool` - Execute single test method
- `ListTestsTool` - Discover all available tests in project
- `BuildsPhpunitArguments` (trait) - Shared argument building logic

**Core Services**:
- `Runner/PhpunitRunner` - Executes PHPUnit with JUnit XML logging via ProcessExecutor
- `Parser/JunitXmlParser` - Parses JUnit XML into structured TestResult
- `Parser/TestResult` - DTO containing test counts, failures, errors, warnings, time
- `Formatter/ToonFormatter` - Converts results to TOON format with multiple modes
- `Config/ConfigurationDetector` - Auto-detects phpunit.xml/phpunit.xml.dist
- `Discovery/TestDiscovery` - Finds test files and methods using Symfony Finder

### Data Flow

```
Tool → PhpunitRunner → ProcessExecutor (common package)
                                ↓
                         PHPUnit CLI with --log-junit
                                ↓
                         JunitXmlParser → TestResult
                                ↓
                         ToonFormatter → TOON output
```

### Output Modes

The ToonFormatter supports five output modes:
- `default` - Summary + failures/errors with truncated messages (~40-50% token reduction)
- `summary` - Just totals and status (tests, passed, failed, errors, time)
- `detailed` - Full error messages without truncation
- `by-file` - Errors grouped by file path (basename)
- `by-class` - Errors grouped by test class (short class name)

### Common Package Integration

Uses `matesofmate/common` package for shared functionality:

**ProcessExecutor** - CLI tool execution with PHP binary reuse
- Configured with vendor path: `%mate.root_dir%/vendor/bin/phpunit`
- Default timeout: 300 seconds
- Always uses `--log-junit` to generate JUnit XML output

**ConfigurationDetector** - Auto-detects config files in order:
1. phpunit.xml
2. phpunit.xml.dist

### Service Registration

All services registered in `config/config.php` with:
- Autowiring enabled
- Autoconfiguration enabled (discovers #[McpTool] attributes)
- Custom process executor with vendor path injection
- Project root parameter injection for configuration detection and test discovery

## Code Quality Standards

### PHP Requirements
- PHP 8.2+ minimum
- No `declare(strict_types=1)` by convention
- No final classes (extensibility)
- JSON encoding: Always use `\JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT`

### Quality Tools Configuration
- **PHPStan**: Level 8, includes phpstan-phpunit extension
- **PHP CS Fixer**: `@Symfony` + `@Symfony:risky` rulesets with ordered class elements
- **Rector**: PHP 8.2, code quality, dead code removal, early return, type declarations
- **PHPUnit**: Version 10.0+

### File Header Template

All PHP files must include:
```php
<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

### DocBlock Annotations

**@author annotation**: Required on all class-level DocBlocks:
```php
/**
 * Description of the class.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class YourClass
```

**@internal annotation**: Mark implementation details not for external use:
```php
/**
 * Internal parser for JUnit XML output.
 *
 * @internal
 * @author Johannes Wachter <johannes@sulu.io>
 */
class JunitXmlParser
```

Use @internal for:
- Parser, formatter, runner classes
- Helper traits
- Internal DTOs (RunResult, TestResult)
- Classes not intended for extension consumers

## Discovery Mechanism

Symfony AI Mate auto-discovers tools via `composer.json`:

```json
{
    "extra": {
        "ai-mate": {
            "scan-dirs": ["src/Capability"],
            "includes": ["config/config.php"]
        }
    }
}
```

## Testing Philosophy

### Test Structure
- Tests mirror `src/` structure in `tests/Unit/`
- Extend `PHPUnit\Framework\TestCase`
- Test method names: `testExecute`, `testFormatDefault`, `testParseJunitXml`, etc.

### Key Testing Areas
- Tool parameter validation (configuration paths, filter patterns, stop-on-failure flags)
- JUnit XML parsing correctness
- TOON format output validation
- Configuration detection logic
- Test discovery functionality

### Integration Testing
- Service registration and dependency injection
- Attribute-based discovery (#[McpTool])
- Process executor integration with common package

## Common Development Patterns

### Adding New Tools

1. Create tool class in `src/Capability/` with `#[McpTool]` attribute
2. Inject required services via constructor (PhpunitRunner, parsers, formatters)
3. Use `BuildsPhpunitArguments` trait if needed for argument construction
4. Register service in `config/config.php`
5. Add corresponding test in `tests/Unit/Capability/`

### Adding New Output Modes

1. Add mode to enum in `#[Schema]` attribute on tool parameters
2. Implement format method in `ToonFormatter` (e.g., `formatCustomMode()`)
3. Add match arm in `ToonFormatter::format()` method
4. Add test case in `ToonFormatterTest`

## Commit Message Convention

Keep commit messages clean without AI attribution.

**Format:**
```
Short summary (50 chars or less)

- Conceptual change description
- Another concept or improvement
```

**Rules:**
- ❌ NO AI attribution (no "Co-Authored-By: Claude", etc.)
- ✅ Short, descriptive summary line
- ✅ Bullet list describing concepts/improvements
- ✅ Focus on the WHY and WHAT
