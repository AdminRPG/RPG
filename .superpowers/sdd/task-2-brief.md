# Task 2: Custom Floating Navbar

**Goal:** Create the floating navbar across all pages: dark solid navbar with Personaje/Trámites/Guías/Zona Privada links, user icon dropdown on the right.

**Context:**
- MyBB installation at `C:\laragon\www\iforge\`
- Child theme "I-Forge RPG" (tid=3, templateset sid=2)
- Templates stored in `mybb_foro.mybb_templates` — INSERT with sid=2 to override
- MySQL: host=127.0.0.1, user=root, password=empty, database=mybb_foro
- Binary: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe`

## Steps

### Step 1: Create nav-icon SVG

Write this SVG file to `C:\laragon\www\iforge\images\nav-icon.svg`:

```xml
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
```

### Step 2: Insert `header` template in MyBB templateset sid=2

Run this SQL (use the mysql binary directly):

```sql
INSERT INTO mybb_templates (title, template, sid, version, status, dateline)
VALUES ('header', '<div id="iforge-navbar">
  <nav class="iforge-nav">
    <div class="iforge-nav-left">
      <a href="{$mybb->settings[\"bburl\"]}/index.php" class="iforge-nav-logo">I-FORGE</a>
      <a href="{$mybb->settings[\"bburl\"]}/personajes.php" class="iforge-nav-link">Personaje</a>
      <a href="{$mybb->settings[\"bburl\"]}/tramites.php" class="iforge-nav-link">Trámites</a>
      <a href="{$mybb->settings[\"bburl\"]}/guias.php" class="iforge-nav-link">Guías</a>
      {$iforge_zona_privada_link}
    </div>
    <div class="iforge-nav-right">
      {$iforge_user_menu}
    </div>
  </nav>
</div>
<div style="display:none">
{$pm_notice}
{$bannedwarning}
{$privwarning}
</div>', 2, '1823', '', UNIX_TIMESTAMP());
```

### Step 3: Add navbar variables to `index.php`

Read the file `C:\laragon\www\iforge\index.php`.

Find the line before `eval('$index = "'.$templates->get('index').'";');` (around line 468) and insert BEFORE it:

```php
// I-Forge navbar: Zona Privada link visibility
$iforge_zona_privada_link = '';
if ($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1 || my_is_admin()) {
    $iforge_zona_privada_link = '<a href="'.$mybb->settings['bburl'].'/private.php" class="iforge-nav-link">Zona Privada</a>';
}

// I-Forge navbar: User menu
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

### Step 4: Commit

This is in the live MyBB dir, not the repo. Just run:
```bash
git -C C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG add -A
git -C C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG commit -m "feat(navbar): floating navbar with user menu dropdown"
```
(Only the svg icon is tracked in the repo; the index.php edit is outside the repo.)

### Step 5: Verify

Run: `curl -s http://localhost/iforge/index.php | grep -c "iforge-navbar"`
Expected output: `1`

## Report

Write to `.superpowers/sdd/task-2-report.md` with:
- status: DONE or BLOCKED or NEEDS_CONTEXT
- commits made
- summary of what was done
- output of the verify step
