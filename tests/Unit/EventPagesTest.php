<?php

use Symfony\Component\Process\Process;

test('Event pages offer discovery and authorized management controls in Manila time', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/events-pages.mjs'], dirname(__DIR__, 2));
    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
