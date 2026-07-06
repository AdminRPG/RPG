# Token System — I-Forge "Archivo del Cazador"

> Fuente de verdad de todas las decisiones visuales. Cualquier componente, template o estilo nuevo debe usar exclusivamente estos tokens. No hardcodear valores.
>
> **Estilo: Neobrutalismo.** Bordes gruesos (3px), sombras sólidas sin blur, tipografía brush para títulos, paleta pergamino + verde bosque + tinta dorada.

---

## 1. Paleta

| Token | Hex | Rol |
|---|---|---|
| `--bg` | `#f4f0e6` | Fondo general de página (pergamino) |
| `--bg-dark` | `#ebe6d6` | Fondo de tarjetas y paneles interiores |
| `--panel` | `#2d5a27` | Navbar, headers, botones primarios (verde bosque) |
| `--panel-hover` | `#3d7a35` | Hover de paneles verdes |
| `--gold` | `#c9a84c` | Títulos, enlaces, hover, badges (tinta dorada) |
| `--gold-hover` | `#e2c96b` | Hover del acento dorado |
| `--ink` | `#1a1a1a` | Texto principal (negro tinta) |
| `--ink-secondary` | `#5a5a4a` | Metadatos, fechas, texto secundario (marrón) |
| `--border` | `#1e3d1a` | Todos los bordes (verde casi negro) |
| `--shadow` | `3px 3px 0 #1e3d1a` | Sombra estándar (sólida, sin blur) |
| `--shadow-hover` | `4px 4px 0 #1e3d1a` | Sombra hover (ligeramente más grande) |
| `--text-on-panel` | `#ffffff` | Texto sobre fondos verdes y oscuros |
| `--danger` | `#f85149` | Alertas, errores, rechazado |
| `--success` | `#3fb950` | Aprobado, éxito |
| `--rank-E` | `#8b949e` | Badge rango E |
| `--rank-D` | `#6e7681` | Badge rango D |
| `--rank-C` | `#58a6ff` | Badge rango C |
| `--rank-B` | `#a371f7` | Badge rango B |
| `--rank-A` | `#f0883e` | Badge rango A |
| `--rank-S` | `#c9a84c` | Badge rango S (dorado) |

## 2. Tipografía

| Token | Familia | Uso |
|---|---|---|
| `--font-brush` | 'Permanent Marker', 'Comic Sans MS', cursive | Nombres, títulos de categoría, headers de tarjeta, tabs, banner |
| `--font-serif` | Georgia, 'Palatino Linotype', serif | Texto general, párrafos, posts |
| `--font-sans` | -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif | Navegación, botones, badges, metadatos, labels |
| `--font-mono` | Menlo, Consolas, Monaco, monospace | Stats, cantidades, valores numéricos |

Escala tipográfica orientativa: 10 / 11 / 12 / 13 / 14 / 15 / 16 / 18 / 20 / 24 / 28 / 32 / 48 px

**Regla:** Permanent Marker solo en títulos y elementos decorativos. Nunca en body text. Monospace solo para datos numéricos.

## 3. Espaciado y bordes

| Token | Valor | Uso |
|---|---|---|
| `--radius` | `3px` | Todo: botones, inputs, tarjetas, paneles |
| `--border-width` | `3px` | Borde estándar de componentes |
| `--border-color` | `#1e3d1a` | Color de todos los bordes |
| `--navbar-height` | `56px` | Altura de la barra de navegación fija |
| `--content-max` | `1200px` | Ancho máximo de contenido |

Escala de espaciado: 4 / 6 / 8 / 10 / 12 / 14 / 16 / 20 / 24 / 32 / 48 px

## 4. Sombras

**Sin sombras con blur.** Todas las sombras son sólidas (offset-x, offset-y, 0, color):

| Token | Valor |
|---|---|
| `--shadow-sm` | `2px 2px 0 var(--border)` |
| `--shadow` | `3px 3px 0 var(--border)` |
| `--shadow-hover` | `4px 4px 0 var(--border)` |

Esto simula capas de cartulina/papel apiladas. Es esencial para la estética neobrutalista.

## 5. Elemento firma — Sello de Cazador

Un carácter grande en Permanent Marker (normalmente "HUNTER" o las iniciales del personaje) a baja opacidad (4-14%) usado como marca de agua en:
- Banner del foro
- Header de ficha de personaje
- Tarjetas de categoría (esquina)

## 6. Iconografía

SVGs inline de línea fina (stroke-width: 2), 20-24px, color heredado. Sin emojis en UI de producción (en prototipos se permiten como placeholder).

## 7. Prohibiciones

- No glassmorphism ni fondos translúcidos
- No dark mode (el tema es pergamino claro con acentos oscuros)
- No sombras con blur (box-shadow con 3er parámetro > 0)
- No border-radius > 6px (usar 3px universal)
- No bordes de 1px (mínimo 2px, idealmente 3px)
- No colores sin función asignada en esta tabla
- No usar Permanent Marker para body text
- No mezclar múltiples familias display (solo Permanent Marker para títulos)
