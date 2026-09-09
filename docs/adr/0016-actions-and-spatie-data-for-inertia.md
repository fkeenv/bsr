# Use-cases are Actions; Inertia props are Spatie Data (not JsonResource)

Controllers stay thin. Each application use-case lives in an **Action** class with a single
responsibility (one public `handle` / `__invoke`). **Inertia page and shared props** that the
frontend types against are **Spatie Laravel Data** objects (with TypeScript generated from them).
We do **not** use Laravel `JsonResource` / API Resources for that path — Resources do not give us
generated TS, and this app is an Inertia SPA, not a JSON API-first surface.

Wayfinder already types routes. Spatie Data types **prop shapes**. Together they keep PHP and Vue
aligned without hand-duplicating interfaces for every domain payload.

## Considered Options

Laravel API Resources (or anonymous Resource collections) as the Inertia prop layer. Rejected:
no first-class TypeScript export, and the Resource vocabulary is for HTTP JSON APIs we are not
building as the primary UI contract.

Hand-written `resources/js/types/*` only, forever. Rejected as the long-term default: domain
payloads (Property roster, Membership Application, Statement of Account, unpaid roster) will drift
from PHP. Manual types remain fine for tiny one-off flags until a real shape exists.

Fat controllers or “service” bags with many methods. Rejected: hard to test in isolation and easy
to grow past one reason to change. Fortify already uses `app/Actions/…`; domain Actions follow that
altitude.

Passing Eloquent models straight into Inertia as the standing contract. Rejected for growing
domain screens: hidden attributes, accidental over-exposure, and no generated FE types.

## Consequences

- New write/query use-cases go in `app/Actions/…` (domain folders as needed), invoked from
  controllers or jobs — not buried as private controller methods once the logic is non-trivial.
- Install `spatie/laravel-data` (and TypeScript generation as configured) when the first fat Inertia
  prop surface lands (roster, Membership Application, or similar); do not add empty scaffolding.
- Controllers: validate (Form Request) → Action → return `Inertia::render` with Data (or primitives).
- JsonResource stays out of the Inertia prop path. If a JSON API appears later, prefer Data there
  too so one TS pipeline remains.
- Tiny props (booleans, plain strings) need not be wrapped in Data until a stable shape appears.
