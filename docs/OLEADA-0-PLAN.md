# Oleada 0 — Plan de Implementación Detallado

> **Objetivo**: Migrar el código de la escala F-M+ (1-10) a la escala del diseño (5-100+), implementar Haki completo, y redefinir el sistema de monedas.
> **Duración estimada**: 2-3 semanas
> **Archivos afectados**: 4 core + 1 nuevo + migraciones BD

---

## Decisiones Tomadas

| # | Decisión | Qué significa en código |
|---|----------|------------------------|
| D-1 | Escala 5-100+ con 30 PS | Stats pasan de letras (F-M+) a números. Base=5, PS=30 repartibles, Humanos=40 PS |
| D-2 | PA = 3 + (AGI/20) + (mejorMental/20) + bono_rango | División entera, rango 3-9 PA. Bono rango basado en Nivel (no en letra) |
| D-3 | EN = (VOL × 3) + (CON × 2) | Fórmula directa, no tabla precalculada. Escala con los stats |
| D-4 | Pasivas raciales con % | +25% FUE, +20% VIG... Se aplican como multiplicador sobre el valor base |
| D-5 | Haki completo (3 tipos × 4 niveles) | Sistema nuevo con PP, tiradas, y PL |
| D-6 | PP=progresión, Berries=tienda, PL=premium, Wanted=flavor | Wanted existe como número cosmético en ficha, sin triggers mecánicos |

---

## Tarea 0.0 — Nuevo esquema de BD (migración)

**Archivo a crear**: `scripts/migrate-oleada0.php`

### Nuevas tablas necesarias:

| Tabla | Columnas | Propósito |
|---|---|---|
| `rol_haki` | `pid, tipo (busoshoku/kenbunshoku/haoshoku), nivel (1-4), pp_gastado, unlocked_at` | Tracking de Haki por personaje |
| `rol_pl` | `pid, pl_total, pl_gastado, pl_disponible, last_update` | Puntos de Leyenda (moneda premium) |
| `rol_pl_log` | `pid, pl_cambio, tipo, notas, dateline` | Auditoría de PL |
| `rol_wanted` | `pid, bounty (INT), last_update` | Wanted cosmético (flavor) |

### Columnas nuevas en tablas existentes:

| Tabla | Columna | Tipo | Propósito |
|---|---|---|---|
| `rol_personajes` | `stats_json` | TEXT/JSON | Los 12 stats en formato `{"FUE": 8, "DES": 7, ...}` (valores numéricos) |
| `rol_personajes` | `nivel` | INT | Nivel calculado = floor(suma_stats / 10) |
| `rol_personajes` | `ps_gastados` | INT | Tracking de cuántos PS se usaron en creación |

### Migración de personajes existentes:

Para cada personaje con datos en escala F-M+:
1. Leer `stats_efectivas` actuales (valores 1-10)
2. Convertir a valor numérico equivalente: F=5, E=7, D=9, C=11, B=13, A=15, S=18, SS=21, M=25, M+=30
3. Guardar en `stats_json` nuevo
4. Recalcular `nivel = floor(suma_stats / 10)`
5. Marcar `ps_gastados = suma_stats - 60` (asumiendo base 5 en 12 stats)

---

## Tarea 0.1 — Reescribir `inc/ope_rol_data.php`

**Secciones a modificar:**

### 0.1.1 — `ope_rol_stats()` (líneas 18-51)
**Sin cambios**. La estructura de 12 stats en 3 pilares es idéntica. Solo cambia cómo se almacena el valor.

### 0.1.2 — `ope_rol_rank_scale()` (líneas 65-72)
**Eliminar**. Ya no se usa escala de letras. Se reemplaza por:
```php
function ope_rol_nivel_from_sum($sum) {
    return (int) floor($sum / 10);
}
```

