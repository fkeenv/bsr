# Blank roster load; Opening Balance is prior arrears on the Property

There is no existing association register to import (see the property-records ticket). The roster is
built from a blank slate: a Super Admin CSV upload at go-live, then Officer or Super Admin add/edit
in the UI. A Property is identified by block and lot (unique together), with optional street address
and optional recorded-owner name. Re-upload creates unknown rows, updates address and recorded
owner, applies Opening Balance only on create, and never deletes. Block and lot are immutable once
set.

An Opening Balance is prior arrears (≥ ₱0, default 0) as of the start of the first Billing Period
Charges are generated for. It lives on the Property, not as a Charge or Fee Type line, and levy runs
do not rewrite it. It freezes once any confirmed Payment exists on that Property. Go-live credits /
prepaid cash are out of this field. How Payments clear it is a separate allocation decision.

A Property with no Charge may be deleted. Once any Charge exists, it is only marked inactive (off
future generation, history kept) and may be reactivated. Inactive is not a Suspend.

## Considered Options

Importing from existing books. Rejected: no books exist; the association is new.

Synthetic Charge or first-Charge line for opening arrears. Rejected: an Opening Balance is money
owed before any Billing Period in the system, and folding it into Fee Types would lie about what was
levied. Payment allocation must still be able to clear it.

Signed opening balance (credits). Rejected: prepaid at go-live is rare enough to record as a
Payment against the first Charge; a credit wallet is out of MVP.

Bulk-only roster with no UI. Rejected: houses join after go-live and corrections are day-to-day
Officer work.

Hard-delete of charged Properties. Rejected: Statements and Payment history must remain.
