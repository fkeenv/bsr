# Blessed Sacrament Residences

The member-facing platform for the Blessed Sacrament Residences homeowners association: the
association's source of truth for announcements, dues, and payment records, replacing a Facebook
group and paper bills.

## Language

**Property**:
A single handed-over house on the association roster, identified by block and lot. It is what a
Statement of Account belongs to. It may carry an optional street address, an optional recorded-owner
name (not a Membership), and an Opening Balance, and it may be made inactive so it leaves the levy
set without losing history.
_Avoid_: Unit, household, home, address

**Membership**:
A person's tie to a Property in a role — owner or resident — that authorized staff may change. It is
what redeeming a Property Invitation creates, it keeps a reference to that invitation and to the
Terms of Service and Privacy Policy versions accepted, it can be ended, a person holds at most one
live on a given Property and may hold live ones on several Properties, and it is not a platform
role.
_Avoid_: Member, member record, subscription

**Property Invitation**:
A single-use link or code issued for one Property and one role — owner or resident. Staff with Invitation creation permission may
issue either role; a User Account with a live owner Membership may invite residents to that same
active Property. Owners may view and revoke their own unused invitations for Properties they
currently own, and staff manage Invitations according to their permissions. It expires after 30 days and may
be revoked while unused. A signed-in User Account that accepts the current
Terms of Service and Privacy Policy redeems it into a live Membership straight away, with no
Officer approval step. Following one from **Join a Property** is how a person gains a Membership.
_Avoid_: Membership Application (retired before launch), registration form, onboarding form

**Property Profile**:
The shared household details of one Property — Household Members, Emergency Contacts, and Vehicles —
kept by its live Membership holders and authorized staff. It belongs to the Property, not to any one Membership.
_Avoid_: Application details, member profile

**Household Member**:
A person named on a Property Profile only. Listing them does not create a User Account or a
Membership.
_Avoid_: Family member, dependent, occupant (when the profile listing is meant)

**Emergency Contact**:
A name, contact number, and relationship listed on a Property Profile for use if the association
must reach someone other than the household.

**Vehicle**:
A year, make, model, plate, and sticker number listed on a Property Profile for vehicles kept at the
Property.
_Avoid_: Car, sticker (when the vehicle record is meant)

**Terms of Service**:
The association's published terms a person must accept to redeem a Property Invitation. Super
Admin maintains the text; the resulting Membership records which version was accepted.
_Avoid_: Bylaws checkbox, user agreement (when this document is meant)

**Privacy Policy**:
The association's published privacy notice a person must accept to redeem a Property Invitation.
Super Admin maintains the text; the resulting Membership records which version was accepted.

