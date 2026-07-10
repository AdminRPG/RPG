# Hoja de Ruta — One Piece Eternal

Versión 1.0 · Julio 2026

---

## Visión General

El sistema de reglas (27 documentos, ~v3.0) está muy por delante del código (fase 1 inicial). Esta hoja de ruta cierra la brecha en 4 oleadas, ordenadas por dependencia. Cada oleada entrega un sistema funcional completo que los jugadores pueden usar de inmediato.

**Principio rector:** No se puede rolear progresión sin PP. No se puede combatir sin fórmulas base. No se puede viajar sin Oráculo. Implementamos en ese orden.

---

## Oleada 1: El Motor Básico (Semanas 1-2)

Lo mínimo para que el juego "se mueva": los jugadores rolean, ganan PP, gastan PP en stats.

### 1.1 Usuario Sistema "OP-Eternal"

Crear la cuenta bot que firmará todos los posts automáticos (viajes, misiones, notificaciones del sistema).

**Qué crear:**
- Usuario MyBB `uid=N` (reservado, p.ej. uid=2 si está libre) con username `OP-Eternal`
- Personaje `pid=N` asociado a ese uid, con `nombre = 'OP-Eternal'`, facción neutral, `staff_narrador = 1`, `es_npc = 1`
- El personaje NO aparece en listados de jugadores, NO consume slots
- Su avatar/postbit muestra un diseño especial de "sistema" (estilo Eternal News)

**Archivos:**
- `scripts/create-op-eternal.php` — script de seed
- `inc/ope_rol_system.php` — funciones helper: `ope_system_uid()`, `ope_system_pid()`, `ope_system_post_as()`

### 1.2 Motor de PP (Puntos de Progreso)

Contar palabras de cada post y asignar PP automáticamente.

**Especificación (de INI-04):**
| Palabras | PP |
|----------|-----|
| 0-300 | 1 |
| 301-700 | 2 |
| 701-1200 | 3 |
| 1201+ | 4 |

**Qué implementar:**
- Hook `datahandler_post_insert_post_end` y `datahandler_post_insert_thread_end`: contar palabras del mensaje, calcular PP, guardar en `rol_pp_log`
- Nueva tabla `rol_pp_log`: `log_id, pid, tid, pid_post, palabras, pp_ganados, tipo ('post','mision','arco','evento'), dateline`
- Nueva tabla `rol_pp_saldo`: `pid, pp_total, pp_gastado, last_update` — saldo vivo (cacheado)
- Endpoint/función para que el staff añada PP manual (misiones, arcos)
- Mostrar PP en la ficha del personaje (`ficha.php`)

**Archivos:**
- `scripts/migrate-pp.php` — crear tablas
- `inc/ope_rol_pp.php` — lógica
- Hook en `inc/plugins/ope_rol.php`

### 1.3 Tabla de Costes de Stats

La progresión de stats usa la tabla exponencial de INI-04:

| De → A | Coste PP | Acumulado |
|--------|----------|-----------|
| F(1)→E(2) | 5 | 5 |
| E(2)→D(3) | 10 | 15 |
| D(3)→C(4) | 20 | 35 |
| C(4)→B(5) | 35 | 70 |
| B(5)→A(6) | 55 | 125 |
| A(6)→S(7) | 80 | 205 |
| S(7)→SS(8) | 110 | 315 |
| SS(8)→M(9) | 150 | 465 |
| M(9)→M+(10) | 200 | 665 |

**Qué implementar:**
- Página `progresion.php` — panel del jugador: ve sus PP, sus stats actuales, y puede gastar PP para subir stats
- Validación server-side: comprobar saldo, aplicar coste, actualizar stat, descontar PP
- Las pasivas raciales negativas encarecen (subir desde 0 cuesta el coste 0→F + F→E)
- Log de gastos en `rol_pp_log`
- Las stats se actualizan en `rol_personajes` (columna `stats_json` o similar)

**Archivos:**
- `progresion.php`
- `inc/ope_rol_progresion.php`

---

## Oleada 2: Fórmulas de Combate (Semanas 3-4)

