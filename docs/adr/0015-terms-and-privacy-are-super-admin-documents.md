# Terms of Service and Privacy Policy are Super Admin documents

**Status**: amended — acceptance moved from Membership Application to Property Invitation redemption (see Amendment)

**Terms of Service** and **Privacy Policy** are association-authored pages. **Super Admin** creates
and updates them in the product (not Officers, and not Administrators — appointing remains their
only bundle per ADR-0003). Submitting a Membership Application requires accepting the current
published version of each; the application stores which versions were accepted.

## Considered Options

Hard-code legal text in the frontend. Rejected: the association will revise wording without a
deploy.

Let Officers or Administrators edit legal text. Rejected: this is platform/association policy
ops, not day-to-day Officer work or board appointment.

Accept “current text” with no version recorded. Rejected: later disputes need to know what the
applicant saw.

## Consequences

Public (or at least applicant-reachable) read routes exist for the current Terms and Privacy
pages. Super Admin gets CRUD (or publish-current) in their shell. Membership Application (#20)
depends on at least one published version of each existing before submit can succeed.

## Amendment: acceptance happens at Property Invitation redemption

With Membership Applications retired, a person accepts the current published Terms of Service and
Privacy Policy when redeeming a **Property Invitation**. Redemption fails if either version changed
since the confirmation page loaded. The resulting **Membership** stores the exact versions accepted,
alongside its reference to the consumed invitation. Super Admin ownership of the documents is
unchanged.
