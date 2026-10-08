import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { useForwardProps, useForwardPropsEmits } from 'reka-ui';
import { mount } from './mount-vue.mjs';

const primitive = (name) =>
    vue.defineComponent({
        inheritAttrs: false,
        setup:
            (_, { attrs, slots }) =>
            () =>
                vue.h(name, attrs, slots.default?.({})),
    });
const reka = {
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
