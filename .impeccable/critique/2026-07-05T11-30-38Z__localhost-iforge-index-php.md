---
target: "http://localhost/iforge/index.php"
total_score: 18
p0_count: 1
p1_count: 3
timestamp: 2026-07-05T11-30-38Z
slug: localhost-iforge-index-php
---
# Critique: I-Forge Homepage (index.php)

**Method: dual-agent (A: design-review · B: detector)**

## Design Health Score

| # | Heurística | Score | Issue clave |
|---|-----------|-------|-------------|
| 1 | Visibility of System Status | 2/4 | Últimos Mensajes vacío sin feedback |
| 2 | Match System / Real World | 3/4 | "My Category" rompe la inmersión |
| 3 | User Control and Freedom | 2/4 | Sin breadcrumbs ni back-to-top |
| 4 | Consistency and Standards | 2/4 | Icono "search" para "Ver más" y "Archivo" |
| 5 | Error Prevention | 1/4 | Empty state no manejado |
| 6 | Recognition Rather Than Recall | 3/4 | "Ver más" ambiguo en múltiples cards |
| 7 | Flexibility and Efficiency | 1/4 | Sin atajos ni paths alternativos |
| 8 | Aesthetic and Minimalist Design | 3/4 | Iconos redundantes en news items |
| 9 | Error Recovery | 0/4 | Ningún error state manejado |
| 10 | Help and Documentation | 1/4 | Sin onboarding ni tooltips |
| **Total** | | **18/40** | **Poor — necesita overhaul** |

## Anti-Patterns Verdict

**AI slop**: Riesgo moderado. La paleta dark+gold es uno de los 3 clusters default de IA, pero los detalles de worldbuilding (calendario, curiosidades, sello) son elecciones específicas. Lo que más delata "esto lo hizo una IA" son los placeholders estáticos y "My Category".

**Detector**: Encontró 2 issues reales:
- `#ffffff` en `.iforge-banner-title` (prohibido por DESIGN.md)
- `border-radius: 4px` en `.iforge-staff-mp` (fuera de la escala `sm=6px`)

## Overall Impression

Buenos cimientos visuales — paleta cohesiva, identidad clara, worldbuilding presente — pero la ejecución está a medio camino. El esqueleto de diseño está bien, pero los vacíos (empty states, placeholders, categorías default) hacen que se sienta inacabado.

## What's Working
1. **Identidad oscura consistente**: paleta #0d1117/#161b22 con acento #e2b714 aplicada coherentemente
2. **Worldbuilding**: calendario in-game, curiosidades, banner — crean inmersión
3. **Jerarquía visual limpia**: hero → calendas → tablón → categorías → footer, flujo lógico

## Priority Issues

### [P0] Últimos Mensajes vacío sin fallback
La primera card que ve el usuario está vacía. El foro parece muerto. Fix: añadir empty state.

### [P1] "My Category" default
Nombre de categoría MyBB sin renombrar. Fix: renombrar a algo in-world.

### [P1] Placeholders estáticos
Búsquedas Activas y Noticias con HTML hardcodeado. Fix: reemplazar con queries DB.

### [P1] Sello de cazador ausente
Las clases CSS existen pero el HTML no las incluye. Fix: añadir sellos al banner y categorías.

### [P2] Sin CTA de registro
Fix: añadir "Registrarse" visible para visitantes.

### [P2] Color #ffffff en banner-title
Fix: reemplazar con var(--color-text).

## Persona Red Flags

**Nuevo jugador**: Sin CTA de registro, card vacía sugiere foro muerto, "My Category" proyecta inacabado.

**Roleplayer activo**: Búsquedas estáticas nunca cambian, sin indicador de no-leídos.

**Admin/GM**: Staff usa icono genérico, sin dashboard de moderación visible.

## Minor Observations
- CSS activo es theme7/iforge.css, no theme4
- Barra de calendario tiene onclick sin cursor:pointer
- "[MP]" como texto plano en staff
- Fechas mezclan "Día 60" (in-game) y "05/07/2026" (real)

## Questions to Consider
- ¿El sello fantasma se omitió a propósito?
- ¿Las Búsquedas Activas y Noticias son placeholder temporal?
- ¿Por qué el CSS activo es theme7 y no theme4?