### 0.1.3 — `ope_rol_rank_from_sum()` (líneas 74-88)
**Reescribir**. Ahora devuelve nivel numérico:
```php
function ope_rol_nivel_label($nivel) {
    // Niveles con nombre para UI
    if ($nivel >= 100) return 'Leyenda';
    if ($nivel >= 80)  return 'Emperador';
    if ($nivel >= 60)  return 'Almirante';
    if ($nivel >= 40)  return 'Vicealmirante';
    if ($nivel >= 25)  return 'Capitán';
    if ($nivel >= 15)  return 'Oficial';
    if ($nivel >= 9)   return 'Novato';
    return 'Civil';
}
```

### 0.1.4 — `ope_rol_stat_num()` (líneas 90-97)
**Reescribir**. Ya no devuelve 0-10, devuelve el valor numérico real:
```php
function ope_rol_stat_num($stats, $key, $default = 5) {
    if (!is_array($stats) || !array_key_exists($key, $stats)) {
        return max(0, (int) $default);
    }
    return max(0, (int) $stats[$key]);
}
```

### 0.1.5 — `ope_rol_stat_sum()` (líneas 99-107)
**Sin cambios lógicos**. La suma ahora da valores mucho más altos (60-200+ en vez de 12-120).

### 0.1.6 — `ope_rol_rank_from_val()` (líneas 109-124)
**Eliminar**. Ya no hay rangos de letras para stats individuales.

### 0.1.7 — `ope_rol_val_from_rank()` (líneas 126-131)
**Eliminar**.

### 0.1.8 — `ope_rol_stat_upgrade_cost()` (líneas 138-153)
**Reescribir completamente**. Nueva tabla de costes por tramos numéricos:
```php
function ope_rol_stat_upgrade_cost($current_val) {
    $v = (int) $current_val;
    if ($v >= 101) return 12;  // 12 PP por punto
    if ($v >= 81)  return 8;   // 8 PP por punto
    if ($v >= 61)  return 5;   // 5 PP por punto
    if ($v >= 41)  return 3;   // 3 PP por punto
    if ($v >= 21)  return 2;   // 2 PP por punto
    if ($v >= 5)   return 1;   // 1 PP por punto
    return 1;
}
```

### 0.1.9 — `ope_rol_stat_rank_labels()` (líneas 155-171)
**Eliminar** o reemplazar por etiquetas textuales basadas en valor numérico:
```php
function ope_rol_stat_label($val) {
    if ($val >= 100) return 'Trascendente';
    if ($val >= 80)  return 'Legendario';
    if ($val >= 60)  return 'Excepcional';
    if ($val >= 40)  return 'Notable';
    if ($val >= 25)  return 'Bueno';
    if ($val >= 15)  return 'Normal';
    if ($val >= 10)  return 'Bajo';
    return 'Mínimo';
}
```

### 0.1.10 — `ope_rol_razas()` (líneas 180-322)
**Reescribir TODOS los `mod`**. Pasar de deltas fijos a multiplicadores %:

| Raza | Mod actual | Mod nuevo |
|---|---|---|
| Humano | `[]` | `[]` (40 PS en vez de 30, va en la lógica de creación) |
| Skypiean | `AGI+1`, `ING+1` | `AGI => 1.15, ING => 1.10` (+15% AGI, +10% ING) |
| Gyojin | `VIG+1`, `FUE+1` | `VIG => 1.15, FUE => 1.15` |
| Gigante | `FUE+2, AGI-1, VIG+1` | `FUE => 1.25, AGI => 0.85, VIG => 1.10` |
| Mink | `[]`, `PER+1` | `PER => 1.10` (+ elemental Electro) |
| Lunarian | `[]`, `VOL+1` | `VOL => 1.10` (+ fuego) |
| Sirena | `SEN+1`, `CAR+1` | `SEN => 1.15, CAR => 1.10` |
| Bucaneer | `FUE+1, VIG+1, VOL+1` | `FUE => 1.15, VIG => 1.15, VOL => 1.10` |
| Tontatta | `FUE-1, AGI+2, DES+2, ING+1` | `FUE => 0.80, AGI => 1.25, DES => 1.25, ING => 1.10` |

