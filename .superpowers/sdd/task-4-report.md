# Task 4: Override Index Template — Banner + Tablón + Categorías

**Status:** DONE

## Summary

Implemented the custom MyBB index page with rotating banner, calendar bar, tablón grid (4 cards), category visual cards, and footer.

### Steps completed

1. **Banner images directory** — Created `images/banners/` with default SVG gradient placeholder
2. **index.php PHP logic** — Added variables for: random banner, calendario text, latest 5 posts (DB query), active searches (static), news (static), curiosidades (array + JSON), staff list (DB query), and visual category cards (DB query replacing `$forums`). Inserted before the `eval('$index = ...')` line, preserving existing navbar code from Task 2.
3. **Index template inserted into MySQL** — The full HTML template was written to a temp file, single-quote escaped via PowerShell (`'` → `''`), and inserted into `mybb_templates` (sid=2) via `DELETE`+`INSERT`.
4. **Export script** — Ran `php scripts/export-theme.php`, successfully exported theme XML with 2 templates and 1 stylesheet.

### Verification

- PHP syntax check passed (`No syntax errors detected`)
- PHP execution via `shell_exec('curl -s http://localhost/iforge/index.php')` shows:
  - `iforge-banner` div rendered with correct SVG URL
  - `iforge-calendar-bar` with `DÍA 1 · PRIMAVERA · AÑO 925`
  - `iforge-tablon` with all 5 cards (latest posts, active searches, news, curiosidades, staff)
  - `iforge-categories` with category cards from DB
  - Curiosidades JavaScript with JSON data
  - Footer with powered by MyBB

### Issues

- None. File is a directory junction (`C:\laragon\www\iforge` → workspace), so single edit covers both locations.
- The `SELECT oid` query for categories was adjusted to use `type = 'c'` instead of `oid` (since MyBB categories use `type='c'` in the forums table).
