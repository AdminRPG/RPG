# Task 7: Override `footer` Template

**Goal:** Replace MyBB's default footer with a minimal I-Forge footer.

**Context:**
- MySQL: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe` -u root -D mybb_foro
- Templateset sid=2

## Steps

### Step 1: Insert `footer` template

```sql
DELETE FROM mybb_templates WHERE title='footer' AND sid=2;
INSERT INTO mybb_templates (title, template, sid, version, status, dateline)
VALUES ('footer', '<!-- I-Forge Footer -->
<div id="iforge-footer">
  {$mybb->settings[\'bbname\']} &mdash; {$lang->powered_by} <a href="https://mybb.com" target="_blank" rel="noopener">MyBB</a>
  {$themecopyright}
</div>', 2, '1823', '', UNIX_TIMESTAMP());
```

Wait — since the template uses `{\$lang->powered_by}` with a `->` operator, the backslash escaping in SQL might cause issues. Use single quotes for array keys: `{$mybb->settings['bbname']}`.

For the SQL, escape single quotes:
- `{$mybb->settings['bbname']}` → `{$mybb->settings['\''bbname'\'']}` → Actually just change to `{\$mybb->settings['bbname']}` but this won't work in SQL.

Simpler: use heredoc-style with PowerShell to build the SQL:

```powershell
$template = @'
<!-- I-Forge Footer -->
<div id="iforge-footer">
  {$mybb->settings['bbname']} &mdash; {$lang->powered_by} <a href="https://mybb.com" target="_blank" rel="noopener">MyBB</a>
  {$themecopyright}
</div>
'@
$escaped = $template -replace "'", "''"
$sql = "DELETE FROM mybb_templates WHERE title='footer' AND sid=2;
INSERT INTO mybb_templates (title, template, sid, version, status, dateline)
VALUES ('footer', '$escaped', 2, '1823', '', UNIX_TIMESTAMP());"
$sql | Out-File -FilePath temp_footer.sql -Encoding ASCII
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -D mybb_foro < temp_footer.sql
Remove-Item temp_footer.sql
```

### Step 2: Run export script

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" scripts/export-theme.php
```

### Step 3: Verify

Check page source has `iforge-footer` div:
```powershell
curl -s http://localhost/iforge/index.php | Select-String -Pattern "iforge-footer"
```

## Report

Write to `.superpowers/sdd/task-7-report.md`.