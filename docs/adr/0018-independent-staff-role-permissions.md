# Staff roles grant independent permissions

**Status**: accepted; supersedes [ADR-0017](0017-spatie-platform-roles-with-level-inheritance.md).

Staff access is the union of catalog permissions assigned to Officer, Administrator,
and custom staff roles, without numeric inheritance, deny overrides, or direct User
Account grants. A live Owner Membership is required for each staff role. Super Admin
is exempt and always has full access, including future catalog additions; new
permissions remain ungranted to staff roles until explicitly enabled.

## Protected authority

Only Super Admin manages role definitions and grants or appoints Administrators.
Only Super Admin and eligible Administrators appoint Officers; Administrators
cannot appoint themselves. Custom grants cannot confer these protected powers.
The five built-in roles cannot be renamed or deleted; an assigned custom role cannot
be deleted. Member remains derived from live Memberships, User remains automatic,
and Member/Owner self-service stays scoped to the relevant Property.

## Consequences

Role CRUD and grant changes commit together with their audit records. Permissions
and protected-role identity are read afresh on the next request, including existing
sessions. Losing the last live Owner Membership removes all staff assignments in
the Membership transaction and audit; ownership returning never restores them.
Zero-permission staff may open the association workspace, but no action permissions
are implied. Role assignments from the User Account directory are a subsequent slice.

## Considered Options

Numeric Administrator inheritance was rejected because it overrides independently
configured Officer grants. Direct account grants and deny overrides were rejected
because they make combined staff roles hard to understand and audit.
