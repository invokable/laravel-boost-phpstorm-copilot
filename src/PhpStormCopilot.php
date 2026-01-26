<?php

declare(strict_types=1);

namespace Revolution\Laravel\Boost;

use Exception;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Enums\Platform;
use Revolution\Laravel\Boost\Concerns\WithWSL;

class PhpStormCopilot extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    use WithWSL;

    public function name(): string
    {
        return 'phpstorm-copilot';
    }

    public function displayName(): string
    {
        return 'PhpStorm with GitHub Copilot';
    }

    public function useAbsolutePathForMcp(): bool
    {
        return true;
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

    /**
     * Get the file path where AI guidelines should be written.
     *
     * @return string The relative or absolute path to the guideline file
     */
    public function guidelinesPath(): string
    {
        return config('boost.agents.phpstorm_copilot.guidelines_path', '.github/instructions/laravel-boost.instructions.md');
    }

    /**
     * Get the file path where agent skills should be written.
     */
    public function skillsPath(): string
    {
        return config('boost.agents.phpstorm_copilot.skills_path', '.github/skills');
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

    /**
     * @throws Exception
     */
    protected function installFileMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        if ($this->isRunningInTestbench()) {
            throw new Exception('Testbench is not supported. Consider using laravel-boost-copilot-cli instead.');
        }

        if ($this->isWSL()) {
            return $this->installMcpViaWsl($key, $command, $args);
        }

        $transformed = $this->transformSailCommand($command, $args);

        return parent::installFileMcp($key, $transformed['command'], $transformed['args'], $env);
    }

    /**
     * Transform Sail command to use bash -c wrapper for proper working directory.
     *
     * @param  string  $command  The command (e.g., './vendor/bin/sail' or absolute PHP path)
     * @param  array  $args  The arguments array (e.g., ["artisan", "boost:mcp"])
     * @return array{command: string, args: array} The transformed config
     */
    public function transformSailCommand(string $command, array $args): array
    {
        // If not using Sail, return as-is with absolute path if needed
        if ($command !== './vendor/bin/sail' && ! str_ends_with($command, '/vendor/bin/sail')) {
            return [
                'command' => $command,
                'args' => $args,
            ];
        }

        // Use bash -c to cd into project directory before running sail
        $projectPath = base_path();
        $argsString = implode(' ', array_map('escapeshellarg', $args));

        return [
            'command' => 'bash',
            'args' => [
                '-c',
                "cd {$projectPath} && ./vendor/bin/sail {$argsString}",
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

    protected function isRunningInTestbench(): bool
    {
        return defined('TESTBENCH_CORE');
    }
}
