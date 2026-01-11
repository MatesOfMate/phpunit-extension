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
vendor/bin/mate discover
```

The extension is automatically enabled by Symfony AI Mate.

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

## Requirements

- PHP 8.2 or higher
- PHPUnit 10.0 or higher (installed in your project)
- Symfony AI Mate 0.1 or higher

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](https://github.com/MatesOfMate/.github/blob/main/CONTRIBUTING.md) for details.

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Resources

- [Symfony AI Mate Documentation](https://symfony.com/doc/current/ai/components/mate.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [TOON Format Specification](https://github.com/HelgeSverre/toon-php)
- [MatesOfMate Organization](https://github.com/matesofmate)

---

*Built with the MatesOfMate community*
