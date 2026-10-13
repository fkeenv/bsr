<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import StaffAssignmentDialog from './StaffAssignmentDialog.vue';
import AccountMembershipDialog from './AccountMembershipDialog.vue';
import { index } from '@/routes/administrator/user-accounts';
import type {
    StaffRoleOption,
    UserAccount,
    UserAccountMembership,
} from '@/types/user-account';
defineProps<{
    account: UserAccount;
    ended_memberships: UserAccountMembership[];
    staff_role_options: StaffRoleOption[];
    can_end_memberships: boolean;
    can_change_membership_roles: boolean;
    staff_access_loss_membership_ids: number[];
}>();
function date(value: string | null) {
    return value
        ? new Date(value).toLocaleDateString('en-PH', {
              timeZone: 'Asia/Manila',
          })
        : '—';
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'User Accounts', href: index() },
            { title: 'Manage account' },
        ],
    },
});
</script>
<template>
    <div class="flex flex-col gap-6 p-4">
        <Head :title="account.name" />
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="account.name"
                description="Review account details, Property Memberships, and staff assignments."
            /><Button as-child variant="outline"
                ><Link :href="index()">Back to accounts</Link></Button
            >
        </div>
        <Card
            ><CardHeader><CardTitle>Account details</CardTitle></CardHeader
            ><CardContent
                ><dl class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-muted-foreground text-sm">Email</dt>
                        <dd class="break-all">{{ account.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Mobile number
                        </dt>
                        <dd>{{ account.mobile_number || 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Registered
                        </dt>
                        <dd>{{ date(account.registered_at) }}</dd>
                    </div>
                </dl></CardContent
            ></Card
        >
        <Card
            ><CardHeader
                ><CardTitle>Staff roles</CardTitle>
                <p class="text-muted-foreground text-sm">
                    Staff roles control association access. Owner and Resident
                    are separate Property Membership roles.
                </p></CardHeader
            ><CardContent class="space-y-3">
                <p v-if="account.is_super_admin" class="text-sm">
                    Super Admin is protected and managed through the operations
                    process.
                </p>
                <p
                    v-if="
                        !account.has_owner_membership && !account.is_super_admin
                    "
                    class="text-muted-foreground text-sm"
                >
                    A live Owner Membership is required before assigning staff
                    roles.
                </p>
                <div
                    v-for="role in staff_role_options"
                    :key="role.name"
                    class="flex items-center justify-between gap-3 rounded-lg border p-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span>{{ role.label }}</span
                        ><Badge
                            :variant="role.assigned ? 'default' : 'secondary'"
                            >{{
                                role.assigned ? 'Assigned' : 'Not assigned'
                            }}</Badge
                        >
                    </div>
                    <StaffAssignmentDialog :account="account" :role="role" />
                </div> </CardContent
        ></Card>
        <Card
            ><CardHeader
                ><CardTitle>Live Property Memberships</CardTitle></CardHeader
            ><CardContent class="space-y-3"
                ><p
                    v-if="!account.live_memberships.length"
                    class="text-muted-foreground text-sm"
                >
                    No live Property Memberships.
                </p>
                <div
                    v-for="membership in account.live_memberships"
                    :key="membership.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3"
                >
                    <div>
                        <p>
                            {{ membership.property_label }}
                            <Badge variant="secondary">{{
                                membership.role === 'owner'
                                    ? 'Owner'
                                    : 'Resident'
                            }}</Badge>
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Started {{ date(membership.started_at) }}
                        </p>
                    </div>
                    <AccountMembershipDialog
                        :account="account"
                        :membership="membership"
                        :can-end="can_end_memberships"
                        :can-change-role="can_change_membership_roles"
                        :loses-staff-access="
                            staff_access_loss_membership_ids.includes(
                                membership.id,
                            )
                        "
                    />
                </div> </CardContent
        ></Card>
        <Card
            ><CardHeader
                ><CardTitle>Ended Membership history</CardTitle></CardHeader
            ><CardContent class="space-y-3"
                ><p
                    v-if="!ended_memberships.length"
                    class="text-muted-foreground text-sm"
                >
                    No ended Memberships.
                </p>
                <div
                    v-for="membership in ended_memberships"
                    :key="membership.id"
                    class="rounded-lg border p-3"
                >
                    <p>
                        {{ membership.property_label }} ·
                        {{ membership.role === 'owner' ? 'Owner' : 'Resident' }}
                    </p>
                    <p class="text-muted-foreground text-sm">
                        {{ date(membership.started_at) }} –
                        {{ date(membership.ended_at) }} · Ended by
                        {{ membership.ended_by || 'Unknown' }}
                    </p>
                    <p class="mt-2 text-sm">
                        {{ membership.end_reason || 'No reason recorded' }}
                    </p>
                </div></CardContent
            ></Card
        >
    </div>
</template>
