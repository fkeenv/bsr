# The Property is the spine of the model, not the Member

Blessed Sacrament Residences has 300+ properties but only ~150 resident families, and dues for
Guard and Garbage collection are levied on a Property regardless of who lives in it. We therefore
hang Charges and Statements of Account off the **Property**, and treat a **Membership** as a
person's tie to a Property in a role (owner or resident), separate again from the **User Account**
that logs in.

## Considered Options

The obvious alternative, given that the platform was described entirely from the member's seat, is a
person-centric model where dues belong to a member and a property is just an address field on their
profile. It is simpler and would carry a smaller MVP.

We rejected it because every case that actually occurs at BSR breaks it: an owner holding several
lots, a tenant living in a house whose owner owes the dues, an owner who sells mid-year, a couple who
both want logins for one house, and — most importantly — the ~150 unoccupied properties that must
still be billable with no resident member to attach a charge to.

## Consequences

Arrears follow the property across a change of owner unless we explicitly close them out at
transfer, which is the behaviour the association actually wants but which will surprise anyone who
assumes a person owes their own debts. A member's dashboard must also handle holding more than one
property from the start, rather than assuming a single one.

Which of those Properties actually receive a Charge is ADR-0002: every house on the roster, not lots
or pre-handover inventory, and not gated on occupancy or registration.
