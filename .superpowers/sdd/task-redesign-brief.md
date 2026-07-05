# Task: Visual Redesign — Apply Token System

**Goal:** Replace all hardcoded values with the new token system, update DB stylesheets, recreate banner SVGs, update PHP variables to use SVGs instead of emojis, update all MyBB templates.

**Context:**
- MyBB root: `C:\laragon\www\iforge\` (junction to workspace)
- MySQL: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -u root -D mybb_foro`
- PHP binary: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
- Export script: `scripts/export-theme.php`

## Step 1: Update Stylesheet in DB

The CSS has been written to `cache/themes/theme3/iforge.css`. Now insert it into the DB:

```powershell
$css = Get-Content -Raw -Path "C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\cache\themes\theme3\iforge.css"
$escaped = $css -replace "'", "''"
$sql = @"
DELETE FROM mybb_themestylesheets WHERE name='iforge.css' AND tid=3;
INSERT INTO mybb_themestylesheets (name, tid, attachedto, stylesheet, cachefile, lastmodified)
VALUES ('iforge.css', 3, 'global', '$escaped', 'cache/themes/theme3/iforge.css', UNIX_TIMESTAMP());
"@
$sql | Out-File -FilePath temp_css.sql -Encoding ASCII
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -D mybb_foro < temp_css.sql
Remove-Item temp_css.sql
```

## Step 2: Create New Banner SVGs

Recreate all 4 banner SVGs in `images/banners/` with the new warm/parchment palette:

Each banner is 1200x500, uses a warm background gradient, and has the I-Forge title in dark text (no overlay darkening since the bg is light).

`default-banner.svg`:
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#e8e3d8"/>
      <stop offset="50%" style="stop-color:#f2efe8"/>
      <stop offset="100%" style="stop-color:#ddd8cb"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="240" font-family="Playfair Display, Georgia, serif" font-size="80" fill="#4a7c59" text-anchor="middle" letter-spacing="4" font-weight="700">I-FORGE</text>
  <text x="600" y="290" font-family="Inter, sans-serif" font-size="20" fill="#6b5e53" text-anchor="middle" letter-spacing="3">Un mundo de Cazadores</text>
</svg>
```

`banner-01.svg` (green accent, diagonal gradient):
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#e8e3d8"/>
      <stop offset="50%" style="stop-color:#f2efe8"/>
      <stop offset="100%" style="stop-color:#ddd8cb"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="240" font-family="Playfair Display, Georgia, serif" font-size="80" fill="#4a7c59" text-anchor="middle" letter-spacing="4" font-weight="700">I-FORGE</text>
  <text x="600" y="290" font-family="Inter, sans-serif" font-size="20" fill="#6b5e53" text-anchor="middle" letter-spacing="3">Un mundo de Cazadores</text>
</svg>
```

`banner-02.svg` (gold accent, different gradient direction):
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" style="stop-color:#e3ddd0"/>
      <stop offset="50%" style="stop-color:#f2efe8"/>
      <stop offset="100%" style="stop-color:#e3ddd0"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="240" font-family="Playfair Display, Georgia, serif" font-size="80" fill="#c4a951" text-anchor="middle" letter-spacing="4" font-weight="700">I-FORGE</text>
  <text x="600" y="290" font-family="Inter, sans-serif" font-size="20" fill="#6b5e53" text-anchor="middle" letter-spacing="3">Un mundo de Cazadores</text>
</svg>
```

`banner-03.svg` (vertical gradient):
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" style="stop-color:#ddd8cb"/>
      <stop offset="50%" style="stop-color:#f2efe8"/>
      <stop offset="100%" style="stop-color:#ddd8cb"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="240" font-family="Playfair Display, Georgia, serif" font-size="80" fill="#4a7c59" text-anchor="middle" letter-spacing="4" font-weight="700">I-FORGE</text>
  <text x="600" y="290" font-family="Inter, sans-serif" font-size="20" fill="#6b5e53" text-anchor="middle" letter-spacing="3">Un mundo de Cazadores</text>
</svg>
```

`banner-04.svg` (horizontal gradient):
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="100%" y1="0%" x2="0%" y2="0%">
      <stop offset="0%" style="stop-color:#e8e3d8"/>
      <stop offset="50%" style="stop-color:#f2efe8"/>
      <stop offset="100%" style="stop-color:#e8e3d8"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="240" font-family="Playfair Display, Georgia, serif" font-size="80" fill="#b85a3e" text-anchor="middle" letter-spacing="4" font-weight="700">I-FORGE</text>
  <text x="600" y="290" font-family="Inter, sans-serif" font-size="20" fill="#6b5e53" text-anchor="middle" letter-spacing="3">Un mundo de Cazadores</text>
</svg>
```

## Step 3: Update index.php PHP variables

Read `C:\laragon\www\iforge\index.php`.

Find and replace all emoji in the PHP strings with SVG `<img>` tags:

- `🗣️` → `<img src="{$bburl}/images/icons/speech.svg" class="icon" alt="">` (use PHP var: `'.$mybb->settings['bburl'].'`)
- `🔍` → same with search.svg
- `📰` → same with newspaper.svg
- `💡` → same with idea.svg
- `👥` → same with users.svg
- `📅` → same with calendar.svg
- `👑  ` → nothing, use users.svg or seal.svg
- `🛡️` → nothing, use shield.svg

IMPORTANT: The index.php uses `{$bburl}` or `'.$mybb->settings['bburl'].'` for URLs. Use the correct format.

For the card headers in PHP strings, replace:
```php
🗣️ Últimos Mensajes
```
with:
```php
<img src="'.$mybb->settings['bburl'].'/images/icons/speech.svg" class="icon" alt=""> Últimos Mensajes
```

Same for all other section headers.

For the calendar bar:
```php
📅 <strong>{$calendario_texto}</strong>
```
→ 
```php
<img src="'.$mybb->settings['bburl'].'/images/icons/calendar.svg" class="icon" alt=""> <strong>{$calendario_texto}</strong>
```

For staff icons:
```php
$icon = $roleIcons[$staff['grouptitle']] ?? '👤';
```
→ use SVG for admin: seal.svg, for mod: shield.svg, default: users.svg

## Step 4: Update MyBB Templates

### 4.1 Header template

The header template currently has `{$iforge_zona_privada_link}` and `{$iforge_user_menu}` as variables (defined in index.php, so they work globally). The header template itself shouldn't need changes since it just outputs those variables.

BUT: check if there are any hardcoded emojis in the header template. If not, leave it.

### 4.2 Index template

The index template uses `{$variables}` for all dynamic content. The PHP changes in Step 3 will flow through. BUT check the index template for any hardcoded content.

Also update the curiosidades JS to use the new classes.

### 4.3 Headerinclude template

Update `<meta charset>` to be explicit:
```html
<meta charset="utf-8">
```

### 4.4 Footer template

The footer template uses `{$mybb->settings['bbname']}` etc. Should be fine.

## Step 5: Verify

1. Open `http://localhost/iforge/index.php` - should show warm parchment background
2. Banner shows green/gold/terracotta text on light gradient
3. No emojis anywhere in the UI
4. Tablón cards have SVG icons in headers
5. Staff section has SVG icons
6. Calendar bar has calendar SVG icon
7. Category cards have sword SVG icon
8. Navbar is now warm surface color

## Step 6: Run export

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" scripts/export-theme.php
```

## Report

Write to `.superpowers\sdd\task-redesign-report.md` with:
- status: DONE or BLOCKED
- summary per step
- verify results
- any issues
