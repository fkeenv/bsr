<script setup lang="ts">
import { ref } from 'vue';
import { Ellipsis } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { edit } from '@/routes/officer/announcements';
import type { Announcement } from '@/types/announcement';

const props = defineProps<{ announcement: Announcement }>();
const open = ref(false);
const form = useForm({});
type Action = 'publish' | 'unpublish' | 'pin' | 'unpin';
function submit(action: Action) {
    if (form.processing) return;
    form.clearErrors();
    form.post(AnnouncementController[action].url(props.announcement), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <div class="w-full shrink-0 space-y-2 sm:w-32">
        <div class="flex items-center gap-3">
            <Button variant="outline" as-child class="min-h-11">
                <Link
                    :href="edit(announcement)"
                    :aria-label="`Edit ${announcement.title}`"
                    >Edit</Link
                >
            </Button>
            <DropdownMenu v-model:open="open">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-11"
                        :aria-label="`More actions for ${announcement.title}`"
                    >
                        <Ellipsis class="size-5" aria-hidden="true" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="min-w-44">
                    <DropdownMenuItem
                        v-if="!announcement.is_published"
                        :disabled="form.processing"
                        @select.prevent="submit('publish')"
                        >Publish</DropdownMenuItem
                    >
                    <DropdownMenuItem
                        v-else
                        :disabled="form.processing"
                        @select.prevent="submit('unpublish')"
                        >Unpublish</DropdownMenuItem
                    >
                    <DropdownMenuItem
                        v-if="
                            announcement.is_published && !announcement.is_pinned
                        "
                        :disabled="form.processing"
                        @select.prevent="submit('pin')"
                        >Pin</DropdownMenuItem
                    >
                    <DropdownMenuItem
                        v-if="announcement.is_pinned"
                        :disabled="form.processing"
                        @select.prevent="submit('unpin')"
                        >Unpin</DropdownMenuItem
                    >
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
        <p
            v-if="form.processing"
            role="status"
            class="text-muted-foreground text-xs"
        >
            Saving…
        </p>
        <div
            v-if="Object.keys(form.errors).length"
            role="alert"
            class="break-words"
        >
            <InputError
                v-for="(message, key) in form.errors"
                :key="key"
                :message="message"
            />
        </div>
    </div>
</template>
