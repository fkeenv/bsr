import { toUrl } from '@/lib/utils';
import type { NavItem, NavSection } from '@/types';

function pathname(url: string): string {
    return new URL(url, 'http://localhost').pathname.replace(/\/$/, '') || '/';
}

function matchesPattern(pattern: string, path: string): boolean {
    const segments = pathname(pattern)
        .replace(/%7B/gi, '{')
        .replace(/%7D/gi, '}')
        .split('/');
    const current = path.split('/');
    return (
        segments.length === current.length &&
        segments.every((segment, index) =>
            /^\{[^}]+\}$/.test(segment)
                ? current[index].length > 0
                : segment === current[index],
        )
    );
}

export function resolveNavigationSelection(
    sections: NavSection[],
    currentUrl: string,
): { sectionId: string | undefined; item: NavItem | undefined; tab?: NavItem } {
    const path = pathname(currentUrl);
    const candidates = [...sections].reverse().flatMap((section) =>
        section.items.flatMap((item) =>
            (item.tabs ?? [item]).map((destination) => ({
                section,
                item,
                destination,
                tab: item.tabs ? destination : undefined,
                path: pathname(toUrl(destination.href)),
            })),
        ),
    );
    const exact = candidates.find((candidate) => candidate.path === path);
    const patterned = candidates.find((candidate) =>
        candidate.destination.activeRoutePatterns?.some((pattern) =>
            matchesPattern(pattern, path),
        ),
    );
    const sectionPattern = [...sections]
        .reverse()
        .find((section) =>
            section.activeRoutePatterns?.some((pattern) =>
                matchesPattern(pattern, path),
            ),
        );
    const parent = candidates
        .filter(
            (candidate) =>
                candidate.path !== '/' && path.startsWith(`${candidate.path}/`),
        )
        .sort((a, b) => b.path.length - a.path.length)[0];
    const match = exact ?? patterned;
    if (match) {
        return {
            sectionId: match.section.id,
            item: match.item,
            tab: match.tab,
        };
    }
    if (sectionPattern) {
        return { sectionId: sectionPattern.id, item: undefined };
    }
    return {
        sectionId: parent?.section.id ?? sections[0]?.id,
        item: parent?.item,
        tab: parent?.tab,
    };
}
