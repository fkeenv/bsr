<?php

use Symfony\Component\Process\Process;

test('association navigation preserves authorized destinations and mobile drawer behavior', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/sidebar-navigation.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
