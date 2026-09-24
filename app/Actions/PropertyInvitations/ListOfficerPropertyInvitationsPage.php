<?php

namespace App\Actions\PropertyInvitations;

use App\Data\PropertyInvitationData;
use App\Data\PropertyInvitationPropertyOptionData;
use App\Models\Property;
use App\Models\PropertyInvitation;
use Illuminate\Support\Carbon;

class ListOfficerPropertyInvitationsPage
{
    /**
     * @return array{
     *     invitations: list<PropertyInvitationData>,
     *     properties: list<PropertyInvitationPropertyOptionData>,
     *     table: array{
     *         dateRanges: list<string>,
     *         values: array{
     *             created_from: string|null,
     *             created_to: string|null,
     *             expires_from: string|null,
     *             expires_to: string|null
     *         }
     *     }
     * }
     */
    public function handle(
        ?string $createdFrom = null,
        ?string $createdTo = null,
        ?string $expiresFrom = null,
        ?string $expiresTo = null,
    ): array {
        $invitations = array_values(
            PropertyInvitation::query()
                ->with(['property', 'creator'])
                ->when(
                    filled($createdFrom),
                    function ($query) use ($createdFrom): void {
                        $start = Carbon::parse($createdFrom, 'Asia/Manila')->startOfDay()->utc();
                        $query->where('created_at', '>=', $start);
                    },
                )
                ->when(
                    filled($createdTo),
                    function ($query) use ($createdTo): void {
                        $end = Carbon::parse($createdTo, 'Asia/Manila')->endOfDay()->utc();
                        $query->where('created_at', '<=', $end);
                    },
                )
                ->when(
                    filled($expiresFrom),
                    function ($query) use ($expiresFrom): void {
                        $start = Carbon::parse($expiresFrom, 'Asia/Manila')->startOfDay()->utc();
                        $query->where('expires_at', '>=', $start);
                    },
                )
                ->when(
                    filled($expiresTo),
                    function ($query) use ($expiresTo): void {
                        $end = Carbon::parse($expiresTo, 'Asia/Manila')->endOfDay()->utc();
                        $query->where('expires_at', '<=', $end);
                    },
                )
                ->latest('id')
                ->get()
                ->map(fn (PropertyInvitation $invitation): PropertyInvitationData => PropertyInvitationData::fromModel($invitation))
                ->all(),
        );

        $properties = array_values(
            Property::query()
                ->active()
                ->orderBy('block')
                ->orderBy('lot')
                ->get()
                ->map(fn (Property $property): PropertyInvitationPropertyOptionData => PropertyInvitationPropertyOptionData::fromModel($property))
                ->all(),
        );

        return [
            'invitations' => $invitations,
            'properties' => $properties,
            'table' => [
                'dateRanges' => ['created', 'expires'],
                'values' => [
                    'created_from' => $createdFrom,
                    'created_to' => $createdTo,
                    'expires_from' => $expiresFrom,
                    'expires_to' => $expiresTo,
                ],
            ],
        ];
    }
}
