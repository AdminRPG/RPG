### Task 3: Modificar `personajes.php` — quitar aprobación, añadir flag "En revisión"

**Files:**
- Modify: `personajes.php`

**Interfaces:**
- Consumes: `rol_personajes.estado`
- Produces: Página de personajes sin panel de aprobación staff, con flags de estado visibles

---

**Step 1: Eliminar la sección de aprobación de staff**

Eliminar líneas 353-397 completas de `personajes.php` (todo el bloque `<?php if ($staff_level >= 1): ?>` con la placa de "Aprobación de expedientes"). Este bloque incluye el `<section class="reveal">`, la `.plate` con título "Aprobación de expedientes", los contadores de pendientes, y el grid de `.pjcard` con formularios approve/reject.

**Step 2: Eliminar el POST handler `approve_char` y `reject_char`**

Eliminar líneas 83-96 de `personajes.php` (el bloque que procesa `approve_char` y `reject_char` del lado staff). Es el bloque que empieza con:
```php
if (($action === 'approve_char' || $action === 'reject_char') && $staff_level >= 1
```

**Step 3: Mejorar el badge visual de estado "En revisión"**

En el CSS del `<style>` block de personajes.php, añadir al final del bloque de estilos:

```css
.pjcard-chip{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 11px;border:2px solid #000;display:inline-block}
.pjcard-chip[style*="--h6"]{animation:pulse-revision 2s ease-in-out infinite}
@keyframes pulse-revision{0%,100%{box-shadow:0 0 0 0 rgba(255,203,147,.4)}50%{box-shadow:0 0 0 6px rgba(255,203,147,0)}}
```

**Step 4: Eliminar también la línea de consulta de pendientes del staff**

Eliminar el bloque de consulta de pendientes del staff (aproximadamente líneas 180-193) que hace `SELECT ... FROM rol_personajes ... WHERE p.estado = 'revision'`. Solo se necesita si no se usa en ninguna otra parte (después de quitar el panel de aprobación, ya no se usa).

---

**Verificación**: Navegar a `http://localhost/iforge/personajes.php` como staff y verificar:
- No aparece la sección "Aprobación de expedientes"
- Los personajes en estado `revision` muestran chip "En revisión" con animación pulsante
- La página carga sin errores PHP
