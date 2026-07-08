### Task 2: Modificar `iforge_rol_navbar_html()` — añadir campana de alertas y enlace a mensajes

**Files:**
- Modify: `inc/plugins/iforge_rol.php`

**Interfaces:**
- Consumes: Tablas `rol_alertas` y `rol_mensajes` (Task 1)
- Produces: Navbar actualizado con contador de alertas y enlace a mensajes

---

**Step 1: Añadir función helper para contar alertas no leídas**

Añadir ANTES de `function iforge_rol_navbar_html()`:

```php
function iforge_rol_alertas_no_leidas(int $uid): int {
    global $db;
    if (!$db->table_exists('rol_alertas')) return 0;
    $q = $db->simple_select('rol_alertas', 'COUNT(*) as cnt', "uid = {$uid} AND leido = 0");
    return (int)$db->fetch_field($q, 'cnt');
}
```

**Step 2: Añadir función helper para contar mensajes no leídos**

```php
function iforge_rol_mensajes_no_leidos(int $pid): int {
    global $db;
    if (!$db->table_exists('rol_mensajes') || $pid <= 0) return 0;
    $q = $db->query("
        SELECT COUNT(DISTINCT thread_id) as cnt
        FROM " . TABLE_PREFIX . "rol_mensajes
        WHERE destino_pid = {$pid} AND leido = 0
    ");
    return (int)$db->fetch_field($q, 'cnt');
}
```

**Step 3: Modificar el bloque del lado derecho en `iforge_rol_navbar_html()`**

La función actual genera `$right` en las líneas ~194-207 del archivo. Reemplazar el bloque completo `if ($loggedin)` y `else` del lado derecho con:

```php
// ── Lado derecho ──
$bburl = $mybb->settings['bburl'];
$right = '<div class="iforge-nav-right">';

if ($loggedin) {
    // Campana de alertas
    $alertas_count = iforge_rol_alertas_no_leidas($uid);
    $right .= '<a href="' . $bburl . '/alertas.php" class="iforge-nav-bell" title="Alertas">';
    $right .= '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
    if ($alertas_count > 0) {
        $right .= '<span class="iforge-bell-badge">' . $alertas_count . '</span>';
    }
    $right .= '</a>';

    // Menú de usuario
    $right .= '<div class="iforge-user-menu">';
    $right .= '<button type="button" class="iforge-user-name" onclick="this.nextElementSibling.classList.toggle(\'open\')" aria-expanded="false">' . htmlspecialchars_uni($displayName) . '</button>';
    $right .= '<div class="iforge-dropdown">';
    $right .= '<a href="' . $bburl . '/personajes.php" class="iforge-dropdown-item">Mis personajes</a>';
    
    // Mensajes (solo si hay personaje activo)
    if ($activePid > 0) {
        $msgs_count = iforge_rol_mensajes_no_leidos($activePid);
        $msg_label = 'Mensajes';
        if ($msgs_count > 0) $msg_label .= ' (' . $msgs_count . ')';
        $right .= '<a href="' . $bburl . '/mensajes.php" class="iforge-dropdown-item">' . $msg_label . '</a>';
    }
    
    $right .= '<a href="' . $bburl . '/alertas.php" class="iforge-dropdown-item">Alertas';
    if ($alertas_count > 0) $right .= ' (' . $alertas_count . ')';
    $right .= '</a>';
    $right .= '<hr class="iforge-dropdown-divider">';
    $right .= '<a href="' . $bburl . '/usercp.php" class="iforge-dropdown-item">Panel</a>';
    $right .= '<a href="' . $bburl . '/member.php?action=profile&amp;uid=' . $uid . '" class="iforge-dropdown-item">Perfil</a>';
    $right .= '<hr class="iforge-dropdown-divider">';
    $right .= '<a href="' . $bburl . '/member.php?action=logout&amp;logoutkey=' . $logoutkey . '" class="iforge-dropdown-item">Salir</a>';
    $right .= '</div></div>';
} else {
    $right .= '<a href="' . $bburl . '/member.php?action=register" class="iforge-nav-cta">Reg&iacute;strate</a>';
    $right .= '<a href="' . $bburl . '/member.php?action=login" class="iforge-btn-ghost iforge-btn-sm">Acceder</a>';
}
$right .= '</div>';
```

**Step 4: Añadir CSS para la campana en `iforge_rol_navbar_css()`**

Dentro de la función `iforge_rol_navbar_css()`, después del bloque CSS `.iforge-btn-ghost`, añadir:

```css
#iforge-navbar .iforge-nav-bell{position:relative;display:flex;align-items:center;justify-content:center;padding:6px 8px;color:var(--paper-dim);transition:color .12s}
#iforge-navbar .iforge-nav-bell:hover{color:var(--ember-hi)}
#iforge-navbar .iforge-nav-bell svg{fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
#iforge-navbar .iforge-bell-badge{position:absolute;top:0;right:2px;min-width:16px;height:16px;padding:0 4px;border-radius:8px;background:var(--crack);color:#fff;font-family:var(--mono);font-size:.58rem;font-weight:700;line-height:16px;text-align:center}
```

---

**IMPORTANTE**: La variable `$activePid` ya existe en la función (se carga al inicio como `$mybb->user['iforge_active_pid']`). La variable `$displayName` también ya existe. Solo necesitas añadir las funciones helper y modificar el HTML del lado derecho + CSS.

**Verificación**: Navegar a `http://localhost/iforge/index.php` y verificar que:
- Campana aparece a la derecha del nombre de usuario
- Menú desplegable tiene "Mis personajes", "Mensajes", "Alertas", "Panel", "Perfil", "Salir"
- Sin errores de consola
