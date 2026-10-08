<?php

use Symfony\Component\Process\Process;

test('stock Accordion preserves controlled navigation and accessibility props', function () {
    $process = new Process(
        ['node', '--test', 'tests/Fixtures/stock-accordion.mjs'],
        dirname(__DIR__, 2),
    );

    $process->mustRun();

    expect($process->getOutput())->toContain('# fail 0');
});
