# Agentes y herramientas — I-Forge-RPG / GBF Eternal

> **Léelo antes de portar UI, crear páginas PHP o tocar el tema MyBB.**  
> Aplica igual en **Cursor**, **OpenCode** y **Antigravity**.

---

## 1. Incidente que NO debe repetirse (julio 2026)

**Síntoma:** `localhost/iforge/` no se veía como `docs/Prototypes/Granblue/index.html` pese a “haber portado el index”.

**Causa:** portado **parcial** — solo el carrusel GBF; el resto seguía con clases/reglas OP (tablón brutalista, navbar oscura, categorías `.ope-block-cat`, fuentes Archivo/Big Shoulders).

**Regla de oro:**

> **Un prototipo aprobado es un sistema visual completo (árbol HTML + tokens + overrides + fuentes + scope `body`).**  
> **Copiar un solo componente NO cuenta como portado.**  
> **No marques la tarea como hecha hasta pasar el checklist de §2 y la comparación visual de §3.**

---

## 2. Checklist obligatorio — portado visual (cualquier pantalla)

### 2.1 Antes de escribir código

- [ ] Leer el **árbol de secciones** del prototipo (`DESIGN-GRANBLUE-ETERNAL.md` §6 para portada, §8 para ficha).
- [ ] Abrir el HTML prototipo y listar **todas** las capas (no solo la hero).
- [ ] Identificar qué archivos de producción toca cada capa (tabla §2.3).
- [ ] `py -m graphify query "..."` si no conoces dependencias entre archivos.

### 2.2 Durante el port (las 5 capas)

| Capa | Qué incluye | Olvidar esto = UI “a medias” |
|---|---|---|
| **A — Estructura** | HTML/XML/PHP: secciones, wrappers, `body` scope, grid areas | Hero suelto sin gaceta/skydoms |
| **B — Tokens** | `:root`, `--gbe-*` / mapeo `--iron`, `--paper`… | Fondo claro pero paneles OP negros |
| **C — Overrides legacy** | Reglas bajo `body.gbe-index` o `body.ope-pg-*` que **anulan** `.ope-panel`, `.ope-shead`, `#ope-navbar`… | Clases OP heredadas dominan |
| **D — Head / fuentes** | `ope_rol_head_base()` **y** `headerinclude` del tema | Solo el tema tiene Cinzel; PHP no |
| **E — Datos / copy** | Lore, títulos de sección, slugs Skydom en `index.php` | Estructura GBF con textos OP |

### 2.3 Mapa archivo ↔ capa (portada)

| Capa prototipo | Archivos producción |
|---|---|
| Hero 100vh + CTAs | `docs/themes/ope-index.xml`, `ope.css` → `body.gbe-index .gbe-hero*` |
| Gaceta bento | `ope-index.xml`, `ope.css` → `.gbe-bento` + overrides `.ope-panel` |
| Skydoms | `index.php` (HTML categorías), `ope.css` → `.gbe-world-bento` |
| Off Topic | `index.php`, `ope.css` → `.gbe-slab` |
| El Puerto | `ope-index.xml`, `ope.css` → `.gbe-harbor` |
| Navbar | `inc/plugins/ope_rol.php` + `ope.css` scope portada (luego global) |
| Fuentes | `ope_rol.php` + `ope-index.xml` `headerinclude` |

### 2.4 Después de editar (siempre)

```bash
php scripts/sync-theme.php import
php scripts/sync-theme.php verify          # debe decir: OK CSS: in sync
php scripts/check-inline-styles.php      # limpio
py -m graphify update .
```

- [ ] **Hard refresh** en navegador (`Ctrl+Shift+R`) — el navegador sirve `cache/themes/theme13/ope.css`, no `docs/themes/ope.css`.
- [ ] Comparación visual lado a lado con prototipo (§3).

### 2.5 Checklist mínimo — portada (`body.gbe-index`)

- [ ] `body class="gbe-index"` en plantilla
- [ ] Hero `min-height: 100vh`, full-bleed, sin `border-radius` en wrap
- [ ] Gaceta en **sección aparte** bajo hero (no al lado en `ope-top`)
- [ ] Grid bento: `"lore feed onrol" / "lore news staff"`
- [ ] Overrides de **todos** los `.ope-panel` en scope portada
- [ ] Categorías en `.gbe-section` + `.gbe-wm` + `.gbe-stitle` (no `.ope-block-cat`)
- [ ] Harbor / censo con tokens claros
- [ ] Navbar 66px, crest, sin bordes negros 2px (en portada como mínimo)
- [ ] Fuentes Cinzel/Cormorant/Spectral en plugin **y** tema

