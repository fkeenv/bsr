import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { test } from 'node:test';
import postcss from 'postcss';
import { parse } from '@vue/compiler-sfc';

const root = new URL('../../', import.meta.url);
const css = postcss.parse(
    readFileSync(new URL('resources/css/app.css', root), 'utf8'),
);
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
        assert.equal(color('--sidebar-primary'), color('--primary'));
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
