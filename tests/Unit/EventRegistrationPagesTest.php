<?php

use Symfony\Component\Process\Process;

test('Event registration pages provide group controls and private ticket management', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/event-registration-pages.mjs'], dirname(__DIR__, 2));
    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
