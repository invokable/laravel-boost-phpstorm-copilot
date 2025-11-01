<?php

declare(strict_types=1);

namespace Revolution\Laravel\Boost;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\Enums\Platform;

class PhpStormCopilot extends CodeEnvironment implements Agent, McpClient
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
        // 実機で確認済：Darwin(macOS), Windows
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

    /**
     * Get the file path where AI guidelines should be written.
     *
     * @return string The relative or absolute path to the guideline file
     */
    public function guidelinesPath(): string
    {
        return '.github/copilot-instructions.md';
    }

    public function mcpConfigKey(): string
    {
        return 'servers';
    }

    public function mcpConfigPath(): string
    {
        $platform = Platform::current();

        // 実機で確認済：Darwin(macOS), Windows
        return match ($platform) {
            Platform::Darwin, Platform::Linux => '~/.config/github-copilot/intellij/mcp.json',
            Platform::Windows => '%LOCALAPPDATA%\\github-copilot\\intellij\\mcp.json',
        };
    }
}
