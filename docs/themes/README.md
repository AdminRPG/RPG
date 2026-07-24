# One Piece: Eternal — tema MyBB (fuente de verdad y sincronización)

> **Agentes (Cursor / OpenCode / Antigravity):** antes de editar estos archivos, lee `docs/AGENTES-Y-HERRAMIENTAS.md` §2 y `.cursor/rules/visual-port-gbe.mdc`.

El tema **no** vive en un único XML monolítico. Está repartido en archivos con
roles claros para que un import no pise otro. Todo el CSS vive en **una sola
hoja** (`ope.css`); ninguna página PHP lleva `<style>` inline. Ver la convención
completa en `docs/DESIGN-ONE-PIECE-ETERNAL.md` (§5).

## Archivos canónicos (edita estos)

| Archivo | Contenido |
|---------|-----------|
| `ope.css` | **Única** hoja de estilos: tokens + componentes + CSS por página (`body.ope-pg-*`) |
| `ope-index.xml` | Portada, navbar, footer, forumbit |
| `ope-forumdisplay.xml` | Lista de temas |
| `ope-showthread.xml` | Hilo y postbit principal |
| `ope-forms.xml` | newthread, newreply, editpost, errores |
| `ope-shared.xml` | Plantillas auxiliares MyBB (postbit_*, opciones de formulario, etc.) |

> La navbar (HTML y su CSS) y el `<head>` de las páginas autónomas se generan
> desde `inc/plugins/ope_rol.php` (`ope_rol_navbar_html()`, `ope_rol_navbar_css()`,
> `ope_rol_head_base()`). No dupliques esas reglas en `ope.css` ni en PHP.

## Portada (`index.php`)

La portada **no** usa `body.ope-pg-*`. Usa `body.ope-index` (plantilla `ope-index.xml`) con componentes `.ope-section`, `.ope-bento`, `.ope-hero`. Las categorías las genera `index.php`. Ver `DESIGN-ONE-PIECE-ETERNAL.md` §6, §6.7 y `docs/AGENTES-Y-HERRAMIENTAS.md` §2.5.

**No declarar portada terminada** sin overrides `body.ope-index` en `ope.css` y comparación con `docs/Prototypes/Granblue/index.html`.

## Color por facción

> **Pendiente F3:** las facciones en código siguen siendo las de One Piece (`ope_rol_facciones()`). Los tokens `--fac-*` en `ope.css` se renombrarán cuando existan facciones OPE (Gremio, Erste, etc.). Hasta entonces, no añadir facciones OP nuevas en copy OPE.

- Resolución del slug: `ope_rol_faccion_slug($faccion)` en `inc/plugins/ope_rol.php`
  (normaliza acentos y alias). `ope_rol_char()` ya devuelve `faccion` y `faccion_slug`.
- **Ficha** (`ficha.php`): el `<body>` lleva `ope-pg-ficha fac-<slug>`; en `ope.css`
  eso resuelve `--fac/--fac-hi/--fac-ink` y tiñe idbanner, retrato, nameplate y `.tag.line`.
- **Postbit** (`ope-showthread.xml`): `.ope-post-author {$post['ope_fac_class']}` +
  nombre envuelto en `.ope-pa-fac fac-<slug>` (lo pone el hook `ope_rol_postbit`).

## Archivos generados (no edites a mano)

| Archivo | Cómo se genera |
|---------|----------------|
| `ope-child-theme.xml` | Solo propiedades del tema (stub con `templateset`) |
| `ope-child-theme.bundle.xml` | `php scripts/sync-theme.php build-xml` — bundle para importar en Admin CP (regenerable, no versionado) |
| `cache/themes/theme*/ope.css` | Al hacer `import` |

## Comandos

```bash
# Desplegar repo → base de datos (uso habitual)
php scripts/sync-theme.php import

# Comprobar que repo y BD coinciden
php scripts/sync-theme.php verify

# Traer cambios hechos en Admin CP → repo
php scripts/export-theme.php

# Generar XML monolítico para Admin CP
php scripts/sync-theme.php build-xml
```

## Orden de import de plantillas

Si dos XML definen la misma plantilla, gana el **último** en este orden:

1. `ope-shared.xml`
2. `ope-forms.xml`
3. `ope-showthread.xml`
4. `ope-forumdisplay.xml`
5. `ope-index.xml`

Así las pantallas principales (index, foro, hilo) siempre prevalecen sobre las
auxiliares.

## Scripts

- `sync-theme.php` → único punto de entrada (import / export / verify / build-xml / bootstrap)
- `_theme-sync-lib.php` → librería compartida (no se ejecuta directo)
- `export-theme.php` → atajo: exporta BD → repo y regenera el bundle
- `import-theme.php` → solo crea el tema si no existe (instalación inicial)

## Flujo recomendado

1. Editar `ope.css` y/o `ope-*.xml` (nunca añadir `<style>` en `.php`)
2. `php scripts/sync-theme.php import`
3. `php scripts/sync-theme.php verify`
4. Commit de los archivos en `docs/themes/`

Si editas en Admin CP y quieres conservarlo en git: `php scripts/export-theme.php` y commit.
