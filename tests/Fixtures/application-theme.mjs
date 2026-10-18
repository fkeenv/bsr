import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { test } from 'node:test';
import postcss from 'postcss';
import { parse } from '@vue/compiler-sfc';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const root = new URL('../../', import.meta.url);
const css = postcss.parse(
    readFileSync(new URL('resources/css/app.css', root), 'utf8'),
);

void test('landing call-to-action hover is limited to hover-capable devices', () => {
    const { descriptor } = parse(
        readFileSync(new URL('resources/js/pages/Welcome.vue', root), 'utf8'),
    );
    let found = false;
    for (const style of descriptor.styles) {
        postcss.parse(style.content).walkRules((rule) => {
            if (!rule.selector.includes('.landing-primary-link:hover')) return;
            found = true;
            let parent = rule.parent;
            while (
                parent &&
                !(
                    parent.type === 'atrule' &&
                    parent.name === 'media' &&
                    parent.params === '(hover: hover)'
                )
            )
                parent = parent.parent;
            assert.ok(
                parent,
                'the CTA hover treatment needs a pointer-capability guard',
            );
        });
    }
    assert.equal(found, true);
});

void test('shared transitions keep reduced-motion feedback opacity-only', () => {
    const declarations = (selector, motionSafe = false) => {
        const result = {};
        css.walkRules((rule) => {
            if (
                !rule.selectors.some(
                    (value) =>
                        value.replace(/["']/g, '') ===
                        selector.replace(/["']/g, ''),
                )
            )
                return;
            let parent = rule.parent;
            let gated = false;
            while (parent) {
                if (parent.type === 'atrule' && parent.name === 'media') {
                    gated = parent.params.includes('no-preference');
                }
                parent = parent.parent;
            }
            if (gated && !motionSafe) return;
            rule.nodes
                .filter((node) => node.type === 'decl')
                .forEach((node) => {
                    result[node.prop] = node.value;
                });
        });
        return result;
    };
    for (const name of ['ui-fade', 'ui-surface', 'ui-sheet', 'ui-collapse']) {
        assert.equal(
            declarations(`.${name}-enter-active`)['transition-property'],
            'opacity',
        );
        assert.equal(declarations(`.${name}-enter-from`).opacity, '0');
        assert.equal(declarations(`.${name}-leave-to`).opacity, '0');
        assert.equal(declarations(`.${name}-enter-from`).transform, undefined);
        assert.notEqual(
            declarations(`.${name}-enter-active`, true)['transition-property'],
            'all',
        );
    }
    assert.equal(
        declarations('.ui-sheet-enter-from[data-side="left"]', true).transform,
        'translateX(-100%)',
    );
});

void test('appearance changes suppress transitions through the theme paint and recover after rapid changes', async (context) => {
    const originals = Object.fromEntries(
        ['window', 'document', 'localStorage', 'requestAnimationFrame'].map(
            (key) => [key, globalThis[key]],
        ),
    );
    const frames = [];
    const styles = new Set();
    const classes = new Set();
    let flushes = 0;
    globalThis.document = {
        cookie: '',
        head: { appendChild: (style) => styles.add(style) },
        createElement: () => ({
            textContent: '',
            remove() {
                styles.delete(this);
            },
        }),
        documentElement: {
            classList: {
                contains: (name) => classes.has(name),
                toggle: (name, active) =>
                    active ? classes.add(name) : classes.delete(name),
            },
        },
        body: {
            get offsetHeight() {
                flushes++;
                return 1;
            },
        },
    };
    globalThis.window = { matchMedia: () => ({ matches: false }) };
    globalThis.requestAnimationFrame = (callback) => {
        frames.push(callback);
        return frames.length;
    };
    globalThis.localStorage = { getItem: () => 'light', setItem: () => {} };
    context.after(() => {
        for (const [key, value] of Object.entries(originals)) {
            if (value === undefined) delete globalThis[key];
            else globalThis[key] = value;
        }
    });
    const ui = mount('resources/js/components/AppearanceTabs.vue');
    context.after(ui.unmount);
    const click = (label) =>
        ui
            .all('button')
            .find((button) => ui.text(button).trim() === label)
            .props.onClick();
    click('Dark');
    await vue.nextTick();
    assert.equal(classes.has('dark'), true);
    assert.ok(styles.size > 0, 'theme changes need a transition override');
    assert.ok(
        [...styles].every((style) =>
            /transition:\s*none\s*!important/.test(style.textContent),
        ),
    );
    assert.ok(flushes > 0, 'new colors commit under the override');
    click('Light');
    await vue.nextTick();
    assert.equal(classes.has('dark'), false);
    for (const callback of frames.splice(0)) callback();
    assert.ok(styles.size > 0, 'keep the override through the next paint');
    for (const callback of frames.splice(0)) callback();
    assert.equal(styles.size, 0);
});
function palette(dark) {
    const values = {};
    css.walkRules((rule) => {
        if (rule.selector !== ':root' && !(dark && rule.selector === '.dark'))
            return;
        rule.walkDecls((declaration) => {
            values[declaration.prop] = declaration.value;
        });
    });
    const resolve = (key) => {
        const value = values[key];
        assert.ok(value, `Missing theme value: ${key}`);
        const reference = /^var\((--[\w-]+)\)$/.exec(value);
        return reference ? resolve(reference[1]) : value;
    };
    return resolve;
}
function luminance(hex) {
    assert.match(hex, /^#[\da-f]{6}$/i);
    const channels = hex
        .slice(1)
        .match(/../g)
        .map((channel) => {
            const value = parseInt(channel, 16) / 255;
            return value <= 0.04045
                ? value / 12.92
                : ((value + 0.055) / 1.055) ** 2.4;
        });
    return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
}
function contrast(first, second) {
    const a = luminance(first);
    const b = luminance(second);
    return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}

for (const dark of [false, true]) {
    void test(`${dark ? 'dark' : 'light'} theme keeps body, controls, and portaled surfaces readable`, () => {
        const color = palette(dark);
        for (const [foreground, background] of [
            ['foreground', 'background'],
            ['card-foreground', 'card'],
            ['popover-foreground', 'popover'],
            ['primary-foreground', 'primary'],
            ['secondary-foreground', 'secondary'],
            ['accent-foreground', 'accent'],
            ['destructive-foreground', 'destructive'],
            ['sidebar-foreground', 'sidebar'],
            ['sidebar-primary-foreground', 'sidebar-primary'],
            ['sidebar-accent-foreground', 'sidebar-accent'],
            ['muted-foreground', 'background'],
            ['muted-foreground', 'card'],
            ['muted-foreground', 'muted'],
        ]) {
            assert.ok(
                contrast(color(`--${foreground}`), color(`--${background}`)) >=
                    4.5,
                `${foreground} on ${background}`,
            );
        }
        for (const background of ['background', 'card']) {
            assert.ok(
                contrast(color('--input'), color(`--${background}`)) >= 3,
                `Input boundary on ${background}`,
            );
            assert.ok(
                contrast(color('--ring'), color(`--${background}`)) >= 3,
                `Focus ring on ${background}`,
            );
        }
        assert.equal(color('--popover'), color('--card'));
        assert.notEqual(color('--sidebar-primary'), color('--primary'));
        assert.equal(color('color-scheme'), dark ? 'dark' : 'light');
    });
}

void test('the initial document uses the same background before the app stylesheet loads', () => {
    const document = readFileSync(
        new URL('resources/views/app.blade.php', root),
        'utf8',
    );
    const style = /<style>([\s\S]*?)<\/style>/.exec(document)[1];
    const initial = {};
    postcss.parse(style).walkRules((rule) => {
        rule.walkDecls('background-color', (declaration) => {
            initial[rule.selector] = declaration.value;
        });
    });
    assert.equal(initial.html, palette(false)('--background'));
    assert.equal(initial['html.dark'], palette(true)('--background'));
});

void test('page and navigation styles inherit semantic colors from the shared theme', () => {
    const semanticColor =
        /^--(?:background|foreground|card(?:-foreground)?|popover(?:-foreground)?|primary(?:-foreground)?|secondary(?:-foreground)?|muted(?:-foreground)?|accent(?:-foreground)?|border|input|ring|sidebar(?:-[\w-]+)?)$/;
    for (const path of [
        'resources/js/pages',
        'resources/js/layouts',
        'resources/js/components',
    ]) {
        for (const entry of readdirSync(new URL(`${path}/`, root), {
            recursive: true,
            encoding: 'utf8',
        })) {
            if (!entry.endsWith('.vue')) continue;
            const file = `${path}/${entry}`;
            const { descriptor } = parse(
                readFileSync(new URL(file, root), 'utf8'),
            );
            for (const style of descriptor.styles) {
                postcss.parse(style.content).walkDecls((declaration) => {
                    assert.equal(
                        semanticColor.test(declaration.prop),
                        false,
                        `${file} shadows ${declaration.prop}`,
                    );
                });
            }
        }
    }
});
