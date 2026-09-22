<?php

test('role navigation keeps its accordion and dynamic route contracts', function () {
    $appSidebar = file_get_contents(
        dirname(__DIR__, 2).'/resources/js/components/AppSidebar.vue',
    );
    $navMain = file_get_contents(
        dirname(__DIR__, 2).'/resources/js/components/NavMain.vue',
    );

    expect($appSidebar)
        ->toContain('<AccordionRoot v-model="openSection" type="single" as-child>')
        ->toContain('activeRoutePatterns: [statementOfAccountShow.definition.url]')
        ->and($navMain)
        ->toContain('<AccordionTrigger as-child>')
        ->toContain('<DropdownMenuTrigger as-child>');
});
