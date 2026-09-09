<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home, login } from '@/routes';
import { show as privacyPolicy } from '@/routes/privacy-policy';
import type { LegalDocumentVersion } from '@/types/legal-document';

defineProps<{
    document: LegalDocumentVersion;
}>();
</script>

<template>
    <Head title="Terms of Service" />

    <div class="bg-background text-foreground flex min-h-screen flex-col">
        <header
            class="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-6 py-6"
        >
            <Link :href="home()" class="text-sm font-medium hover:underline">
                Home
            </Link>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="privacyPolicy()" class="hover:underline">
                    Privacy Policy
                </Link>
                <Link :href="login()" class="hover:underline">Log in</Link>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-6 pb-16">
            <h1 class="mb-6 text-2xl font-semibold tracking-tight">
                Terms of Service
            </h1>
            <div class="text-sm leading-relaxed whitespace-pre-wrap">
                {{ document.body }}
            </div>
        </main>
    </div>
</template>
