<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LegalDocumentController from '@/actions/App/Http/Controllers/SuperAdmin/LegalDocumentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { edit as editTermsOfService } from '@/routes/super-admin/terms-of-service';
import type { LegalDocumentVersion } from '@/types/legal-document';

const props = defineProps<{
    document: LegalDocumentVersion | null;
}>();

const body = ref(props.document?.body ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: superAdminDashboard(),
            },
            {
                title: 'Terms of Service',
                href: editTermsOfService(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit Terms of Service" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Terms of Service"
            description="Publishing creates a new version. Applicants will accept the latest published text."
        />

        <Form
            v-bind="LegalDocumentController.updateTermsOfService.form()"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="body">Body</Label>
                <input type="hidden" name="body" :value="body" />
                <RichTextEditor id="body" v-model="body" />
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
