# Guía de Estilos para Nuevas Páginas PHP

> Regla de oro: **CERO `<style>` y CERO `style=""` estáticos en PHP.**
> Todo el CSS va en `docs/themes/ope.css` bajo `body.ope-pg-<pagina>`.

---

## Checklist al crear una nueva página PHP

### 1. Encabezado PHP
```php
<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mi-pagina.php');
require_once './global.php';
// Añade SOLO los require_once que necesites
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);

// Iniciales para navbar (OBLIGATORIO)
$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);
```

### 2. Estructura HTML obligatoria
```php
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Título</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-mi-pagina) -->
</head>
<body class="ope-pg-mi-pagina">    <!-- ← scope CSS -->

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Mi Página</b>
  </div>
</div>

<div class="wrap">
  <!-- Contenido aquí -->
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
// IntersectionObserver para animaciones reveal (OBLIGATORIO)
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>
```

### 3. Componentes disponibles (CLASES CSS, no inventes nuevas)

| Componente | HTML |
|---|---|
| **Hero section** | `<section class="reveal"><div class="shead"><h1>Título</h1><span class="code">// subtítulo</span><span class="rule"></span></div></section>` |
| **Card / Plate** | `<div class="plate"><div class="plate-h"><span class="t">Título</span><span class="c">// código</span></div><div class="plate-b">Contenido</div></div>` |
| **Estado vacío** | `<p class="pj-empty">Mensaje</p>` |
| **Flash message** | `<div class="flash ok">Éxito</div>` o `<div class="flash error">Error</div>` |
| **Botón primario** | `<button class="btn btn-hot">Acción</button>` |
| **Botón secundario** | `<a class="btn btn-ghost">Cancelar</a>` |
| **PP bar** | `<div class="ope-prog-ppbar"><div class="ope-prog-ppbar-total"><span class="ope-prog-ppbar-val">N</span><span class="ope-prog-ppbar-label">PP</span></div></div>` |
| **Grid de stats** | `<div class="ope-prog-stats-grid">...</div>` |
| **Tabla** | `<table class="ope-prog-log">...</table>` |

### 4. Reglas CSS en ope.css

**SIEMPRE** scopea bajo `body.ope-pg-mi-pagina`:

```css
/* ═══════════════════════════════════════════════
   Mi Página — mi-pagina.php
   ═══════════════════════════════════════════════ */

body.ope-pg-mi-pagina .mi-clase { ... }
body.ope-pg-mi-pagina .mi-otra-clase { ... }
```

### 5. NO hacer NUNCA

- ❌ `<style>` en el head del PHP
- ❌ `style="color: red"` con valores estáticos en HTML
- ❌ `style="margin-top: 1rem"` → usa una clase CSS
- ❌ Inventar clases nuevas si ya existe una en ope.css que hace lo mismo
- ❌ Olvidar `ope-pg-<pagina>` en el `<body>`
- ❌ Olvidar el IntersectionObserver al final
- ❌ Olvidar `<?php include __DIR__ . '/inc/footer_custom.php'; ?>`

### 6. SÍ permitido

- ✅ `style="width:<?php echo $pct; ?>%"` para barras de progreso dinámicas
- ✅ `style="background:<?php echo $color; ?>"` para colores calculados en PHP
- ✅ `style="display:<?php echo $visible ? 'block' : 'none'; ?>"` para toggle condicional
- ✅ Usar variables CSS del sistema: `var(--ember)`, `var(--paper)`, `var(--ink-dim)`, `var(--plate-bg)`, etc.

### 7. Después de crear/modificar

```bash
php scripts/sync-theme.php
```

Esto compila ope.css al cache del tema activo.
