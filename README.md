# Laravel Boost Custom CodeEnvironment for PhpStorm with GitHub Copilot plugin

## Requirements
- PHP >= 8.3
- Laravel >= 12.x
- [Laravel Boost](https://github.com/laravel/boost) >= 1.6
- [GitHub Copilot plugin](https://plugins.jetbrains.com/plugin/17718-github-copilot) installed in PhpStorm

## Supported OS
- macOS
- Windows
  - For WSL, please use [laravel-boost-copilot-cli](https://github.com/invokable/laravel-boost-copilot-cli)
  - It supports an environment where PhpStorm runs on native Windows and only the `php` command is used in WSL.
- Linux

## Installation

```shell
composer require revolution/laravel-boost-phpstorm-copilot --dev
```

## Usage

When you run the Laravel Boost installation command within your Laravel project, you'll see a `PhpStorm with GitHub Copilot` item added to the list. Select it to generate MCP config file. To generate `.github/copilot-instructions.md`, also select the boost standard `GitHub Copilot`.

```shell
php artisan boost:install
```

### Important
With PhpStorm and GitHub Copilot plugin, the MCP configuration file is stored in a system-wide location. Therefore, you need to run the `boost:update` command to update the configuration file whenever you switch Laravel projects. The configuration file contains the absolute path to your Laravel project.

```shell
php artisan boost:update
```

### MCP Configuration File Location by OS
- macOS, Linux: `~/.config/github-copilot/intellij/mcp.json`
- Windows: `%LOCALAPPDATA%\\github-copilot\\intellij\\mcp.json`

## License
MIT
