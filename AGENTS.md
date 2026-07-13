## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `py -m graphify query "<question>"` when graphify-out/graph.json exists. Use `py -m graphify path "<A>" "<B>"` for relationships and `py -m graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `py -m graphify update .` to keep the graph current (AST-only, no API cost).

## Páginas PHP y CSS (crear/editar cualquier `.php` del RPG)

Fuente de verdad: **`docs/DESIGN-ONE-PIECE-ETERNAL.md` §7.8**. Léela antes de crear o rediseñar una página.

Reglas que NO se pueden saltar:
- Las clases base **`.shead .plate .plate-h .plate-b .reveal .flash .pj-empty` NO son globales**: se re-declaran por cada scope `body.ope-pg-<pagina>`. Si estrenas un `<body class="ope-pg-nueva">` y usas esas clases sin pegar su scaffolding scopeado (bloque en §7.8), la página sale sin estilo (texto plano). Globales: `.wrap .breadcrumb .btn* .ope-prog-*`.
- Prohibido `<style>` y `style="..."` estáticos; solo `style` con valor dinámico de PHP.
- El navegador sirve `cache/themes/theme13/ope.css`, no el fuente. Tras editar CSS: `php scripts/sync-theme.php import` y `verify`.
- Antes de terminar: `php scripts/check-inline-styles.php` limpio + `verify` en `OK CSS: in sync` + comprobación visual en navegador contra una página hermana.