Sin esto, el combate es puramente narrativo. Con esto, las cartas de técnica tienen impacto medible.

### 2.1 PV, EN y PA como columnas cacheadas

Añadir a `rol_personajes` las columnas calculadas:
- `pv_max` = (FUE + VIG) × 5 + (VOL + CON) × 2
- `en_max` = según tabla de rangos (INI-04 / AV-01)
- `pa_por_turno` = AGI + max(INT, ING, CAR) + bono_rango

Recalcular automáticamente al cambiar stats (hook en progresion.php).

**Archivos:**
- `scripts/migrate-combat-cols.php`
- Funciones en `inc/ope_rol_data.php`

### 2.2 Sistema de Estados

16+ condiciones (Aturdido, Quemado, Envenenado, Empapado, Anulado, etc.).

- Tabla de referencia `rol_estados`: `estado_key, nombre, efecto, duracion_default`
- En combate, los estados se almacenan en el post (no en BD permanente) — o en una tabla temporal `rol_combate_estados` si el combate es multi-post
- El sistema de cartas de técnica aplica/quita estados según sus tags

### 2.3 Heridas Localizadas

Sistema de heridas de AV-01 §Heridas:
- Umbrales: <10% PV (sin herida), 10-19% (Leve), 20-34% (Grave), 35%+ (Crítica)
- Localización 1D6: Cabeza, Torso, Brazo Izq, Brazo Der, Pierna Izq, Pierna Der
- Acumulación: 2 Leves → Grave, 2 Graves → Crítica, 2 Críticas → Inutilizada

---

## Oleada 3: El Oráculo de Viaje (Semanas 5-7) ★ PRIORITARIO

Este es el sistema estrella. Cuando un jugador solicita un viaje, **OP-Eternal** crea automáticamente un tema de rol en la zona "Alta Mar" con todo resuelto.

### 3.1 Flujo Completo

```
Jugador → formulario (origen, destino, barco, tripulación)
       → Sistema calcula tramos, modificadores de oficios, dificultad del mar
       → Para cada tramo: tira 4 mesas D100 (Clima, Encuentros, Hallazgos, Peligros)
       → Ensambla descripción narrativa combinando todos los resultados
       → OP-Eternal crea TEMA en el foro "Alta Mar" con el primer post (resolución del oráculo)
       → Los jugadores rolean la travesía (posts mínimos según tramos)
       → Al completar, OP-Eternal cierra con post de "Llegada a [destino]"
```

### 3.2 Componentes Técnicos

**3.2.1 Página de solicitud: `viajes.php`**
- Formulario multi-step conectado a la API del backend
- Select de personaje activo + barco (de su tripulación/inventario)
- Select de origen y destino (islas del mundo)
- Autocomplete de tripulantes (personajes del grupo)
- Check de suministros
- Vista previa de: tramos estimados, duración, modificadores de oficios

**3.2.2 Endpoint API: `POST /api/v1/viajes/solicitar`**
- Recibe: `{ pid, barco_id, origen, destino, tripulantes: [pid], suministros }`
- Valida: el personaje tiene tripulación, el barco existe, los tripulantes están en el grupo
- Calcula tramos y modificadores (Navegante, Timonel, Vigía, Carpintero, Cocinero, Médico)
- Ejecuta el Oráculo (4 mesas D100 × N tramos)
- Ensambla el HTML del primer post
- Crea el tema en MyBB vía `DataHandler` como OP-Eternal
- Crea `rol_thread_meta` con tag='Viaje'
- Crea `rol_viaje` con los datos del viaje (origen, destino, tramos, resultado_json)
- Devuelve `{ tid, url_tema }`

**3.2.3 El Oráculo (`inc/ope_rol_oraculo.php`)**

Las 4 mesas como arrays PHP:

```php
function ope_rol_oraculo_clima($d100, $mods) { ... }
function ope_rol_oraculo_encuentros($d100, $mods) { ... }
function ope_rol_oraculo_hallazgos($d100, $mods) { ... }
function ope_rol_oraculo_peligros($d100, $mods) { ... }
```

Cada función devuelve un array con `{ resultado, descripcion, efecto_mecanico }`.

