<?php

declare(strict_types=1);

use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Revolution\Laravel\Boost\PhpStormCopilot;

test('PhpStormCopilot returns correct name', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->name())->toBe('phpstorm-copilot');
});

test('PhpStormCopilot returns correct display name', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->displayName())->toBe('PhpStorm with GitHub Copilot');
});

test('PhpStormCopilot returns correct MCP config key', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->mcpConfigKey())->toBe('servers');
});

test('PhpStormCopilot system detection config has paths for Darwin', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $config = $phpStormCopilot->systemDetectionConfig(\Laravel\Boost\Install\Enums\Platform::Darwin);

    expect($config)->toHaveKey('paths')
        ->and($config['paths'])->toContain('~/Library/Application Support/JetBrains/PhpStorm*/plugins/github-copilot-intellij');
});

test('PhpStormCopilot system detection config has paths for Windows', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $config = $phpStormCopilot->systemDetectionConfig(\Laravel\Boost\Install\Enums\Platform::Windows);

    expect($config)->toHaveKey('paths')
        ->and($config['paths'])->toContain('%LOCALAPPDATA%\\github-copilot\\intellij');
});

test('PhpStormCopilot project detection config checks for copilot-instructions.md', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $config = $phpStormCopilot->projectDetectionConfig();

    expect($config)->toHaveKey('files')
        ->and($config['files'])->toContain('.github/copilot-instructions.md');
});

test('PhpStormCopilot returns absolute PHP_BINARY path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->getPhpPath())->toBe(PHP_BINARY);
});

test('PhpStormCopilot returns absolute artisan path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $artisanPath = $phpStormCopilot->getArtisanPath();

    // Should be an absolute path ending with 'artisan'
    expect($artisanPath)->toEndWith('artisan')
        ->and($artisanPath)->not()->toBe('artisan');
});

test('PhpStormCopilot paths remain absolute regardless of forceAbsolutePath parameter', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    // PhpStormCopilot always uses absolute paths, so forceAbsolutePath shouldn't change behavior
    expect($phpStormCopilot->getPhpPath(true))->toBe(PHP_BINARY);
    expect($phpStormCopilot->getPhpPath(false))->toBe(PHP_BINARY);

    $artisanPath = $phpStormCopilot->getArtisanPath(true);
    expect($artisanPath)->toEndWith('artisan')
        ->and($artisanPath)->not()->toBe('artisan');

    expect($phpStormCopilot->getArtisanPath(false))->toBe($artisanPath);
});

test('transformMcpCommandForWsl handles Sail with relative path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $command = './vendor/bin/sail';
    $args = ['artisan', 'boost:mcp'];

    $result = $phpStormCopilot->transformMcpCommandForWsl($command, $args);

    expect($result['command'])->toBe('wsl.exe')
        ->and($result['args'])->toBe([
            '--cd',
            base_path(),
            './vendor/bin/sail',
            'artisan',
            'boost:mcp',
        ]);
});

test('transformMcpCommandForWsl handles Sail with absolute path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $command = '/home/user/project/vendor/bin/sail';
    $args = ['artisan', 'boost:mcp'];

    $result = $phpStormCopilot->transformMcpCommandForWsl($command, $args);

    expect($result['command'])->toBe('wsl.exe')
        ->and($result['args'])->toBe([
            '--cd',
            base_path(),
            './vendor/bin/sail',
            'artisan',
            'boost:mcp',
        ]);
});

test('transformMcpCommandForWsl handles Sail with Windows-style path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $command = 'C:\\Users\\user\\project\\vendor\\bin\\sail';
    $args = ['artisan', 'boost:mcp'];

    $result = $phpStormCopilot->transformMcpCommandForWsl($command, $args);

    expect($result['command'])->toBe('wsl.exe')
        ->and($result['args'])->toBe([
            '--cd',
            base_path(),
            './vendor/bin/sail',
            'artisan',
            'boost:mcp',
        ]);
});

test('transformMcpCommandForWsl handles WSL without Sail', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $command = 'wsl.exe';
    $args = ['/usr/bin/php', '/home/user/project/artisan', 'boost:mcp'];

    $result = $phpStormCopilot->transformMcpCommandForWsl($command, $args);

    expect($result['command'])->toBe('wsl.exe')
        ->and($result['args'])->toBe([
            '/usr/bin/php',
            '/home/user/project/artisan',
            'boost:mcp',
        ]);
});

test('transformMcpCommandForWsl handles direct PHP path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $command = '/usr/bin/php';
    $args = ['/home/user/project/artisan', 'boost:mcp'];

    $result = $phpStormCopilot->transformMcpCommandForWsl($command, $args);

    expect($result['command'])->toBe('wsl.exe')
        ->and($result['args'])->toBe([
            '--cd',
            base_path(),
            '/usr/bin/php',
            '/home/user/project/artisan',
            'boost:mcp',
        ]);
});

test('transformMcpCommandForWsl handles relative PHP path', function (): void {
    $strategyFactory = $this->mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $command = 'php';
    $args = ['artisan', 'boost:mcp'];

    $result = $phpStormCopilot->transformMcpCommandForWsl($command, $args);

    expect($result['command'])->toBe('wsl.exe')
        ->and($result['args'])->toBe([
            '--cd',
            base_path(),
            'php',
            'artisan',
            'boost:mcp',
        ]);
});
