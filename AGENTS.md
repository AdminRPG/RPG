# Instrucciones para agentes — One Piece: Eternal

> **Documentación de sistemas (reglas de juego):** repo hermano `Eternal-Sistema/docs/` (Haki, Frutas, Eternal, Combate…).  
> **Documentación de este foro (código MyBB):** `docs/` aquí.  
> Plan de implementación: `Eternal-Sistema/docs/10-AUTOMATISMOS/PLAN-IMPLEMENTACION-MYBB.md`.

---

## Referencias de Lore y Voz de Marca (repo hermano)

> **Nota importante:** Estas referencias viven en un repositorio separado (`Eternal-Sistema`). Si el workspace actual abierto es solo `Eternal-RPG`, estos archivos NO son accesibles directamente al agente salvo que se abran o se referencien manualmente — consúltense siempre que se trabaje contenido de lore o copy narrativo desde este repo.

| Documento | Ruta Absoluta | Cuándo Consultarlo |
|---|---|---|
| Dirección de Lore y Sistemas | `c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Sistema/docs/DIRECCION-LORE-Y-SISTEMAS.md` | Antes de escribir cualquier copy narrativo o definir tono |
| Facciones | `c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Sistema/docs/03-MUNDO/FACCIONES.md` | Al mencionar Marina, Gobierno Mundial, Emperadores, Revolucionarios |
| Islas | `c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Sistema/docs/03-MUNDO/ISLAS.md` | Al crear o describir islas/lugares |
| Cronología y NPCs | `c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Sistema/docs/03-MUNDO/CRONOLOGIA-Y-NPCS.md` | Al introducir NPCs o referencias temporales |
| Sistema de NPCs | `c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Sistema/docs/03-MUNDO/SISTEMA-NPCS.md` | Al definir mecánicas o stats de NPCs |

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

## Marca y codename

- **Producto:** One Piece: Eternal
- **Prefijo de código:** `ope_` (funciones PHP), `ope-` (CSS / plantillas)
- **Plugin:** `inc/plugins/ope_rol.php` (codename `ope_rol`)
- **Backend:** `inc/ope_rol/` (capas: `core/`, `catalogos/`, `dominio/`, `sistemas/`, `mundo/`, `tramites/`) — entrada `inc/ope_rol/bootstrap.php`. Stubs en `inc/ope_rol_*.php` para compatibilidad.
- **CSS canónico:** `docs/themes/ope.css` → sync a MyBB con `php scripts/sync-theme.php import`
- **Prohibido** reintroducir `gbe_`, `gbe-`, Granblue, GBF, I-Forge o iforge en código nuevo

---

## Páginas PHP y CSS

Fuente de verdad visual: **`docs/DESIGN-ONE-PIECE-ETERNAL.md`** (si existe) y `docs/GUIA-ESTILOS-PHP.md`.

Reglas que NO se pueden saltar:
- Las clases **`.shead .plate .plate-h .plate-b .reveal .flash .pj-empty` NO son globales**: se re-declaran por scope `body.ope-pg-<pagina>` (o `ope-pg-*`). Sin scaffolding → texto plano.
- **Nuevas páginas:** scaffolding con tokens claros (`--line`, `border-radius`) — no brutalismo legacy.
- Prohibido `<style>` y `style="..."` estáticos; solo `style` dinámico de PHP.
- El navegador sirve `cache/themes/theme13/ope.css` (tras sync). Tras editar CSS: `php scripts/sync-theme.php import` y `verify`.
- Antes de terminar: `check-inline-styles` limpio + `OK CSS: in sync` + comparación visual en navegador.

### Portada MyBB

No usa `body.ope-pg-*`. Usa **`body.ope-index`**.

**Botones:** rectangulares `border-radius: 8px` — prohibido pill.

---

## Documentación obligatoria por tarea

| Tarea | Leer primero |
|---|---|
| Sistemas / lore (reglas) | `Eternal-Sistema/docs/DIRECCION-LORE-Y-SISTEMAS.md` |
| UI / tema / portada | `docs/DESIGN-ONE-PIECE-ETERNAL.md`, `docs/themes/README.md` |
| Página PHP nueva | DESIGN + `docs/GUIA-ESTILOS-PHP.md` + `.cursor/rules/page-scaffold.mdc` |
| Producto / copy | `docs/PRODUCT.md` |
| Roadmap de implementación | `Eternal-Sistema/docs/10-AUTOMATISMOS/PLAN-IMPLEMENTACION-MYBB.md` |
| Tema MyBB / sync | `docs/themes/README.md` |

---

## Herramientas de desarrollo

| Entorno | Qué lee el agente |
|---|---|
| **Cursor** | `AGENTS.md`, `.cursor/rules/*.mdc`, skills `.agents/skills/` |
| **OpenCode** | `AGENTS.md` (convención automática), `opencode.json` (MCPs: playwright, firecrawl; plugin: superpowers), skills `.agents/skills/` vía `skills.paths` |

**PowerShell (Windows):** separar comandos con `;`, no `&&`.

---

## Producto (resumen)

**One Piece: Eternal** — foro rol PBP en el universo One Piece (mares, islas, Haki, Akuma no Mi, Clases Bélicas + Oficios v4). Motor MyBB + plugin `ope_rol`. Detalle: `docs/PRODUCT.md` y el repo hermano de sistemas.
