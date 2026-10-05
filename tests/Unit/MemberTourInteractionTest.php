<?php

use Symfony\Component\Process\Process;

test('Member dashboard tour acknowledgement can recover without replaying', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/member-tour-interactions.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
