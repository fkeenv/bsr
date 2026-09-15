<?php

namespace App\Actions\Statements;

use App\Data\StatementOfAccountPageData;
use App\Data\StatementPropertyOptionData;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;
use App\Support\PropertyBalances;

class BuildMemberStatementOfAccountPage
{
    public function __construct(
        private BuildStatementOfAccountPage $buildStatementOfAccountPage,
        private PropertyBalances $propertyBalances,
    ) {}

    public function handle(User $member, Property $property, ?int $selectedChargeId = null): StatementOfAccountPageData
    {
        $hasLiveMembership = $member->memberships()
            ->live()
            ->where('property_id', $property->id)
            ->exists();

        if (! $hasLiveMembership) {
            abort(403);
        }

        return $this->buildStatementOfAccountPage->handle(
            $property,
            $selectedChargeId,
            $this->switcherFor($member),
        );
    }

    /**
     * @return list<StatementPropertyOptionData>
     */
    private function switcherFor(User $member): array
    {
        $memberships = Membership::query()
            ->live()
            ->where('user_id', $member->id)
            ->with('property')
            ->orderBy('property_id')
            ->get();

        return array_values($memberships
            ->map(function (Membership $membership): StatementPropertyOptionData {
                $property = $membership->property;
                $balances = $this->propertyBalances->forProperty($property);

                return new StatementPropertyOptionData(
                    property_id: $property->id,
                    label: 'Block '.$property->block.' · Lot '.$property->lot,
                    outstanding_balance: $balances['outstanding_balance'],
                );
            })
            ->all());
    }
}
