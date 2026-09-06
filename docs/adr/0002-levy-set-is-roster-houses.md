# Roster houses get a Charge

Which Properties get a Charge was still open after hanging Charges on the Property (ADR-0001). Every
house on the roster is charged at a flat schedule, even if nobody has registered; vacant lots and
pre-handover inventory are not. Occupancy, a stored developer-handover event, and registration were
rejected as gates.

## Considered Options

Occupancy (someone lives there) as the gate, including empty house = no Charge. Rejected: occupancy
is not recorded, it is easy to game, and it contradicted ADR-0001's reason for hanging Charges on
the Property.

Developer Turnover as a stored event an Officer ticks. Rejected: the product does not track
handover. People who register are assumed to already have a turned-over house, and that house is
assumed to already be on the roster. Pre-handover developer inventory is out of the MVP.

Registration as the gate (only Properties with a User Account are billed). Rejected: the Treasurer
must still Charge a house whose family never signs up. That is ADR-0001.

Rates that vary by house type, lot size, or occupancy. Rejected: the only real bill is ₱200 Guard
and ₱200 Garbage collection for a house. Exceptions are Suspends, not a rate matrix.

## Consequences

The roster is houses already handed over, not lots. Empty houses on the roster are still charged.
Move-in is not a levy event. Owner-built houses do not happen at BSR.

The schedule is ₱200 Guard and ₱200 Garbage collection for every charged house. Officers add, change,
and retire Fee Types; a type that has been charged is retired, not deleted. A new Fee Type applies
to every roster house until an Officer Suspends it.

A Suspend is one Property, one Fee Type, a start Billing Period, and an optional end Billing Period
(no end = standing). Months only. If any Suspend covers that Property, Fee Type, and Billing Period,
that line is omitted; other Fee Types still apply. The Property stays on the roster. A messy first
or last month is an Officer hand-edit of that Charge.
