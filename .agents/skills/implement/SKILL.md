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

## Final report

After code-review (and commit, when you commit), end with a short **human** handoff — plain language a teammate can skim. Not an agent checklist, not a dump of files touched.

Use these four sections, in this order:

### What changed

2–5 bullets on the user-visible or behaviour change. Lead with outcomes, not file paths.

### What to expect

What should now work, look different, or stay the same. Call out any deliberate gaps or follow-ups only if they affect how someone verifies the work.

### Automated checks

The exact commands to re-run locally (full suite / project CI script preferred). One short note if anything must be green before push.

### Try it in the browser

Numbered steps a person can follow: who to sign in as (or seed), which URL or nav path, what to click, and what they should see. Include only the paths that matter for this change.
