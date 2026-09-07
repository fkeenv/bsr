# Blessed Sacrament Residences

The member-facing platform for the Blessed Sacrament Residences homeowners association: the
association's source of truth for announcements, dues, and payment records, replacing a Facebook
group and paper bills.

## Language

**Property**:
A single house or lot within the subdivision. It is what a Statement of Account belongs to.
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
A notice published by an Officer on behalf of the association to the members it concerns.
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
The amount levied on a Property for one Billing Period, itemised by Fee Type.
_Avoid_: Bill, invoice, assessment, dues (when the record is meant)

**Fee Type**:
A named category of dues the association levies — Guard, Garbage collection, and so on.
_Avoid_: Fee, category, line item, security (when Guard is meant)

**Billing Period**:
The calendar month a Charge covers.
_Avoid_: Cycle, term, billing cycle

**Suspend**:
A pause of one Fee Type on one Property for some Billing Periods.
_Avoid_: Waiver, exemption
