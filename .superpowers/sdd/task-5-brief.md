# Task 5: Override `headerinclude` Template

**Goal:** Strip the MyBB `<head>` down to essentials. Remove RSS links, extra meta tags, and default clutter. Keep only charset, viewport, stylesheets, and JS.

**Context:**
- MyBB root: `C:\laragon\www\iforge\` (junction to workspace root)
- MySQL: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe` -u root -D mybb_foro
- Templateset sid=2 ("I-Forge RPG" child theme)

## Steps

### Step 1: Insert `headerinclude` template

Run this SQL:

```sql
DELETE FROM mybb_templates WHERE title='headerinclude' AND sid=2;
INSERT INTO mybb_templates (title, template, sid, version, status, dateline)
VALUES ('headerinclude', '<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
<meta name="viewport" content="width=device-width, initial-scale=1">
{$stylesheets}
<script src="{$mybb->settings[\"bburl\"]}/jscripts/jquery.js?ver=1823"></script>
<script src="{$mybb->settings[\"bburl\"]}/jscripts/general.js?ver=1823"></script>
<script src="{$mybb->settings[\"bburl\"]}/jscripts/rol-widgets.js?ver=1"></script>', 2, '1823', '', UNIX_TIMESTAMP());
```

Note: The template uses `{\"bburl\"}` for double quotes inside the PHP string — MyBB evaluates these as `$mybb->settings['bburl']`.

### Step 2: Run export script

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" scripts/export-theme.php
```

### Step 3: Verify

Check the page source has the clean head:
```powershell
curl -s http://localhost/iforge/index.php | Select-String -Pattern "rol-widgets.js"
```
Should find a match.

## Report

Write to `.superpowers/sdd/task-5-report.md` with status DONE/BLOCKED, summary, and verify output.