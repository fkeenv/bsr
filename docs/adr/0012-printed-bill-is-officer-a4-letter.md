# Printed Bill is an Officer A4 letter keyed to a Billing Period

The Printed Bill is the association’s paper face for families who never
register: an **A4, one-page-per-Property** PDF an **Officer** generates
on demand for a chosen Billing Period (print one Property, or batch every
Property with Outstanding Balance > ₱0). Layout follows the classic month
letter — letterhead, Bill-to (recorded-owner or Block+Lot), that period’s
Fee Type lines, balance forward, then **Amount due = the Property’s live
Outstanding Balance**. How-to-pay and letterhead come from **Officer-editable
settings** (seeded defaults), not code. Plain paper only — no QR or barcode.
Members do not download it in MVP; they use the Statement of Account screen
(ADR-0011). Rendering uses Cloudflare PDF on Laravel Cloud (ADR-0008).

## Considered Options

**Outstanding-first notice with remittance stub.** Strong on the total; weaker
as a familiar dues letter and invents a tear-off workflow the Treasurer did
not ask for.

**Abbreviated 2-up cut slips for batch.** Dense for door drops; rejected as
the family-facing artifact — batch still emits full letter pages stacked in
one PDF.

**Amount due = this period only.** Rejected: contradicts Outstanding Balance
as the cross-period answer (ADR-0010) and understates debt for families months
behind.

**Member self-serve PDF.** Deferred; paper is the offline channel, the screen
is the Member channel.

**Machine-readable pay marks.** Out of scope with payment-gateway automation.
