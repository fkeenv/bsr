---
name: implement
description: 'Implement a piece of work based on a spec or set of tickets.'
disable-model-invocation: true
---

Implement the work described by the user in the spec or tickets.

Use /tdd where possible, at pre-agreed seams.

Run typechecking regularly, single test files regularly, and the full test suite once at the end.

Once done, use /code-review to review the work.

Commit your work to the current branch.

## Before any push

Do **not** push until local checks that mirror CI are green:

1. Prefer the project's full CI script when it exists (this repo: `composer ci:check`).
2. Otherwise: format/lint frontend, format PHP (`vendor/bin/pint --dirty`), run the **full** test suite.
3. Fix failures and re-run until green.
4. Only then push.
