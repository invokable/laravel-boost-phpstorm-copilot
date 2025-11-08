<?php

declare(strict_types=1);

namespace Revolution\Laravel\Boost;

use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\Enums\Platform;

class PhpStormCopilot extends CodeEnvironment implements McpClient
{
    public bool $useAbsolutePathForMcp = true;

    public function name(): string
    {
        return 'phpstorm-copilot';
    }

    public function displayName(): string
    {
        return 'PhpStorm with GitHub Copilot';
    }

    /**
     * Get the detection configuration for system-wide installation detection.
     *
     * @return array{paths?: string[], command?: string, files?: string[]}
     */
    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => [
                    '~/Library/Application Support/JetBrains/PhpStorm*/plugins/github-copilot-intellij',
                    '/Applications/PhpStorm.app',
                ],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/phpstorm',
                    '/opt/PhpStorm*',
                    '/usr/local/bin/phpstorm',
                    '~/.local/share/JetBrains/Toolbox/apps/PhpStorm/ch-*',
                    // WSL
                    '/mnt/c/Users/*/AppData/Local/github-copilot',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%LOCALAPPDATA%\\github-copilot\\intellij',
                    '%ProgramFiles%\\JetBrains\\PhpStorm*',
                    '%LOCALAPPDATA%\\JetBrains\\Toolbox\\apps\\PhpStorm\\ch-*',
                    '%LOCALAPPDATA%\\Programs\\PhpStorm',
                ],
            ],
        };
    }

    /**
     * Get the detection configuration for project-specific detection.
     *
     * @return array{paths?: string[], files?: string[]}
     */
    public function projectDetectionConfig(): array
    {
        return [
            'files' => ['.github/copilot-instructions.md'],
        ];
    }

    public function mcpConfigKey(): string
    {
        return 'servers';
    }

    public function mcpConfigPath(): string
    {
        $platform = Platform::current();

        $home = getenv('HOME');

        return match ($platform) {
            Platform::Darwin, Platform::Linux => $home.'/.config/github-copilot/intellij/mcp.json',
            Platform::Windows => '%LOCALAPPDATA%\\github-copilot\\intellij\\mcp.json',
        };
    }

    protected function installFileMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $is_wsl = ! empty(getenv('WSL_DISTRO_NAME'));

        if ($is_wsl) {
            return $this->installMcpViaWsl($key, $command, $args);
        }

        return parent::installFileMcp($key, $command, $args, $env);
    }

    protected function installMcpViaWsl(string $name, string $command, array $args): bool
    {
        // Get Windows username via wslvar command
        $usernameResult = Process::run('wslvar USERNAME');
        $username = trim($usernameResult->output());

        if (empty($username)) {
            return false;
        }

        $winPath = "C:\\Users\\{$username}\\AppData\\Local\\github-copilot\\intellij";
        $filePath = "{$winPath}\\mcp.json";

        // Read existing config via PowerShell
        $readCommand = "powershell.exe -NoProfile -Command \"if (Test-Path '{$filePath}') { Get-Content '{$filePath}' -Raw } else { '{}' }\"";
        $result = Process::run($readCommand);

        $config = json_decode($result->output() ?: '{}', true) ?: [];

        if (! isset($config[$this->mcpConfigKey()])) {
            $config[$this->mcpConfigKey()] = [];
        }

        // Transform command and args for PhpStorm WSL compatibility
        $transformed = $this->transformMcpCommandForWsl($command, $args);

        $config[$this->mcpConfigKey()][$name] = [
            'command' => $transformed['command'],
            'args' => $transformed['args'],
        ];

        // Remove empty arrays from existing config to avoid compatibility issues
        $config = $this->removeEmptyArrays($config);

        $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Use Windows TEMP path directly
        $tempFileName = 'mcp_'.uniqid().'.json';
        $winTempPath = "C:\\Users\\{$username}\\AppData\\Local\\Temp\\{$tempFileName}";

        // Write JSON content to temp file via PowerShell with Base64 encoding
        $base64Content = base64_encode($jsonContent);
        $writeTempCommand = 'powershell.exe -NoProfile -Command "'
            ."[System.IO.File]::WriteAllBytes('{$winTempPath}', [System.Convert]::FromBase64String('{$base64Content}'))\"";

        $writeTempResult = Process::run($writeTempCommand);

        if (! $writeTempResult->successful()) {
            return false;
        }

        // Create directory and copy file via PowerShell
        $copyCommand = 'powershell.exe -NoProfile -Command "'
            ."New-Item -ItemType Directory -Path '{$winPath}' -Force | Out-Null; "
            ."Copy-Item -Path '{$winTempPath}' -Destination '{$filePath}' -Force; "
            ."Remove-Item -Path '{$winTempPath}' -Force\"";

        $copyResult = Process::run($copyCommand);

        return $copyResult->successful();
    }

    /**
     * Transform MCP command and args to PhpStorm-compatible WSL format.
     *
     * @param  string  $command  The command from installMcpViaWsl (e.g., 'wsl', './vendor/bin/sail', or absolute sail path)
     * @param  array  $args  The arguments array
     * @return array{command: string, args: array} The transformed config for PhpStorm
     */
    public function transformMcpCommandForWsl(string $command, array $args): array
    {
        // Case 1: Sail is being used (command is ./vendor/bin/sail or absolute path to sail)
        if (str_ends_with($command, '/vendor/bin/sail') || str_ends_with($command, '\\vendor\\bin\\sail')) {
            // Expected args: ["artisan", "boost:mcp"]
            // Transform to: wsl.exe --cd /absolute/path ./vendor/bin/sail artisan boost:mcp
            $projectPath = base_path();

            return [
                'command' => 'wsl.exe',
                'args' => [
                    '--cd',
                    $projectPath,
                    './vendor/bin/sail',
                    ...$args,
                ],
            ];
        }

        // Case 2: WSL without Sail (command is already 'wsl.exe')
        if (str_starts_with($command, 'wsl')) {
            // Args are already in correct format: [php_path, artisan_path, "boost:mcp"]
            // No transformation needed
            return [
                'command' => $command,
                'args' => $args,
            ];
        }

        // Case 3: Future-proof - direct PHP path (absolute or relative)
        // This might happen if boost changes its behavior in the future
        // Transform to: wsl.exe --cd /absolute/path {command} {args}
        $projectPath = base_path();

        return [
            'command' => 'wsl.exe',
            'args' => [
                '--cd',
                $projectPath,
                $command,
                ...$args,
            ],
        ];
    }

    /**
     * Recursively remove empty arrays from config to avoid compatibility issues.
     * Some MCP tools fail when encountering empty arrays (e.g., "headers": []).
     */
    protected function removeEmptyArrays(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $this->removeEmptyArrays($value);
                }
            }
        }

        return $data;
    }
}
