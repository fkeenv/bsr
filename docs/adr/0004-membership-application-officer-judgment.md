# Membership is recognised by Officer judgment, not proof

There is no owner register, so a **Membership Application** does not collect title, lease, or other
proof. The Officer recognises the person from knowledge of the village; the applicant may leave an
optional note. The Officer records owner or resident on approval — the application has no role
field — and may change that role in place. A Property may have several live Memberships (a couple,
an owner and a tenant) but one person may not hold two on the same Property. Day-to-day,
Memberships are created only by approving an application.

## Considered Options

Required proof (title for owner, lease for resident). Rejected: the association has no process for
collecting papers, and Officers already know the households. Optional files were also rejected: an
optional note is enough when the Officer is unsure.

Applicant-stated role, taken on trust or confirmed. Rejected: the Officer is the one recognising the
Membership, so they record the role.

A contested-claim workflow when a Property already has a Membership. Rejected: a spouse applying is
the common case, not a dispute. A false claim is a rejection. A sale is ending the seller's
Membership, then approving the buyer's.

A new application row after rejection. Rejected: the applicant edits and resubmits the same
request.

An Officer shortcut that creates a Membership with no application. Rejected: that would skip the
exchange this decision is for. Super Admin bootstrap is unchanged.

Two live Memberships for one person on one Property (owner and resident as two rows). Rejected:
owner-occupier is one Membership with role owner. The Officer changes the role in place when a
tenant buys the house they already live in.

## Consequences

Changing owner to resident in place still ends Officer and Administrator assignments if that was
the person's last owner Membership (ADR-0003). Ending is dated: the person may leave with no
reason; an Officer ending one (sale, error) must leave a reason. After a Membership has ended, a
later claim is a new application; the ended Membership stays in history.

Who may see a Statement of Account, and whether one person may hold Memberships on several
Properties, remain a separate question.
