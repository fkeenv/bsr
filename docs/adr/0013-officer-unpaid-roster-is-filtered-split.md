# Officer unpaid roster is a filtered split workspace

The Officer unpaid roster lists every Property with Outstanding Balance > ₱0 in a
**split workspace**: a filtered left rail (default sort Outstanding Balance descending)
and the same Property Statement of Account drill-in Members use (ADR-0011). Filters are
**Block**, **Lot**, **this Billing Period status** (Paid / Unpaid / Partial — word
badges, not dots alone), and **Owes for** (Opening Balance or a Billing Period that still
has remaining). Empty means the whole roster is at ₱0; a filtered empty is “no matches.”
It is one thin Officer surface beside Announcements, Applications, Payments, and Levy —
not a reporting suite (aging and collection rates stay out).

## Considered Options

**Dense scan table** with full-page drill-in. Rejected: strong for column scanning, but
leaving the list to open a Property breaks the Treasurer’s desk rhythm; filters belong on
the list, not only on a separate results page.

**Severity-grouped cards** (this month / months behind / Opening Balance only). Rejected:
useful urgency hierarchy, but harder to filter by Block+Lot and weaker continuity with the
Member SoA surface Officers already drill into.
