<?php

declare(strict_types=1);

namespace Revolution\Laravel\Boost\Concerns;

use Illuminate\Support\Facades\Process;

trait WithWSL
{
    protected function isWSL(): bool
    {
        return ! empty(getenv('WSL_DISTRO_NAME'));
    }

    protected function installMcpViaWsl(string $name, string $command, array $args): bool
    {
        // Get Windows LOCALAPPDATA path via wslvar command
        $localAppDataResult = Process::run('wslvar LOCALAPPDATA')->throw();

        $localAppData = trim($localAppDataResult->output());
        if (empty($localAppData)) {
            return false;
        }

        $winPath = "{$localAppData}\\github-copilot\\intellij";
        $filePath = "{$winPath}\\mcp.json";

        // Read existing config via PowerShell
        $readCommand = "powershell.exe -NoProfile -Command \"if (Test-Path '{$filePath}') { Get-Content '{$filePath}' -Raw } else { '{}' }\"";
        $result = Process::run($readCommand)->throw();

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
        $winTempPath = "{$localAppData}\\Temp\\{$tempFileName}";

        // Write JSON content to temp file via PowerShell with Base64 encoding
        $base64Content = base64_encode($jsonContent);
        $writeTempCommand = 'powershell.exe -NoProfile -Command "'
            ."[System.IO.File]::WriteAllBytes('{$winTempPath}', [System.Convert]::FromBase64String('{$base64Content}'))\"";

        $writeTempResult = Process::run($writeTempCommand)->throw();

        if (! $writeTempResult->successful()) {
            return false;
        }

        // Create directory and copy file via PowerShell
        $copyCommand = 'powershell.exe -NoProfile -Command "'
            ."New-Item -ItemType Directory -Path '{$winPath}' -Force | Out-Null; "
            ."Copy-Item -Path '{$winTempPath}' -Destination '{$filePath}' -Force; "
            ."Remove-Item -Path '{$winTempPath}' -Force\"";

        $copyResult = Process::run($copyCommand)->throw();

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
}
