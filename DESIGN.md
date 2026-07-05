---
version: alpha
name: I-Forge — Archivo del Cazador
colors:
  bg: "#0d1117"
  surface: "#161b22"
  surface-hover: "#1c2128"
  accent: "#e2b714"
  accent-hover: "#f0c940"
  text: "#f0f6fc"
  text-secondary: "#8b949e"
  border: "#30363d"
  divider: "#21262d"
  danger: "#f85149"
  success: "#3fb950"
  rank-t1: "#58a6ff"
  rank-t2: "#a371f7"
  rank-t3: "#f0883e"
typography:
  display-xl:
    fontFamily: Georgia
    fontSize: 56px
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: 0.08em
  display-lg:
    fontFamily: Georgia
    fontSize: 32px
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: 0.04em
  display-md:
    fontFamily: Georgia
    fontSize: 20px
    fontWeight: 700
    lineHeight: 1.3
    letterSpacing: 0.02em
  body-lg:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.6
  body-md:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif"
    fontSize: 12px
    fontWeight: 500
    lineHeight: 1.4
  label-caps:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif"
    fontSize: 11px
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: 0.08em
  data:
    fontFamily: "Menlo, Consolas, Monaco, monospace"
    fontSize: 13px
    fontWeight: 400
    lineHeight: 1.4
rounded:
  sm: 6px
  md: 10px
  lg: 16px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
components:
  button-outline:
    backgroundColor: transparent
    textColor: "{colors.accent}"
    rounded: "{rounded.sm}"
    borderColor: "{colors.border}"
    padding: 8px 16px
  button-outline-hover:
    borderColor: "{colors.accent}"
    backgroundColor: "{colors.surface-hover}"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.md}"
    borderColor: "{colors.border}"
  card-hover:
    borderColor: "{colors.divider}"
  badge-rank-1:
    backgroundColor: "rgba(88, 166, 255, 0.15)"
    textColor: "{colors.rank-t1}"
    rounded: 10px
    padding: 2px 8px
  badge-rank-2:
    backgroundColor: "rgba(163, 113, 247, 0.15)"
    textColor: "{colors.rank-t2}"
    rounded: 10px
    padding: 2px 8px
  badge-rank-3:
    backgroundColor: "rgba(240, 136, 62, 0.15)"
    textColor: "{colors.rank-t3}"
    rounded: 10px
    padding: 2px 8px
---

# DESIGN.md — I-Forge: Archivo del Cazador

## Overview

I-Forge es un foro de rol play-by-post de ambientación oscura (Hunter x Hunter, lore original). El diseño se lee como el **archivo de un cazador veterano**: apuntes en papel pergamino oscuro, anotaciones al margen en tinta dorada, mapa desgastado. La interfaz debe sentirse artesanal, densa en información pero con jerarquía clara, como un grimorio de caza.

