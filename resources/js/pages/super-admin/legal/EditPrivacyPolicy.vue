<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import LegalDocumentController from '@/actions/App/Http/Controllers/SuperAdmin/LegalDocumentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { edit as editPrivacyPolicy } from '@/routes/super-admin/privacy-policy';
import type { LegalDocumentVersion } from '@/types/legal-document';

const props = defineProps<{
    document: LegalDocumentVersion | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: superAdminDashboard(),
            },
            {
                title: 'Privacy Policy',
                href: editPrivacyPolicy(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit Privacy Policy" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Privacy Policy"
            description="Publishing creates a new version. Applicants will accept the latest published text."
        />

        <Form
            v-bind="LegalDocumentController.updatePrivacyPolicy.form()"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="body">Body</Label>
                <textarea
                    id="body"
                    name="body"
                    rows="16"
                    required
                    class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                    >{{ props.document?.body ?? '' }}</textarea>
                <InputError :message="errors.body" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Publish</Button>
                <Button variant="ghost" as-child>
                    <Link :href="superAdminDashboard()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
