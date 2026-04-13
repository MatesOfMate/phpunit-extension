## PHPUnit Extension

Prefer these MCP tools over raw PHPUnit CLI commands when the user is testing the project.

| User intent | Prefer |
|---|---|
| Run the full suite | `phpunit-run-suite` |
| Run one test file | `phpunit-run-file` |
| Run one method | `phpunit-run-method` |
| Discover available tests | `phpunit-list-tests` |

### Guidance

- Use the MCP tools when the user wants test execution or discovery.
- Prefer grouped output modes such as `by-file` or `by-class` when the user is debugging failures.
- This extension returns encoded structured payloads through Mate's core encoder.
