<?php

use Symfony\Component\Process\Process;

test('access history shows recorded snapshots and paginated filters without mutations', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/access-history.mjs'], dirname(__DIR__, 2));
    $process->mustRun();
    expect($process->getOutput())->toContain('# fail 0');
});