Tres ideas fuerzan el carácter visual:
1. **Oscuridad intencional** — fondos profundos (#0d1117) no por moda, sino porque el mundo de Cazadores es peligroso, nocturno, de sombras
2. **Tinta dorada** — el acento oro (#e2b714) es el hilo conductor: aparece en enlaces, hover, bordes y acentos, pero nunca inunda. Es la tinta con la que un cazador marca su mapa
3. **Sello de cazador** — el elemento firma es un gran sello heráldico fantasmal (opacidad 6%) que actúa como marca de agua de fondo en banners y categorías, evocando un blasón grabado en piedra

## Colors

La paleta es deliberadamente reducida: fondo nocturno, superficies de piedra oscura, y un único acento dorado.

- **bg (#0d1117):** fondo general del foro. Noche profunda.
- **surface (#161b22):** navbar, tarjetas, paneles. Pizarra oscura.
- **accent (#e2b714):** el único color que llama la atención. Enlaces, hover, bordes de categoría al hover, badge del logo. Se usa con moderación — como tinta dorada sobre un mapa, no como pintura de pared.
- **text (#f0f6fc):** texto principal. Casi blanco, alto contraste.
- **text-secondary (#8b949e):** metadatos, fechas, descripciones secundarias. Gris pálido.

Colores funcionales:
- **danger (#f85149):** alertas, errores, rechazos.
- **success (#3fb950):** confirmaciones, aprobado.
- **rank-t1 (#58a6ff), rank-t2 (#a371f7), rank-t3 (#f0883e):** exclusivo para badges de rango. No se usan en ningún otro contexto.

**Prohibido:** glassmorphism, azules brillantes tipo Bootstrap, fondos blancos (#ffffff), degradados arcoíris, sombras de neón.

## Typography

La tipografía comunica oficio y antigüedad.

- **Display (Georgia, serif):** títulos de categoría, nombre del foro, banner principal. La serifa evoca documento impreso, archivo. Solo en pesos 700; el espaciado de letras generoso (hasta 0.08em) le da empaque de título de capítulo.
- **Body (sistema sans-serif):** texto general, posts, descripciones. Neutro funcional que no compite con la display.
- **Data (Menlo/Consolas, monospace):** estadísticas en fichas de personaje, cantidades, datos numéricos. Transmite precisión técnica.

### Escala

| Token | Tamaño | Uso |
|---|---|---|
| display-xl | 56px | Título del banner principal |
| display-lg | 32px | Título de página/sección |
| display-md | 20px | Título de tarjeta |
| body-lg | 16px | Texto de post |
| body-md | 14px | Texto general, descripciones |
| body-sm | 12px | Metadatos, timestamps |
| label-caps | 11px UPPERCASE | Badges, headers de tarjeta |
| data | 13px | Stats en fichas |

**Regla:** no usar Georgia para nada que no sea display. No usar monospace para texto general.

## Layout

El layout es de **ancho máximo fijo** (1200px) centrado, con padding lateral de 24px en desktop y 16px en móvil.

- **Navbar:** fija superior, 60px de alto, con blur de fondo. Logo a la izquierda, navegación centrada, menú de usuario a la derecha.
- **Banner hero:** 400px (desktop) / 260px (móvil), con imagen de fondo, gradiente superpuesto oscuro, sello fantasmal al fondo, y título centrado.
- **Tablón (índice):** 2 columnas en desktop, 1 columna en móvil. Tarjetas de sección y categorías.
- **Categorías:** layout vertical, tarjetas individuales con icono, título, descripción y flecha. Fondo con gradiente oscuro y sello decorativo.
- **Ficha de personaje:** layout de dashboard denso. Barra de stats superior, secciones en tabs (Portada, Biografía, Bélico, Técnicas, Inventario). Stats con valores grandes y barras de progreso sutiles.

### Spacing

Escala estricta de 8px con salto de 4px para micro-ajustes:
- xs: 4px (entre icono y texto inline)
- sm: 8px (entre elementos relacionados)
- md: 16px (padding interno de tarjetas)
- lg: 24px (entre secciones)
- xl: 32px (márgenes grandes)
- xxl: 48px (separación de bloques mayores)

## Elevation & Depth

Sin sombras de neón ni profundidad exagerada. La jerarquía se comunica con:
- **Superposición tonal:** surface (#161b22) sobre bg (#0d1117). El contraste entre fondo y elevado es sutil pero suficiente para separar planos.
- **Bordes tenues:** cada tarjeta/superficie tiene un borde de 1px solid #30363d. El hover cambia el borde (no añade sombra grande).
- **Sombras opacas:** se usan solo para dropdowns y modales — negras puras con opacidad 0.3-0.5, nada de sombras de color.

## Shapes

- **sm (6px):** badges, inputs, icon-wrap
- **md (10px):** tarjetas, dropdowns
- **lg (16px):** tarjetas de categoría
- **50%:** avatares, botón de usuario

Transiciones suaves de 0.15s en colores, 0.2s en border/box-shadow. Sin animaciones decorativas sueltas.

## Components

### Navbar
Fija superior, 60px. Fondo semitransparente (rgba 22,27,34 con blur 12px). Logo en Georgia, acento dorado, uppercase, letter-spacing 3px. Enlaces de navegación con línea inferior animada en hover. Menú de usuario con dropdown de border-radius md.

### Banner
Imagen de fondo con overlay gradiente (negro 30% → 70%). Sello fantasmal enorme (280px, Georgia, opacidad 6%) como marca de agua. Título en display-xl, subtítulo en acento dorado con letter-spacing.

### Categorías
Tarjetas de borde lg, con gradiente oscuro de fondo, sello decorativo a la derecha. Icono cuadrado (44px, borde sm) + título en display-md + descripción en body-sm + flecha animada. Al hover: borde dorado y glow sutil.

### Tarjetas (tablón)
Dos columnas, surface con borde. Header en label-caps con acento dorado. Items con divisor sutil. Hover cambia fondo a surface-hover. "Ver más" al final, centrado.

### Badges de rango
Píldoras de border-radius 10px. Fondo del color del rango con 15% de opacidad. Texto del color completo. Solo se usan para rangos T1/T2/T3.

### Staff
Lista horizontal wrap. Items con avatar, nombre y badge de MP. Fondo semitransparente 3%. Borde divider.

## Do's and Don'ts

- **Sí** usar el acento dorado con moderación — es especia, no plato principal
- **Sí** mantener la paleta reducida: si añades un color nuevo, justifícalo con una función concreta
- **Sí** respetar la escala de espaciado: no pongas 17px donde va 16px
- **Sí** usar monospace para datos numéricos (stats, cantidades)
- **No** mezclar Georgia con serifs modernos (Playfair, etc.) — Georgia es la única serif
- **No** usar más de dos pesos tipográficos por pantalla
- **No** añadir sombras de colores (solo negro con opacidad)
- **No** usar glassmorphism, fondos blancos, ni neón
- **No** decorar sin función: cada línea, borde y acento debe tener un propósito
