# Token System — I-Forge "Archivo del Cazador"

> Fuente de verdad de todas las decisiones visuales. Cualquier componente, template o estilo nuevo debe usar exclusivamente estos tokens. No hardcodear valores.

---

## 1. Paleta

| Token | Hex | Rol |
|---|---|---|
| `--color-bg` | `#f2efe8` | Fondo general de página |
| `--color-surface` | `#e8e3d8` | Navbar, tarjetas, paneles |
| `--color-surface-hover` | `#ddd8cb` | Hover en tarjetas |
| `--color-accent` | `#4a7c59` | Verde licencia — botones, enlaces, bordes activos |
| `--color-accent-hover` | `#5d9e6e` | Hover de acento |
| `--color-gold` | `#c4a951` | Dorado archivo — detalles premium, sello |
| `--color-gold-hover` | `#d4be6a` | Hover de dorado |
| `--color-text` | `#2c2420` | Marrón tinta — texto principal |
| `--color-text-secondary` | `#6b5e53` | Metadatos, fechas, descripciones |
| `--color-text-on-accent` | `#f2efe8` | Texto sobre fondo acento |
| `--color-border` | `#d1cabc` | Bordes sutiles |
| `--color-border-strong` | `#b8ac9e` | Bordes destacados |
| `--color-danger` | `#c44a4a` | Alertas, errores, rechazado |
| `--color-success` | `#4a7c59` | Aprobado, éxito |
| `--color-t1` | `#2c5f7e` | Badge rango T1 |
| `--color-t2` | `#6b4a8e` | Badge rango T2 |
| `--color-t3` | `#b85a3e` | Badge rango T3 |

## 2. Tipografía

| Token | Familia | Uso |
|---|---|---|
| `--font-display` | 'Playfair Display', Georgia, serif | Nombre foro, títulos de categoría, headings grandes |
| `--font-body` | 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif | Texto general, párrafos, labels |
| `--font-mono` | 'JetBrains Mono', 'Menlo', 'Consolas', monospace | Stats, cantidades, códigos |

Escala tipográfica: 12 / 14 / 16 / 18 / 24 / 32 / 48 / 64 px

## 3. Espaciado y bordes

| Token | Valor | Uso |
|---|---|---|
| `--radius-sm` | 6px | Botones, inputs |
| `--radius-md` | 10px | Tarjetas, paneles |
| `--radius-lg` | 16px | Contenedores grandes |
| `--space-xs` | 4px | |
| `--space-sm` | 8px | |
| `--space-md` | 16px | |
| `--space-lg` | 24px | |
| `--space-xl` | 32px | |
| `--navbar-height` | 60px | |
| `--content-max` | 1200px | Ancho máximo de contenido |

## 4. Elemento firma — Sello de Cazador

Un emblema circular con una "I" estilizada en el centro, rodeado por un aro con inscripción tipo sello de la Asociación de Cazadores. Se usa como:

- Marca de agua en el banner (grande, centrado, opacidad 8%)
- Sello decorativo en el footer (pequeño)
- Marca de agua en cards de categoría (esquina, opacidad 5%)
- Borde decorativo en la cabecera de cada card del tablón

## 5. Sombras

| Token | Valor |
|---|---|
| `--shadow-sm` | `0 1px 3px rgba(44, 36, 32, 0.08)` |
| `--shadow-md` | `0 4px 12px rgba(44, 36, 32, 0.1)` |
| `--shadow-lg` | `0 8px 24px rgba(44, 36, 32, 0.12)` |

## 6. Iconografía

Todos los iconos son SVGs inline de línea fina (stroke-width: 1.5), 20x20px, color heredado. Sin emojis.

Iconos del sistema: speech, search, newspaper, idea, users, calendar, sword, shield, seal.

## 7. Prohibiciones

- No emojis en UI
- No glassmorphism
- No dark mode (excepción: overlay de banner)
- No colores sin función asignada en esta tabla
- No bordes de 1px con colores no definidos aquí
