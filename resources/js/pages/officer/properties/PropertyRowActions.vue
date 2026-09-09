<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import PropertyController from '@/actions/App/Http/Controllers/Officer/PropertyController';
import { Button } from '@/components/ui/button';
import { edit as editProperty } from '@/routes/officer/properties';
import type { Property } from '@/types/property';

defineProps<{
    property: Property;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button variant="outline" size="sm" as-child>
            <Link :href="editProperty(property)">Edit</Link>
        </Button>
        <Form
            v-if="property.is_active"
            v-bind="PropertyController.deactivate.form(property)"
        >
            <Button type="submit" variant="ghost" size="sm">
                Deactivate
            </Button>
        </Form>
        <Form v-else v-bind="PropertyController.activate.form(property)">
            <Button type="submit" variant="ghost" size="sm">Activate</Button>
        </Form>
        <Form
            v-if="!property.has_been_charged"
            v-bind="PropertyController.destroy.form(property)"
        >
            <Button type="submit" variant="destructive" size="sm">
                Delete
            </Button>
        </Form>
    </div>
</template>
