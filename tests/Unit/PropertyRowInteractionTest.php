<?php

use Symfony\Component\Process\Process;

test('Property Manage dialog handles permitted operations and request outcomes', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/property-row-interactions.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
