# Super Admin is seeded; Officer and Administrator are dated assignments

Access is three layers that compose rather than replace each other. A **Super Admin** is a seeded
User Account with every product permission and no Membership requirement — bootstrap and recovery,
created by seed or ops, never in the application. **Officer** and **Administrator** are dated
assignments (start required, end optional; live when the end is empty or in the future). Each
requires an approved owner Membership. Ending that last qualifying Membership writes an end date on
the assignments. Titles such as Treasurer are optional display on an Officer assignment; they do
not split permissions.

## Considered Options

Titled offices (Treasurer / President / Secretary) with different permissions. Rejected: the Officer
side is thin, and a three-person board has to cover for each other.

Member as a platform role. Rejected: a User Account, a Membership, and an assignment are already
three things; a Member flag collides with Membership's owner/resident role.

Administrator as the sole god account, Membership required. Rejected: Officers approve Memberships
and Officers must already be members — go-live cannot start.

Administrator as the sole god account, Membership not required. Rejected: that login is platform
ops (the person who deploys), not a board office, and lumping it into Administrator hides the
distinction. We named the seeded login **Super Admin** instead.

Administrator includes the Officer bundle. Rejected: appointing the board is not collecting dues.
The same person may hold both assignments.

A last-Administrator lock so a sale cannot empty the role. Rejected: Super Admin is the recovery
path and can appoint the next Administrator.

## Consequences

The first User Account is a Super Admin. That account loads the roster and Memberships, then
appoints Officers and Administrators. Day-to-day association work stays on Officer. An election
reseats dated assignments; it does not touch Super Admin.

A resident Membership cannot be appointed Officer or Administrator. Owner-versus-resident
visibility of a Statement of Account is a separate question.

There is no in-app "create Super Admin." A second Super Admin is an ops act.
