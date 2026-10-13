<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { AccessHistoryEntry } from '@/types/access-history';
const props = defineProps<{
    entry: AccessHistoryEntry;
    permissionLabels: Record<string, string>;
}>();
const open = ref(false);
function label(value: string) {
    return props.entry.source === 'role'
        ? (props.permissionLabels[value] ?? value)
        : value.replaceAll('-', ' ');
}
const reasons: Record<string, string> = {
    'last-owner-membership-ended': 'The last live Owner Membership ended.',
    'last-owner-membership-changed-to-resident':
        'The last live Owner Membership changed to Resident.',
};
</script>
<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child
            ><Button variant="outline" size="sm">View</Button></DialogTrigger
        >
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader
                ><DialogTitle>Recorded access change</DialogTitle
                ><DialogDescription
                    >{{ entry.account_name || entry.role_name }} ·
                    {{
                        new Date(entry.occurred_at).toLocaleString('en-PH', {
                            timeZone: 'Asia/Manila',
                        })
                    }}</DialogDescription
                ></DialogHeader
            >
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">Actor</dt>
                    <dd>
                        {{ entry.actor_name }}
                        <Badge
                            v-if="entry.origin === 'automatic'"
                            variant="secondary"
                            >Automatic removal</Badge
                        >
                    </dd>
                </div>
                <div v-if="entry.account_email">
                    <dt class="text-muted-foreground">Affected account</dt>
                    <dd class="break-all">
                        {{ entry.account_name }} · {{ entry.account_email }}
                    </dd>
                </div>
            </dl>
            <p v-if="entry.reason" class="bg-muted rounded-lg p-3 text-sm">
                {{ reasons[entry.reason] || entry.reason }}
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <section
                    v-for="state in ['previous', 'new'] as const"
                    :key="state"
                    class="rounded-lg border p-4"
                >
                    <h3 class="mb-3 font-medium">
                        {{ state === 'previous' ? 'Before' : 'After' }}
                    </h3>
                    <p v-if="entry.source === 'role'" class="mb-3 text-sm">
                        Role:
                        {{
                            (state === 'previous'
                                ? entry.previous_role_name
                                : entry.new_role_name) || 'Not present'
                        }}
                    </p>
                    <p class="text-muted-foreground mb-2 text-xs">
                        {{
                            entry.source === 'role'
                                ? 'Permissions'
                                : 'Staff role assignments'
                        }}
                    </p>
                    <ul
                        v-if="
                            (state === 'previous'
                                ? entry.previous_values
                                : entry.new_values
                            ).length
                        "
                        class="space-y-2 text-sm"
                    >
                        <li
                            v-for="value in state === 'previous'
                                ? entry.previous_values
                                : entry.new_values"
                            :key="value"
                            class="break-words"
                        >
                            {{ label(value) }}
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground text-sm">None</p>
                </section>
            </div>
            <p class="text-muted-foreground text-xs">
                This is the recorded snapshot at the time of the change. History
                is read-only.
            </p>
        </DialogContent>
    </Dialog>
</template>
