# I-Forge theme — fuente de verdad y sincronización

El tema **no** vive en un único XML monolítico. Está repartido en archivos con roles claros para que un import no pise otro.

## Archivos canónicos (edita estos)

| Archivo | Contenido |
|---------|-----------|
| `iforge.css` | Hoja de estilos Foundry Brutalism |
| `iforge-foundry-index.xml` | Portada, navbar, footer, forumbit |
| `iforge-foundry-forumdisplay.xml` | Lista de temas |
| `iforge-foundry-showthread.xml` | Hilo y postbit principal |
| `iforge-foundry-forms.xml` | newthread, newreply, editpost, errores |
| `iforge-foundry-shared.xml` | Plantillas auxiliares MyBB (postbit_*, opciones de formulario, etc.) |

## Archivos generados (no edites a mano)

| Archivo | Cómo se genera |
|---------|----------------|
| `iforge-child-theme.xml` | Solo propiedades del tema (stub con `templateset`) |
| `iforge-child-theme.bundle.xml` | `php scripts/sync-theme.php build-xml` — bundle para importar en Admin CP (regenerable, no versionado) |
| `cache/themes/theme*/iforge.css` | Al hacer `import` |

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

1. `iforge-foundry-shared.xml`
2. `iforge-foundry-forms.xml`
3. `iforge-foundry-showthread.xml`
4. `iforge-foundry-forumdisplay.xml`
5. `iforge-foundry-index.xml`

Así las pantallas principales (index, foro, hilo) siempre prevalecen sobre las auxiliares.

## Scripts

- `sync-theme.php` → único punto de entrada (import / export / verify / build-xml / bootstrap)
- `_theme-sync-lib.php` → librería compartida (no se ejecuta directo)
- `export-theme.php` → atajo: exporta BD → repo y regenera el bundle
- `import-theme.php` → solo crea el tema si no existe (instalación inicial)

## Flujo recomendado

1. Editar `iforge.css` y/o `iforge-foundry-*.xml`
2. `php scripts/sync-theme.php import`
3. `php scripts/sync-theme.php verify`
4. Commit de los archivos en `docs/themes/`

Si editas en Admin CP y quieres conservarlo en git: `php scripts/export-theme.php` y commit.
