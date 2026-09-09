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
A person's tie to a Property in a role — owner or resident — that an Officer records and may
change. It is what a Membership Application creates once approved, it can be ended, a person
holds at most one live on a given Property and may hold live ones on several Properties, and it
is not a platform role.
_Avoid_: Member, member record, subscription

**Membership Application**:
A person's request to be recognised as holding a Membership of one Property. It selects that
Property from the roster, may list Household Members, Emergency Contacts, and Vehicles, may carry
an optional note, and must accept the current Terms of Service and Privacy Policy. The role is not
on the application — an Officer records owner or resident on approval.
_Avoid_: Registration form, onboarding form (when the application record is meant)

**Household Member**:
A person named on a Membership Application only. Listing them does not create a User Account or a
Membership.
_Avoid_: Family member, dependent, occupant (when the application listing is meant)

**Emergency Contact**:
A name, contact number, and relationship listed on a Membership Application for use if the
association must reach someone other than the applicant.

**Vehicle**:
A year, make, model, plate, and sticker number listed on a Membership Application for vehicles kept
at the Property.
_Avoid_: Car, sticker (when the vehicle record is meant)

**Terms of Service**:
The association's published terms a person must accept to submit a Membership Application. Super
Admin maintains the text; acceptance records which version was accepted.
_Avoid_: Bylaws checkbox, user agreement (when this document is meant)

**Privacy Policy**:
The association's published privacy notice a person must accept to submit a Membership Application.
Super Admin maintains the text; acceptance records which version was accepted.

**User Account**:
A login belonging to one person. It exists independently of any Membership — a person can hold an
account without yet holding a Membership. It holds that person's email and mobile number.
_Avoid_: Member, user (when the Membership is what's meant)

**Super Admin**:
A seeded User Account with every product permission. It does not require a Membership. Super Admin
accounts are created by seed or ops, not in the application.
_Avoid_: Superuser, Administrator (when the seeded account is meant)

**Administrator**:
A person with a current Administrator assignment. They appoint Officers and Administrators. This
assignment does not include Officer powers. It requires an approved owner Membership.
_Avoid_: Super Admin, Superuser, admin (when Officer is meant)

**Officer**:
A person with a current Officer assignment, acting on the association's behalf — posting
Announcements, levying dues, approving Membership Applications, and recording Payments. An
assignment requires an approved owner Membership. Treasurer, President, and Secretary are optional
titles on the assignment, not separate roles.
_Avoid_: Admin, staff

**Announcement**:
A notice an Officer authors on behalf of the association. It is a draft until published; published
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
