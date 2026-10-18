import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { eventDateInput } from '@/lib/eventDates';
import { eventCentavos } from '@/lib/eventMoney';
import { store, update } from '@/routes/officer/events';
import type { AssociationEvent, EventManagement } from '@/types/event';

export type EventEditorValues = {
    title: string;
    description: string;
    venue: string;
    starts_at: string;
    ends_at: string;
    registration_deadline: string;
    audience: AssociationEvent['audience'];
    capacity: string | number;
    price: string | number;
    payment_instructions: string;
    image: File | null;
};

function values(event?: AssociationEvent): EventEditorValues {
    return {
        title: event?.title ?? '',
        description: event?.description ?? '<p></p>',
        venue: event?.venue ?? '',
        starts_at: eventDateInput(event?.starts_at),
        ends_at: eventDateInput(event?.ends_at),
        registration_deadline: eventDateInput(event?.registration_deadline),
        audience: event?.audience ?? 'public',
        capacity: event?.capacity ?? '',
        price: String((event?.price_centavos ?? 0) / 100),
        payment_instructions: event?.payment_instructions ?? '',
        image: null,
    };
}

export function useEventEditor(
    event: () => AssociationEvent | undefined = () => undefined,
    management: () => EventManagement | undefined = () => undefined,
) {
    const form = useForm(values(event()));
    const element = ref<HTMLFormElement | null>(null);
    const imageRevision = ref(0);

    async function syncSavedEvent() {
        await nextTick();
        form.defaults(values(event()));
        form.reset();
        imageRevision.value += 1;
    }

    async function focusErrors() {
        await nextTick();
        const field = element.value?.querySelector<HTMLElement>(
            '[data-event-error="true"]:not([role="alert"])',
        );
        const target =
            field?.querySelector<HTMLElement>('[contenteditable="true"]') ??
            field?.querySelector<HTMLElement>(
                'input:not([type="hidden"]), textarea, button',
            ) ??
            field ??
            element.value?.querySelector<HTMLElement>('[role="alert"]');
        target?.focus();
    }

    function submit() {
        if (form.processing || event()?.status === 'cancelled') return;
        const saved = event();
        const target = saved ? update.form(saved.id) : store.form();
        form.transform(({ price, ...data }) => ({
            ...data,
            audience:
                management()?.registration_fields_locked && saved
                    ? saved.audience
                    : data.audience,
            price_centavos:
                management()?.registration_fields_locked && saved
                    ? saved.price_centavos
                    : eventCentavos(price),
        })).submit(target.method, target.action, {
            preserveScroll: true,
            onFinish: () => {
                if (form.hasErrors) void focusErrors();
            },
            onSuccess: (page) => {
                form.defaults(values(page.props.event as AssociationEvent));
                form.reset();
                imageRevision.value += 1;
            },
        });
    }

    return { form, element, imageRevision, submit, syncSavedEvent };
}
