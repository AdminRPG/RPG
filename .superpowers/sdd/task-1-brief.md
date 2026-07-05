# Task 1: Define Forum Identity + Create Child Theme

**Goal:** Fill the identity document with the forum's name, palette, and typography.

**Project context:** This is a MyBB forum themed after Hunter x Hunter (world and terminology, but original lore and systems). The goal is to replace ALL of MyBB's UI with a custom dark design. This is the first task — establishing the identity before any other UI work.

## Steps

### Step 1: Update identidad-visual-front.md

Edit `docs/frontend/identidad-visual-front.md` and fill the placeholder values in section 1 (tabla de identidad) with:

| Campo | Valor |
|---|---|
| Nombre del foro | **I-Forge** |
| Ambientación / universo | **Hunter x Hunter (mundo propio, lore original)** |
| Una frase que resuma el tono | **"Un mundo de Cazadores"** |
| Público objetivo | **Comunidad hispana de rol, 18+** |
| 3 adjetivos que NUNCA debe transmitir | **"infantil", "corporativo", "genérico"** |
| 3 adjetivos que SÍ debe transmitir | **"oscuro", "artesanal", "misterioso"** |

### Step 2: Fill palette (section 2)

Replace placeholder hex values with these:

| Rol | Hex | Uso |
|---|---|---|
| Fondo base | `#0d1117` | Fondo general del foro |
| Fondo elevado | `#161b22` | Navbar, tarjetas, paneles |
| Borde | `#30363d` | Bordes de componentes |
| Acento primario | `#e2b714` | Enlaces, hover, elementos interactivos (oro) |
| Texto principal | `#f0f6fc` | Texto body |
| Texto muted | `#8b949e` | Metadatos, fechas |
| Éxito | `#3fb950` | Aprobado, positivo |
| Peligro | `#f85149` | Rechazado, alerta |
| Rango T1 | `#58a6ff` | Badge de rango T1 |
| Rango T2 | `#a371f7` | Badge de rango T2 |
| Rango T3 | `#f0883e` | Badge de rango T3 |

### Step 3: Fill typography (section 3)

| Rol | Familia | Uso |
|---|---|---|
| Display / títulos | Georgia, serif | Nombre foro, títulos categoría |
| Cuerpo | -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif | Texto general |
| Datos / stats | Menlo, Consolas, monospace | Estadísticas, cantidades |

### Step 4: Fill layout (section 4)

Fill the layout template for ficha with a placeholder note "Pendiente de definir en fase de personajes".

### Step 5: Fill prohibido section

Add: "no usar glassmorphism, no usar azules brillantes tipo bootstrap, no usar fondos blancos"

### Step 6: Commit

```bash
git add docs/frontend/identidad-visual-front.md
git commit -m "feat(identity): define I-Forge name, palette and typography"
```

### Note about other steps

Steps 2-4 in the plan (create child theme via ACP, set as default, export XML) require Admin CP interaction that you cannot do. Leave those for the human. Only update `identidad-visual-front.md` and commit.

## Report

Write to `.superpowers/sdd/task-1-report.md`:
- status: DONE or NEEDS_CONTEXT
- commits made
- summary of what was done
