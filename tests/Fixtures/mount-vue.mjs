import { existsSync, readFileSync, statSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { compileFunction } from 'node:vm';
import { compileScript, parse, registerTS } from '@vue/compiler-sfc';
import ts from 'typescript';
import * as vue from 'vue';
import * as vueUse from '@vueuse/core';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);
registerTS(() => ts);

export function mount(
    file,
    props = {},
    capabilities = null,
    url = '/dashboard',
    adapters = {},
) {
    const page = vue.reactive({ url, props: { auth: { capabilities } } });
    const visits = [];
    const cache = new Map();
    const primitive = (name) =>
        vue.defineComponent({
            inheritAttrs: false,
            setup(_, { attrs, slots }) {
                return () => vue.h(name, attrs, slots.default?.());
            },
        });
    const primitives = new Proxy({}, { get: (_, name) => primitive(name) });
    const load = (filename) => {
        if (cache.has(filename)) return cache.get(filename);
        let source = readFileSync(filename, 'utf8');
        if (filename.endsWith('.vue')) {
            const { descriptor } = parse(source, { filename });
            source = compileScript(descriptor, {
                id: filename,
                fs: {
                    fileExists: (path) =>
                        existsSync(path) && statSync(path).isFile(),
                    readFile: (path) => readFileSync(path, 'utf8'),
                },
                inlineTemplate: true,
                templateOptions: { compilerOptions: { hoistStatic: false } },
            }).content;
        }
        const code = ts.transpileModule(source, {
            compilerOptions: {
                module: ts.ModuleKind.CommonJS,
                target: ts.ScriptTarget.ES2022,
            },
        }).outputText;
        const module = { exports: {} };
        cache.set(filename, module.exports);
        const importModule = (specifier) => {
            if (specifier in adapters) return adapters[specifier];
            if (specifier.endsWith('.css')) return {};
            if (specifier === 'vue') return vue;
            if (specifier === '@vueuse/core') return vueUse;
            if (specifier === '@inertiajs/vue3')
                return {
                    usePage: () => page,
                    Link: primitive('Link'),
                    router: { get: (...args) => visits.push(args) },
                };
            if (specifier === '@lucide/vue' || specifier === 'reka-ui')
                return primitives;
            if (specifier === '@/components/ui/sidebar')
                return {
                    ...Object.fromEntries(
                        [
                            'Sidebar',
                            'SidebarContent',
                            'SidebarFooter',
                            'SidebarHeader',
                            'SidebarMenu',
                            'SidebarMenuButton',
                            'SidebarMenuItem',
                            'SidebarGroup',
                            'SidebarMenuSub',
                            'SidebarMenuSubButton',
                            'SidebarMenuSubItem',
                        ].map((name) => [name, primitive(name)]),
                    ),
                    useSidebar: () => ({
                        isMobile: vue.ref(false),
                        state: vue.ref('expanded'),
                    }),
                };
            if (specifier.startsWith('@/components/ui/')) return primitives;
            if (specifier === '@/components/data-table/features')
                return { dataTableFeatures: {} };
            if (specifier === '@tanstack/vue-table')
                return {
                    ...require(specifier),
                    useTable: () => ({
                        getHeaderGroups: () => [],
                        getRowModel: () => ({ rows: [] }),
                        getAllColumns: () => [],
                        getVisibleLeafColumns: () => [],
                    }),
                };
            if (
                specifier.endsWith('.vue') &&
                !specifier.endsWith('/NavMain.vue')
            )
                return {
                    default: primitive(
                        specifier.split('/').at(-1).replace('.vue', ''),
                    ),
                };
            if (specifier.startsWith('@/') || specifier.startsWith('.')) {
                let target = specifier.startsWith('@/')
                    ? resolve(root, 'resources/js', specifier.slice(2))
                    : resolve(dirname(filename), specifier);
                if (!target.endsWith('.vue') && !target.endsWith('.ts')) {
                    try {
                        readFileSync(`${target}.ts`);
                        target += '.ts';
                    } catch {
                        target = resolve(target, 'index.ts');
                    }
                }
                return load(target);
            }
            return require(specifier);
        };
        compileFunction(code, ['require', 'module', 'exports'])(
            importModule,
            module,
            module.exports,
        );
        cache.set(filename, module.exports);
        return module.exports;
    };
    const renderer = vue.createRenderer({
        createElement: (type) => ({ type, props: {}, children: [] }),
        createText: (text) => ({ text }),
        createComment: () => ({}),
        setText: (node, text) => {
            node.text = text;
        },
        setElementText: (node, text) => {
            node.children = [{ text }];
        },
        patchProp: (node, key, _, value) => {
            node.props[key] = value;
        },
        insert: (node, parent, anchor) => {
            if (node.parent)
                node.parent.children.splice(
                    node.parent.children.indexOf(node),
                    1,
                );
            const index = anchor ? parent.children.indexOf(anchor) : -1;
            parent.children.splice(
                index < 0 ? parent.children.length : index,
                0,
                node,
            );
            node.parent = parent;
        },
        remove: (node) => {
            node.parent?.children.splice(node.parent.children.indexOf(node), 1);
        },
        parentNode: (node) => node.parent,
        nextSibling: (node) =>
            node.parent?.children[node.parent.children.indexOf(node) + 1],
    });
    const component = load(resolve(root, file)).default;
    const values = vue.reactive(props);
    const tree = { children: [] };
    const app = renderer.createApp({ render: () => vue.h(component, values) });
    app.mount(tree);
    const all = (type, node = tree) => [
        ...(node.type === type ? [node] : []),
        ...(node.children ?? []).flatMap((child) => all(type, child)),
    ];
    const text = (node = tree) =>
        [node.text ?? '', ...(node.children ?? []).map(text)].join(' ');
    return { page, visits, values, all, text, unmount: () => app.unmount() };
}