Los modificadores se calculan según:
- Nivel de oficio de cada tripulante (tablas de AV-02 §3.1)
- Tipo de barco (tabla AV-02 §3.2)
- Mar/zona (tabla AV-02 §3.3)

**3.2.4 Generador de Post Automático**

El HTML del primer post de OP-Eternal sigue el estilo neobrutalista del tema:

```html
<div class="ope-viaje-oraculo">
  <header class="ope-vo-head">
    <h2 class="ope-vo-titulo">🌊 Travesía: [Origen] → [Destino]</h2>
    <div class="ope-vo-meta">
      <span>⛵ [Nombre del Barco]</span>
      <span>📏 [N] tramos</span>
      <span>📅 [Primavera/Verano/Otoño/Invierno], Día [N]</span>
      <span>⏱️ Plazo: [N] días</span>
    </div>
  </header>

  <!-- TRAMO 1 -->
  <section class="ope-vo-tramo">
    <h3 class="ope-vo-tramo-titulo">Tramo 1 — [Nombre tramo]</h3>
    <div class="ope-vo-grid">
      <div class="ope-vo-card ope-vo-clima">
        <div class="ope-vo-card-icon">🌤️</div>
        <div class="ope-vo-card-label">Clima</div>
        <div class="ope-vo-card-valor">[Resultado clima]</div>
        <div class="ope-vo-card-efecto">[Efecto mecánico]</div>
      </div>
      <div class="ope-vo-card ope-vo-encuentro">
        <div class="ope-vo-card-icon">⚔️</div>
        <div class="ope-vo-card-label">Encuentro</div>
        <div class="ope-vo-card-valor">[Resultado encuentro]</div>
      </div>
      <div class="ope-vo-card ope-vo-hallazgo">
        <div class="ope-vo-card-icon">💎</div>
        <div class="ope-vo-card-label">Hallazgo</div>
        <div class="ope-vo-card-valor">[Resultado hallazgo o "Sin hallazgo"]</div>
      </div>
      <div class="ope-vo-card ope-vo-peligro">
        <div class="ope-vo-card-icon">⚠️</div>
        <div class="ope-vo-card-label">Peligro</div>
        <div class="ope-vo-card-valor">[Resultado peligro o "Sin peligro"]</div>
      </div>
    </div>
    <div class="ope-vo-narrativa">
      <p>[Descripción narrativa generada combinando los 4 resultados]</p>
    </div>
  </section>

  <!-- TRAMO 2, 3... -->

  <footer class="ope-vo-footer">
    <div class="ope-vo-reglas">
      <h4>📋 Reglas del Viaje</h4>
      <ul>
        <li>Posts mínimos: <strong>[N]</strong> (mínimo <strong>[X]</strong> por jugador)</li>
        <li>Plazo máximo: <strong>[N] días</strong> (off-rol)</li>
        <li>Orden de posteo: libre, respetando el turno una vez iniciado</li>
        <li>Al llegar a <strong>[N] posts</strong>, OP-Eternal cerrará el viaje con la llegada a destino</li>
      </ul>
    </div>
    <div class="ope-vo-oficios">
      <h4>🔧 Oficios Activos</h4>
      <div class="ope-vo-oficios-grid">
        <!-- Navegante, Timonel, Vigía, Carpintero, Cocinero, Médico con sus niveles -->
      </div>
    </div>
  </footer>
</div>
```

**3.2.5 CSS para el Oráculo**

Añadir a `docs/themes/ope.css`:

