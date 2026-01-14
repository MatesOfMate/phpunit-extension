# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **PHPUnit extension for symfony/ai-mate** that provides token-optimized testing tools for AI assistants.

**Key Features:**
- TOON (Token-Oriented Object Notation) format for 40-50% token reduction vs. raw PHPUnit output
- Test execution via Symfony Process component using current PHP binary
- JUnit XML parsing for structured results
- Auto-detection of PHPUnit configuration files

**Core Components:**
- **Tools** (`src/Capability/`): MCP tools for running tests and listing tests
- **Runner** (`src/Runner/`): PHPUnit process execution with PHP binary detection
- **Parser** (`src/Parser/`): JUnit XML parsing into structured data
- **Formatter** (`src/Formatter/`): TOON output formatting using helgesverre/toon library
- **Discovery** (`src/Discovery/`): Test file and method discovery
- **Config** (`src/Config/`): PHPUnit configuration detection

**Available Tools:**
- `phpunit_run_suite` - Run entire test suite with optional filtering
- `phpunit_run_file` - Run tests from a specific file
- `phpunit_run_method` - Run a single test method
- `phpunit_list_tests` - List all available tests in the project

## Response Format

All tools return **raw TOON-formatted strings** for maximum token efficiency:

```
summary{tests,passed,failed,errors,time}:
100|95|5|0|12.4s

status: FAILED

failures[5]{class,method,message,file,line}:
UserTest|testCreate|Expected 200 got 401|UserTest.php|45
...
```

## Formatter Modes

Tools support multiple output modes via the `mode` parameter:

- **`toon` (default)**: Compact token-optimized format (~40-50% token reduction)
- **`summary`**: Just totals and status (ultra-compact)
- **`detailed`**: Full error details without truncation
- **`by-file`**: Errors grouped by file path
- **`by-class`**: Errors grouped by test class

**Example**:
```php
phpunit_run_suite(mode: 'summary')  // Just totals
phpunit_run_suite(mode: 'detailed') // Full details
phpunit_run_suite(mode: 'by-file')  // Grouped by file
```

## Essential Commands

### Development Workflow
```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run tests with coverage report
composer test -- --coverage-html coverage/

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
vendor/bin/rector process --dry-run             # Preview changes
vendor/bin/rector process                       # Apply changes

# PHPUnit (run specific test)
vendor/bin/phpunit tests/Capability/ExampleToolTest.php
vendor/bin/phpunit --filter testMethodName
```

## Architecture

### Core Concepts

**Tools vs Resources:**
- **Tools** (`#[McpTool]`): Executable actions invoked by AI (e.g., list entities, analyze code)
- **Resources** (`#[McpResource]`): Static/semi-static data provided to AI (e.g., configuration, routes)

**Discovery Mechanism:**
The `extra.ai-mate` section in `composer.json` defines:
- `scan-dirs`: Directories to scan for `#[McpTool]` and `#[McpResource]` attributes
- `includes`: Service configuration files to load

### Directory Structure

```
src/Capability/          # All tools and resources go here
config/services.php      # Symfony DI configuration for registering capabilities
tests/Capability/        # Tests mirror src/Capability/ structure
```

### Service Registration Pattern

In `config/services.php`:
```php
$services = $container->services()
    ->defaults()
    ->autowire()      // Auto-inject dependencies
    ->autoconfigure(); // Auto-register MCP attributes

$services->set(YourTool::class);
```

All classes in `src/Capability/` with `#[McpTool]` or `#[McpResource]` attributes are automatically discovered if registered as services.

### Tool Implementation Pattern

```php
use Mcp\Capability\Attribute\McpTool;

class YourTool
{
    public function __construct(
        private readonly SomeService $service,
    ) {
    }

    #[McpTool(
        name: 'framework-action-name',  // Format: {framework}-{action}
        description: 'Precise description of when AI should use this tool'
    )]
    public function execute(string $param): string
    {
        // Return JSON for structured data
        return json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
    }
}
```

**Key points:**
- Tool names use lowercase with hyphens: `example-list-entities`
- Descriptions are critical - AI uses them to decide when to invoke tools
- Return JSON strings for structured data
- Use constructor injection for dependencies

### Resource Implementation Pattern

```php
use Mcp\Capability\Attribute\McpResource;

class YourResource
{
    #[McpResource(
        uri: 'myframework://config',    // Custom URI scheme
        name: 'framework_config',
        mimeType: 'application/json'
    )]
    public function getConfig(): array
    {
        return [
            'uri' => 'myframework://config',
            'mimeType' => 'application/json',
            'text' => json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
        ];
    }
}
```

