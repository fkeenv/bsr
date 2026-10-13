<?php

use Symfony\Component\Process\Process;

test('public announcement previews provide accessible carousel controls and article navigation', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/public-announcement-previews.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