**Nuevo campo en el array de raza**: `multiplicadores` (array asociativo stat => factor)
**Mantener**: `primaria_nombre`, `primaria_desc`, `secundaria_nombre`, `secundaria_desc` (texto descriptivo)
**Eliminar**: `mod` y `mod_secundaria` (deltas fijos)

### 0.1.11 — `ope_rol_pc_iniciales()` (líneas 600-602)
**Sin cambios**. Sigue siendo 6 PC.

### 0.1.12 — Nueva función: `ope_rol_ps_iniciales($raza)`
```php
function ope_rol_ps_iniciales($raza = '') {
    if ($raza === 'humano') return 40;
    return 30;
}
```

### 0.1.13 — Nueva función: `ope_rol_aplicar_pasivas($stats_base, $raza_data)`
Aplica multiplicadores raciales a stats base:
```php
function ope_rol_aplicar_pasivas($stats_base, $raza_data) {
    $efectivas = $stats_base;
    $mults = $raza_data['multiplicadores'] ?? [];
    foreach ($mults as $stat => $factor) {
        if (isset($efectivas[$stat])) {
            $efectivas[$stat] = (int) round($efectivas[$stat] * $factor);
        }
    }
    return $efectivas;
}
```

---

## Tarea 0.2 — Reescribir `inc/ope_rol_system.php` (sección combate)

### 0.2.1 — `ope_combat_pa_bonus()` (líneas 449-458)
**Reescribir**. Bono basado en `nivel` numérico, no en letra:
```php
function ope_combat_pa_bonus($nivel) {
    $n = (int) $nivel;
    if ($n >= 80) return 3;
    if ($n >= 50) return 2;
    if ($n >= 25) return 1;
    return 0;
}
```

### 0.2.2 — `ope_combat_calc_pa()` (líneas 463-471)
**Reescribir**. Nueva fórmula híbrida:
```php
function ope_combat_calc_pa($stats, $nivel) {
    $agi = ope_rol_stat_num($stats, 'AGI');
    $int = ope_rol_stat_num($stats, 'INT');
    $ing = ope_rol_stat_num($stats, 'ING');
    $car = ope_rol_stat_num($stats, 'CAR');
    $bonus = ope_combat_pa_bonus($nivel);
    return 3 + (int)($agi / 20) + (int)(max($int, $ing, $car) / 20) + $bonus;
}
```

### 0.2.3 — `ope_combat_en_table()` (líneas 417-423)
**Eliminar**. Ya no se usa tabla precalculada.

### 0.2.4 — `ope_combat_calc_en()` (líneas 440-444)
**Reescribir**. Fórmula directa:
```php
function ope_combat_calc_en($stats) {
    $vol = ope_rol_stat_num($stats, 'VOL');
    $con = ope_rol_stat_num($stats, 'CON');
    return ($vol * 3) + ($con * 2);
}
```

### 0.2.5 — `ope_combat_calc_pv()` (líneas 428-435)
**Sin cambios en la fórmula**. `(FUE+VIG)×5 + (VOL+CON)×2` funciona con valores 5-100+. Los resultados escalan naturalmente.

### 0.2.6 — `ope_combat_recalc()` (líneas 477-504)
**Actualizar**. Ahora usa `nivel` en vez de `rango`, y llama a las nuevas funciones:
```php
function ope_combat_recalc($pid) {
    // ... leer stats_json en vez de stats_efectivas
    $nivel = ope_rol_nivel_from_sum(ope_rol_stat_sum($stats));
    $pv = ope_combat_calc_pv($stats);
    $en = ope_combat_calc_en($stats);
    $pa = ope_combat_calc_pa($stats, $nivel);
    // guardar pv_max, en_max, pa_por_turno, nivel
}
```

### 0.2.7 — `ope_pp_stat_cost_table()` (líneas 310-323)
**Eliminar**. Ya no hay costes por letra. Se usa `ope_rol_stat_upgrade_cost()`.

---

## Tarea 0.3 — Reescribir `crear-personaje.php`

