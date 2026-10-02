# Membership Application carries household details; Officer judgment still decides

**Status**: superseded — Membership Applications were retired before launch (see Amendment)

A **Membership Application** still creates Membership only when an **Officer** (or Super Admin)
approves it by judgment — no title or lease upload (that part of ADR-0004 stands). The application
itself is no longer note-only: the applicant selects a roster **Property**, may list **Household
Members** (name only), **Emergency Contacts** (name, contact number, relationship), and **Vehicles**
(year, make, model, plate, sticker number), may leave an optional note, and must accept the current
**Terms of Service** and **Privacy Policy**. Owner versus resident is still recorded by the Officer
on approval, not on the form. Rejection keeps the same application editable and resubmittable.

## Considered Options

Keep the application as Property + optional note only. Rejected: the association needs household,
emergency, and vehicle details at recognition time, and applicants must accept published legal
text.

Treat listed Household Members as User Accounts or Memberships. Rejected: a name on the application
is not a login and is not a Membership; adults who need access apply themselves.

Applicant-stated role on the form. Rejected: unchanged from ADR-0004 — the Officer records the role.

## Consequences

Plain User Accounts without a live Membership are routed to Membership Application onboarding, not
into Officer surfaces. Name, email, and mobile stay on the User Account; Block+Lot comes from the
selected Property on the roster. Legal-document publishing and versioned acceptance are covered in
ADR-0015. This supersedes the field-scope part of ADR-0004; Officer judgment, no-proof, no
applicant role, and reject-and-edit remain.

## Amendment: household details belong to the Property Profile

The Membership Application and its Household Member, Emergency Contact, and Vehicle tables were
removed before launch in a clean rebuild. Those details now live only on the shared **Property
Profile**, which a Property's live Memberships and Officers maintain. Memberships come from
redeeming a **Property Invitation** (ADR-0004 amendment). Plain User Accounts without a live
Membership are no longer routed to onboarding; they open the dashboard and use **Join a Property**.
