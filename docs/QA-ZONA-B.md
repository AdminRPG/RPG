# QA · Panel Zona B en el editor real de MyBB (XAMPP)

> **Qué verifica esto:** que el panel de cartas de combate (Zona B, F2.2/5.10) se
> **inyecta correctamente bajo el editor** de MyBB en el entorno de desarrollo
> (XAMPP / `http://rpg.test`), que compone las cartas, que al enviar se incrusta
> el bloque `[ope7-zonab]{json}[/ope7-zonab]` y que el post resultante renderiza
> y persiste los turnos. Es la verificación visual y manual del pendiente del
> AGENTS («editor real aún no inyecta el panel … falta QA visual con XAMPP»).

---

## 0. Contexto rápido

El ciclo Zona B tiene **tres partes**, todas ya cableadas en el plugin:

| Pieza | Dónde | Hook | Hace qué |
|---|---|---|---|
| Panel en el editor | `ope7_zonab_editor_html()` · `inc/ope_rol/sistemas/combate_ui.php` | `pre_output_page` → `ope_rol_inject_zonab_editor` (en `inc/plugins/ope_rol.php`) | Compone las cartas en vivo y al enviar apenda `[ope7-zonab]{json}[/ope7-zonab]` |
| Render en el post | `ope7_zonab_parse()` · `combate_ui.php` | `parse_message` | Convierte el bloque en la Zona B que ve el rival |
| Persistencia | `ope7_zonab_on_post()` · `combate_ui.php` | `datahandler_post_insert_thread_end` / `post_end` | Guarda `ope_turnos_combate` + `ope_sala_combate` con los avisos |

El panel **solo** aparece si el usuario tiene un personaje 7 Seas **activo y con
vida** (`ope7_zonab_contexto()`). Si no hay personaje, `ope7_zonab_editor_html()`
devuelve `''` y la página no se toca.

---

## 1. Chequeo estructural automático (rápido)

Corre esto primero desde la raíz del repo **en la máquina de desarrollo**:

```bash
php scripts/check-zona-b.php
```

Debe acabar con `Resultado: ZONA B OK` y **15/15 OK**. Verifica sin tocar la BD:
el hook registrado, que el inyector solo actúa en `newthread/newreply/editpost`
con un textarea de mensaje, que el panel y el ciclo existen, el CSS en
`docs/themes/ope.css` y un smoke test que evalúa la **función real** y confirma
que incrusta el panel justo después del `</textarea>`.

> Si cambias el CSS del panel, recuerda `php scripts/sync-theme.php import` y
> `php scripts/sync-theme.php verify` (→ `OK CSS: in sync`).

---

## 2. Prerrequisitos del QA visual (XAMPP)

1. **BD sembrada** (si es una BD fresca): sigue la batería del §11-bis del
   AGENTS.md (migración de esquema + seeds de F1/F2 como mínimo, porque el panel
   lee técnicas y estados).
2. **Un personaje 7 Seas aprobado y ACTIVO** con el que vas a probar, y con al
   menos 1 técnica (trámite 13) para que las cartas aparezcan.
3. **Tema abierto** donde puedas postear (por ejemplo un tema propio en el foro
   de prueba). La Zona B aplica a respuestas (`newreply.php`) y a temas nuevos
   (`newthread.php`).
4. **Editor MyBB activado** (el textarea `name="message"`): tanto el editor
   simple como SCEditor expone ese textarea, así que vale cualquiera.

---

## 3. Pasos de verificación visual (los 6 chequeos)

Abriendo `http://rpg.test/newreply.php?tid=<ID de tu tema>` (o creando un tema
nuevo), con la sesión del usuario con el personaje activo:

### 3.1 El panel aparece bajo el editor
- **Esperado:** justo **debajo del área de escritura** se ve una tarjeta con el
  badge **«ZONA B»**, la línea «Turno de <nombre> · Nv X · AGI X · PA base Y
  (6 + AGI/10 + Nv/5)» y tres columnas: **Técnicas jugadas** · **Estados
  activos** (con consumibles) · **Modificadores del turno** (con contadores
  PV/PE/PA).
- **Si NO aparece:** revisa que el personaje activo sea del esquema 7 Seas
  (`ope_cuentas.personaje_activo` canónico, no el puntero legacy) y que esté
  **aprobado y con vida**. Mira la consola del navegador (paso 3.6).
- **Error que NO debe ocurrir:** error fatal PHP o una pantalla blanca. El
  inyector devuelve la página sin tocar si no aplica, nunca rompe.

### 3.2 Las técnicas del personaje se listan
- **Esperado:** en la columna «Técnicas jugadas» aparecen los botones con tu
  técnica (nombre, tier, PA y %PE). Si no tienes ninguna, verás el aviso
  «Sin técnicas en la librería. Créalas por el trámite 13.» (también correcto).
- **Si falta:** la técnica puede no estar `activa=1` o el personaje no ser el
  activo. Comprueba `ope_tecnicas` para el `personaje_id` del PID activo.

