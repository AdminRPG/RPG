# Instrucciones para agentes — I-Forge-RPG / GBF Eternal

> **Documentación extendida:** `docs/AGENTES-Y-HERRAMIENTAS.md` (portado visual, Cursor, OpenCode, Antigravity).  
> **Antigravity:** `docs/ANTIGRAVITY.md` — prompt de arranque y contexto fijo.  
> **OpenCode:** `.opencode/README.md` + este archivo.

---

## graphify

Este proyecto tiene un grafo de conocimiento en `graphify-out/`.

Cuando el usuario escribe `/graphify`, usa el skill graphify antes de cualquier otra cosa.

Reglas:
- Para preguntas sobre el código, ejecuta primero `py -m graphify query "<pregunta>"` si existe `graphify-out/graph.json`. Usa `py -m graphify path "<A>" "<B>"` para relaciones y `py -m graphify explain "<concepto>"` para conceptos.
- Los archivos sucios en `graphify-out/` tras hooks o updates incrementales son normales; no omitas graphify por eso.
- Si existe `graphify-out/wiki/index.md`, úsalo para navegar en lugar de leer fuentes a ciegas.
- Lee `graphify-out/GRAPH_REPORT.md` solo para revisión amplia de arquitectura.
- Tras modificar código: `py -m graphify update .` (AST, sin coste API).

---

## Portado visual GBF — REGLA CRÍTICA (no repetir incidente jul-2026)

**Problema:** portar solo el carrusel/hero dejó el index con tablón, navbar y categorías OP → `localhost/iforge/` no parecía el prototipo.

**Regla:** un prototipo aprobado (`docs/Prototypes/Granblue/`) es un **sistema completo**. Portar = **5 capas** (estructura, tokens, overrides legacy OP, head/fuentes, datos). **No marques done** sin checklist y comparación visual.

Checklist completo: **`docs/AGENTES-Y-HERRAMIENTAS.md` §2–§3**. Regla Cursor: `.cursor/rules/visual-port-gbe.mdc`.

### Portada — mínimo antes de cerrar tarea

- `body.gbe-index` en `gbe-index.xml`
- Hero 100vh full-bleed + gaceta bento debajo (no `gbe-top` OP)
- Overrides `body.gbe-index` para `.gbe-panel`, categorías `.gbe-section`, navbar
- `index.php` genera Skydoms/OT con watermark y títulos GBF
- Fuentes en `gbe_rol_head_base()` **y** `headerinclude`
- `sync-theme import` + `verify` → `OK CSS: in sync` + hard refresh

### Definición de “hecho” UI

Solo **Done** si: checklist §2 + verify CSS + `check-inline-styles` limpio + comparación con prototipo + `graphify update` si hubo código.

---

## Páginas PHP y CSS

Fuente de verdad: **`docs/DESIGN-GRANBLUE-ETERNAL.md` §5** y `docs/GUIA-ESTILOS-PHP.md`.

Reglas que NO se pueden saltar:
- Las clases **`.shead .plate .plate-h .plate-b .reveal .flash .pj-empty` NO son globales**: se re-declaran por scope `body.gbe-pg-<pagina>` (o `gbe-pg-*`). Sin scaffolding → texto plano.
- **Nuevas páginas GBF:** scaffolding con tokens claros (`--line`, `border-radius`) — ver §5.4 DESIGN, **no** copiar brutalismo OP (`border:2px solid #000`) del scaffolding antiguo.
- Prohibido `<style>` y `style="..."` estáticos; solo `style` dinámico de PHP.
- El navegador sirve `cache/themes/theme13/gbe.css`. Tras editar CSS: `php scripts/sync-theme.php import` y `verify`.
- Antes de terminar: `check-inline-styles` limpio + `OK CSS: in sync` + comparación visual en navegador.

### Portada MyBB

No usa `body.gbe-pg-*`. Usa **`body.gbe-index`**. Ver DESIGN §6 y §6.7 (estado F2b / brecha vs prototipo).

**Botones:** rectangulares `border-radius: 8px` — prohibido pill (DESIGN §4.4).

---

## Documentación obligatoria por tarea

| Tarea | Leer primero |
|---|---|
| UI / tema / portada | `DESIGN-GRANBLUE-ETERNAL.md`, `AGENTES-Y-HERRAMIENTAS.md` §2 |
| Página PHP nueva | DESIGN §5, `GUIA-ESTILOS-PHP.md`, `.cursor/rules/page-scaffold.mdc` |
| Producto / copy | `docs/PRODUCT.md` |
| Fases / roadmap | `docs/PLAN-MAESTRO-GRANBLUE-ETERNAL.md` |
| Tema MyBB / sync | `docs/themes/README.md` |
| Purga ope→gbe | `docs/MIGRACION-GRANBLUE-TECNICA.md` |

---

## Herramientas de desarrollo

| Entorno | Qué lee el agente |
|---|---|
| **Cursor** | `AGENTS.md`, `.cursor/rules/*.mdc`, skills `.agents/skills/` |
| **OpenCode** | `AGENTS.md`, `.opencode/README.md`, plugin `graphify.js` |
| **Antigravity** | Contexto manual — usar `docs/ANTIGRAVITY.md` prompt de arranque |

**PowerShell (Windows):** separar comandos con `;`, no `&&`.

---

## Producto (resumen)

**Granblue Fantasy: Eternal** — foro rol PBP, cielo/Skydoms, look Relink (claro/acuarela). Detalle: `docs/PRODUCT.md`. Anti-referencia: neobrutalismo OP, plantilla MyBB genérica.