**User Account**:
A login belonging to one person. It exists independently of any Membership — a person can hold an
account without yet holding a Membership. It holds that person's email and mobile number.
_Avoid_: Member, user (when the Membership is what's meant)

**Super Admin**:
A seeded User Account with protected full association access, including role definitions and
Administrator appointments. It does not require a Membership and is created by seed or operations.
_Avoid_: Superuser, Administrator (when the seeded account is meant)

**Administrator**:
A person holding the protected Administrator staff role who may appoint Officers for other
eligible Owners. A live Owner Membership is required; action permissions are independently assigned.
_Avoid_: Super Admin, Superuser, admin (when Officer is meant)

**Officer**:
A person holding the Officer staff role, acting on the association’s behalf through its assigned
permissions. A live Owner Membership is required.
_Avoid_: Admin, staff (when this specific role is meant)

**Staff role**:
An association role—Officer, Administrator, or a custom role—with independently assigned action
permissions. An eligible Owner may hold several staff roles and use their combined permissions.
_Avoid_: Membership role, access level

**Custom staff role**:
An association role defined by Super Admin for a particular set of duties. It requires a live Owner
Membership and does not confer protected Administrator or Super Admin authority.

**Permission**:
An independently enabled association action for a staff role, such as reading Payments or issuing
Invitations. Enabling one action does not enable another.

**Member** (platform role):
The account state derived from holding at least one live Membership. It is not hand-assigned and
is not the same thing as a Property Membership.
_Avoid_: Member record, membership role (when Membership owner/resident is meant)

**User** (platform role):
The automatic base account state given to every User Account on registration.
_Avoid_: Member (when only a login is meant)

**Announcement**:
A notice authorized staff author on behalf of the association. It is a draft until published; published
Announcements form a single feed every live Membership can read.
_Avoid_: Post, news, bulletin

**Statement of Account**:
A Property's Charge for one Billing Period together with the Payments recorded against it.
_Avoid_: Ledger, SOA

**Printed Bill**:
The A4 PDF an Officer generates on demand for a Property and a Billing Period —
that period's Fee Type lines, balance forward, and the Property's Outstanding
Balance as amount due — so the association can still reach families on paper.
_Avoid_: Bill (when Charge is meant), invoice, PDF statement, remittance slip

**Payment**:
A record that money was received toward what a Property owes. Money moves outside the platform —
cash, bank transfer, GCash, or Maya — so a Payment is a record of that fact, not the movement
itself. A Member with a live Membership may declare it for Officer confirmation, or an Officer may
record it already confirmed; a confirmed Payment may be voided with a reason.
_Avoid_: Transaction, remittance

## Dues

**Charge**:
The amount levied on a Property for one Billing Period, itemised by Fee Type. There is one per
Property per Billing Period; the amounts start as the schedule in force when it is levied.
_Avoid_: Bill, invoice, assessment, dues (when the record is meant)

**Fee Type**:
A named category of dues the association levies — Guard, Garbage collection, and so on.
Changing its amount does not rewrite Charges already levied.
_Avoid_: Fee, category, line item, security (when Guard is meant)

**Billing Period**:
The calendar month a Charge covers.
_Avoid_: Cycle, term, billing cycle

**Suspend**:
A pause of one Fee Type on one Property for some Billing Periods.
_Avoid_: Waiver, exemption

**Opening Balance**:
Prior arrears on a Property as of the start of the first Billing Period Charges are generated for.
It is a single non-negative amount on the Property, not a Charge, and defaults to zero when nothing
was owed before go-live.
_Avoid_: Carried forward, prior balance, starting balance, credit

**Prepaid**:
The leftover confirmed Payment amount on a Property after Opening Balance and Charges have been
cleared. It applies automatically to new debt in the same order as Payment allocation.
_Avoid_: Credit, credit wallet, overpayment, advance

**Outstanding Balance**:
What a Property still owes after Prepaid: remaining Opening Balance plus remaining amounts on its
Charges. Pending Payment declarations do not reduce it. It is a derived total on the Property, not
a running ledger of movements.
_Avoid_: Arrears, balance due, amount due (when the Property total is meant)

**Unpaid roster**:
The Officer list of Properties with Outstanding Balance greater than zero — filtered and sorted
for collection follow-up, with drill-in to that Property’s Statement of Account.
_Avoid_: Arrears report, collection report, delinquent list, aging report

## Events

**Event**:
An association gathering with a venue, schedule in Manila time, registration deadline, audience,
and optional attendee capacity. It is a Draft until Published, may be Cancelled without losing its
history, and is either free or has one fixed price per attendee in Philippine pesos.
_Avoid_: Activity, meeting, campaign (when the Event record is meant)

**Event registration**:
A signed-in User Account's reservation of one or more named attendees for one Event. It belongs to
that account and Event; it does not create a Property Membership or a dues obligation.
_Avoid_: Membership registration, Property registration

**Event ticket**:
An individual attendee's admission credential for one Event registration. It is independent of
Property Membership and becomes invalid when its registration or Event is cancelled.
_Avoid_: Membership card, receipt

**Event payment record**:
A record of money received for an Event registration, separate from a Payment toward Property dues.
It and any Event refund never change a Property's Opening Balance, Charges, Prepaid, or Outstanding Balance.
_Avoid_: Payment (without the Event qualifier), dues Payment

**Event refund**:
A record of money returned for an Event registration. It belongs to the Event payment history and
does not reverse or allocate a Property dues Payment.
