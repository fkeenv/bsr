# Payment allocates on the Property, oldest debt first

A Payment lands on the Property, not on a chosen Billing Period. On confirmation it clears
Opening Balance first, then the oldest unpaid Charge as a whole amount, with any leftover held as
Prepaid on the Property and applied the same way when new debt appears. Members may declare;
Officers confirm, may create-and-confirm without a declaration, may reassign allocation only at
confirm, and void a confirmed Payment with a reason rather than edit it. Pending declarations do
not reduce the displayed balance; unmatched ones are rejected with a reason.

## Considered Options

Charge-targeted Payments (Member or Officer picks the period). Rejected: people pay "what's owed"
or "six months," Opening Balance is not a Charge, and targeting fights the paper habit.

Refusing excess or confirming only up to current unpaid. Rejected: six-month bank transfers are
common; forcing exact match or pre-generating future Charges invents Officer busywork.

A Member-facing credit wallet. Rejected for go-live credits in ADR-0006 and still rejected as a
product surface — Prepaid is only the residual of confirmed Payments under Property-level
allocation, applied automatically.

Silent edit of a confirmed Payment. Rejected: same integrity reason as freezing a Charge
(ADR-0005); void reverses allocation and restores editability.
