# AGENTS.md

Guidelines for agents working on the PHPUnit extension.

## Focus

Maintain a package-specific MCP extension for PHPUnit workflows. Keep docs grounded in the actual package, not in the generic template.

## Important Rules

- Register capabilities in `config/config.php`.
- Keep docs aligned with current Mate setup and troubleshooting commands.
- This package uses Mate's core `ResponseEncoder` for MCP-facing payloads.
- Describe TOON as optional runtime behavior provided by Mate, with JSON fallback.

## When Updating Behavior

1. update the relevant capability, runner, parser, formatter, or discovery code
2. update tests
3. update README and `INSTRUCTIONS.md`
4. run `composer test` and `composer lint`

## Commit Messages

Never include AI attribution. Focus on what changed conceptually and why.
