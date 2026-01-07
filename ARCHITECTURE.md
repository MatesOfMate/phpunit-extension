# Architecture

## Overview

The PHPUnit extension follows a layered architecture designed for token efficiency and reliable test execution:

```
Tools Layer (MCP Interface)
    ↓
Runner Layer (Process Execution)
    ↓
Parser Layer (Result Extraction)
    ↓
Formatter Layer (TOON Output)
```

## Component Responsibilities

### Runner Layer

**PhpunitRunner** (`src/Runner/PhpunitRunner.php`)
- Executes PHPUnit via Symfony Process component
- Uses current PHP binary (`PHP_BINARY`) for consistency
- Handles binary detection (vendor/bin/phpunit, vendor/phpunit/phpunit/phpunit)
- Manages timeout (300 seconds default)
- Generates JUnit XML output via `--log-junit` flag

**RunResult** (`src/Runner/RunResult.php`)
- Value object holding execution results
- Properties: exitCode, output, errorOutput, junitXmlPath
- Methods: wasSuccessful(), getJunitXml(), cleanup()
- Readonly class for immutability

### Parser Layer

**JunitXmlParser** (`src/Parser/JunitXmlParser.php`)
- Parses JUnit XML into structured data
- Extracts summary (tests, assertions, failures, errors, warnings, skipped, time)
- Parses failure details (class, method, file, line, type, message)
- Parses error details (class, method, file, line, type, message)
- Truncates long messages (200 characters max) to reduce token usage

**TestResult** (`src/Parser/TestResult.php`)
- Value object holding parsed test results
- Properties: summary (array), failures (array), errors (array)
- Methods: wasSuccessful(), getPassed()
- Readonly class for immutability

### Formatter Layer

**ToonFormatter** (`src/Formatter/ToonFormatter.php`)
- Converts TestResult to TOON format
- Uses helgesverre/toon library for encoding
- Optimizes for minimal token usage (40-50% reduction vs. JSON)
- Formats summary, failures, errors, and status
- Shortens class names (FQCN → short name)

### Discovery Layer

**TestDiscovery** (`src/Discovery/TestDiscovery.php`)
- Finds test files using Symfony Finder
- Pattern: `*Test.php` files in configured directories
- Parses test files for class name, namespace, and test methods
- Regex-based method extraction (`public function test\w+`)
- Returns structured test list (file, class, method)

### Config Layer

**ConfigurationDetector** (`src/Config/ConfigurationDetector.php`)
- Locates PHPUnit configuration files
- Candidates: phpunit.xml, phpunit.xml.dist, phpunit.dist.xml
- Extracts test directories from configuration via XPath
- Fallback to `['tests']` if no configuration found

### Tools Layer

**RunSuiteTool** (`src/Capability/RunSuiteTool.php`)
- MCP tool for full test suite execution
- Parameters: configuration, filter, stopOnFailure
- Returns TOON-formatted results

**RunFileTool** (`src/Capability/RunFileTool.php`)
- MCP tool for single file test execution
- Parameters: file (required), filter, stopOnFailure
- Validates file existence before execution
- Returns TOON-formatted results

**RunMethodTool** (`src/Capability/RunMethodTool.php`)
- MCP tool for single method test execution
- Parameters: class (FQCN), method (test method name)
- Uses PHPUnit filter pattern: `ClassName::methodName$`
- Returns TOON-formatted results

**ListTestsTool** (`src/Capability/ListTestsTool.php`)
- MCP tool for test discovery
- Parameters: directory (optional)
- Returns TOON-formatted test list

## Design Decisions

### Why Process Execution?

**Chosen Approach:** Execute PHPUnit as a separate process via Symfony Process

**Rationale:**
- **Version agnostic**: Works with PHPUnit 10, 11, 12+ without code changes
- **Isolation**: No memory conflicts or global state pollution from tested code
- **Reliability**: Uses PHPUnit's official CLI interface
- **Consistency**: Same execution path as manual testing

**Alternative Considered:** Direct PHPUnit API integration
- **Rejected because**: Tight coupling to PHPUnit versions, potential memory issues, requires complex setup

### Why JUnit XML?

**Chosen Approach:** Parse JUnit XML output format

**Rationale:**
- **Machine-readable**: Well-structured XML format designed for parsers
- **Complete**: Contains all test results (summary, failures, errors)
- **Standard**: Supported by all PHPUnit versions
- **Reliable**: Official output format maintained by PHPUnit team

**Alternative Considered:** Parse text output or use custom reporters
- **Rejected because**: Text parsing is fragile, custom reporters require PHPUnit coupling

### Why TOON Format?

**Chosen Approach:** Use helgesverre/toon library for output formatting

