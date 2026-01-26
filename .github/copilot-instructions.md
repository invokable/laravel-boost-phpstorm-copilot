# Laravel Boost PhpStorm with GitHub Copilot Plugin - Project Guidelines

## Project Overview

This is a Laravel package that provides custom CodeEnvironment integration for PhpStorm with GitHub Copilot plugin with Laravel Boost. It enables Laravel projects to use GitHub Copilot plugin in PhpStorm with Laravel Boost's MCP (Model Context Protocol) server functionality.

## Technology Stack

- **Language**: PHP 8.3+
- **Framework**: Laravel 12.x+
- **Dependencies**: Laravel Boost 2.0+
- **Target Platforms**: macOS, Linux, Windows (Native Windows supported)
- **Testing**: Pest PHP 4.x
- **Code Quality**: Laravel Pint (PSR-12)

## Architecture

### Core Components

1. **PhpStormCopilot.php**: Main `Agent` implementation
   - `src/Concerns/WithWSL.php` trait is just for code splitting purposes

2. **PhpStormCopilotServiceProvider.php**: Laravel service provider
   - Registers the PhpStormCopilot `Agent` with Laravel Boost
   - Auto-discovered via Laravel's package discovery

### Key Features

- System-wide PhpStorm and GitHub Copilot plugin detection
- MCP server configuration for PhpStorm's GitHub Copilot plugin
- System-wide MCP installation strategy (not project-local)
- Integration with `php artisan boost:install` and `boost:update` commands
- Multi-project support with per-project configuration updates

## Code Style Guidelines

### PHP Standards
- Follow PSR-12 coding standards (enforced by Laravel Pint)
- Use strict types declaration (`declare(strict_types=1);`)
- Use return type declarations for all methods
- Follow Laravel conventions and best practices
- Run `composer lint` before committing
- Verify formatting with `composer test:lint`

### Namespace Convention
- Root namespace: `Revolution\Laravel\Boost`
- Follow PSR-4 autoloading standards
- Test namespace: `Tests\`

## Development Guidelines

### When Making Changes

1. **Service Provider**: Only modify for registration logic
2. **PhpStormCopilot Class**: 
   - Keep detection configs simple and reliable
   - Maintain compatibility with Laravel Boost interfaces
   - Ensure JSON configuration format is valid
   - Use `File` facade for file operations
   - Handle system-wide MCP config file paths per OS

3. **Configuration Files**:
   - System-wide MCP config file locations:
     - macOS, Linux: `~/.config/github-copilot/intellij/mcp.json`
     - Windows: `%LOCALAPPDATA%\github-copilot\intellij\mcp.json`

### Testing Approach

#### Automated Tests
- **Framework**: Pest PHP with Orchestra Testbench
- **Run tests**: `composer test` or `vendor/bin/pest`
- **Test coverage**: `vendor/bin/pest --coverage`
- **Test structure**: 
  - `tests/Feature/` - Feature tests for main functionality
  - `tests/TestCase.php` - Base test case with package provider setup
  - `tests/Pest.php` - Pest configuration and architecture presets
  - `tests/ArchTest.php` - Architecture rules and code quality checks

#### Writing Tests
- Use Pest PHP syntax (test functions, not classes)
- Mock `DetectionStrategyFactory` for unit tests
- Test public methods and behavior, not implementation details
- Use descriptive test names: `test('description of expected behavior')`
- Use temporary directories for file system tests and clean up after
- Follow the pattern in existing tests

#### Integration Tests
- Test with `php artisan boost:install` command in a Laravel project
- Verify system-wide MCP config file creation at correct location
- Test with `php artisan boost:update` command when switching projects
- Test in PhpStorm with GitHub Copilot plugin enabled

#### Test Requirements
- All tests must pass before merging: `composer test`
- Code must pass linting: `composer test:lint`
- Maintain test coverage above 90%
- Write tests for all new features and bug fixes

## Package Integration

### MCP Configuration Format
System-wide MCP config file contains absolute path to Laravel project:

```json
{
  "servers": {
    "laravel-boost": {
      "command": "/absolute/path/to/php",
      "args": ["/absolute/path/to/laravel/artisan", "boost:mcp"]
    },
    "existing-server": {
      "url": "https://example.com/mcp"
    }
  }
}
```

**Important**: Empty arrays in the configuration are automatically removed to ensure compatibility. Some MCP tools fail when encountering empty arrays (e.g., `"headers": []`), but work correctly when the field is absent entirely. The implementation recursively removes all empty arrays before writing the configuration file.

#### WSL

```json
{
  "servers": {
    "laravel-boost": {
      "command": "wsl.exe",
      "args": [
          "/absolute/path/to/php", 
          "/absolute/path/to/laravel/artisan", 
          "boost:mcp"
      ]
    }
  }
}
```

#### WSL and Laravel Sail

```json
{
  "servers": {
    "laravel-boost": {
      "command": "wsl.exe",
      "args": [
          "--cd",
          "/absolute/path/to/project",
          "./vendor/bin/sail", 
          "artisan", 
          "boost:mcp"
      ]
    }
  }
}
```

#### macOS/Linux and Laravel Sail

```json
{
  "servers": {
    "laravel-boost": {
      "command": "bash", 
      "args": [
         "-c",
         "cd /absolute/path/to/project && ./vendor/bin/sail artisan boost:mcp"
      ]
    }
  }
}
```

## Important Constraints

1. **Platform Support**: Native Windows is supported (macOS, Linux, Windows)
2. **PHP Version**: Minimum PHP 8.3
3. **Laravel Version**: Minimum Laravel 12.x
4. **File Structure**: 
   - `.github/copilot-instructions.md` is project-local
   - MCP config is system-wide in OS-specific locations
5. **Project Switching**: Users must run `boost:install` when switching Laravel projects

### Composer Scripts
```bash
composer test          # Run all tests
composer lint          # Format code with Pint
composer test:lint     # Check code formatting
```
