# Phase 1: Foundation — Child Theme + Navbar + Index Page

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the visual foundation of the I-Forge forum: a custom MyBB child theme with a floating navbar, rotating banner, and the new index page layout (tablón + visual categories).

**Architecture:** MyBB manages auth and forum data; we override its header, index, footer, and CSS templates to render our own design. A child theme (pid=1) inherits the default theme but overrides specific templates.

**Tech Stack:** MyBB 1.8.39 template system, PHP 8.x, CSS3, vanilla JS.

## Global Constraints

- All new templates must be inserted into the custom templateset (sid > 0), not master (-2) or global (-1)
- Custom CSS goes into a new stylesheet `iforge.css` attached to `global` pages, not inline
- Banner images stored in `images/banners/` directory
- No glassmorphism effects
- Everything must feel cohesive — same palette, same radii, same spacing everywhere
- For initial phase, use placeholder colors (to be refined after Phase 1 approval)

---
### Task 1: Define Forum Identity + Create Child Theme

**Files:**
- Modify: `docs/frontend/identidad-visual-front.md` (fill name, palette, fonts)
- Create: `docs/themes/iforge-child-theme.xml` (export after creation)

**Interfaces:**
- Consumes: Existing MyBB default theme (tid=1)
- Produces: New child theme in DB with tid=X, templateset id=X

- [ ] **Step 1: Choose forum name + palette + typography**

Pick a name (e.g., "I-Forge") and define placeholder palette:

```css
/* Placeholder palette — refined after visual review */
:root {
  --color-bg: #0d1117;         /* Fondo base oscuro */
  --color-surface: #161b22;    /* Tarjetas, navbar, panels */
  --color-border: #30363d;     /* Bordes sutiles */
  --color-accent: #e2b714;     /* Acento principal (oro) */
  --color-accent-hover: #f0c940;
  --color-text: #f0f6fc;       /* Texto principal */
  --color-text-muted: #8b949e; /* Metadatos, fechas */
  --color-success: #3fb950;
  --color-danger: #f85149;
  --color-t1: #58a6ff;         /* Badge rango T1 */
  --color-t2: #a371f7;         /* Badge rango T2 */
  --color-t3: #f0883e;         /* Badge rango T3 */
  --font-display: 'Georgia', serif;
  --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-mono: 'Menlo', 'Consolas', monospace;
  --radius: 8px;
  --navbar-height: 56px;
}
```

- [ ] **Step 2: Create child theme via Admin CP**

Go to `http://localhost/iforge/admin/index.php?module=style-themes`:
1. Click "Create New Theme"
2. Name: "I-Forge RPG"
3. Parent theme: "MyBB Master Style" (tid=1)
4. Click "Save"

Note the new theme's `tid` and templateset `sid` from the DB (or from the URL after creation).

- [ ] **Step 3: Set the child theme as default**

In Admin CP > Styles & Templates > Themes:
1. Find "I-Forge RPG" in the list
2. Click the checkmark (Set as Default) so all users see this theme

- [ ] **Step 4: Export the theme as XML for version control**

In Admin CP > Styles & Templates > Themes:
1. Click "Export" next to "I-Forge RPG"
2. Save to `docs/themes/iforge-child-theme.xml`

- [ ] **Step 5: Commit**

```bash
git add docs/frontend/identidad-visual-front.md docs/themes/iforge-child-theme.xml
git commit -m "feat(theme): create I-Forge child theme + define identity palette"
```

---

### Task 2: Custom Navbar (Override `header` Template)

**Files:**
- Create: `images/nav-icon.svg` (generic character icon for [❤])
- Modify: `mybb_theme.xml` or Admin CP — override `header` template in the child theme's templateset

**Interfaces:**
- Produces: `{$header}` variable in index template renders the floating navbar
- Consumes: `{$mybb->user['uid']}` for auth state, `{$mybb->user['username']}` for user name

- [ ] **Step 1: Create the nav-icon SVG**

Create file `images/nav-icon.svg`:
```xml
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
```

- [ ] **Step 2: Override `header` template in Admin CP**

Go to Admin CP > Templates > I-Forge RPG templateset > Find `header` > Edit:

