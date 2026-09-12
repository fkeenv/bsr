# No Cursor Co-authored-by commit trailer

**Glob:** always (any commit)

Do not add this trailer to commits:

```text
Co-authored-by: Cursor <cursoragent@cursor.com>
```

Do not pass it via `--trailer`, the commit message body, or any other mechanism.

## Cursor auto-injection

Cursor Agent may still append that trailer after `git commit` / `git commit --amend`. Keep CLI attribution off:

```json
// ~/.cursor/cli-config.json
"attribution": { "attributeCommitsToAgent": false }
```

If a commit still gains the trailer in-session, rewrite HEAD without it (unpushed only) via `git commit-tree` + `git reset --soft`, not a second `git commit --amend` that will re-inject the trailer.
