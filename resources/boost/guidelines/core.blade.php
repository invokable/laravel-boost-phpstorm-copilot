## PhpStorm with GitHub Copilot Plugin

This package provides custom CodeEnvironment integration for PhpStorm with GitHub Copilot plugin with Laravel Boost. It enables PhpStorm users to leverage Laravel Boost's MCP (Model Context Protocol) server functionality.

### Important: Project Path Verification

**Before using Laravel Boost MCP tools in PhpStorm with GitHub Copilot plugin, verify that the project path in the global MCP configuration file matches your current project.**

The MCP configuration file is stored system-wide at:
- macOS, Linux: `~/.config/github-copilot/intellij/mcp.json`
- Windows: `%LOCALAPPDATA%\github-copilot\intellij\mcp.json`

If the project path in the MCP configuration does not match your current Laravel project, **you must update it before using MCP tools**:

@verbatim
<code-snippet name="Update MCP Configuration for Current Project" lang="bash">
php artisan boost:update
</code-snippet>
@endverbatim

This command updates the MCP configuration file with the absolute path to your current Laravel project, ensuring MCP tools interact with the correct project.

### When to Run boost:update

Run `php artisan boost:update` whenever you:
- Switch to a different Laravel project
- Clone or move your project to a new location
- Notice MCP tools are accessing the wrong project's data

### Why This is Necessary

Unlike project-local MCP configurations, PhpStorm with GitHub Copilot plugin stores MCP server configurations in a system-wide location. This allows multiple projects to share the same MCP server registration, but requires updating the configuration when switching between projects to ensure the correct project path is used.
