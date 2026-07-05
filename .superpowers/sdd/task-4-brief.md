# Task 4: Override Index Template — Banner + Tablón + Categorías

**Goal:** Replace the MyBB index page with our custom layout: rotating banner, calendar bar, tablón grid (4 cards), category visual cards, and footer.

**Context:**
- MyBB at `C:\laragon\www\iforge\`
- MySQL: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe` -u root -D mybb_foro
- PHP: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
- Child theme tid=3, templateset sid=2

## Steps

### Step 1: Create banner images directory

```bash
mkdir -p C:\laragon\www\iforge\images\banners
```

Create a default banner gradient as an SVG placeholder at `C:\laragon\www\iforge\images\banners\default-banner.svg`:

```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#0d1117"/>
      <stop offset="50%" style="stop-color:#1c2128"/>
      <stop offset="100%" style="stop-color:#161b22"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
</svg>
```

### Step 2: Insert `index` template into MyBB

Run this SQL to override the index template:

Note: The template HTML must be escaped for single quotes (every `'` becomes `''`).

```sql
DELETE FROM mybb_templates WHERE title='index' AND sid=2;
INSERT INTO mybb_templates (title, template, sid, version, status, dateline)
VALUES ('index', '[INDEX_HTML]', 2, '1823', '', UNIX_TIMESTAMP());
```

The [INDEX_HTML] is:

```html
<!DOCTYPE html>
<html lang="es">
<head>
<title>{$mybb->settings['bbname']}</title>
{$headerinclude}
</head>
<body>
{$header}

<div id="iforge-banner" style="background-image: url('{$banner_url}');">
  <div class="iforge-banner-content">
    <h1 class="iforge-banner-title">I-FORGE</h1>
    <p class="iforge-banner-subtitle">Un mundo de Cazadores</p>
  </div>
</div>

<div id="iforge-calendar-bar" onclick="window.location.href='{$mybb->settings['bburl']}/calendario.php'">
  📅 <strong>{$calendario_texto}</strong>
  &nbsp;—&nbsp; ⏱ 1 día real = 2 días on-rol
</div>

<div class="iforge-tablon">
  <div class="iforge-card">
    <div class="iforge-card-header">🗣️ Últimos Mensajes</div>
    <div class="iforge-card-body">{$iforge_latest_posts}</div>
    <a href="{$mybb->settings['bburl']}/search.php?action=latest" class="iforge-card-more">← Ver más →</a>
  </div>
  <div class="iforge-card">
    <div class="iforge-card-header">🔍 Búsquedas Activas</div>
    <div class="iforge-card-body">{$iforge_active_searches}</div>
    <a href="#" class="iforge-card-more">+ Publicar búsqueda</a>
  </div>
  <div class="iforge-card">
    <div class="iforge-card-header">📰 Noticias</div>
    <div class="iforge-card-body">{$iforge_news}</div>
    <a href="{$mybb->settings['bburl']}/forumdisplay.php?fid=1" class="iforge-card-more">← Archivo →</a>
  </div>
  <div class="iforge-card">
    <div class="iforge-card-header">💡 Curiosidades</div>
    <div class="iforge-card-body" id="iforge-curiosidad">{$iforge_curiosidad}</div>
    <a href="#" class="iforge-card-more" id="iforge-next-curiosidad">← Otra curiosidad →</a>
  </div>
  <div class="iforge-card iforge-staff-section">
    <div class="iforge-card-header">👥 Staff</div>
    <div class="iforge-staff-list">{$iforge_staff_list}</div>
  </div>
</div>

<div class="iforge-categories">
  {$forums}
</div>

<div id="iforge-footer">
  {$mybb->settings['bbname']} &mdash; {$lang->powered_by} <a href="https://mybb.com" target="_blank">MyBB</a>
  {$themecopyright}
</div>

<script>
var iforge_curiosidades = {$curiosidades_json};
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('iforge-user-btn');
  var dd = document.getElementById('iforge-dropdown');
  if (btn && dd) {
    btn.addEventListener('click', function(e) { e.stopPropagation(); dd.classList.toggle('open'); });
    document.addEventListener('click', function() { dd.classList.remove('open'); });
    dd.addEventListener('click', function(e) { e.stopPropagation(); });
  }
  var cBtn = document.getElementById('iforge-next-curiosidad');
  var cEl = document.getElementById('iforge-curiosidad');
  if (cBtn && cEl && iforge_curiosidades.length) {
    var cIdx = 0;
    cBtn.addEventListener('click', function(e) {
      e.preventDefault();
      cIdx = (cIdx + 1) % iforge_curiosidades.length;
      cEl.textContent = iforge_curiosidades[cIdx];
    });
  }
});
</script>
</body>
</html>
```