### 2.6 Checklist mínimo — página PHP nueva

- [ ] `DESIGN-GRANBLUE-ETERNAL.md` §5 leído
- [ ] `body.ope-pg-<slug>` (o `gbe-pg-*` post-F1)
- [ ] Scaffolding scopeado pegado en `ope.css` — **tokens GBF** (`--line`, `border-radius`), no brutalismo OP (`border:2px solid #000`)
- [ ] Sin `<style>` ni `style=""` estáticos
- [ ] Verificación contra página hermana ya correcta (`ficha.php` como referencia GBF)

---

## 3. Comparación visual obligatoria

Antes de cerrar cualquier tarea de UI:

1. Abrir prototipo: `docs/Prototypes/Granblue/index.html` (o `ficha.html`).
2. Abrir producción: `http://localhost/iforge/index.php` (o la PHP correspondiente).
3. Revisar en orden de scroll: **navbar → hero → gaceta → skydoms → off topic → puerto → footer**.
4. Anotar drift (tipografía, sombras, bordes, espaciado, colores).
5. Si hay drift en una sección, **no** declarar “portado”; corregir overrides en la capa C.

**Anti-patrón explícito:** “El carrusel ya se ve bien” ≠ portada terminada.

---

## 4. Documentación por rol

| Documento | Cuándo leerlo |
|---|---|
| `docs/DESIGN-GRANBLUE-ETERNAL.md` | Antes de cualquier UI — fuente de verdad visual |
| `docs/PRODUCT.md` | Tono, usuarios, anti-referencias (no neobrutalismo OP) |
| `docs/PLAN-MAESTRO-GRANBLUE-ETERNAL.md` | Fases, estado F2b, prioridades |
| `docs/GUIA-ESTILOS-PHP.md` | How-to páginas PHP nuevas |
| `docs/themes/README.md` | sync-theme, archivos canónicos, `body.gbe-index` |
| `docs/MIGRACION-GRANBLUE-TECNICA.md` | Purga `ope`→`gbe` (F1, no a medias) |
| `AGENTS.md` (raíz) | Resumen que leen los agentes en cada sesión |

---

## 5. Cursor

| Recurso | Uso |
|---|---|
| `AGENTS.md` | Reglas siempre cargadas en el agente |
| `.cursor/rules/graphify.mdc` | Graphify obligatorio antes de explorar |
| `.cursor/rules/page-scaffold.mdc` | Scaffold PHP + CSS por scope |
| `.cursor/rules/visual-port-gbe.mdc` | Portado visual — checklist y anti-patrones |
| `.cursor/rules/mybb-theme-dev.mdc` | Plantillas MyBB |
| Skills `.agents/skills/` | graphify, impeccable, mybb-theme-dev, frontend-design |

**Al delegar a subagentes:** incluir en el prompt las reglas de §2 y graphify.

---

## 6. OpenCode

| Recurso | Uso |
|---|---|
| `AGENTS.md` (raíz) | OpenCode lee instrucciones del proyecto desde la raíz — **mismo contenido que Cursor** |
| `docs/AGENTES-Y-HERRAMIENTAS.md` | Este archivo — detalle del protocolo de portado |
| `.opencode/plugins/graphify.js` | Plugin: recuerda usar graphify en el primer `bash` |
| `opencode.json` (local, gitignored) | Config personal; no commitear claves |

### Comandos habituales en OpenCode (PowerShell)

```powershell
cd C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG
py -m graphify query "portada index tema css"
php scripts/sync-theme.php import
php scripts/sync-theme.php verify
php scripts/check-inline-styles.php
```

**PowerShell:** usar `;` entre comandos, no `&&` (Windows 5.1).

### Qué decirle a OpenCode al portar UI

Copia/pega o adapta:

```
Portar [pantalla] siguiendo docs/AGENTES-Y-HERRAMIENTAS.md §2.
Prototipo: docs/Prototypes/Granblue/[index|ficha].html
NO cerrar hasta: 5 capas (estructura, tokens, overrides legacy, head/fuentes, datos),
sync-theme verify OK, comparación visual con prototipo.
Leer DESIGN-GRANBLUE-ETERNAL.md §6 o §8 según corresponda.
```

---

