<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import BillSettingsController from '@/actions/App/Http/Controllers/Officer/BillSettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { edit as billSettingsEdit } from '@/routes/officer/bill-settings';

type PaymentChannel = {
    method: string;
    detail: string;
};

type Props = {
    letterheadName: string;
    letterheadShortName: string;
    letterheadAddressLines: string[];
    letterheadContact: string;
    letterheadTreasurer: string;
    paymentChannels: PaymentChannel[];
};

const props = defineProps<Props>();

const addressLines = ref(props.letterheadAddressLines.join('\n'));
const paymentChannels = ref(
    props.paymentChannels.map((channel) => ({ ...channel })),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Printed Bill',
                href: billSettingsEdit(),
            },
        ],
    },
});

function addChannel(): void {
    paymentChannels.value.push({ method: '', detail: '' });
}

function removeChannel(index: number): void {
    if (paymentChannels.value.length <= 1) {
        return;
    }

    paymentChannels.value.splice(index, 1);
}
</script>

<template>
    <Head title="Printed Bill settings" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Printed Bill settings"
            description="Letterhead and how-to-pay channels shown on Officer-generated Printed Bills."
        />

        <Form
            v-bind="BillSettingsController.update.form()"
            class="max-w-2xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="letterhead_name">Association name</Label>
                <Input
                    id="letterhead_name"
                    name="letterhead_name"
                    required
                    :default-value="props.letterheadName"
                />
                <InputError :message="errors.letterhead_name" />
            </div>

            <div class="grid gap-2">
                <Label for="letterhead_short_name">Short name</Label>
                <Input
                    id="letterhead_short_name"
                    name="letterhead_short_name"
                    required
                    :default-value="props.letterheadShortName"
                />
                <InputError :message="errors.letterhead_short_name" />
            </div>

            <div class="grid gap-2">
                <Label for="letterhead_address_lines">Address lines</Label>
                <textarea
                    id="letterhead_address_lines_display"
                    v-model="addressLines"
                    rows="3"
                    required
                    class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-16 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                />
                <template
                    v-for="(line, index) in addressLines
                        .split('\n')
                        .map((value) => value.trim())
                        .filter(Boolean)"
                    :key="`address-${index}`"
                >
                    <input
                        type="hidden"
                        :name="`letterhead_address_lines[${index}]`"
                        :value="line"
                    />
                </template>
                <InputError :message="errors.letterhead_address_lines" />
            </div>

            <div class="grid gap-2">
                <Label for="letterhead_contact">Contact</Label>
                <Input
                    id="letterhead_contact"
                    name="letterhead_contact"
                    required
                    :default-value="props.letterheadContact"
                />
                <InputError :message="errors.letterhead_contact" />
            </div>

            <div class="grid gap-2">
                <Label for="letterhead_treasurer">Treasurer line</Label>
                <Input
                    id="letterhead_treasurer"
                    name="letterhead_treasurer"
                    required
                    :default-value="props.letterheadTreasurer"
                />
                <InputError :message="errors.letterhead_treasurer" />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <Label>How to pay</Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addChannel"
                    >
                        Add channel
                    </Button>
                </div>

                <div
                    v-for="(channel, index) in paymentChannels"
                    :key="index"
                    class="border-sidebar-border/70 dark:border-sidebar-border grid gap-2 rounded-md border p-3"
                >
                    <div
                        class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-start"
                    >
                        <div class="grid gap-2">
                            <Label :for="`payment_channels_${index}_method`"
                                >Method</Label
                            >
                            <Input
                                :id="`payment_channels_${index}_method`"
                                v-model="channel.method"
                                :name="`payment_channels[${index}][method]`"
                                required
                            />
                            <InputError
                                :message="
                                    errors[`payment_channels.${index}.method`]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`payment_channels_${index}_detail`"
                                >Detail</Label
                            >
                            <Input
                                :id="`payment_channels_${index}_detail`"
                                v-model="channel.detail"
                                :name="`payment_channels[${index}][detail]`"
                                required
                            />
                            <InputError
                                :message="
                                    errors[`payment_channels.${index}.detail`]
                                "
                            />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="paymentChannels.length <= 1"
                            @click="removeChannel(index)"
                        >
                            Remove
                        </Button>
                    </div>
                </div>
                <InputError :message="errors.payment_channels" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Save</Button>
                <Button variant="ghost" as-child>
                    <Link :href="officerDashboard()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
