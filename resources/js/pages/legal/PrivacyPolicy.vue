<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RichTextContent from '@/components/RichTextContent.vue';
import { home, login } from '@/routes';
import { show as termsOfService } from '@/routes/terms-of-service';
import type { LegalDocumentVersion } from '@/types/legal-document';

defineProps<{
    document: LegalDocumentVersion;
}>();
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="bg-background text-foreground flex min-h-screen flex-col">
        <header
            class="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-6 py-6"
        >
            <Link :href="home()" class="text-sm font-medium hover:underline">
                Home
            </Link>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="termsOfService()" class="hover:underline">
                    Terms of Service
                </Link>
                <Link :href="login()" class="hover:underline">Log in</Link>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-6 pb-16">
            <h1 class="mb-6 text-2xl font-semibold tracking-tight">
                Privacy Policy
            </h1>
            <RichTextContent :html="document.body" />
        </main>
    </div>
</template>
