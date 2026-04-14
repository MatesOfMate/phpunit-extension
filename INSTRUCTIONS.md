## PHPUnit Extension

Prefer these MCP tools over raw PHPUnit CLI commands when the user is testing the project.

| User intent | Prefer |
|---|---|
| Run the full suite, one file, one class, or one method | `phpunit-run` |
| Discover available tests | `phpunit-list-tests` |

### Guidance

- Use the MCP tools when the user wants test execution or discovery.
- Use the `file`, `class`, `method`, and `filter` parameters on `phpunit-run` instead of switching between multiple tool names.
- This extension returns encoded structured payloads through Mate's core encoder.
