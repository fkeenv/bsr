<?php

use Symfony\Component\Process\Process;

test('household controls enforce independent grants and Membership self management rules', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/household-controls.mjs'], dirname(__DIR__, 2));
    $process->mustRun();
    expect($process->getOutput())->toContain('# fail 0');
});
