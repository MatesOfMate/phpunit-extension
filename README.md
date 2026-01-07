# PHPUnit Extension for Symfony AI Mate

Token-optimized PHPUnit testing tools for AI assistants. This extension provides MCP (Model Context Protocol) tools that execute PHPUnit tests and return results in TOON (Token-Oriented Object Notation) format, achieving 40-50% token reduction compared to raw PHPUnit output.

## Features

- **Run tests efficiently** - Execute entire suite, specific files, or single methods
- **TOON format output** - 40-50% token reduction vs. raw PHPUnit output using [helgesverre/toon](https://github.com/HelgeSverre/toon-php)
- **Test discovery** - List all available tests in your project
- **Auto-configuration** - Automatically detects `phpunit.xml` configuration
- **Fast execution** - Direct Symfony Process integration with current PHP binary
- **JUnit XML parsing** - Structured test result extraction

## Installation

```bash
composer require --dev matesofmate/phpunit-extension
```

The extension is automatically discovered by Symfony AI Mate.

## Available Tools

### `phpunit_run_suite`

Run the entire PHPUnit test suite.

**Parameters:**
- `configuration` (optional): Path to phpunit.xml configuration file
- `filter` (optional): Filter pattern for test names
- `stopOnFailure` (optional): Stop execution on first failure

**Examples:**
```php
// Run all tests
phpunit_run_suite()

// Run with filter
phpunit_run_suite(filter: "UserTest")

// Stop on first failure
phpunit_run_suite(stopOnFailure: true)

// Custom configuration
phpunit_run_suite(configuration: "phpunit.custom.xml")
```

### `phpunit_run_file`

Run PHPUnit tests from a specific file.

**Parameters:**
- `file` (required): Path to test file
- `filter` (optional): Filter pattern for method names
- `stopOnFailure` (optional): Stop execution on first failure

**Examples:**
```php
// Run all tests in file
phpunit_run_file("tests/Service/UserServiceTest.php")

// Run specific method in file
phpunit_run_file("tests/Api/AuthTest.php", filter: "testLogin")

// Stop on first failure
phpunit_run_file("tests/Unit/CalculatorTest.php", stopOnFailure: true)
```

### `phpunit_run_method`

Run a single PHPUnit test method.

**Parameters:**
- `class` (required): Fully qualified class name
- `method` (required): Test method name

**Examples:**
```php
// Run specific test method
phpunit_run_method("App\\Tests\\UserServiceTest", "testCreateUser")

// Run another test
phpunit_run_method("App\\Tests\\Api\\AuthTest", "testLoginWithValidCredentials")
```

### `phpunit_list_tests`

List all available PHPUnit tests in the project.

**Parameters:**
- `directory` (optional): Specific directory to scan (defaults to configured test directories)

**Examples:**
```php
// List all tests
phpunit_list_tests()

// List tests in specific directory
phpunit_list_tests(directory: "tests/Unit")
```

## Output Format (TOON)

TOON (Token-Oriented Object Notation) provides minimal token usage while maintaining readability:

**Successful run:**
```
summary{tests,passed,failed,errors,warnings,skipped,time}:
42|42|0|0|0|0|1.234s

status:OK
```

**Failed run:**
```
summary{tests,passed,failed,errors,warnings,skipped,time}:
156|152|3|1|0|0|4.892s

failures[3]{class,method,message,file,line}:
UserServiceTest|testCreateUser|Expected 200 got 401|UserServiceTest.php|45
OrderTest|testCalculateTotal|99.99 !== 100.00|OrderTest.php|112
PaymentTest|testRefund|Null returned|PaymentTest.php|78

errors[1]{class,method,exception,file,line}:
DatabaseTest|testConnection|PDOException: Connection refused|DatabaseTest.php|23

status:FAILED
```

**Test listing:**
```
tests[4]{file,class,method}:
tests/UserTest.php|App\Tests\UserTest|testCreate
tests/UserTest.php|App\Tests\UserTest|testUpdate
tests/UserTest.php|App\Tests\UserTest|testDelete
tests/OrderTest.php|App\Tests\OrderTest|testCalculateTotal
```

### Token Efficiency

Compared to standard JSON output:

**JSON (186 tokens):**
```json
{
  "tests": 42,
  "passed": 39,
  "failed": 2,
  "time": "1.234s",
  "failures": [
    {"class": "UserTest", "method": "testCreate", "message": "Expected 200"}
  ]
}
```

**TOON (~110 tokens - 41% reduction):**
```
summary{tests,passed,failed,time}:
42|39|2|1.234s

failures[1]{class,method,message}:
UserTest|testCreate|Expected 200
```

## How It Works

### Architecture

The extension uses a layered architecture:

1. **Runner Layer** - Executes PHPUnit via Symfony Process (uses current PHP binary)
2. **Parser Layer** - Extracts structured data from JUnit XML output
3. **Formatter Layer** - Converts results to TOON format using helgesverre/toon
4. **Tools Layer** - MCP tools with `#[McpTool]` attributes

### Process Flow

```
User Request
    ↓
MCP Tool (RunSuiteTool, RunFileTool, etc.)
    ↓
PhpunitRunner (Symfony Process with current PHP binary + PHPUnit)
    ↓
JUnit XML Output
    ↓
JunitXmlParser (Extract failures, errors, summary)
    ↓
ToonFormatter (Convert to TOON format)
    ↓
Return to AI Assistant
```

### PHP Binary Detection

The extension automatically uses the same PHP binary that's currently running (`PHP_BINARY`), ensuring consistency between your environment and test execution.

## Development

### Quality Commands

```bash
# Run tests
composer test

# Check code quality (PHPStan level 8, PHP CS Fixer, Rector)
composer lint

# Auto-fix code style and apply refactorings
composer fix
```

### Testing

The extension includes comprehensive tests:

- Unit tests for all core components
- Integration tests for tool execution
- PHPStan level 8 compliance
- PHP CS Fixer code style enforcement
- Rector PHP 8.2+ modernization

### CI/CD

GitHub Actions automatically runs on every push and pull request:
- **Lint**: Validates composer.json, runs Rector, PHP CS Fixer, PHPStan
- **Test**: Runs PHPUnit on PHP 8.2 and 8.3

## Requirements

- PHP 8.2 or higher
- PHPUnit 10.0 or higher (installed in your project)
- Symfony AI Mate 0.1 or higher

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Resources

- [Symfony AI Mate Documentation](https://symfony.com/doc/current/ai/components/mate.html)
- [TOON Format Specification](https://github.com/HelgeSverre/toon-php)
- [MatesOfMate Organization](https://github.com/matesofmate)

---

*Built with the MatesOfMate community*
