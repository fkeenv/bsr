<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { joinProperty } from '@/routes';

defineProps<{ message: string }>();
</script>

<template>
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Head title="Invitation unavailable" />
        <Heading title="Invitation unavailable" />
        <Card>
            <CardContent class="space-y-4 pt-6">
                <p role="status" class="text-muted-foreground">{{ message }}</p>
                <Button as-child variant="outline"
                    ><Link :href="joinProperty()"
                        >Back to Join a Property</Link
                    ></Button
                >
            </CardContent>
        </Card>
    </div>
</template>
