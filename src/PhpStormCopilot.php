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

        return match ($platform) {
            Platform::Darwin, Platform::Linux => '~/.config/github-copilot/intellij/mcp.json',
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
        $username = getenv('USER');
        $winPath = "C:\\Users\\{$username}\\AppData\\Local\\github-copilot\\intellij";
        $filePath = "$winPath\\mcp.json";

        // Read existing config via PowerShell
        $readCommand = "powershell.exe -NoProfile -Command \"if (Test-Path '$filePath') { Get-Content '$filePath' -Raw } else { '{}' }\"";
        $result = Process::run($readCommand);

        $config = json_decode($result->output() ?: '{}', true) ?: [];

        if (! isset($config[$this->mcpConfigKey()])) {
            $config[$this->mcpConfigKey()] = [];
        }

        $config[$this->mcpConfigKey()][$name] = [
            'command' => $command,
            'args' => $args,
        ];

        $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
        $escapedJson = str_replace('"', '`"', $jsonContent);

        // Create directory and write file via PowerShell
        $writeCommand = 'powershell.exe -NoProfile -Command "'
            ."New-Item -ItemType Directory -Path '$winPath' -Force | Out-Null; "
            ."Set-Content -Path '$filePath' -Value \\\"$escapedJson\\\" -Encoding UTF8\"";

        $writeResult = Process::run($writeCommand);

        return $writeResult->successful();
    }
}