```css
/* ===== ORÁCULO DE VIAJE (posts de OP-Eternal) ===== */
.ope-viaje-oraculo{background:var(--concrete);color:var(--ink);border:2px solid #000;margin:10px 0;box-shadow:5px 5px 0 rgba(0,0,0,.35)}
.ope-vo-head{background:var(--iron);border-bottom:3px solid #000;padding:18px 20px}
.ope-vo-titulo{font-family:var(--disp);font-weight:900;font-size:1.5rem;text-transform:uppercase;color:var(--paper);letter-spacing:.5px}
.ope-vo-meta{display:flex;flex-wrap:wrap;gap:12px;margin-top:10px;font-family:var(--mono);font-size:.68rem;color:var(--paper-dim)}
.ope-vo-meta span{background:var(--iron-edge);padding:4px 10px;border:1px solid var(--rivet)}
.ope-vo-tramo{padding:18px 20px;border-bottom:2px solid var(--concrete-line)}
.ope-vo-tramo:last-of-type{border-bottom:none}
.ope-vo-tramo-titulo{font-family:var(--disp);font-weight:800;font-size:1.15rem;color:var(--iron);text-transform:uppercase;margin-bottom:14px;border-left:4px solid var(--ember);padding-left:12px}
.ope-vo-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.ope-vo-card{border:2px solid #000;padding:12px;text-align:center;background:var(--paper);box-shadow:3px 3px 0 rgba(0,0,0,.15)}
.ope-vo-card-icon{font-size:1.6rem;margin-bottom:4px}
.ope-vo-card-label{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ash);margin-bottom:4px}
.ope-vo-card-valor{font-family:var(--disp);font-weight:800;font-size:1.1rem;color:var(--ink);line-height:1.1}
.ope-vo-card-efecto{font-family:var(--mono);font-size:.6rem;color:var(--ink-2);margin-top:4px;background:var(--concrete-2);padding:3px 6px}
.ope-vo-clima{border-color:var(--patina)}.ope-vo-clima .ope-vo-card-icon{color:var(--patina)}
.ope-vo-encuentro{border-color:var(--crack)}.ope-vo-encuentro .ope-vo-card-icon{color:var(--crack)}
.ope-vo-hallazgo{border-color:var(--gold)}.ope-vo-hallazgo .ope-vo-card-icon{color:var(--gold-deep)}
.ope-vo-peligro{border-color:#e3a836}.ope-vo-peligro .ope-vo-card-icon{color:#e3a836}
.ope-vo-narrativa{padding:12px 16px;background:var(--iron-plate);border:2px solid #000;color:var(--paper);font-size:.9rem;line-height:1.6;font-style:italic}
.ope-vo-footer{display:grid;grid-template-columns:1fr 1fr;gap:0;background:var(--iron-edge);border-top:3px solid #000;padding:16px 20px;color:var(--paper-dim)}
.ope-vo-footer h4{font-family:var(--disp);font-weight:800;font-size:.95rem;text-transform:uppercase;color:var(--paper);margin-bottom:8px}
.ope-vo-footer ul{list-style:none;padding:0}
.ope-vo-footer ul li{font-family:var(--mono);font-size:.64rem;padding:2px 0}
.ope-vo-footer ul li::before{content:"▸ ";color:var(--ember)}
.ope-vo-oficios-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.ope-vo-oficio{display:flex;justify-content:space-between;padding:4px 8px;border:1px solid var(--iron-hi);font-family:var(--mono);font-size:.62rem;background:var(--iron)}
.ope-vo-oficio-l{text-transform:uppercase;color:var(--paper-dim)}
.ope-vo-oficio-v{color:var(--ember-hi);font-weight:700}
@media(max-width:768px){.ope-vo-grid{grid-template-columns:1fr 1fr}.ope-vo-footer{grid-template-columns:1fr}}
```

**3.2.6 Cierre Automático del Viaje**

Cuando se alcanzan los posts mínimos, OP-Eternal publica un post de cierre:
- Calcula el estado de llegada (Óptima / Con Daños / Con Heridos / Crítica / Triunfal)
- Aplica consecuencias (daño al barco, heridas a tripulantes, botín)
- Mueve a los personajes a la nueva ubicación en el sistema
- Calcula IM (Impacto Mundial) si hubo eventos de PE 2+
- Notifica al staff para revisión (si aplica)

### 3.3 Archivos Nuevos

