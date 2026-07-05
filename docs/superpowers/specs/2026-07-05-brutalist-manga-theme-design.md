# Design Spec: I-Forge RPG — Brutalist Manga Theme

## Overview
Rediseño completo del tema I-Forge RPG para MyBB 1.8, migrando de un estilo dark/transparente/glassmorphism a un estilo **brutalista manga/panel** sólido y opaco, inspirado en la estética de Hunter x Hunter.

## Palette

| Role | Color | Hex |
|---|---|---|
| Background (page) | Pergamino claro | `#f4f0e6` |
| Panel base | Verde Hunter oscuro | `#2d5a27` |
| Panel hover | Verde más claro | `#3d7a35` |
| Accent gold | Oro envejecido | `#c9a84c` |
| Accent gold hover | Oro brillante | `#e2c96b` |
| Text primary | Tinta negra | `#1a1a1a` |
| Text secondary | Verde grisáceo | `#5a5a4a` |
| Border panels | Verde bosque intenso | `#1e3d1a` |
| Hard shadow | Sombra sólida | `#1e3d1a` (usada en offset) |
| Panel light (cards) | Pergamino ligeramente oscurecido | `#ebe6d6` |

## Visual Rules (Hard Constraints)

1. **No transparency** — All backgrounds use solid hex colors only. No `rgba()`, no `opacity` on containers.
2. **No glassmorphism** — No `backdrop-filter`, no `blur`, no frosted effects.
3. **Hard shadows only** — `box-shadow: 4px 4px 0 #1e3d1a` (offset fixed, zero blur radius).
4. **Thick borders** — All panels use `3px solid #1e3d1a`.
5. **Minimal rounding** — `border-radius: 4px` maximum (or `0px` for a sharper feel).
6. **Panel stacking** — Components should look like stacked manga panels with visible gutters and borders.
7. **Torn paper effect** — Optional separators between major sections using jagged `clip-path` or SVG.

## Typography

- **Display/brush titles**: `font-family: 'Permanent Marker', 'Comic Sans MS', cursive;` (or a loaded webfont). Uppercase with wide letter-spacing.
- **Body text**: `font-family: Georgia, 'Palatino Linotype', 'Times New Roman', serif;` for a parchment/classic feel.
- **Nav/links**: `font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;` semibold uppercase.

## Layout Structure (index template)

1. **Navbar** (`#iforge-navbar`) — Solid `#2d5a27` background, gold links, thick bottom border.
2. **Banner** (`#iforge-banner`) — Full-width panel with background image, 3px border, hard shadow, brush title overlay.
3. **Calendar Bar** (`#iforge-calendar-bar`) — Compact panel with icon and date text.
4. **Tablón (Panel Grid)** — 2-column grid:
   - Left: Últimos mensajes (tall panel)
   - Right top: Búsquedas + Noticias (stacked panels)
   - Right bottom: Staff (horizontal panel)
5. **Categories** (`iforge-category-card`) — Each category is a solid panel with border + hard shadow + hover lift.
6. **Footer** (`#iforge-footer`) — Minimal solid panel.

## MyBB Overrides

- Hide default `#header`, `#logo`, `#panel` via `display: none !important`.
- Force `#container`, `#content`, `.tborder`, `.trow1`, `.trow2` to transparent background so our panels show through.
- Override MyBB default link colors to use the new palette.

## Assets

- Background pattern: subtle topographic lines or very low-opacity Hunter X watermark on `#f4f0e6`.
- Icons: keep current SVG icons but recolor to match palette via CSS `filter` or `currentColor`.
- No transparency on any panel.

## Responsive

- Below `768px`: stack all panels into single column, reduce padding, shrink banner height.

## Acceptance Criteria

- [ ] No `rgba()` or `opacity < 1` on panel backgrounds.
- [ ] No `backdrop-filter` or `blur` properties.
- [ ] All panels have 3px solid border and hard offset shadow.
- [ ] CSS loads correctly via `css.php?stylesheet=29` on `index.php`.
- [ ] Default MyBB header is hidden.
- [ ] Visual look matches "panel manga" aesthetic with green-gold on parchment.
