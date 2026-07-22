# Antigravity (Gemini IDE) — One Piece: Eternal

Antigravity **no** carga automáticamente las reglas de Cursor. Usa este documento para configurar sesiones de trabajo en el mismo repo.

---

## Contexto fijo recomendado

Adjunta o fija en cada sesión de UI/RPG:

| Archivo | Por qué |
|---|---|
| `AGENTS.md` | Reglas base del proyecto |
| `docs/AGENTES-Y-HERRAMIENTAS.md` | Protocolo anti-portado parcial (crítico) |
| `docs/DESIGN-GRANBLUE-ETERNAL.md` | Fuente de verdad visual |
| `docs/Prototypes/Granblue/index.html` | Referencia portada (abrir en navegador) |

---

## Prompt de arranque (copiar al iniciar chat)

```
Proyecto One Piece: Eternal — foro MyBB + PHP en C:\Users\Fgonz\Documents\Proyectos\One Piece: Eternal

OBLIGATORIO antes de código UI:
- Leer AGENTS.md y docs/AGENTES-Y-HERRAMIENTAS.md
- Portado visual = 5 capas completas (estructura, tokens, overrides OP, fuentes, datos). NUNCA solo un componente.
- Fuente diseño: docs/DESIGN-GRANBLUE-ETERNAL.md
- Prototipo portada: docs/Prototypes/Granblue/index.html v3.2

Tras editar CSS/plantillas:
  php scripts/sync-theme.php import
  php scripts/sync-theme.php verify   (OK CSS: in sync)
  php scripts/check-inline-styles.php

Explorar código: py -m graphify query "pregunta"  (grafo en graphify-out/)

PowerShell: separar comandos con ; no &&

No estilo One Piece brutalista. Tokens OPE claros/acuarela (Relink).
```

---

## Tareas típicas

### Portar una pantalla del prototipo

```
Sigue docs/AGENTES-Y-HERRAMIENTAS.md §2.
Prototipo: docs/Prototypes/Granblue/[archivo].html
Producción: [ope-index.xml | ficha.php | ope.css | index.php]
No marcar hecho sin comparación visual lado a lado y sync-theme verify OK.
```

### Nueva página PHP

```
Sigue DESIGN-GRANBLUE-ETERNAL.md §5 y docs/GUIA-ESTILOS-PHP.md.
body.ope-pg-<slug> + scaffolding OPE en ope.css (sin bordes negros 2px OP).
```

### Solo backend / BD

Graphify recomendado; reglas de portado visual no aplican.

---

## Brain folder

Antigravity puede usar `~/.gemini/antigravity-ide/brain/`. **No** sustituye la documentación del repo. Si generas notas ahí, sincroniza decisiones importantes a `docs/` o `AGENTS.md`.

---

## Paridad con Cursor y OpenCode

Los tres entornos deben seguir el **mismo** `AGENTS.md` y `docs/AGENTES-Y-HERRAMIENTAS.md`. Si actualizas reglas en Cursor, actualiza también este archivo si cambia el prompt de arranque.

---

*Última actualización: julio 2026*