**Key points:**
- Must return array with `uri`, `mimeType`, and `text` keys
- URI uses custom scheme (e.g., `example://`, `symfony://`)
- Typically return `application/json` or `text/plain`

## Code Quality Standards

### Important Design Decisions

⚠️ **Template-specific conventions** (users can customize when creating their extensions):

- **No strict types declarations** - All PHP files omit `declare(strict_types=1)` by design
- **No final classes** - All classes are non-final to allow extensibility
- **JSON error handling** - Always use `\JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT` with `json_encode()`

### PHP CS Fixer Configuration
- Follows `@Symfony` ruleset with risky rules enabled
- Enforces specific class element ordering (traits → constants → properties → methods)
- Requires MatesOfMate organisation header comment
- Uses parallel processing for performance
- Excludes only `var/` and `vendor/` directories

### PHPStan Configuration
- **Level 8** (maximum strictness)
- Analyzes both `src/` and `tests/`
- PHPDoc types are not treated as certain (forces proper type declarations)
- PHPUnit extension enabled
- Empty `ignoreErrors` section available for adding exceptions

### Rector Configuration
- Targets **PHP 8.2+**
- Applies: UP_TO_PHP_82, code quality, dead code removal, early return, type declarations
- PHPUnit 10.0 rules enabled

## Testing Conventions

- Tests live in `tests/` mirroring `src/` structure
- Extend `PHPUnit\Framework\TestCase`
- Use descriptive test method names: `testReturnsValidJson`, `testContainsExpectedKeys`
- Test JSON output validity and structure for tools
- Test return array structure for resources

## CI/CD

GitHub Actions workflow (`.github/workflows/ci.yml`) runs automatically:
- **Lint job**: Validates composer.json, runs Rector, PHP CS Fixer, PHPStan
- **Test job**: Runs PHPUnit on PHP 8.2 and 8.3

## When Creating New Extensions

1. Replace all `example`/`Example`/`ExampleExtension` references with your framework name
2. Update `composer.json` package name to `matesofmate/{framework}-extension` and description
3. Update `.github/CODEOWNERS` - replace `@your-username` with your GitHub handle (keep `@wachterjohannes`)
4. Create tools in `src/Capability/` with clear, descriptive tool names and descriptions
5. Register services in `config/services.php`
6. Write tests in `tests/Capability/` covering tool/resource behavior
7. Update README.md with framework-specific installation and usage instructions
8. Ensure all quality checks pass: `composer lint && composer test`
9. Tag release (e.g., `v0.1.0`) and submit to Packagist

## File Header Template

All PHP files must include this copyright header:

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

## DocBlock Annotations

**@author annotation**: All class-level DocBlocks should include an @author annotation with the current user:
```php
/**
 * Runs PHPUnit test suite and returns token-optimized results.
 *
 * @author Your Name <your@email.com>
 */
class RunSuiteTool
{
}
```

**@internal annotation**: Use @internal for classes, methods, or properties that are implementation details and should not be used by extension consumers:
```php
/**
 * Parses JUnit XML output into structured data.
 *
 * @internal
 * @author Your Name <your@email.com>
 */
class JunitXmlParser
{
}
```

Use @internal for:
- Implementation detail classes (parsers, formatters, internal DTOs)
- Private helper methods exposed for testing
- Framework-specific adapters
- Classes in `src/` subdirectories not meant for direct use

## Commit Message Convention

**Important**: Keep commit messages clean without AI attribution.

**Format**:
```
Short summary (50 chars or less)

- Conceptual change description
- Another concept or improvement
- More changes as needed
```

**✅ Good Examples**:
```
Add Doctrine entity discovery tool

- Enable AI to discover entity metadata
- Support association mapping queries
- Include field type information
```

```
Improve error handling for API tools

- Add graceful degradation for missing services
- Provide helpful error messages
- Include recovery suggestions
```

**❌ Bad Examples**:
```
Update tool files

Co-Authored-By: Claude Code <noreply@anthropic.com>
```

```
Implement features - coded by claude-code
```

**Rules**:
- ❌ NO AI attribution (no "Co-Authored-By: Claude", "coded by claude-code", etc.)
- ✅ Short, descriptive summary line
- ✅ Bullet list describing concepts/improvements, not file names
- ✅ Natural language explaining what changed
- ✅ Focus on the WHY and WHAT, not technical details
