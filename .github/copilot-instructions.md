# Laravel Boost PhpStorm with GitHub Copilot Plugin - Project Guidelines

## Project Overview

This is a Laravel package that provides custom CodeEnvironment integration for PhpStorm with GitHub Copilot plugin with Laravel Boost. It enables Laravel projects to use GitHub Copilot plugin in PhpStorm with Laravel Boost's MCP (Model Context Protocol) server functionality.

## Technology Stack

- **Language**: PHP 8.3+
- **Framework**: Laravel 12.x+
- **Dependencies**: Laravel Boost 1.7+
- **Target Platforms**: macOS, Linux, Windows (Native Windows supported)
- **Testing**: Pest PHP 4.x
- **Code Quality**: Laravel Pint (PSR-12)

## Architecture

### Core Components

1. **PhpStormCopilot.php**: Main CodeEnvironment implementation
   - Implements `Agent`, `McpClient` interfaces
   - Handles detection, configuration, and MCP installation
   - Generates system-wide MCP config file
   - `src/Concerns/WithWSL.php` trait is just for code splitting purposes

2. **PhpStormCopilotServiceProvider.php**: Laravel service provider
   - Registers the PhpStormCopilot CodeEnvironment with Laravel Boost
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

### Laravel Boost Integration Points
- Extends `CodeEnvironment` base class
- Implements `McpClient` interface for MCP server setup

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
5. **Project Switching**: Users must run `boost:update` when switching Laravel projects

## Common Tasks

### Adding New Detection Methods
1. Update `systemDetectionConfig()` for PhpStorm and plugin detection
2. Keep detection lightweight and fast
3. Write tests in `tests/Feature/PhpStormCopilotTest.php`
4. Run `composer test` to verify

### Modifying MCP Configuration
1. Edit `installFileMcp()` method for file-based MCP installation
2. Handle OS-specific config file paths correctly
3. Use absolute paths for Laravel project location
4. Remove empty arrays from configuration before writing to ensure compatibility
5. Validate JSON format before writing
6. Add test cases for the new configuration
7. Test with temporary directories in tests

### Adding New Features
1. Write tests first (TDD approach)
2. Implement the feature
3. Run `composer test` to verify tests pass
4. Run `composer lint` to format code
5. Verify with `composer test:lint`
6. Update documentation if needed

### Updating Documentation
- Update README.md for user-facing changes
- Keep installation instructions clear and concise
- Include version requirements
- Update this file for development guideline changes

## Dependencies Management

### Production Dependencies
- Keep minimal: only Laravel core packages and Laravel Boost
- PHP 8.3+ required
- Laravel 12.x+ required

### Development Dependencies
- `pestphp/pest` - Testing framework
- `orchestra/testbench` - Package testing support
- `mockery/mockery` - Mocking library
- `laravel/pint` - Code formatter

### Installation
- This package is development-only (require-dev in user projects)
- Run `composer install` to set up development environment

## Development Workflow

### Before Committing
1. Run all tests: `composer test`
2. Format code: `composer lint`
3. Verify formatting: `composer test:lint`
4. Check test coverage: `vendor/bin/pest --coverage`
5. Ensure all tests pass in CI (GitHub Actions)

### Continuous Integration
- **GitHub Actions**: `.github/workflows/tests.yml`
- Tests run on PHP 8.3 and 8.4
- Runs on every push and pull request to main branch
- Must pass before merging

### Composer Scripts
```bash
composer test          # Run all tests
composer lint          # Format code with Pint
composer test:lint     # Check code formatting
```

## Release Notes

When preparing releases, ensure:
- All tests pass: `composer test`
- Code is properly formatted: `composer test:lint`
- Test coverage remains above 90%
- Compatibility with Laravel Boost version requirements
- Test with latest PhpStorm and GitHub Copilot plugin versions
- Test on all supported platforms (macOS, Linux, Windows)
- Update README.md if installation steps change
- Update CHANGELOG.md (if exists)
- Follow semantic versioning
- Tag releases appropriately
