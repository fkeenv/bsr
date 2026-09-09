# Member Statement of Account is a Property split workspace

The Member Statement of Account screen is a split workspace on the Property: a left rail
holds Outstanding Balance (with Opening Balance or Prepaid remaining when relevant), pending
Payment declarations, and the Billing Period list; the right pane holds the selected period’s
Statement of Account — Fee Type lines, remaining, and Payments allocated to that Charge.
**I paid** opens a declare-Payment sheet (amount, method, reference, receipt screenshot) and
does not target a Billing Period. Period navigation is the rail, not a month pager or an
Outstanding-Balance-only hero. This matches how Officers will later drill in from the unpaid
roster without reintroducing a running ledger (ADR-0010).

## Considered Options

**Paper-bill month-first** (monthly SoA as the page, older/newer pager, Outstanding Balance as
a strip). Rejected: the cross-period question is secondary; Families who are months behind
must page to understand debt.

**Outstanding Balance hero** with an accordion period list. Rejected: strong on the total, weak
on reading one month like the paper bill the association still prints.
