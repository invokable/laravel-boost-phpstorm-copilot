# Laravel Boost Custom CodeEnvironment for PhpStorm with GitHub Copilot plugin

## Requirements
- PHP >= 8.3
- Laravel >= 12.x
- [Laravel Boost](https://github.com/laravel/boost) >= 1.6
- [GitHub Copilot plugin](https://plugins.jetbrains.com/plugin/17718-github-copilot) installed in PhpStorm

## Supported OS
- macOS
- Windows (Native Windows)
- Linux

### WSL (Windows Subsystem for Linux)

This package supports WSL environments where PhpStorm runs on native Windows and PHP runs in WSL. This is a common development setup that provides Windows IDE features with Linux development environment.

#### Requirements for WSL
- `wslu` package must be installed in WSL
- Check if installed: `wslvar -v`
- Install if needed: `sudo apt install wslu`

#### How it works
1. **Detection**: Automatically detects WSL environment by checking `WSL_DISTRO_NAME` environment variable
2. **Username Resolution**: Uses `wslvar USERNAME` to get Windows username (WSL and Windows usernames may differ)
3. **File Writing**: Writes MCP config to Windows side via PowerShell commands
   - Creates temporary file in Windows `%TEMP%` directory
   - Uses Base64 encoding to safely transfer JSON content
   - Copies to final location: `%LOCALAPPDATA%\github-copilot\intellij\mcp.json`
4. **Path Handling**: Converts WSL paths to Windows paths for absolute command and artisan paths

#### Troubleshooting WSL
- Ensure `wslu` is installed and `wslvar` command works
- Check that PowerShell is accessible from WSL with `powershell.exe -Command "Write-Output 'test'"`
- Verify Windows username with `wslvar USERNAME`
- If MCP config file is not created, check Windows directory permissions

#### Alternative for WSL
If you encounter issues with WSL setup, consider using [laravel-boost-copilot-cli](https://github.com/invokable/laravel-boost-copilot-cli) which uses a different approach better suited for some WSL configurations.


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
