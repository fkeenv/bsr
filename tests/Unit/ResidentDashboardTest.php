<?php

use Symfony\Component\Process\Process;

test('resident overview keeps Property actions and onboarding accessible', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/resident-dashboard.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
