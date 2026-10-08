<?php

use Symfony\Component\Process\Process;

test('Announcement workspace keeps notice views and management usable', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/announcement-workspace.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
