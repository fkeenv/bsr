<?php

use Symfony\Component\Process\Process;

test('Officer workspace keeps everyday destinations and orientation accessible', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/officer-dashboard.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