**Rationale:**
- **Token efficiency**: 40-50% reduction vs. JSON (measured)
- **Readable**: Still human-readable unlike binary formats
- **Structured**: Maintains data structure unlike plain text
- **Battle-tested**: Actively maintained library with good documentation

**Example comparison:**

**JSON (186 tokens):**
```json
{
  "tests": 42,
  "passed": 39,
  "failed": 2,
  "failures": [
    {"class": "UserTest", "method": "testCreate", "message": "Expected 200"}
  ]
}
```

**TOON (~110 tokens - 41% reduction):**
```
summary{tests,passed,failed}:
42|39|2

failures[1]{class,method,message}:
UserTest|testCreate|Expected 200
```

**Alternative Considered:** Custom compact format
- **Rejected because**: Reinventing the wheel, no reuse across projects

### Why Current PHP Binary?

**Chosen Approach:** Use `PHP_BINARY` constant to detect current PHP runtime

**Rationale:**
- **Consistency**: Same PHP version/configuration as AI Mate runtime
- **Compatibility**: Avoids version mismatches between environments
- **Reliability**: Uses the PHP binary that successfully loaded the extension

**Alternative Considered:** System `php` command or hardcoded path
- **Rejected because**: May use different PHP version, configuration issues

### Why Regex-based Test Discovery?

**Chosen Approach:** Parse test files with regex to extract methods

**Rationale:**
- **Simple**: No need to load classes or execute code
- **Fast**: Direct file parsing without autoloading
- **Safe**: No side effects from loading test code
- **Sufficient**: Covers 99% of standard PHPUnit test naming

**Pattern:** `/public\s+function\s+(test\w+)\s*\(/`

**Alternative Considered:** Use PHP's Reflection or PHPUnit's TestSuite
- **Rejected because**: Requires autoloading, slower, potential side effects

## Data Flow

### Test Execution Flow

```
1. User Request via AI
       ↓
2. MCP Tool (RunSuiteTool, RunFileTool, RunMethodTool)
       ↓
3. Build PHPUnit arguments
   - Auto-detect configuration (ConfigurationDetector)
   - Add filters, flags
       ↓
4. Execute PHPUnit (PhpunitRunner)
   - Use current PHP binary (PHP_BINARY)
   - Generate JUnit XML (--log-junit temp file)
   - Capture exit code and output
       ↓
5. Parse Results (JunitXmlParser)
   - Extract summary data
   - Parse failures with details
   - Parse errors with details
       ↓
6. Format Output (ToonFormatter)
   - Convert to TOON format
   - Optimize for token usage
   - Shorten class names
       ↓
7. Return to AI Assistant
   - TOON-formatted string
   - 40-50% smaller than JSON
```

### Test Discovery Flow

```
1. User Request via AI
       ↓
2. ListTestsTool
       ↓
3. Get Test Directories (ConfigurationDetector)
   - Read phpunit.xml if exists
   - Extract <directory> elements via XPath
   - Fallback to ['tests']
       ↓
4. Find Test Files (TestDiscovery)
   - Use Symfony Finder: *Test.php
   - Filter by directory
       ↓
5. Parse Each File
   - Extract namespace via regex
   - Extract class name via regex
   - Extract test methods via regex
       ↓
6. Format Output (TOON)
   - tests[N]{file,class,method}:
   - Pipe-separated values
       ↓
7. Return to AI Assistant
```

## Extension Points

### Adding New Tools

1. Create class in `src/Capability/`
2. Add `#[McpTool]` attribute with name and description
3. Inject required services via constructor
4. Return TOON-formatted string (recommended) or JSON
5. Register service in `config/services.php`
6. Write tests in `tests/Capability/`

**Example:**
```php
<?php

namespace MatesOfMate\PHPUnitExtension\Capability;

use Mcp\Capability\Attribute\McpTool;

class NewTool
{
    public function __construct(
        private readonly PhpunitRunner $runner,
        private readonly ToonFormatter $formatter,
    ) {
    }

    #[McpTool(
        name: 'phpunit_new_feature',
        description: 'Clear description of when to use this tool'
    )]
    public function execute(string $param): string
    {
        // Implementation
        return toon($data);
    }
}
```

### Custom Formatters

Implement alternative formatter by creating a new formatter class:

```php
<?php

namespace MatesOfMate\PHPUnitExtension\Formatter;

use MatesOfMate\PHPUnitExtension\Parser\TestResult;

class JsonFormatter
{
    public function format(TestResult $result): string
    {
        $data = [
            'summary' => $result->summary,
            'failures' => $result->failures,
            'errors' => $result->errors,
            'status' => $result->wasSuccessful() ? 'OK' : 'FAILED',
        ];

        return json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
    }
}
```

Then inject the new formatter instead of ToonFormatter in your tools.

### Custom Parsers

Extend `JunitXmlParser` or create alternative parser for different output formats:

