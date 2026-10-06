<?php

namespace App\Actions\Statements;

use App\Data\StatementOfAccountPageData;
use App\Data\StatementPropertyOptionData;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;

class BuildMemberStatementOfAccountPage
{
    public function __construct(
        private BuildStatementOfAccountPage $buildStatementOfAccountPage,
        private LoadPropertyFinancials $loadPropertyFinancials,
    ) {}

    public function handle(User $member, Property $property, ?int $selectedChargeId = null): StatementOfAccountPageData
    {
        $memberships = Membership::query()
            ->live()
            ->where('user_id', $member->id)
            ->with('property')
            ->orderBy('property_id')
            ->get();

        if (! $memberships->contains('property_id', $property->id)) {
            abort(403);
        }

        $properties = $memberships->map(fn (Membership $membership): Property => $membership->property);
        $financials = $this->loadPropertyFinancials->handle($properties);

        $switcher = array_values($memberships
            ->map(function (Membership $membership) use ($financials): StatementPropertyOptionData {
                $property = $membership->property;
                $balances = $financials->get($property->id)->balances;

                return new StatementPropertyOptionData(
                    property_id: $property->id,
                    label: 'Block '.$property->block.' · Lot '.$property->lot,
                    outstanding_balance: $balances['outstanding_balance'],
                );
            })
            ->all());

        return $this->buildStatementOfAccountPage->handle(
            $property,
            $selectedChargeId,
            $switcher,
            $financials->get($property->id),
        );
    }
}
