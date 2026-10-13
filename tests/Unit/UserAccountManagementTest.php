<?php

use Symfony\Component\Process\Process;

test('account management dialogs respect eligibility permissions and staff loss confirmation', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/user-account-management.mjs'], dirname(__DIR__, 2));
    $process->mustRun();
    expect($process->getOutput())->toContain('# fail 0');
});
