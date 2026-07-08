## Task 2 Report: Modificar iforge_rol_navbar_html()

### Status: DONE

### Commits
- `9e8ef30` feat: add alert bell and messages link to navbar

### What was done
1. **Added two helper functions** (`inc/plugins/iforge_rol.php:162-180`):
   - `iforge_rol_alertas_no_leidas(int $uid): int` - counts unread alerts with `table_exists('rol_alertas')` guard
   - `iforge_rol_mensajes_no_leidos(int $pid): int` - counts unread message threads with `table_exists('rol_mensajes')` guard

2. **Added `$activePid` variable** to `iforge_rol_navbar_html()` at line 202 for use in mensajes lookup

3. **Replaced right-side HTML block** (lines 219-257): 
   - Bell icon (SVG) linking to `alertas.php` with unread count badge
   - "Mis personajes" dropdown item linking to `personajes.php`
   - "Mensajes" dropdown item (with unread count) linking to `mensajes.php` — only shown when active character exists
   - "Alertas" dropdown item (with unread count) linking to `alertas.php`
   - Retained existing Panel/Perfil/Salir items and guest Register/Login buttons

4. **Added bell CSS** to `iforge_rol_navbar_css()` (lines 310-313):
   - `.iforge-nav-bell` styling (positioning, colors, hover)
   - `.iforge-bell-badge` styling (absolute position, red background, monospace font)

### Test Results
- Navigated to `http://localhost/iforge/index.php`
- Page loaded without PHP errors
- Navbar renders correctly: logo, navigation links, and guest buttons (Registrate/Acceder)
- Only console error: 404 for `/favicon.ico` (pre-existing, unrelated)
- No PHP warnings or errors in output

### Concerns
- None. The bell icon and mensajes link only query the rol_alertas/rol_mensajes tables safely (guarded by `table_exists`), which were created by Task 1 migration.

### File modified
- `inc/plugins/iforge_rol.php`
