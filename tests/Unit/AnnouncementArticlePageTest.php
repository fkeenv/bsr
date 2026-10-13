<?php

use Symfony\Component\Process\Process;

test('announcement articles provide a standalone reading layout and retain attachment links', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/announcement-article.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
