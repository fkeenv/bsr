<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { edit as editPrivacyPolicy } from '@/routes/super-admin/privacy-policy';
import { edit as editTermsOfService } from '@/routes/super-admin/terms-of-service';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: superAdminDashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Super Admin" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Super Admin"
            description="Platform documents and association policy ops."
        />

        <ul class="max-w-lg space-y-3 text-sm">
            <li>
                <Link
                    :href="editTermsOfService()"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    Terms of Service
                </Link>
            </li>
            <li>
                <Link
                    :href="editPrivacyPolicy()"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    Privacy Policy
                </Link>
            </li>
        </ul>
    </div>
</template>
