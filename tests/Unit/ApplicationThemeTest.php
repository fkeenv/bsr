<?php

use Symfony\Component\Process\Process;

test('association theme stays consistent and readable across light and dark pages', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/application-theme.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
