# A Charge belongs to every house on the roster, not to a login or a handover event

The association bills a flat schedule on houses it already knows about. Every house on the roster
gets a Charge for each Billing Period, whether or not anyone has a User Account or Membership.
Vacant lots are not charged. Developer handover is assumed already finished for those houses and is
not recorded. An Officer may Suspend one Fee Type on one Property for a span of Billing Periods;
that omits the line without removing the house from the roster.

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

The property roster is the levy set: houses already handed over, not lots and not developer
inventory. Charge generation applies every Fee Type unless a Suspend covers that Property, Fee Type,
and Billing Period. Officers add, change, and retire Fee Types; a type that has been charged is
retired, not deleted. Empty houses on the roster are still charged.
