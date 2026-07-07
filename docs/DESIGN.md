---
version: alpha
name: I-Forge — Archivo del Rol
colors:
  bg: "#f4f0e6"
  bg-dark: "#ebe6d6"
  panel: "#2d5a27"
  panel-hover: "#3d7a35"
  gold: "#c9a84c"
  gold-hover: "#e2c96b"
  ink: "#1a1a1a"
  ink-secondary: "#5a5a4a"
  border: "#1e3d1a"
  shadow: "3px 3px 0 #1e3d1a"
  shadow-hover: "4px 4px 0 #1e3d1a"
typography:
  display:
    fontFamily: "Permanent Marker"
    fontWeight: 400
    letterSpacing: "varies (1-5px)"
  body:
    fontFamily: "Georgia, 'Palatino Linotype', serif"
    fontWeight: 400
  ui:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif"
    fontWeight: "400-800"
  data:
    fontFamily: "Menlo, Consolas, Monaco, monospace"
    fontWeight: "400-700"
rounded: "3px"
borders: "3px solid #1e3d1a"
shadow: "3px 3px 0 #1e3d1a"
maxWidth: "1200px"
spacing: "8px base (4-48px range)"
---

# DESIGN.md — I-Forge: Archivo del Rol

## Overview

I-Forge es un foro de rol play-by-post de ambientación oscura con lore original. El diseño es **neobrutalista**: páginas color pergamino (#f4f0e6), paneles verde oscuro (#2d5a27) con bordes negros gruesos (3px) y sombras sólidas sin blur, tipografía brush (Permanent Marker) para títulos que evocan manuscritos antiguos, y acentos en tinta dorada (#c9a84c) como hilo conductor visual.

Tres ideas fuerzan el carácter visual:
1. **Pergamino antiguo** — fondo beige con líneas de cuaderno (repeating-linear-gradient sutil), como un archivo de notas de campo
2. **Brutalismo controlado** — bordes gruesos (3px), sombras sólidas sin difuminar, sin border-radius grandes (todo 3px). Cada elemento tiene peso visual y ocupa su espacio sin ambigüedad
3. **Tinta dorada** — el acento oro (#c9a84c) aparece en títulos, enlaces, hover y badges, pero nunca inunda. Es la tinta con la que se firma un archivo

## Colors

Paleta reducida con alto contraste y personalidad:

- **bg (#f4f0e6):** fondo general. Pergamino envejecido.
- **bg-dark (#ebe6d6):** fondos de tarjetas y paneles interiores. Pergamino más oscuro.
- **panel (#2d5a27):** barras de navegación, headers de sección, botones primarios. Verde bosque profundo.
- **gold (#c9a84c):** títulos, enlaces, hover, badges. Tinta dorada.
- **ink (#1a1a1a):** texto principal. Negro tinta.
- **ink-secondary (#5a5a4a):** metadatos, fechas, texto secundario. Marrón apagado.
- **border (#1e3d1a):** todos los bordes. Verde casi negro.
- **shadow:** 3px 3px 0 #1e3d1a (sólida, sin blur).
- **shadow-hover:** 4px 4px 0 #1e3d1a (ligeramente más grande al hover).

Colores de rango (solo para badges de stat):
- **rank-E (#8b949e):** gris
- **rank-D (#6e7681):** gris medio
- **rank-C (#58a6ff):** azul
- **rank-B (#a371f7):** violeta
- **rank-A (#f0883e):** naranja
- **rank-S (#c9a84c):** dorado (solo para el máximo)

**Prohibido:** glassmorphism, sombras con blur, border-radius > 6px, fondos blancos puros (#fff), azules bootstrap, neón, degradados arcoíris.

## Typography

- **Display (Permanent Marker, cursive):** títulos de sección, nombre del foro, banner principal, tabs, headers de tarjeta. Evoca escritura a mano, anotaciones de archivo. Siempre uppercase + letter-spacing. Con text-shadow: 2px 2px 0 var(--border) para dar profundidad.
- **Body (Georgia, serif):** texto general, posts, descripciones. Serif clásica que refuerza la estética de libro/documento antiguo.
- **UI (system sans-serif):** botones, labels, metadatos, navegación, badges. Funcional y legible.
- **Data (Menlo/Consolas, monospace):** estadísticas, valores numéricos, cantidades. Solo para datos.

**Regla:** Permanent Marker solo en títulos y elementos decorativos — nunca en body text. Monospace solo para números y datos. Georgia para todo el contenido legible.

## Layout

- **Ancho máximo:** 1200px centrado con padding lateral de 16px
- **Navbar:** fija superior, 56px, fondo verde panel con border-bottom 3px + shadow
- **Banner hero:** 300-320px, verde panel, con sello decorativo en Permanent Marker enorme al 14% opacidad, título en brush dorado con doble text-shadow
- **Tablón (índice):** 3 columnas en desktop, 1 columna en móvil. Tarjetas con border 3px + shadow sólida
- **Categorías:** headers verde panel con título brush dorado, subforos en grid de tarjetas (280px min-width) con hover que levanta la tarjeta (-2px translateY) y cambia el borde a dorado
- **Ficha de personaje:** layout de dashboard denso. Tabs con estilo botón (mismo estilo que el tema). Stats en columnas con barras de progreso y badges de rango letra. Estadísticas derivadas en grid de tarjetas pequeñas.

## Spacing

Escala estricta de 8px con saltos de 4px:
- 4px: entre icono y texto inline, gap entre badges
- 8px: entre elementos relacionados, padding interior de chips
- 12-16px: padding de tarjetas y secciones
- 24px: entre bloques mayores
- 48px: separación de secciones grandes

## Elevation

Sin sombras difuminadas. La jerarquía se comunica con:
- **Contraste de color:** paneles verdes (#2d5a27) sobre fondo beige (#f4f0e6)
- **Bordes gruesos:** 3px solid #1e3d1a en TODOS los elementos elevados
- **Sombras sólidas:** 3px 3px 0 (sin blur) — simula capas de papel/cartulina apiladas
- **Hover:** la tarjeta se eleva (-2px translateY), el borde cambia a dorado, la sombra crece a 4px 4px 0

## Shapes

Todo usa border-radius: 3px. Solo los avatares y botones de usuario circular usan border-radius: 50%.

## Components

### Navbar
Fija superior, 56px, verde panel. Logo en Permanent Marker dorado con text-shadow. Links en sans-serif blanco con underline animada dorada al hover. Botón de usuario circular con borde 3px.

### Banner
Fondo verde panel, 300-320px. Sello decorativo enorme (200px, Permanent Marker, opacidad 14%) centrado. Título en brush dorado con doble text-shadow (sombra verde + brillo blanco). Subtítulo en Georgia blanca. Stats en brush dorado.

### Categorías (subforos)
Header: panel verde, título brush dorado con text-shadow. Grid de tarjetas: panel verde, texto blanco, nombre en brush dorado. Hover: translateY(-2px), borde dorado, shadow-hover.

### Tarjetas (tablón)
Fondo bg-dark, borde 3px + shadow. Header en panel verde con título brush dorado. Items con borde dashed entre ellos. Hover de items: color dorado.

### Tabs (pestañas)
Botones verdes con borde 3px + shadow. Brush font, uppercase, blanco. Activo/hover: fondo dorado, texto verde.

### Badges de rango
Píldoras con borde 2px + shadow 2px. Fondo semitransparente del color de rango. Texto del color sólido. Solo para rangos de stat.

### Botones
- **Primario (verde):** fondo panel, borde 3px, texto dorado, sombra sólida. Hover: panel-hover.
- **Acento (dorado):** fondo gold, borde 2px, texto verde panel. Hover: gold-hover.
- **Cancelar:** fondo bg, borde 2px, texto ink-secondary.

### Formularios
Inputs: fondo bg, borde 3px, sombra 2px sólida. Focus: borde dorado. Sin border-radius grande.

## Do's and Don'ts

- **Sí** usar Permanent Marker solo en títulos y elementos decorativos — nunca en body text
- **Sí** mantener bordes de 3px y sombras sólidas consistentes en TODOS los componentes
- **Sí** usar el dorado con moderación: títulos, enlaces, hover, badges — no fondos completos
- **Sí** respetar la paleta: beige + verde + dorado + negro. No introducir colores nuevos sin justificación
- **Sí** usar monospace para datos numéricos (stats, cantidades)
- **Sí** text-shadow en títulos brush para dar profundidad
- **No** usar border-radius > 6px en nada
- **No** usar sombras con blur (box-shadow con 3er parámetro distinto de 0)
- **No** usar glassmorphism, fondos blancos puros, ni neón
- **No** mezclar Permanent Marker con otras fuentes display (Comic Sans, cursive genéricas)
- **No** usar más de 2 pesos tipográficos por pantalla
