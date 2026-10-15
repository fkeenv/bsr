<?php

use Symfony\Component\Process\Process;

test('Event check in pages support manual admission camera fallback and safe scanner cleanup', function () {
    $process = new Process(['node', '--test', 'tests/Fixtures/event-check-in-pages.mjs'], dirname(__DIR__, 2));
    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