| Archivo | Propósito |
|---------|-----------|
| `viajes.php` | Página pública de solicitud de viaje |
| `inc/ope_rol_viajes.php` | Lógica: cálculo de tramos, validación |
| `inc/ope_rol_oraculo.php` | Las 4 mesas (arrays + funciones de tirada) |
| `inc/ope_rol_oraculo_post.php` | Generación de HTML del post automático |
| `inc/ope_rol_navegacion.php` | Modificadores de oficios, barcos y mares |
| `scripts/migrate-viajes.php` | Tablas: `rol_viajes`, `rol_viaje_tramos` |
| `docs/themes/ope.css` | Añadir ~80 líneas de CSS para `.ope-viaje-oraculo` |
| `rol-backend/src/Controllers/ViajeController.php` | Endpoint API |
| `rol-backend/src/Services/OracleService.php` | Oráculo |
| `rol-backend/src/Services/ViajeService.php` | Creación de tema + post |

### 3.4 Sistema de Misiones (mismo patrón)

Reutilizar el mismo flujo para misiones:
- Jugador acepta misión del Tablón → OP-Eternal crea tema autogenerado
- El post inicial detalla: briefing de la misión, objetivos, enemigos esperados, recompensa
- Mismo endpoint, mismo generador de HTML, distinto template

---

## Oleada 4: Batallas Navales + Wanted + Grupos/Bases (Semanas 8-10)

### 4.1 Batallas Navales (AV-03)

Sobre la base del Oráculo (encuentros hostiles), permitir que un encuentro escale a combate naval.

- Modelo `Barco` con stats: PR (Puntos de Ruptura), Artillería (Ligera/Media/Pesada), Maniobrabilidad (1-10), Blindaje
- Tablas `rol_barcos`, `rol_barco_mejoras`
- En combate: los jugadores declaran acciones (andarada, evasión, abordaje) y OP-Eternal arbitra (reducción de PR, estados críticos: Desarbolado, Sin Gobierno, Vía de Agua)
- Integración con Mundo Vivo: hundir/capturar barcos afecta métricas de facción

### 4.2 Sistema Wanted/Bounty

- Tabla `rol_wanted`: `pid, bounty_actual, bounty_max, ultima_actualizacion`
- Cálculo automático al cerrar temas: acciones criminales + notoriedad → aumento de bounty
- Umbrales de INI-04: 0, 1M-50M, 50M-150M, 150M-500M, 500M-1B, 1B-3B, 3B+
- Integración con Oráculo: +probabilidad de cazarrecompensas según bounty
- Los Marines con alto rango pueden ver el bounty de otros

### 4.3 Grupos, Oficios y Bases

- Completar `tripulacion.php` con lo definido en AV-06
- Sistema de oficios (9 tipos, 5 niveles) con ventajas narrativas
- NPC acompañantes vinculados a personajes
- Bases fijas: mismo modelo que barcos pero Maniobrabilidad=0
- Economía grupal: mantenimiento mensual, tesorería compartida, ingresos pasivos

---

## Resumen de Entregables

| Oleada | Semanas | Sistemas | Líneas estimadas | Archivos nuevos |
|--------|---------|----------|-----------------|-----------------|
| 1 | 1-2 | OP-Eternal, PP, Costes Stats | ~800 | 6 |
| 2 | 3-4 | PV/EN/PA, Estados, Heridas | ~600 | 4 |
| **3** | **5-7** | **Oráculo Viajes, Misiones** | **~2500** | **10+** |
| 4 | 8-10 | Batallas Navales, Wanted, Grupos/Bases | ~2000 | 8+ |
| **Total** | | | **~5900** | **28+** |

---

## Arquitectura de OP-Eternal como Bot del Sistema

OP-Eternal no es solo un usuario más. Es el "director de juego automatizado":

- **Viajes:** Crea tema, publica oráculo, cierra al completar
- **Misiones:** Crea tema con briefing, publica recompensa al completar
- **Mundo Vivo:** Publica el periódico mensual (ya funciona en `mundo-vivo.php`)
- **Notificaciones:** Avisa a jugadores de cambios en su Wanted, misiones disponibles, eventos
- **Combates arbitrados:** Si un combate PvP necesita mediación, OP-Eternal resuelve mecánicamente

Estéticamente, los posts de OP-Eternal son inconfundibles: tipografía pirata, paleta océano, cajas neobrutalistas con bordes negros gruesos y sombras duras. Coherente con el tema `ope.css`.