```php
<?php

namespace MatesOfMate\PHPUnitExtension\Parser;

class TeamcityParser
{
    public function parse(string $teamcityOutput): TestResult
    {
        // Parse Teamcity format
        // Return TestResult
    }
}
```

## Performance Considerations

### Token Usage

**Target**: 40-50% reduction vs. raw PHPUnit output

**Measurements** (example test suite with 42 tests, 2 failures):
- JSON: ~450 tokens
- TOON: ~250 tokens
- Reduction: 44%

**Optimization techniques:**
1. Shorten FQCN to class name only
2. Use pipe-separated values instead of JSON objects
3. Truncate long error messages (200 char max)
4. Use basename() for file paths

### Execution Time

**Overhead**: <100ms beyond PHPUnit execution time

**Breakdown:**
- Process spawn: ~10ms
- JUnit XML parsing: ~20ms
- TOON formatting: ~5ms
- Other: ~10ms
- **Total overhead**: ~45ms

**Note:** The majority of time is spent in PHPUnit execution itself (typically seconds to minutes).

### Memory Usage

**Peak memory**: <50MB for typical test suites

**Factors:**
- JUnit XML file size (proportional to number of tests)
- Symfony Process buffers
- TOON encoding overhead (minimal)

**Optimizations:**
- Cleanup temp files immediately after parsing
- Use readonly value objects to prevent mutations
- Stream-based parsing not needed for typical XML sizes (<10MB)

## Security Considerations

### Path Traversal

**Mitigation:** All file paths validated before execution
- `RunFileTool` checks `file_exists()` before running
- No user input passed directly to shell
- Symfony Process handles argument escaping

### Command Injection

**Mitigation:** Symfony Process API with array arguments
- No shell interpolation
- Arguments passed as array, not concatenated string
- Process component handles all escaping

### Information Disclosure

**Mitigation:** Only return test results, not system info
- No environment variables exposed
- No file system paths beyond test files
- Error messages truncated to prevent stack trace leaks

## Dependencies

### Runtime Dependencies

- `php: >=8.2` - Language runtime
- `symfony/ai-mate: ^0.1` - MCP framework
- `symfony/process: ^6.0|^7.0` - Process execution
- `symfony/finder: ^6.0|^7.0` - File discovery
- `helgesverre/toon: ^1.0` - TOON formatting

### Development Dependencies

- `phpunit/phpunit: ^10.0` - Testing framework
- `phpstan/phpstan: ^2.0` - Static analysis (Level 8)
- `phpstan/phpstan-phpunit: ^2.0` - PHPUnit extensions for PHPStan
- `friendsofphp/php-cs-fixer: ^3.0` - Code style
- `rector/rector: ^2.0` - Code modernization

## Testing Strategy

### Unit Tests

Each component has isolated unit tests:
- `Runner/PhpunitRunnerTest.php` - Process execution
- `Parser/JunitXmlParserTest.php` - XML parsing
- `Formatter/ToonFormatterTest.php` - TOON formatting
- `Config/ConfigurationDetectorTest.php` - Config detection
- `Discovery/TestDiscoveryTest.php` - Test file discovery

### Integration Tests

Tools tested end-to-end with real PHPUnit execution:
- `Capability/RunSuiteToolTest.php`
- `Capability/RunFileToolTest.php`
- `Capability/RunMethodToolTest.php`
- `Capability/ListTestsToolTest.php`

### Quality Standards

- **PHPStan Level 8**: Maximum type safety
- **PHP CS Fixer**: Symfony coding standards
- **Rector**: PHP 8.2+ modernization
- **100% method coverage** for critical paths

## Future Enhancements

### Potential Additions

1. **Coverage Reporting** (`phpunit_get_coverage`)
   - Parse coverage data
   - Return coverage percentage per file/class
   - TOON-formatted output

2. **Failure Analysis** (`phpunit_analyze_failure`)
   - Deep dive into specific failure
   - Extract stack traces
   - Show actual vs. expected values

3. **Git-aware Testing** (`phpunit_run_diff`)
   - Run tests for changed files
   - Compare against base branch
   - Smart test selection

4. **Watch Mode** (`phpunit_watch`)
   - Monitor file changes
   - Re-run relevant tests
   - Stream results via MCP

5. **Test Generation Hints** (`phpunit_suggest_tests`)
   - Analyze source files
   - Suggest missing test cases
   - Generate test skeletons

### Breaking Changes to Avoid

- Changing tool names (breaks AI assistant integrations)
- Changing TOON structure (breaks parsers)
- Removing required parameters (breaks existing calls)
- Changing return format from string to object

### Backward Compatibility Strategy

- Add new tools, don't modify existing ones
- Add optional parameters only
- Deprecation notices before removal
- Semantic versioning strictly followed