### 3.3 Componer y validar un turno
1. Haz click en una técnica → se añade a la selección de abajo y el contador
   «PA gastado/PA reserva» se actualiza.
2. Añade un **consumible** (nombre + PA) y marca, si quieres, «1 contra varios»
   o «Sobrecarga».
3. Pulsa **«Validar turno»**.
- **Esperado:** el aviso dice «Presupuesto OK: X/Y PA, reserva Z» si no te
  pasas, o «Presupuesto EXCEDIDO … (aviso para el staff)» si excedes (nunca te
  **bloquea** — regla de hábito 5.10).
- **Si algo no cuadra:** el recálculo de PA lo hace `ope7_combate_pa_turno()`
  (6 + AGI/10 + Nv/5). Ajusta AGI o Nv en la ficha y recarga.

### 3.4 El bloque se incrusta al enviar
1. Completa la narrativa (mín. de Zona A si aspira a puntuar) y envía el post.
2. Inspecciona el **cuerpo del mensaje publicado** (modo edición o ver fuente).
- **Esperado:** al final del mensaje hay un bloque
  `[ope7-zonab]{…json…}[/ope7-zonab]` con las cartas elegidas, contadores y
  modificadores. Si editas el mensaje, el panel vuelve a aparecer bajo el
  editor y puedes regenerar el bloque.
- **Si NO se incrusta:** el JS busca `textarea[name="message"]` y reescribe el
  valor antes del submit. Si tu skin renombra el textarea, hay que adaptar el
  selector en `combate_ui.php`. El editor SCEditor de MyBB usa `MyBBEditor.val()`
  y debe estar presente.

### 3.5 El post renderiza la Zona B (persistencia + parse)
- **Esperado:** bajo tu narrativa, quien vea el tema ve el bloque renderizado
  **«ZONA B · Cartas del turno»** con tus técnicas/consumibles/estados y
  contadores. En BD quedan filas en `ope_turnos_combate` (y `ope_sala_combate`
  creada para el tema).
- **Si NO renderiza pero el JSON está en el mensaje** → el hook `parse_message`
  (`ope7_zonab_parse`) puede no estar disparando (por cache de template) o el
  parse falló (deja el bloque en un `<div class="ope7-zb-block ope7-zb-block--raw">`).
- **Si falta la fila en `ope_turnos_combate`** pero el JSON sí está → revisa
  `ope7_zonab_on_post` y que existan `ope_turnos_combate`/`ope_sala_combate`.

### 3.6 Sin errores en consola ni en servidor
- Abre la consola del navegador (F12) en `newreply.php` y en `showthread.php`.
- **Esperado:** ningún error rojo de JS del panel. Puede haber avisos ajenos al
  foro (extensiones del navegador).
- Revisa el log de errores de **XAMPP** (`logs/php_error.log` de Apache) tras
  la secuencia: no debe haber warning/fatal de `ope7_zonab_*` ni de
  `ope_rol_inject_zonab_editor`.
- **Importante:** el panel se inyecta vía `pre_output_page` sobre el HTML ya
  renderizado. Si tu tema usa caché agresiva, el primer acceso tras activar el
  plugin puede no mostrar el panel hasta purgar caché de templates/shm.

---

## 4. Casos límite a comprobar (rápido)

| Caso | Esperado |
|---|---|
| Sin sesión o sin personaje activo | La página se ve normal, **sin** panel (no rompe nada) |
| Usuario con personaje **en revisión o muerto** | Sin panel (len la vida: `estado_vida != 'activa'`) |
| `newthread.php` (tema nuevo) | Panel presente igual que en `newreply.php` |
| `editpost.php` | Panel presente para recomponer el turno del propio post |
| **Otras páginas** (portada, showthread sin el editor, usercp) | **Sin** panel y sin degradación |
| Exceder PA con «1 contra varios» | Aviso (no bloqueo); `+3 PA` aplicado al presupuesto |
| Misma técnica dos veces en un turno | Aviso de «técnica repetida → revisa reposo» al validar |

---

## 5. Cómo se ve el resultado esperado al terminar

- `php scripts/check-zona-b.php` → **15/15 OK**.
- En el editor: **panel ZONA B visible** bajo el textarea con las tres columnas
  y tus técnicas.
- Al enviar: **JSON incrustado** en el mensaje y **Zona B renderizada** en el
  tema.
- En BD: **`ope_turnos_combate`** con filas del turno y **`ope_sala_combate`**
  creada/aprovechada para el tema.
- Consola y `php_error.log` **limpios**.
- `check-inline-styles` limpio y `sync-theme verify` → `OK CSS: in sync`.

Si algo falla, anota el paso y el síntoma (consola vs servidor vs render) y
abre el caso: la parte implicada es **inyección** (plugin), **composición/JS**
(`combate_ui.php`), **parse** (`ope7_zonab_parse`) o **persistencia**
(`ope7_zonab_on_post`). Los tests del motor (`test-7seas-f2.php`) cubren la
lógica pura; este QA cubre la integración real en el editor.