For the SQL INSERT, escape single quotes in the HTML:
- Every `'` becomes `''`
- The `{$var}` interpolation syntax stays as-is (MyBB's eval() handles it)
- The `{\$` doesn't need escaping - it's PHP echo, not SQL

Better approach: write the template to a file, read it with PowerShell, escape quotes, and run SQL.

### Step 3: Add PHP logic to `index.php`

Read `C:\laragon\www\iforge\index.php`. 

Replace the `eval('$index = ...')` section (around line 468) and everything above it (the current banner/staff/calendario logic that was added in previous tasks should be kept).

Add this code BEFORE the eval line (if not already there from previous tasks, add it):

```php
// I-Forge: Random banner
$banner_url = $mybb->settings['bburl'] . '/images/banners/default-banner.svg';
$bannerDir = MYBB_ROOT . 'images/banners/';
$banners = glob($bannerDir . '*.{svg,jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (!empty($banners)) {
    $randomBanner = $banners[array_rand($banners)];
    $banner_url = $mybb->settings['bburl'] . '/images/banners/' . basename($randomBanner);
}

// I-Forge: Calendario (placeholder until real calendar is built)
$calendario_texto = 'DÍA 1 · PRIMAVERA · AÑO 925';

// I-Forge: Latest posts
$iforge_latest_posts = '';
$q = $db->query("
    SELECT p.pid, p.subject, p.tid, p.uid, p.dateline, u.username
    FROM ".TABLE_PREFIX."posts p
    LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = p.uid)
    WHERE p.visible = 1
    ORDER BY p.dateline DESC
    LIMIT 5
");
while ($post = $db->fetch_array($q)) {
    $date = date('d/m', $post['dateline']);
    $iforge_latest_posts .= '
    <a href="'.$mybb->settings['bburl'].'/showthread.php?tid='.$post['tid'].'&pid='.$post['pid'].'" class="iforge-card-item">
        '.htmlspecialchars_uni($post['subject']).'
        <div class="iforge-card-item-meta">'.$post['username'].' · '.$date.'</div>
    </a>';
}

// I-Forge: Active searches (static placeholder)
$iforge_active_searches = '
    <div class="iforge-card-item">
        Busco compa&ntilde;ero para T3
        <div class="iforge-card-item-meta">Kael · Zona Candelaria</div>
    </div>
    <div class="iforge-card-item">
        Mazmorra busca DPS
        <div class="iforge-card-item-meta">Lyra · Continente Oscuro</div>
    </div>';

// I-Forge: News (static placeholder)
$iforge_news = '
    <div class="iforge-card-item">
        ⚔️ Torneo de combate — Inscripciones abiertas
        <div class="iforge-card-item-meta">hasta el D&iacute;a 60</div>
    </div>
    <div class="iforge-card-item">
        📌 Parche 1.2 — Nuevo sistema de clima
        <div class="iforge-card-item-meta">05/07/2026</div>
    </div>';

// I-Forge: Curiosidades
$curiosidades = [
    '¿Sab&iacute;as que los bosques del sur cambian de color seg&uacute;n la estaci&oacute;n?',
    'Antes de la Gran Tormenta, el Archipi&eacute;lago Candelaria era una sola isla.',
    'Se dice que en las monta&ntilde;as del norte vive un anciano que conoce el futuro.',
    'Hay caminos subterr&aacute;neos que conectan continentes — pero nadie vuelve igual.',
];
$iforge_curiosidad = $curiosidades[0];
$curiosidades_json = json_encode($curiosidades);

// I-Forge: Staff list
$iforge_staff_list = '';
$staffQuery = $db->query("
    SELECT u.uid, u.username, u.usergroup, g.title AS grouptitle
    FROM ".TABLE_PREFIX."users u
    LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid = u.usergroup)
    WHERE u.usergroup IN (SELECT gid FROM ".TABLE_PREFIX."usergroups WHERE issupermod = 1 OR cancp = 1)
       OR u.additionalgroups LIKE '%4%'
    LIMIT 10
");
$roleIcons = ['Administrator' => '👑', 'Super Moderators' => '🛡️'];
while ($staff = $db->fetch_array($staffQuery)) {
    $icon = $roleIcons[$staff['grouptitle']] ?? '👤';
    $iforge_staff_list .= '
    <div class="iforge-staff-item">
        <span class="iforge-staff-icon">'.$icon.'</span>
        <span class="iforge-staff-name">'.htmlspecialchars_uni($staff['username']).'</span>
        <a href="'.$mybb->settings['bburl'].'/private.php?action=send&uid='.$staff['uid'].'" class="iforge-staff-mp">[MP]</a>
    </div>';
}

// I-Forge: Visual category cards (replace $forums)
$iforge_categories = '';
$catQuery = $db->query("
    SELECT fid, name, description
    FROM ".TABLE_PREFIX."forums
    WHERE type = 'c'
    ORDER BY disporder ASC
");
while ($cat = $db->fetch_array($catQuery)) {
    $icon = '🏝️'; // static placeholder icon
    $iforge_categories .= '
    <a href="'.$mybb->settings['bburl'].'/forumdisplay.php?fid='.$cat['fid'].'" class="iforge-category-card" style="background: linear-gradient(135deg, #0d1117 0%, #1c2128 100%);">
        <div class="iforge-category-content">
            <div class="iforge-category-icon">'.$icon.'</div>
            <h2 class="iforge-category-title">'.htmlspecialchars_uni($cat['name']).'</h2>
            <p class="iforge-category-desc">'.htmlspecialchars_uni($cat['description']).'</p>
        </div>
    </a>';
}
$forums = $iforge_categories;
```

### Step 4: Run export script

```bash
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" scripts/export-theme.php
```

### Step 5: Verify

Open `http://localhost/iforge/index.php` or run:
```bash
curl -s http://localhost/iforge/index.php | grep -c "iforge-banner"
```
Expected: `1`

## Report

Write to `.superpowers/sdd/task-4-report.md` with:
- status: DONE or BLOCKED
- summary
- verify output
- any issues found
