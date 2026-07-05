# Task 3 Report: Custom CSS — `iforge.css`

**Status:** DONE

## Summary

### 1. CSS file written to cache
- Created `C:\laragon\www\iforge\cache\themes\theme3\iforge.css` with the full dark theme CSS

### 2. Stylesheet inserted into `mybb_themestylesheets`
- Escaped single quotes in CSS (only `'Segoe UI'` needed escaping → doubled `''`)
- Ran: `DELETE + INSERT INTO mybb_themestylesheets` with escaped CSS content
- `cachefile`: `cache/themes/theme3/iforge.css`

### 3. Theme disporder updated
- Updated `mybb_themes.properties` for tid=3 to include `disporder` entry for `iforge.css` (order=1)

### 4. Theme `stylesheets` field populated
- The `stylesheets` column for tid=3 was empty (`a:0:{}`), causing MyBB not to serve any CSS
- Updated with serialized array including `global` → `global` → `iforge.css` entry, plus `inherited` parent entries

### 5. Bug fix: Header template parse error
- Discovered header template (tid=975, sid=2) used `{$mybb->settings["bburl"]}` (double quotes) which caused a PHP parse error in `eval()` at `global.php:961`
- Fixed by changing `"bburl"` → `'bburl'` in the template

## Verify Output

```
curl -s http://localhost/iforge/index.php | Select-String -SimpleMatch "iforge.css" | Measure-Object -Line
> 1
```
