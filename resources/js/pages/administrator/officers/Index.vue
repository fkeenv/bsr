<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import OfficerController from '@/actions/App/Http/Controllers/Administrator/OfficerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { dashboard as administratorDashboard } from '@/routes/administrator';
import { index as officersIndex } from '@/routes/administrator/officers';

type Candidate = {
    id: number;
    name: string;
    email: string;
    is_officer: boolean;
    is_administrator: boolean;
};

type Props = {
    candidates: Candidate[];
    officers: Candidate[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Administrator',
                href: administratorDashboard(),
            },
            {
                title: 'Officers',
                href: officersIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Officers" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Officers"
            description="Appoint Officers from users with a live owner Membership."
        />

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Current Officers</h2>
            <div
                v-if="officers.length === 0"
                class="text-muted-foreground text-sm"
            >
                No Officers appointed yet.
            </div>
            <ul v-else class="divide-border divide-y border-y">
                <li v-for="officer in officers" :key="officer.id" class="py-3">
                    <p class="font-medium">{{ officer.name }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ officer.email }}
                    </p>
                </li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Eligible owners</h2>
            <div
                v-if="candidates.length === 0"
                class="text-muted-foreground text-sm"
            >
                No live owner Memberships to appoint from.
            </div>
            <ul v-else class="divide-border divide-y border-y">
                <li
                    v-for="candidate in candidates"
                    :key="candidate.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                >
                    <div>
                        <p class="font-medium">{{ candidate.name }}</p>
                        <p class="text-muted-foreground text-sm">
                            {{ candidate.email }}
                            <span v-if="candidate.is_officer"> · Officer</span>
                            <span v-if="candidate.is_administrator">
                                · Administrator</span
                            >
                        </p>
                    </div>

                    <Form
                        v-if="!candidate.is_officer"
                        v-bind="OfficerController.store.form()"
                        class="flex items-center gap-2"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="user_id"
                            :value="candidate.id"
                        />
                        <Button type="submit" :disabled="processing">
                            Appoint Officer
                        </Button>
                        <InputError :message="errors.user_id" />
                    </Form>
                </li>
            </ul>
        </section>
    </div>
</template>
