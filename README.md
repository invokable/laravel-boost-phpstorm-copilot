# Laravel Boost Custom CodeEnvironment for PhpStorm with GitHub Copilot plugin

[![tests](https://github.com/invokable/laravel-boost-phpstorm-copilot/actions/workflows/tests.yml/badge.svg)](https://github.com/invokable/laravel-boost-phpstorm-copilot/actions/workflows/tests.yml)

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/invokable/laravel-boost-phpstorm-copilot)

## Requirements
- PHP >= 8.3
- Laravel >= 12.x
- [Laravel Boost](https://github.com/laravel/boost) >= 1.7
- [GitHub Copilot plugin](https://plugins.jetbrains.com/plugin/17718-github-copilot) installed in PhpStorm

## Supported Platforms
- macOS
- Windows (Native Windows)
- Linux

### Laravel Sail

It also supports Laravel Sail. Before use, start it with `vendor/bin/sail up -d`.

### WSL (Windows Subsystem for Linux)

This package supports WSL environments where PhpStorm runs on native Windows and PHP runs in WSL. This is a common development setup that provides Windows IDE features with Linux development environment.

#### Requirements for WSL
- `wslu` package must be installed in WSL
- Check if installed: `wslvar -v`
- Install if needed: `sudo apt install wslu`
- Ensure your user profile is on the C drive (default location)

<details>
<summary>How it works</summary>

1. **Detection**: Automatically detects WSL environment by checking `WSL_DISTRO_NAME` environment variable
2. **Path Resolution**: Uses `wslvar LOCALAPPDATA` to get Windows AppData\Local path (e.g., `C:\Users\YourUsername\AppData\Local`)
3. **File Writing**: Writes MCP config to Windows side via PowerShell commands
   - Creates temporary file in Windows `%TEMP%` directory
   - Uses Base64 encoding to safely transfer JSON content
   - Copies to final location: `%LOCALAPPDATA%\github-copilot\intellij\mcp.json`
4. **Path Handling**: Converts WSL paths to Windows paths for absolute command and artisan paths
</details>

<details>
<summary>Troubleshooting WSL</summary>

- Ensure `wslu` is installed and `wslvar` command works
- Check that PowerShell is accessible from WSL with `powershell.exe -Command "Write-Output 'test'"`
- Verify Windows LOCALAPPDATA path with `wslvar LOCALAPPDATA` (should return `C:\Users\YourUsername\AppData\Local`)
- If MCP config file is not created, check Windows directory permissions
- Ensure your user profile is on the C drive (default location)
- `[error] WSL Interoperability is disabled. Please enable it before using WSL.` If you see this error, enable WSL Interop by adding the following binfmt configuration:
```shell
echo ":WSLInterop:M::MZ::/init:PF" | sudo tee /usr/lib/binfmt.d/WSLInterop.conf
sudo systemctl restart systemd-binfmt
```
</details>

#### Remote Development

<details>
<summary>Running PhpStorm within WSL is not supported.</summary>

Because it is not possible to distinguish between a "Windows version of PhpStorm and WSL environment" and a "PhpStorm remote development environment with WSL".
The remote development environment is the same as Linux, so if you really want to use it, please configure the MCP file manually.
`~/.config/github-copilot/intellij/mcp.json`

```json
{
  "servers": {
    "laravel-boost": {
      "command": "/absolute/path/to/php",
      "args": ["/absolute/path/to/laravel/artisan", "boost:mcp"]
    }
  }
}
```
</details>

#### Recommendation for WSL
Consider using [laravel-boost-copilot-cli](https://github.com/invokable/laravel-boost-copilot-cli), which allows you to use a project-level MCP configuration file.

### Testbench for Package Developers

Not supported. Please use [laravel-boost-copilot-cli](https://github.com/invokable/laravel-boost-copilot-cli) instead.

## Installation

```shell
composer require revolution/laravel-boost-phpstorm-copilot --dev
```

## Usage

When you run the Laravel Boost installation command within your Laravel project, you'll see a `PhpStorm with GitHub Copilot` item added to the list. Select it to generate MCP config file. To generate `.github/copilot-instructions.md`, also select the boost standard `GitHub Copilot`.

```shell
php artisan boost:install
```

> [!NOTE]
> **DO NOT** select `PhpStorm`, it's actually `PhpStorm Junie`

### Important
With PhpStorm and GitHub Copilot plugin, the MCP configuration file is stored in a system-wide location. Therefore, you need to run the `boost:install` command to update the configuration file whenever you switch Laravel projects. The configuration file contains the absolute path to your Laravel project.

```shell
php artisan boost:install --no-interaction
```

In boost 1.8 and later, the `boost:update` command does not update the MCP configuration file.

### MCP Configuration File Location by OS
- macOS, Linux: `~/.config/github-copilot/intellij/mcp.json`
- Windows: `%LOCALAPPDATA%\github-copilot\intellij\mcp.json`

## License
MIT
