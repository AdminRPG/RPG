# OpenCode — One Piece: Eternal

## Instrucciones del agente

Lee **en este orden** al trabajar en UI o PHP del RPG:

1. `AGENTS.md` (raíz del repo)
2. `docs/AGENTES-Y-HERRAMIENTAS.md` — protocolo anti-portado parcial
3. `docs/DESIGN-GRANBLUE-ETERNAL.md` §5 (PHP) o §6 (portada)

## Plugin graphify

`.opencode/plugins/graphify.js` recuerda usar el grafo en el primer comando bash.

```powershell
py -m graphify query "tu pregunta"
```

## Tema MyBB

Tras editar `docs/themes/ope.css` o `ope-index.xml`:

```powershell
php scripts/sync-theme.php import; php scripts/sync-theme.php verify
```

PowerShell: usar `;` no `&&`.

## Portado visual

**No** portes solo el carrusel. Checklist completo en `docs/AGENTES-Y-HERRAMIENTAS.md` §2.

Prototipo portada: `docs/Prototypes/Granblue/index.html`
