# Platform roles are Spatie levels with strict inheritance

Access is five Spatie roles ordered by level (lower number = more power). A higher
level may do everything a lower level may do. **Member** is synced from a live
Membership and is never hand-assigned. Officer and Administrator are immediate Spatie
assign/revoke (no dated assignments). Super Admin is the `super-admin` role — the
`users.is_super_admin` column is removed.

| Level | Role | Notes |
|------:|------|--------|
| 0 | `super-admin` | Seed/ops; no Membership required; seed as `super-admin` + `user` |
| 1 | `administrator` | Includes Officer powers; may appoint Officers; requires owner Membership |
| 2 | `officer` | Association day-to-day work; requires owner Membership |
| 3 | `member` | Synced from live Membership |
| 4 | `user` | Assigned on registration |

## Considered Options

Dated Officer/Administrator assignments (ADR-0003). Rejected for MVP: revoke on
election is enough; dates added ceremony without product need yet.

Administrator without Officer powers. Rejected: inheritance is intentional so Admin
can cover Officer work.

Member as Membership-only (no Spatie role). Rejected: the level ladder needs Member
as level 3; sync keeps it aligned with live Memberships.

Bespoke `is_super_admin` + assignment tables. Rejected in favour of
`spatie/laravel-permission` so product permissions can grow on the same package.

## Consequences

Gates check numeric level (Officer surfaces: level ≤ 2). Appointing adds the new
role and ensures `user`; Member stays membership-synced. Ending the last live
Membership revokes `member`, `officer`, and `administrator`. Super Admin or
Administrator appoint Officers; Member is never appointed by hand. Optional titles
and avatars belong on a future `user_profiles` table, not on roles.