**Cambios necesarios en el wizard:**

### Paso 2 (Raza):
- Al seleccionar raza, guardar `multiplicadores` en vez de `mod`
- La previsualización de stats muestra % en vez de +/- números

### Paso 4 (Stats) — **El cambio más grande**:
- **Versión actual**: 3 pasos de bumps (+1 a una o dos stats). Máximo M+.
- **Versión nueva**: 
  1. Mostrar las 12 stats con valor base 5
  2. Mostrar contador de PS disponibles (30 o 40 para humanos)
  3. Inputs para que el jugador asigne puntos (sliders o number inputs)
  4. Validación: suma de incrementos = PS disponibles
  5. Previsualización de stats efectivas (con pasivas raciales aplicadas)
  6. Mostrar Nivel resultante = suma_stats / 10
  7. Validación: ningún stat individual > 20 (cap de creación)

### Paso 5 (Virtudes/Defectos):
**Sin cambios mayores**. El sistema de 6 PC sigue igual.

### Paso 6 (Pack inicial):
**Sin cambios**.

### Al guardar:
- `stats_json`: los 12 valores base (antes de pasivas)
- `stats_efectivas`: los 12 valores tras aplicar pasivas raciales
- `nivel`: calculado automáticamente
- `ps_gastados`: tracking de cuántos PS se usaron

---

## Tarea 0.4 — Reescribir `progresion.php`

### Cambios:
- Mostrar stats como números (ej: "FUE: 18") en vez de letras ("FUE: C")
- El botón "Subir" gasta PP según `ope_rol_stat_upgrade_cost()`
- Cada click sube +1 al valor numérico (no salta de letra)
- Mostrar coste del siguiente punto (ej: "Subir FUE de 18 a 19: cuesta 1 PP")
- Mostrar Nivel actual = suma_stats / 10
- Validar cap de creación: si es primer mes, no superar +1 rango/mes

---

## Tarea 0.5 — Implementar Sistema de Haki (NUEVO)

**Nuevo archivo**: `inc/ope_rol_haki.php`

### 0.5.1 — Estructura de datos

```php
function ope_haki_tipos() {
    return [
        'busoshoku' => ['nombre' => 'Busoshoku (Armadura)', 'desc' => 'Endurecimiento corporal. Permite dañar Logias.'],
        'kenbunshoku' => ['nombre' => 'Kenbunshoku (Observación)', 'desc' => 'Percepción extrasensorial. Anticipación.'],
        'haoshoku' => ['nombre' => 'Haoshoku (Conquistador)', 'desc' => 'Rey supremo. Derriba masas.'],
    ];
}

function ope_haki_niveles() {
    return [
        1 => ['nombre' => 'Básico', 'coste_pp' => 15, 'requiere_nivel' => 5],
        2 => ['nombre' => 'Intermedio', 'coste_pp' => 40, 'requiere_nivel' => 15],
        3 => ['nombre' => 'Avanzado', 'coste_pp' => 100, 'requiere_nivel' => 30],
        4 => ['nombre' => 'Supremo', 'coste_pp' => 250, 'requiere_nivel' => 50],
    ];
}
```

### 0.5.2 — Reglas especiales

| Tipo | Regla |
|---|---|
| **Busoshoku** | Nivel 1 = daño a Logias reducido 50%. Nivel 2 = daño completo a Logias. Nivel 3 = bypass defensa. Nivel 4 = daño interno sin contacto. |
| **Kenbunshoku** | Nivel 1 = +1 PA reactivo. Nivel 2 = anticipa intenciones. Nivel 3 = esquiva automática (1/combate). Nivel 4 = visión de futuro (segundos). |
| **Haoshoku** | Tirada d100 gratuita al alcanzar Nivel 25. Si saca ≥ 70, lo desbloquea. Luego se sube con PL (no PP). Nivel 1 = derriba civiles. Nivel 4 = grietas en el entorno. |

### 0.5.3 — Página de Haki

