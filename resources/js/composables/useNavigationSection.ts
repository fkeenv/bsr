import { ref } from 'vue';

const openNavigationSection = ref<string>();

/**
 * Shared open state of the sidebar's navigation accordion, so guided tours can
 * reveal a section and restore what the person had open.
 */
export function useNavigationSection() {
    return { openNavigationSection };
}