```html
<div id="iforge-navbar">
  <nav class="iforge-nav">
    <div class="iforge-nav-left">
      <a href="{$mybb->settings['bburl']}/index.php" class="iforge-nav-logo">I-FORGE</a>
      <a href="{$mybb->settings['bburl']}/personajes.php" class="iforge-nav-link">Personaje</a>
      <a href="{$mybb->settings['bburl']}/tramites.php" class="iforge-nav-link">Trámites</a>
      <a href="{$mybb->settings['bburl']}/guias.php" class="iforge-nav-link">Guías</a>
      <!-- Only render Zona Privada for staff roles -->
      {$iforge_zona_privada_link}
    </div>
    <div class="iforge-nav-right">
      {$iforge_user_menu}
    </div>
  </nav>
</div>

<!-- Original MyBB header content (hidden, just keeps compat) -->
<div style="display:none">
{$pm_notice}
{$bannedwarning}
{$privwarning}
</div>
```

- [ ] **Step 3: Add the `iforge_zona_privada_link` variable logic to `index.php`**

In MyBB's `C:\laragon\www\iforge\index.php`, before `eval('$index = ...')`, add:

```php
// I-Forge: Zona Privada link visibility
$iforge_zona_privada_link = '';
if ($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1 || my_is_admin()) {
    $iforge_zona_privada_link = '<a href="'.$mybb->settings['bburl'].'/private.php" class="iforge-nav-link">Zona Privada</a>';
}

// I-Forge: User menu (guest vs logged-in)
if ($mybb->user['uid']) {
    $iforge_user_menu = '
    <div class="iforge-user-menu">
      <button class="iforge-user-btn" id="iforge-user-btn">
        <img src="'.$mybb->settings['bburl'].'/images/nav-icon.svg" width="28" height="28" alt="Personaje">
      </button>
      <div class="iforge-dropdown" id="iforge-dropdown">
        <a href="'.$mybb->settings['bburl'].'/mensajes.php" class="iforge-dropdown-item">Mensajería</a>
        <a href="'.$mybb->settings['bburl'].'/configuracion.php" class="iforge-dropdown-item">Configuración</a>
        <hr class="iforge-dropdown-divider">
        <a href="'.$mybb->settings['bburl'].'/member.php?action=logout&amp;logoutkey='.$mybb->user['logoutkey'].'" class="iforge-dropdown-item">Cerrar sesión</a>
      </div>
    </div>';
} else {
    $iforge_user_menu = '<a href="'.$mybb->settings['bburl'].'/member.php?action=login" class="iforge-nav-link">Iniciar sesión</a>';
}
```

- [ ] **Step 4: Commit**

```bash
git add images/nav-icon.svg
git commit -m "feat(navbar): floating navbar with character menu + staff link"
```

---

### Task 3: Custom CSS — `iforge.css`

**Files:**
- Create: New stylesheet `iforge.css` attached to `global` in the child theme

**Interfaces:**
- Consumed by: All pages via `{$stylesheets}` in `headerinclude`

- [ ] **Step 1: Add stylesheet via Admin CP**

Go to Admin CP > Styles & Templates > I-Forge RPG theme > Stylesheets > Add New Stylesheet:
- Name: `iforge.css`
- Attached to: `global`
- Cache file: (let MyBB auto-generate)

Paste the full CSS:

```css
/* ─── Reset & Base ─── */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  background: #0d1117;
  color: #f0f6fc;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  padding-top: 56px; /* navbar height */
  min-height: 100vh;
}
a { color: #e2b714; text-decoration: none; }
a:hover { color: #f0c940; }

/* ─── Floating Navbar ─── */
#iforge-navbar {
  position: fixed; top: 0; left: 0; right: 0;
  z-index: 1000;
  background: #161b22;
  border-bottom: 1px solid #30363d;
  height: 56px;
}
.iforge-nav {
  max-width: 1200px; margin: 0 auto;
  display: flex; align-items: center;
  justify-content: space-between;
  height: 100%; padding: 0 20px;
}
.iforge-nav-left { display: flex; align-items: center; gap: 24px; }
.iforge-nav-right { display: flex; align-items: center; }
.iforge-nav-logo {
  font-family: Georgia, serif;
  font-size: 1.3rem; font-weight: bold;
  color: #e2b714; letter-spacing: 1px;
}
.iforge-nav-link {
  color: #8b949e; font-size: 0.9rem;
  padding: 6px 0; transition: color 0.15s;
}
.iforge-nav-link:hover { color: #f0f6fc; }

/* ─── User Menu Dropdown ─── */
.iforge-user-menu { position: relative; }
.iforge-user-btn {
  background: none; border: 2px solid #30363d;
  border-radius: 50%; width: 36px; height: 36px;
  cursor: pointer; display: flex; align-items: center;
  justify-content: center; color: #8b949e;
  transition: border-color 0.15s;
}
.iforge-user-btn:hover { border-color: #e2b714; color: #e2b714; }
.iforge-dropdown {
  display: none; position: absolute; right: 0; top: 44px;
  background: #161b22; border: 1px solid #30363d;
  border-radius: 8px; min-width: 180px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  overflow: hidden;
}
.iforge-dropdown.open { display: block; }
.iforge-dropdown-item {
  display: block; padding: 10px 16px;
  color: #f0f6fc; font-size: 0.88rem;
  transition: background 0.15s;
}
.iforge-dropdown-item:hover { background: #1c2128; }
.iforge-dropdown-divider { border: none; border-top: 1px solid #30363d; margin: 4px 0; }

/* ─── Banner ─── */
#iforge-banner {
  position: relative; width: 100%;
  height: 420px; overflow: hidden;
  background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
}
#iforge-banner::before {
  content: ''; position: absolute; inset: 0;
  background: rgba(0,0,0,0.55);
}
.iforge-banner-content { position: relative; z-index: 1; text-align: center; }
.iforge-banner-title {
  font-family: Georgia, serif; font-size: 3.2rem;
  color: #ffffff; letter-spacing: 2px;
  margin-bottom: 8px; text-shadow: 0 2px 8px rgba(0,0,0,0.5);
}
.iforge-banner-subtitle {
  font-size: 1.05rem; color: rgba(255,255,255,0.75);
  letter-spacing: 1px;
}

/* ─── Calendar Bar ─── */
#iforge-calendar-bar {
  display: flex; align-items: center; justify-content: center;
  gap: 12px; padding: 12px 20px;
  background: #161b22; border-bottom: 1px solid #30363d;
  font-size: 0.88rem; color: #8b949e; cursor: pointer;
  transition: background 0.15s;
}
#iforge-calendar-bar:hover { background: #1c2128; }
#iforge-calendar-bar strong { color: #f0f6fc; }

/* ─── Tablón Grid ─── */
.iforge-tablon {
  max-width: 1200px; margin: 0 auto;
  padding: 24px 20px;
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}
.iforge-card {
  background: #161b22; border: 1px solid #30363d;
  border-radius: 8px; overflow: hidden;
}
.iforge-card-header {
  padding: 12px 16px;
  font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 1px; color: #8b949e;
  border-bottom: 1px solid #30363d;
}
.iforge-card-body { padding: 8px 0; }
.iforge-card-item {
  display: block; padding: 8px 16px;
  color: #f0f6fc; font-size: 0.88rem;
  transition: background 0.15s;
}
.iforge-card-item:hover { background: #1c2128; }
.iforge-card-item-meta {
  font-size: 0.78rem; color: #8b949e; margin-top: 2px;
}
.iforge-card-more {
  display: block; padding: 10px 16px;
  text-align: center; font-size: 0.82rem;
  border-top: 1px solid #30363d; color: #8b949e;
}
.iforge-card-more:hover { background: #1c2128; color: #e2b714; }

/* ─── Staff Section ─── */
.iforge-staff-section {
  grid-column: 1 / -1;
}
.iforge-staff-list {
  display: flex; flex-wrap: wrap; gap: 12px;
  padding: 12px 16px;
}
.iforge-staff-item {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 12px; background: #0d1117;
  border-radius: 6px; font-size: 0.85rem;
}
.iforge-staff-icon { font-size: 1rem; }
.iforge-staff-name { color: #f0f6fc; }
.iforge-staff-mp {
  color: #58a6ff; font-size: 0.78rem;
  margin-left: 4px; cursor: pointer;
}
.iforge-staff-mp:hover { text-decoration: underline; }

/* ─── Categories ─── */
.iforge-categories {
  max-width: 1200px; margin: 0 auto;
  padding: 0 20px 40px;
  display: flex; flex-direction: column; gap: 20px;
}
.iforge-category-card {
  position: relative; border-radius: 10px;
  overflow: hidden; cursor: pointer;
  min-height: 180px; background-size: cover; background-position: center;
  transition: transform 0.2s;
}
.iforge-category-card:hover { transform: scale(1.01); }
.iforge-category-card::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);
}
.iforge-category-content {
  position: relative; z-index: 1;
  padding: 40px 32px;
}
.iforge-category-icon { font-size: 1.8rem; margin-bottom: 8px; }
.iforge-category-title {
  font-family: Georgia, serif; font-size: 1.5rem;
  color: #ffffff; margin-bottom: 6px;
}
.iforge-category-desc {
  color: rgba(255,255,255,0.7); font-size: 0.9rem;
  max-width: 500px;
}

/* ─── Footer ─── */
#iforge-footer {
  background: #161b22; border-top: 1px solid #30363d;
  padding: 24px 20px; text-align: center;
  color: #8b949e; font-size: 0.82rem;
}

/* ─── Badges ─── */
.iforge-badge {
  display: inline-block; padding: 1px 7px;
  border-radius: 10px; font-size: 0.7rem;
  font-weight: 600; margin-right: 4px;
}
.iforge-badge-t1 { background: #1c2e4a; color: #58a6ff; }
.iforge-badge-t2 { background: #2d1c4a; color: #a371f7; }
.iforge-badge-t3 { background: #3a2a1c; color: #f0883e; }
```