**Nueva página**: `haki.php` — Panel donde el jugador ve su progreso de Haki, gasta PP para subir Busoshoku/Kenbunshoku, y ve si tiene Haoshoku desbloqueado.

### 0.5.4 — Integración con creación de personaje

- El wizard indica "Sin Haki al inicio" (ya existe en `crear-personaje.php:627`)
- La virtud `V-PRO-?? Voluntad Temprana` podría dar Haki nivel 1 gratuito (a definir)

---

## Tarea 0.6 — Implementar PL (Puntos de Leyenda)

**Archivo**: Ampliar `inc/ope_rol_system.php` o nuevo `inc/ope_rol_pl.php`

### 0.6.1 — Funciones

```php
function ope_pl_saldo($pid) { /* similar a ope_pp_saldo */ }
function ope_pl_add($pid, $pl, $tipo, $notas) { /* similar a ope_pp_add */ }
function ope_pl_spend($pid, $cost, $tipo, $notas) { /* similar a ope_pp_spend */ }
```

### 0.6.2 — Obtención de PL

| Fuente | PL |
|---|---|
| Completar un Arco narrativo (staff otorga) | +5 PL |
| Logro épico validado por staff | +3 PL |
| Evento del foro | +2 PL |
| Muerte Épica/Legendaria (herencia) | Ver AV-12 en Oleada 2 |

### 0.6.3 — Tienda de PL

Desbloqueos premium:
| Desbloqueo | Coste PL |
|---|---|
| Voluntad de D. (V-LIN-01) | 5 PL |
| Fruta del Diablo específica (elegir, no aleatoria) | 3 PL |
| Linaje Especial | 5 PL |
| Arma Suprema (Saijo O Wazamono) | 10 PL |
| Subir Haoshoku un nivel | 3 PL por nivel |

---

## Tarea 0.7 — Implementar Wanted (flavor)

**Archivo**: Añadir a `inc/ope_rol_system.php`

### 0.7.1 — Tabla y funciones

```php
function ope_wanted_get($pid) { /* leer rol_wanted.bounty */ }
function ope_wanted_set($pid, $amount) { /* actualizar */ }
function ope_wanted_add($pid, $amount, $motivo) { /* sumar + log */ }
```

### 0.7.2 — Visualización

- Mostrar bounty en la ficha del personaje (`ficha.php`)
- Mostrar en el postbit (debajo del avatar/nombre)
- Lunarian: bounty inicial 100M. Bucaneer: 50M. Otros: 0.
- El bounty es puramente cosmético. No bloquea ni desbloquea mecánicas (por ahora).

---

## Orden de Ejecución

```
Día 1-2:  0.0  Migración BD (script migrate-oleada0.php)
Día 3-5:  0.1  ope_rol_data.php (stats, razas, costes)
Día 6-7:  0.2  ope_rol_system.php (fórmulas combate)
Día 8-10: 0.3  crear-personaje.php (wizard stats numéricos)
Día 11:   0.4  progresion.php (gasto PP por punto)
Día 12-14:0.5  Sistema de Haki (ope_rol_haki.php + haki.php)
Día 15:   0.6  Sistema PL (ope_rol_pl.php)
Día 16:   0.7  Wanted flavor
```

---

## Verificación de Oleada 0

Al terminar, estos flujos deben funcionar:

1. **Crear personaje nuevo**: wizard con 30/40 PS, stats 5-100+, pasivas % aplicadas, nivel calculado
2. **Subir stat con PP**: `progresion.php` muestra valores numéricos, gasta 1 PP por punto en tramo 5-20
3. **Recalculo de combate**: PV/EN/PA se calculan con nuevas fórmulas
4. **Haki**: página `haki.php` muestra niveles, permite gastar PP en Busoshoku/Kenbunshoku
5. **PL**: se pueden asignar manualmente, se muestra saldo en panel
6. **Wanted**: número cosmético visible en ficha y postbit
7. **Personajes existentes migrados**: sus stats convertidos de F-M+ a numérico, nivel recalculado
