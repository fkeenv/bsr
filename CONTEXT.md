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
holds at most one live on a given Property, and it is not a platform role.
_Avoid_: Member, member record, subscription

**Membership Application**:
A person's request to be recognised as holding a Membership of one Property. It carries an
optional note; the role is not on the application.

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

**Payment**:
A record that money was received against a Charge. Money moves outside the platform — cash, bank
transfer, GCash, or Maya — so a Payment is a record of that fact, not the movement itself. A person
with a Membership declares it; an Officer confirms it.
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
