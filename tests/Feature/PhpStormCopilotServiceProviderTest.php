<?php

declare(strict_types=1);

use Laravel\Boost\Boost;
use Revolution\Laravel\Boost\PhpStormCopilot;

test('PhpStormCopilotServiceProvider registers code environment', function (): void {
    $environments = Boost::getAgents();

    expect($environments)->toHaveKey('phpstorm-copilot')
        ->and($environments['phpstorm-copilot'])->toBe(PhpStormCopilot::class);
});
