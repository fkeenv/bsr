<?php

use Symfony\Component\Process\Process;

test('welcome page offers appropriate account destinations and invitation guidance', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/welcome-page.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
