---
name: mybb-theme-dev
description: Guía avanzada para el desarrollo, compilación y sincronización de temas visuales en MyBB para One Piece: Eternal (ope.css, sync-theme.php, inspección sin MCP playwright, resolución de template drift).
---

# Desarrollo de Temas MyBB y Estilos Visuales (`ope.css`)

Este documento define los procedimientos y herramientas obligatorias para trabajar en el tema visual de **One Piece: Eternal**.

---

## 1. El Archivo CSS Canónico y el Flujo de Sincronización

La fuente de verdad absoluta de los estilos del proyecto es **`docs/themes/ope.css`**.

> ⚠️ **REGLA FUNDAMENTAL**: El navegador del usuario **NO** lee directamente `docs/themes/ope.css`. En su lugar, lee el CSS compilado en `cache/themes/theme13/ope.css` que gestiona MyBB.

### Flujo de Trabajo Obligatorio tras Modificar CSS:

Cada vez que edites `docs/themes/ope.css` o plantillas XML, debes ejecutar el script de importación y verificación:

```bash
# 1. Importar los cambios de docs/themes/ope.css a la base de datos y compilar la caché de temas
php scripts/sync-theme.php import

# 2. Verificar que el archivo fuente y la caché compilada estén perfectamente en sincronía
php scripts/sync-theme.php verify
```

#### Salida Esperada en Caso de Éxito:
```text
OK   CSS: in sync
```

Si el comando `verify` muestra **`DRIFT`**, significa que hay desincronización y los cambios no se reflejarán correctamente en producción.

---

## 2. Inspección Visual Automatizada sin MCP Playwright

> [!IMPORTANT]
> **NO existe un servidor MCP de Playwright independiente**. Toda inspección visual, pruebas de maquetación y capturas end-to-end deben realizarse mediante la herramienta nativa **`browser_subagent`**.

### Flujo de Verificación Visual Paso a Paso:

1. **Lanzar el subagente de navegador**:
   Utiliza `browser_subagent` indicando la tarea específica, la URL local (ej. `http://localhost/ope/index.php` o `http://localhost/ope/ficha.php`) y la acción.

2. **Realizar interacción y scroll**:
   Indica al subagente que haga scroll completo por la pantalla para forzar la activación de las animaciones `.reveal.vis` de IntersectionObserver.

3. **Captura de evidencia y comparación**:
   El subagente grabará un vídeo en formato `.webp` y tomará capturas de pantalla que se almacenarán automáticamente en la carpeta de artefactos de la sesión.

4. **Comparación con el Prototipo**:
   Compara la captura generada contra el prototipo estático en `docs/Prototypes/Granblue/index.html`.

---

## 3. Resolución de Desincronización de Plantillas (Template Drift)

El script `php scripts/sync-theme.php verify` también compara las plantillas XML almacenadas en el sistema de archivos contra los registros en la tabla `mybb_templates` de la base de datos.

### Diagnóstico de Drift (Ejemplo de Auditoría):
```text
DRIFT TPL: index differs (source: ope-index.xml)
DRIFT TPL: forumdisplay differs (source: ope-forumdisplay.xml)
DRIFT TPL: forumdisplay_usersbrowsing differs (source: ope-forumdisplay.xml)
DRIFT TPL: showthread_usersbrowsing differs (source: ope-showthread.xml)
```

### Cómo Resolver el Drift:
1. **Si el código fuente XML es el correcto** (lo habitual durante el desarrollo de nuevas características):
   ```bash
   php scripts/sync-theme.php import
   ```
   Esto sobrescribirá las plantillas en la base de datos con el contenido de los archivos XML en `inc/plugins/ope_rol/`.

2. **Si la base de datos se modificó manualmente desde el ACP de MyBB**:
   Exporta las plantillas desde el foro o sincroniza hacia el archivo XML antes de hacer commit.

---

## 4. Errores Comunes en Desarrollo de Temas

1. **Editar directamente `cache/themes/theme13/ope.css`**:
   - ❌ *Error*: Modificar la caché del tema manualmente. Los cambios se borrarán automáticamente la próxima vez que se ejecute `sync-theme.php import`.
   - ✅ *Correcto*: Editar **`docs/themes/ope.css`** y ejecutar `php scripts/sync-theme.php import`.

2. **Escribir `<style>` inline o atributos `style=""` en plantillas o vistas PHP**:
   - ❌ *Error*: `<div style="margin-top:20px;color:red;">`.
   - ✅ *Correcto*: Crear la clase correspondiente bajo el scope de la página en `ope.css` (ej. `body.ope-pg-mi-pagina .mi-alerta`) y verificar con `php scripts/check-inline-styles.php`.

3. **Usar selectores de clase sueltos sin el scope de página (`body.ope-pg-<pagina>`)**:
   - ❌ *Error*: `.plate { border-radius: 8px; }` (afecta globalmente o rompe otras páginas).
   - ✅ *Correcto*: `body.ope-pg-ficha .plate { border-radius: 8px; }`.

4. **Usar botones "pill/cápsula" (`border-radius: 24px-30px`)**:
   - ❌ *Error*: Diseñar botones redondeados estilo cápsula.
   - ✅ *Correcto*: Los botones principales deben ser rectangulares con `border-radius: 8px` según el estándar en `docs/DESIGN-ONE-PIECE-ETERNAL.md` §4.4.

---

## 5. Checklist de Entrega Visual

- [ ] Estilos añadidos a `docs/themes/ope.css` con el scope adecuado.
- [ ] Ejecución de `php scripts/sync-theme.php import`.
- [ ] Verificación de sincronización `php scripts/sync-theme.php verify` devolviendo `OK CSS: in sync`.
- [ ] Comprobación de estilos inline sin errores: `php scripts/check-inline-styles.php`.
- [ ] Inspección visual realizada en navegador mediante `browser_subagent`.
