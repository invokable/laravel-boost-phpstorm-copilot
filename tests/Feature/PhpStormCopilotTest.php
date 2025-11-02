<?php

declare(strict_types=1);

use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Revolution\Laravel\Boost\PhpStormCopilot;

test('PhpStormCopilot returns correct name', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->name())->toBe('phpstorm-copilot');
});

test('PhpStormCopilot returns correct display name', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->displayName())->toBe('PhpStorm with GitHub Copilot');
});

test('PhpStormCopilot returns correct MCP config key', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->mcpConfigKey())->toBe('servers');
});

test('PhpStormCopilot system detection config has paths for Darwin', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $config = $phpStormCopilot->systemDetectionConfig(\Laravel\Boost\Install\Enums\Platform::Darwin);

    expect($config)->toHaveKey('paths')
        ->and($config['paths'])->toContain('~/Library/Application Support/JetBrains/PhpStorm*/plugins/github-copilot-intellij');
});

test('PhpStormCopilot system detection config has paths for Windows', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $config = $phpStormCopilot->systemDetectionConfig(\Laravel\Boost\Install\Enums\Platform::Windows);

    expect($config)->toHaveKey('paths')
        ->and($config['paths'])->toContain('%LOCALAPPDATA%\\github-copilot\\intellij');
});

test('PhpStormCopilot project detection config checks for copilot-instructions.md', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $config = $phpStormCopilot->projectDetectionConfig();

    expect($config)->toHaveKey('files')
        ->and($config['files'])->toContain('.github/copilot-instructions.md');
});

test('PhpStormCopilot returns absolute PHP_BINARY path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    expect($phpStormCopilot->getPhpPath())->toBe(PHP_BINARY);
});

test('PhpStormCopilot returns absolute artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    $artisanPath = $phpStormCopilot->getArtisanPath();

    // Should be an absolute path ending with 'artisan'
    expect($artisanPath)->toEndWith('artisan')
        ->and($artisanPath)->not()->toBe('artisan');
});

test('PhpStormCopilot paths remain absolute regardless of forceAbsolutePath parameter', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStormCopilot = new PhpStormCopilot($strategyFactory);

    // PhpStormCopilot always uses absolute paths, so forceAbsolutePath shouldn't change behavior
    expect($phpStormCopilot->getPhpPath(true))->toBe(PHP_BINARY);
    expect($phpStormCopilot->getPhpPath(false))->toBe(PHP_BINARY);

    $artisanPath = $phpStormCopilot->getArtisanPath(true);
    expect($artisanPath)->toEndWith('artisan')
        ->and($artisanPath)->not()->toBe('artisan');

    expect($phpStormCopilot->getArtisanPath(false))->toBe($artisanPath);
});
