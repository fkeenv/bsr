import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { useForwardProps, useForwardPropsEmits } from 'reka-ui';
import { mount } from './mount-vue.mjs';
import { compile } from 'tailwindcss';
import postcss from 'postcss';

const primitive = (name) =>
    vue.defineComponent({
        inheritAttrs: false,
        setup:
            (_, { attrs, slots }) =>
            () =>
                vue.h(name, attrs, slots.default?.({})),
    });
const unmountOnHide = vue.ref(true);
const reka = {
    injectCollapsibleRootContext: () => ({
        unmountOnHide,
        open: vue.ref(false),
    }),
    useForwardProps,
    useForwardPropsEmits,
    ...Object.fromEntries(
        [
            'AccordionRoot',
            'AccordionTrigger',
            'AccordionHeader',
            'AccordionItem',
            'AccordionContent',
        ].map((name) => [name, primitive(name)]),
    ),
};
function setup(context, name, props) {
    const ui = mount(
        `resources/js/components/ui/accordion/${name}.vue`,
        props,
        null,
        '',
        { 'reka-ui': reka },
    );
    context.after(ui.unmount);
    return ui;
}
void test('stock Accordion forwards controlled sections and updates to its caller', async (context) => {
    const changes = [];
    const ui = setup(context, 'Accordion', {
        type: 'single',
        collapsible: true,
        modelValue: 'officer',
        'onUpdate:modelValue': (value) => changes.push(value),
    });
    const root = ui.all('AccordionRoot')[0];
    assert.equal(root.props.modelValue, 'officer');
    assert.equal(root.props.type, 'single');
    assert.equal(root.props.collapsible, true);
    root.props['onUpdate:modelValue']('membership');
    assert.deepEqual(changes, ['membership']);
    ui.values.modelValue = 'membership';
    await vue.nextTick();
    assert.equal(ui.all('AccordionRoot')[0].props.modelValue, 'membership');
});

void test('Accordion content preserves retained DOM and forwards region attributes', (context) => {
    unmountOnHide.value = false;
    const content = setup(context, 'AccordionContent', {
        id: 'retained-content',
        'aria-label': 'Permissions',
    });
    context.after(() => {
        unmountOnHide.value = true;
    });
    const region = content.all('AccordionContent')[0];
    assert.ok(region, 'retained content stays mounted when its item is closed');
    assert.equal(region.props.id, 'retained-content');
    assert.equal(region.props['aria-label'], 'Permissions');
    assert.notEqual(region.props.forceMount, true);
});
void test('stock Accordion retains disabled items and named sections', (context) => {
    const trigger = setup(context, 'AccordionTrigger', {});
    assert.equal(trigger.all('ChevronDown').length, 1);
    const item = setup(context, 'AccordionItem', {
        value: 'officer',
        disabled: true,
    });
    assert.equal(item.all('AccordionItem')[0].props.value, 'officer');
    assert.equal(item.all('AccordionItem')[0].props.disabled, true);
    const content = setup(context, 'AccordionContent', { forceMount: true });
    assert.equal(content.all('AccordionContent')[0].props.forceMount, true);
});

void test('reduced motion disables navigation movement even when timing utilities are present', async (context) => {
    const trigger = setup(context, 'AccordionTrigger', {});
    const sidebar = mount(
        'resources/js/components/ui/sidebar/Sidebar.vue',
        {},
        null,
        '/dashboard',
        {
            './utils': {
                useSidebar: () => ({
                    isMobile: vue.ref(false),
                    state: vue.ref('expanded'),
                }),
            },
        },
    );
    context.after(sidebar.unmount);
    const elements = [
        trigger.all('ChevronDown')[0],
        ...sidebar
            .all('div')
            .filter((node) =>
                node.props.class?.includes('motion-safe:transition-'),
            ),
    ];
    assert.equal(elements.length, 3);
    for (const element of elements) {
        const compiler = await compile('@tailwind utilities;');
        const css = postcss.parse(
            compiler.build(element.props.class.split(/\s+/)),
        );
        const durations = [];
        css.walkDecls('transition-duration', (declaration) => {
            let parent = declaration.parent;
            let motionSafe = false;
            while (parent) {
                if (
                    parent.type === 'atrule' &&
                    parent.name === 'media' &&
                    parent.params.includes('no-preference')
                )
                    motionSafe = true;
                parent = parent.parent;
            }
            if (!motionSafe) durations.push(declaration.value);
        });
        assert.ok(
            durations.every((value) => ['0s', '0ms'].includes(value)),
            'reduced motion must not inherit a nonzero transition duration',
        );
    }
});