## 7. Antigravity (Gemini IDE)

Antigravity no comparte automáticamente las reglas de Cursor. **Debes inyectar contexto manualmente** al iniciar sesión o en “brain”/instrucciones del proyecto.

### Archivos a adjuntar o fijar como contexto

1. `AGENTS.md`
2. `docs/AGENTES-Y-HERRAMIENTAS.md`
3. `docs/DESIGN-GRANBLUE-ETERNAL.md` (al menos §5, §6, §6.7)
4. El prototipo HTML relevante

### Prompt de arranque recomendado (Antigravity)

```
Proyecto: Granblue Fantasy: Eternal (foro MyBB + PHP RPG).
Reglas obligatorias en AGENTS.md y docs/AGENTES-Y-HERRAMIENTAS.md.
Antes de tocar UI: leer DESIGN-GRANBLUE-ETERNAL.md.
Portado visual = sistema completo (5 capas), nunca solo un componente.
Tras CSS: php scripts/sync-theme.php import; verify; check-inline-styles.
Explorar código con: py -m graphify query "..."
No usar estilo One Piece brutalista (bordes negros 2px, Big Shoulders).
Referencia visual: docs/Prototypes/Granblue/index.html v3.2
```

### Antigravity + graphify

Misma regla que Cursor: `py -m graphify query` antes de grep masivo. El grafo está en `graphify-out/`.

### Nota sobre rutas brain

Antigravity puede escribir en `~/.gemini/antigravity-ide/brain/`. **No** uses esa carpeta como fuente de verdad del proyecto; los docs canónicos viven en `docs/` y `AGENTS.md`.

---

## 8. Definición de “hecho” para tareas UI

Una tarea de portado/rediseño solo está **Done** si:

1. Checklist §2 completo para esa pantalla.
2. `OK CSS: in sync` + `check-inline-styles` limpio.
3. Comparación visual §3 sin drift bloqueante.
4. `DESIGN-GRANBLUE-ETERNAL.md` actualizado si cambió estructura o §6.7 estado.
5. `py -m graphify update .` tras cambios en código.

**Prohibido marcar done** con mensajes del tipo: “porté el hero, el resto es follow-up”.

---

## 9. Referencia rápida — archivos que siempre van juntos

```
docs/Prototypes/Granblue/index.html     ← verdad visual portada
docs/themes/ope-index.xml               ← plantilla MyBB portada
docs/themes/ope.css                     ← fuente CSS (scope body.gbe-index)
index.php                               ← HTML categorías Skydom/OT
inc/plugins/ope_rol.php                 ← navbar, head_base, fuentes
cache/themes/theme13/ope.css            ← lo que sirve el navegador (post-import)
```

## 10. Prompt listo para OpenCode (copiar/pegar)

```
Proyecto: Granblue Fantasy: Eternal — C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG

OBLIGATORIO: AGENTS.md + docs/AGENTES-Y-HERRAMIENTAS.md §2 + DESIGN-GRANBLUE-ETERNAL.md

Reglas visuales cerradas:
- Portado = 5 capas completas (nunca solo un componente)
- Botones RECTANGULARES border-radius 8px — PROHIBIDO pill/cápsula (DESIGN §4.4)
- Tokens GBF claros/acuarela; overrides bajo body.gbe-index o body.ope-pg-*
- Tras CSS: php scripts/sync-theme.php import; verify; check-inline-styles
- PowerShell: usar ; no &&

Tarea actual — F2b cierre portada:
1. Comparar localhost/iforge/index.php vs docs/Prototypes/Granblue/index.html (gaceta bento)
2. ~~Navbar GBF global~~ ✅ — `--gbe-nav-h` / `--gbe-wrap` en ope.css
3. ~~Breadcrumb GBF global~~ ✅ — `#ope-breadcrumb` + `.breadcrumb`
4. Tema cielo/noche: html[data-theme] sincronizado con selector navbar
5. forumdisplay + showthread: resto de pantallas foro con tokens GBF (headers, listas, posts)
6. Páginas PHP: scaffolding GBF en body.ope-pg-* (sustituir brutalismo OP)
7. Reseed copy OP en feed (Fuchsia Village, OP-Eternal en posts) — contenido BD

Archivos clave: ope-index.xml, ope.css, index.php, inc/plugins/ope_rol.php
Prototipo: docs/Prototypes/Granblue/index.html
```

---

*Última actualización: julio 2026 — post-incidente portada F2b.*