- [ ] **Step 2: Add JS for dropdown toggle**

In `headerinclude` template or a separate JS file:
```javascript
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('iforge-user-btn');
  var dd = document.getElementById('iforge-dropdown');
  if (btn && dd) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      dd.classList.toggle('open');
    });
    document.addEventListener('click', function() { dd.classList.remove('open'); });
    dd.addEventListener('click', function(e) { e.stopPropagation(); });
  }
});
```

- [ ] **Step 3: Commit**

```bash
git commit -m "feat(css): iforge.css with navbar, banner, tablon, category styles"
```

---

### Task 4: Override `index` Template — Banner + Tablón + Categorías

**Files:**
- Modify: Child theme `index` template (via Admin CP)
- Create: `images/banners/.gitkeep` (banner images directory)

**Interfaces:**
- Consumes: `{$forums}` (MyBB forum/category tree), `{$mybb->settings['bburl']}`
- Produces: The rendered index page

- [ ] **Step 1: Create banner images directory**

```bash
mkdir -p C:\laragon\www\iforge\images\banners
```

Place 3-5 placeholder banner images (we'll generate proper ones later):
- `banner-01.jpg`, `banner-02.jpg`, etc.
- For now, use a solid dark gradient image or a generated placeholder.

- [ ] **Step 2: Add banner rotation logic to `index.php`**

In `C:\laragon\www\iforge\index.php`, before the `eval`:

```php
// I-Forge: Random banner
$bannerDir = MYBB_ROOT . 'images/banners/';
$banners = glob($bannerDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
$bannerUrl = $mybb->settings['bburl'] . '/images/banners/default-banner.jpg';
if (!empty($banners)) {
    $randomBanner = $banners[array_rand($banners)];
    $bannerUrl = $mybb->settings['bburl'] . '/images/banners/' . basename($randomBanner);
}
```

- [ ] **Step 3: Override `index` template**

```html
<!DOCTYPE html>
<html lang="es">
<head>
<title>{$mybb->settings['bbname']}</title>
{$headerinclude}
</head>
<body>
{$header}

<!-- Banner -->
<div id="iforge-banner" style="background-image: url('{$bannerUrl}');">
  <div class="iforge-banner-content">
    <h1 class="iforge-banner-title">I-FORGE</h1>
    <p class="iforge-banner-subtitle">Un mundo de Cazadores</p>
  </div>
</div>

<!-- Calendar Bar -->
<div id="iforge-calendar-bar" onclick="window.location.href='{$mybb->settings['bburl']}/calendario.php'">
  📅 <strong>DÍA 47 · VERANO · AÑO 925</strong>
  &nbsp;—&nbsp; ⏱ 1 día real = 2 días on-rol
</div>

<!-- Tablón -->
<div class="iforge-tablon">
  <div class="iforge-card">
    <div class="iforge-card-header">🗣️ Últimos Mensajes</div>
    <div class="iforge-card-body">
      {$iforge_latest_posts}
    </div>
    <a href="{$mybb->settings['bburl']}/search.php?action=latest" class="iforge-card-more">← Ver más →</a>
  </div>
  <div class="iforge-card">
    <div class="iforge-card-header">🔍 Búsquedas Activas</div>
    <div class="iforge-card-body">
      {$iforge_active_searches}
    </div>
    <a href="{$mybb->settings['bburl']}/newthread.php?fid=X" class="iforge-card-more">+ Publicar búsqueda</a>
  </div>
  <div class="iforge-card">
    <div class="iforge-card-header">📰 Noticias</div>
    <div class="iforge-card-body">
      {$iforge_news}
    </div>
    <a href="{$mybb->settings['bburl']}/forumdisplay.php?fid=X" class="iforge-card-more">← Archivo →</a>
  </div>
  <div class="iforge-card">
    <div class="iforge-card-header">💡 Curiosidades</div>
    <div class="iforge-card-body" id="iforge-curiosidad">
      {$iforge_curiosidad}
    </div>
    <a href="#" class="iforge-card-more" id="iforge-next-curiosidad">← Otra curiosidad →</a>
  </div>
  <div class="iforge-card iforge-staff-section">
    <div class="iforge-card-header">👥 Staff</div>
    <div class="iforge-staff-list">
      {$iforge_staff_list}
    </div>
  </div>
</div>

<!-- Category Visual Cards -->
<div class="iforge-categories">
  {$forums}
</div>

<!-- Footer -->
<div id="iforge-footer">
  {$mybb->settings['bbname']} &mdash; {$lang->powered_by} <a href="https://mybb.com" target="_blank">MyBB</a>
  {$themecopyright}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('iforge-user-btn');
  var dd = document.getElementById('iforge-dropdown');
  if (btn && dd) {
    btn.addEventListener('click', function(e) { e.stopPropagation(); dd.classList.toggle('open'); });
    document.addEventListener('click', function() { dd.classList.remove('open'); });
    dd.addEventListener('click', function(e) { e.stopPropagation(); });
  }

  // Curiosidad rotate on click
  var curiosidadBtn = document.getElementById('iforge-next-curiosidad');
  var curiosidadEl = document.getElementById('iforge-curiosidad');
  if (curiosidadBtn && curiosidadEl) {
    curiosidadBtn.addEventListener('click', function(e) {
      e.preventDefault();
      var idx = parseInt(curiosidadEl.dataset.idx || '0');
      idx = (idx + 1) % curiosidadesData.length;
      curiosidadEl.dataset.idx = idx;
      curiosidadEl.textContent = curiosidadesData[idx];
    });
  }
});

var curiosidadesData = $curiosidades_json;
</script>
</body>
</html>
```

- [ ] **Step 4: Add data variables to `index.php`**

In `C:\laragon\www\iforge\index.php`, before the eval:

```php
// I-Forge: Latest posts (hardcoded query since we can't use fetch in template)
$iforge_latest_posts = '';
$query = $db->query("
    SELECT p.pid, p.subject, p.tid, p.uid, p.dateline, u.username
    FROM ".TABLE_PREFIX."posts p
    LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = p.uid)
    WHERE p.visible = 1
    ORDER BY p.dateline DESC
    LIMIT 5
");
while ($post = $db->fetch_array($query)) {
    $date = date('d/m', $post['dateline']);
    $iforge_latest_posts .= '
    <a href="'.$mybb->settings['bburl'].'/showthread.php?tid='.$post['tid'].'&pid='.$post['pid'].'" class="iforge-card-item">
        '.htmlspecialchars($post['subject']).'
        <div class="iforge-card-item-meta">'.$post['username'].' · '.$date.'</div>
    </a>
    <div class="iforge-card-divider"></div>';
}

// I-Forge: Active searches (threads with prefix or in a specific forum)
$iforge_active_searches = '
    <div class="iforge-card-item">
        Busco compañero para T3
        <div class="iforge-card-item-meta">Kael · Zona Candelaria</div>
    </div>
    <div class="iforge-card-item">
        Mazmorra busca DPS
        <div class="iforge-card-item-meta">Lyra · Continente Oscuro</div>
    </div>';

// I-Forge: News
$iforge_news = '
    <div class="iforge-card-item">
        ⚔️ Torneo de combate — Inscripciones abiertas
        <div class="iforge-card-item-meta">hasta el Día 60</div>
    </div>
    <div class="iforge-card-item">
        📌 Parche 1.2 — Nuevo sistema de clima
        <div class="iforge-card-item-meta">05/07/2026</div>
    </div>';

// I-Forge: Curiosidades
$curiosidades = [
    '¿Sabías que los bosques del sur cambian de color según la estación?',
    'Antes de la Gran Tormenta, el Archipiélago Candelaria era una sola isla.',
    'Se dice que en las montañas del norte vive un anciano que conoce el futuro.',
    'El ojo de agua del Desierto Olvidado nunca se seca, incluso en la estación más árida.',
    'Hay caminos subterráneos que conectan continentes — pero nadie vuelve igual.',
];
$_SESSION['iforge_curiosidad_idx'] = ($_SESSION['iforge_curiosidad_idx'] ?? -1) + 1;
if ($_SESSION['iforge_curiosidad_idx'] >= count($curiosidades)) {
    $_SESSION['iforge_curiosidad_idx'] = 0;
}
$iforge_curiosidad = '<span data-idx="'.$_SESSION['iforge_curiosidad_idx'].'">'.htmlspecialchars($curiosidades[$_SESSION['iforge_curiosidad_idx']]).'</span>';
$curiosidades_json = json_encode(array_values($curiosidades));

// I-Forge: Staff list
$iforge_staff_list = '';
$staffQuery = $db->query("
    SELECT u.uid, u.username, u.usergroup, u.additionalgroups, g.title AS grouptitle
    FROM ".TABLE_PREFIX."users u
    LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid = u.usergroup)
    WHERE u.usergroup IN (SELECT gid FROM ".TABLE_PREFIX."usergroups WHERE issupermod = 1 OR cancp = 1)
       OR u.additionalgroups LIKE '%4%' -- moderator group
    LIMIT 10
");
$roleIcons = ['Administrator' => '👑', 'Super Moderators' => '🛡️', 'Moderators' => '🛡️'];
while ($staff = $db->fetch_array($staffQuery)) {
    $icon = $roleIcons[$staff['grouptitle']] ?? '👤';
    $iforge_staff_list .= '
    <div class="iforge-staff-item">
        <span class="iforge-staff-icon">'.$icon.'</span>
        <span class="iforge-staff-name">'.htmlspecialchars($staff['username']).'</span>
        <a href="'.$mybb->settings['bburl'].'/private.php?action=send&uid='.$staff['uid'].'" class="iforge-staff-mp">[MP]</a>
    </div>';
}
```

- [ ] **Step 5: Replace the `{$forums}` variable with visual category cards**

MyBB's native `{$forums}` renders the default forum list. We need to replace it with custom cards. The forums are already built into the `$forums` variable, but we can modify how they render by overriding the `forumbit_depth1_cat` and related templates.

For Phase 1, the simplest approach: in `index.php`, build the visual categories manually:

```php
// I-Forge: Visual category cards
$iforge_category_cards = '';
$catQuery = $db->query("
    SELECT fid, name, description, linkto
    FROM ".TABLE_PREFIX."forums
    WHERE type = 'c'
    ORDER BY disporder ASC
");
while ($cat = $db->fetch_array($catQuery)) {
    $bgImage = $mybb->settings['bburl'].'/images/banners/category-'.$cat['fid'].'.jpg';
    $iconMap = []; // Map forum IDs to emoji icons
    $iforge_category_cards .= '
    <a href="'.$mybb->settings['bburl'].'/forumdisplay.php?fid='.$cat['fid'].'" class="iforge-category-card" style="background-image: url(\''.$bgImage.'\')">
        <div class="iforge-category-content">
            <div class="iforge-category-icon">🏝️</div>
            <h2 class="iforge-category-title">'.htmlspecialchars($cat['name']).'</h2>
            <p class="iforge-category-desc">'.htmlspecialchars($cat['description']).'</p>
        </div>
    </a>';
}

// Assign to template variable
$forums = $iforge_category_cards;
```

- [ ] **Step 6: Commit**

```bash
git commit -m "feat(index): banner rotation, tablon cards, visual category cards"
```

---

### Task 5: Override `headerinclude` Template

**Files:**
- Modify: Child theme `headerinclude` template (via Admin CP)

- [ ] **Step 1: Override `headerinclude`**

Keep only essentials:
```html
<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
<meta name="viewport" content="width=device-width, initial-scale=1">
{$stylesheets}
<script src="{$mybb->settings['bburl']}/jscripts/jquery.js?ver=1823"></script>
<script src="{$mybb->settings['bburl']}/jscripts/general.js?ver=1823"></script>
<script src="{$mybb->settings['bburl']}/jscripts/rol-widgets.js?ver=1"></script>
```

Remove default MyBB meta tags, RSS links, and other clutter we don't need.

- [ ] **Step 2: Commit**

```bash
git commit -m "feat(templates): override headerinclude with minimal clean head"
```

---

### Task 6: Add Category Background Images + Placeholder Banner

**Files:**
- Create: `images/banners/default-banner.jpg`
- Create: `images/banners/category-{fid}.jpg` for each category in the DB

- [ ] **Step 1: Generate placeholder images**

Since we can't generate real images programmatically, create a simple PHP script that generates gradient placeholder images, or use colored SVG placeholders.

For immediate progress, create a `default-banner.jpg` placeholder and a PHP fallback:

Add to `index.php`:
```php
// Fallback: if no banner image exists, use a CSS gradient
$bannerStyle = 'background: linear-gradient(135deg, #0d1117 0%, #1c2128 50%, #161b22 100%);';
if (file_exists($bannerDir . basename($randomBanner))) {
    $bannerStyle = "background-image: url('{$bannerUrl}');";
}
```

Same approach for category images — if no image found, use a gradient.

- [ ] **Step 2: Create category placeholder images**

Create simple gradient placeholder SVGs as `.jpg` files, or just use CSS gradients as fallback (simpler and more reliable).

- [ ] **Step 3: Commit**

```bash
git commit -m "feat(assets): banner image system with gradient fallback"
```

---

### Task 7: Override `footer` Template

**Files:**
- Modify: Child theme `footer` template (via Admin CP)

- [ ] **Step 1: Minimal footer override**

```html
<!-- I-Forge Footer -->
<div id="iforge-footer">
  {$mybb->settings['bbname']} &mdash; {$lang->powered_by} <a href="https://mybb.com" target="_blank" rel="noopener">MyBB</a>
  {$themecopyright}
</div>
```

This replaces the default MyBB footer entirely.

- [ ] **Step 2: Commit**

```bash
git commit -m "feat(templates): override footer with minimal I-Forge footer"
```

---

### Task 8: Verify and Export Theme

- [ ] **Step 1: Test the index page**

Open `http://localhost/iforge/index.php` in browser:
- Navbar is floating, fixed at top
- Banner shows with placeholder gradient
- Calendar bar is clickable
- Tablón shows 4 cards
- Category cards render as visual blocks
- Dropdown works on user icon click
- Everything is dark-themed and cohesive

- [ ] **Step 2: Test logged-in state**

Log in to the forum. Verify:
- Navbar shows user icon on right
- Dropdown works
- Staff users see "Zona Privada" link

- [ ] **Step 3: Export final theme as XML**

```bash
# Via Admin CP > Export, save to:
docs/themes/iforge-child-theme.xml
```

- [ ] **Step 4: Commit**

```bash
git add docs/themes/iforge-child-theme.xml
git commit -m "chore: export Phase 1 theme XML"
```

---

## Summary of Files Created/Modified

| File | Action | Purpose |
|---|---|---|
| `C:\laragon\www\iforge\images\nav-icon.svg` | Create | Navbar user icon |
| `C:\laragon\www\iforge\images\banners\*` | Create | Banner rotation images |
| `C:\laragon\www\iforge\index.php` | Modify | Add banner, tablon, staff, category logic |
| `C:\laragon\www\iforge\admin\` | Via ACP | Create child theme, override templates, add CSS |
| `docs/frontend/identidad-visual-front.md` | Modify | Fill palette and identity values |
| `docs/themes/iforge-child-theme.xml` | Create | Export for version control |

## Out of Scope (Next Phases)

- Calendar page (`/calendario.php`) — needs API endpoint
- Category page (`forumdisplay.php`) override — Phase 2
- Thread page (`showthread.php`) override — Phase 2
- Personajes frontend — Phase 3
- Mensajería entre personajes — Phase 4
- Zona Privada — Phase 4
- Trámites — Phase 4
