<?php

use Symfony\Component\Process\Process;

test('Property Invitation controls support search and credential sharing', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/property-invitations.mjs'], dirname(__DIR__, 2));
    $process->mustRun();
    expect($process->getOutput())->toContain('# fail 0');
});
