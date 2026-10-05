<?php

use Symfony\Component\Process\Process;

test('tour preparation belongs to a cancellable session', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/tour-session-interactions.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
