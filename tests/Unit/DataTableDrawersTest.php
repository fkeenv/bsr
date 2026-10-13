<?php

use Symfony\Component\Process\Process;

test('shared table controls expand inline hide search outside and preserve active criteria', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/data-table-drawers.mjs'], dirname(__DIR__, 2));
    $process->mustRun();
    expect($process->getOutput())->toContain('# fail 0');
});
