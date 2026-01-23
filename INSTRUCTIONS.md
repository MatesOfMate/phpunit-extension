## PHPUnit Extension

Use MCP tools instead of CLI for testing:

| Instead of...                         | Use                   |
|---------------------------------------|-----------------------|
| `vendor/bin/phpunit`                  | `phpunit-run-suite`   |
| `vendor/bin/phpunit tests/X.php`      | `phpunit-run-file`    |
| `vendor/bin/phpunit --filter testX`   | `phpunit-run-method`  |
| `vendor/bin/phpunit --list-tests`     | `phpunit-list-tests`  |

### Benefits

- Token-optimized TOON output (40-50% reduction)
- Structured error grouping by file or class

### Output Modes

`default`, `summary` (quick check), `detailed` (debugging), `by-file`, `by-class`
