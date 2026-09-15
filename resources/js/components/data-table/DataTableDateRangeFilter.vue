<script setup lang="ts">
import type { DateRange } from 'reka-ui';
import { DateFormatter, parseDate, today } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { cn } from '@/lib/utils';

const props = defineProps<{
    label: string;
    from: string | null | undefined;
    to: string | null | undefined;
}>();

const emit = defineEmits<{
    change: [from: string | null, to: string | null];
}>();

const TIME_ZONE = 'Asia/Manila';

const df = new DateFormatter('en-PH', {
    dateStyle: 'medium',
});

const open = ref(false);

function emptyRange(): DateRange {
    return {
        start: undefined,
        end: undefined,
    };
}

function parseBound(value: string | null | undefined): DateRange['start'] {
    if (!value) {
        return undefined;
    }

    try {
        return parseDate(value) as DateRange['start'];
    } catch {
        return undefined;
    }
}

const range = ref<DateRange>(emptyRange());

watch(
    () => [props.from, props.to] as const,
    ([from, to]) => {
        range.value = {
            start: parseBound(from),
            end: parseBound(to),
        };
    },
    { immediate: true },
);

const buttonLabel = computed(() => {
    const start = range.value.start;
    const end = range.value.end;

    if (start && end) {
        return `${df.format(start.toDate(TIME_ZONE))} – ${df.format(end.toDate(TIME_ZONE))}`;
    }

    if (start) {
        return `${df.format(start.toDate(TIME_ZONE))} – …`;
    }

    return props.label;
});

const hasValue = computed(
    () => range.value.start !== undefined || range.value.end !== undefined,
);

/** Reka UI DateRange / @internationalized DateValue private-field mismatch. */
const rangeModel = computed(
    (): DateRange =>
        ({
            start: range.value.start,
            end: range.value.end,
        }) as DateRange,
);

function onRangeUpdate(value: DateRange | undefined): void {
    const next = value ?? emptyRange();
    range.value = next;

    if (next.start && next.end) {
        emit('change', next.start.toString(), next.end.toString());
        open.value = false;
    }
}

function clearRange(): void {
    range.value = emptyRange();
    emit('change', null, null);
    open.value = false;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="
                    cn(
                        'w-[16.5rem] justify-start text-left font-normal',
                        !hasValue && 'text-muted-foreground',
                    )
                "
            >
                <CalendarIcon class="size-4" />
                <span class="truncate">{{ buttonLabel }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="end">
            <RangeCalendar
                :model-value="rangeModel"
                :number-of-months="2"
                :default-placeholder="today(TIME_ZONE)"
                disable-days-outside-current-view
                initial-focus
                @update:model-value="onRangeUpdate"
            />
            <div
                v-if="hasValue"
                class="border-border flex justify-end border-t p-2"
            >
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="clearRange"
                >
                    Clear
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
