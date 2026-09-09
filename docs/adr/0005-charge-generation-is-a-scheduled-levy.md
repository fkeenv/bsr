# Charge generation is a scheduled levy

A Charge comes into existence by generating one Billing Period for the roster: every house that does
not yet have a Charge for that period gets one, with Fee Type amounts snapshotted from the schedule
and Suspends omitted. The MVP runs this on a cron; an Officer may run the same act for a chosen
period. Re-runs fill gaps only.

## Considered Options

Officer-only generation, with a schedule later. Rejected: the MVP includes the cron, so hosting
must provide a scheduler.

Cron only, no Officer action. Rejected: go-live will not land on the configured day, a house can
join the roster mid-month, and a missed run should not wait a month.

Overwrite existing Charges on a second run. Rejected: an existing Charge is the Officer's object,
not something generation gets to rewrite.

Live lookup of Fee Type amounts. Rejected: raising a fee would rewrite every past Statement of
Account.

Allowing edits after a confirmed Payment. Rejected: the Charge is what was paid against; changing
it under a confirmed Payment is how the books start lying.

Applying a new Fee Type to Charges already generated this month. Rejected: that is the same move as
a rate change. ADR-0002's "applies to every roster house" is prospective from the next generation.

Administrator sets the generation day. Rejected: Administrator appoints only (ADR-0003). Super
Admin-only was rejected too: the bill day is association policy, and officers turn over.

## Consequences

An Officer sets the day of the month. The cron generates the calendar month it falls in. If that
day does not exist in the month, it runs on the last day. Timezone is Asia/Manila.

With no confirmed Payment, an Officer may change a line amount and add or omit a Fee Type line.
The Charge is not deleted. A confirmed Payment freezes it; voiding the Payment (ADR-0009) reverses
allocation and restores editability.

A sale mid-period still does not split the Charge (ADR-0001). No Membership still gets a Charge
(ADR-0002).
