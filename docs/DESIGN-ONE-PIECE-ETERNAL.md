# DESIGN — One Piece: Eternal

> **Fuente de verdad del diseño** para el foro de rol *One Piece: Eternal* (migración desde *One Piece: Eternal*).
>
> **Estado:** v1 — derivado de prototipos aprobados (`docs/Prototypes/Granblue/index.html` v3.2, `ficha.html` v4) y `docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md` v3.
>
> **Documentos hermanos:** `docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md` (visión/fases), `docs/DIRECCION-LORE-Y-SISTEMAS.md` (lore + sistemas), `docs/PRODUCT.md`.
>
> **CSS en producción:** `docs/themes/ope.css` → servido desde `cache/themes/theme13/ope.css`.
>
> **Plantillas MyBB:** `docs/themes/ope-index.xml` (portada), páginas PHP autónomas (`index.php`, `ficha.php`, …).

---

## 0. Índice

0. [Protocolo agentes — no portar a medias](#0-protocolo-agentes--no-portar-a-medias)
1. [Visión, tagline y 6 pilares](#1-visión-tagline-y-6-pilares)
2. [Marca y codename](#2-marca-y-codename)
3. [Sistema de dos temas (tokens)](#3-sistema-de-dos-temas-tokens)
4. [Tipografía](#4-tipografía)
5. [§7.8 — Scoping de CSS en páginas PHP](#58-scoping-de-css-en-páginas-php)
6. [Portada (`index.php` / `ope-index.xml`)](#6-portada-indexphp--ope-indexxml)
7. [Cuádrupla visual del personaje](#7-cuádrupla-visual-del-personaje)
8. [Ficha (`ficha.php`) — layout v4](#8-ficha-fichaphp--layout-v4)
9. [Convención de imágenes (`images/gbe/`)](#9-convención-de-imágenes-imagesgbe)
10. [Patrones de reskin (OP → OPE)](#10-patrones-de-reskin-op--gbf)
11. [Librerías JS y animación](#11-librerías-js-y-animación)

---

## 0. Protocolo agentes — no portar a medias

> **Obligatorio para Cursor, OpenCode y Antigravity.** Detalle: `docs/AGENTES-Y-HERRAMIENTAS.md`.

### 0.1 Regla

Portar un prototipo (`docs/Prototypes/Granblue/`) = implementar **todas** las capas del árbol (§6.1), no un componente aislado. El incidente jul-2026: carrusel OPE + tablón OP → producción no igual al prototipo.

### 0.2 Las 5 capas (todas requeridas)

| # | Capa | Archivos típicos |
|---|---|---|
| 1 | Estructura HTML/XML/PHP | `ope-index.xml`, `index.php`, `ficha.php` |
| 2 | Tokens `:root` | `docs/themes/ope.css` |
| 3 | Overrides legacy bajo scope | `body.ope-index`, `body.ope-pg-*` |
| 4 | Fuentes / head | `inc/plugins/ope_rol.php`, `headerinclude` |
| 5 | Datos y copy | `index.php`, datacache lore |

### 0.3 Cierre de tarea UI

1. Checklist en `AGENTES-Y-HERRAMIENTAS.md` §2.
2. `sync-theme verify` → `OK CSS: in sync`.
3. Comparación visual con prototipo (scroll completo).
4. Actualizar §6.7 si cambia el estado F2b.

**Prohibido:** marcar “portado index” con solo hero/carrusel hecho.

---

## 1. Visión, tagline y 6 pilares

### 1.1 Qué es

Foro de rol play-by-post ambientado en el **universo de los Cielos** (Cielos, Aventureros, Primordial Beasts, aeronaves), con **historia, cronología y personajes originales**. El motor técnico (combate, snapshots, mundo vivo, wizard) se conserva; cambian skin, terminología y ambientación.

### 1.2 Tagline

> **"El cielo no tiene fin. Tu leyenda, tampoco."**

Tema emocional: de *Libertad* (mar, OP) → **Horizonte** (cielo, OPE). La escena de referencia: amanecer desde la cubierta de una aeronave sobre un mar de nubes doradas, islas recortadas contra cielo índigo-turquesa.

### 1.3 Los 6 pilares (no negociables)

| Pilar | Sistema en código | Reskin OPE |
|---|---|---|
| **Personalización** | `crear-personaje.php`, ficha, stats 12×3 | Razas OPE, clase, elemento, arma, cuádrupla visual |
| **Combate** | `inc/ope_rol_system.php` (PV/EN/PA, heridas, estados) | Esencia, ventaja elemental, técnicas por arma |
| **Tramas** | `rol_mv_*`, tags `--tag-trama/mision/viaje/fic` | "El Equilibrio del Cielo", Ancestrales/Primordiales |
| **Misiones** | `tablon-misiones.php` | Órdenes del Cielo, tiers por Cielo |
| **Social** | `rol_tripulaciones`, acompañantes, relaciones | Crews, aeronave, summons, renombre |
| **Aventuras** | Cielos/islas, `inc/ope_rol_viajes.php` | Rutas de aeronave, progresión geográfica |

**Regla de oro:** el motor se conserva, el skin cambia.

### 1.4 Principios de diseño

1. **El cielo es horizonte.** El whitespace es aire, no mar.
2. **Luz cálida sobre índigo profundo.** Contraste amanecer, no noche pirata.
3. **Cada elemento tiene referente OPE.** Esencia, Primordiales, aeronaves, Cielos.
4. **Tipografía de aventura clásica.** Épica y legible, no wanted-poster.
5. **Identidad CSS-first.** Funciona sin imágenes; las imágenes elevan, no sustituyen tokens.

---

## 2. Marca y codename

| Campo | Valor |
|---|---|
| **Nombre público** | One Piece: Eternal |
| **Codename técnico** | `gbe` |
| **Prefijo CSS objetivo** | `ope-*`, `body.ope-pg-*` |
| **Prefijo CSS actual (transición)** | `ope-*`, `body.ope-pg-*` — **no eliminar hasta F1** |
| **Logo** | `images/gbe/crest-eternal.png` |
| **Bot cronista** | Lyria (sustituye OPE Eternal) |
| **Moneda** | Monedas |
| **Disclaimer footer** | Foro no oficial; sin afiliación Cygames; personajes originales |

> CSS canónico: `docs/themes/ope.css` → `cache/themes/theme13/ope.css`. Codename activo: `gbe`.

---

## 3. Sistema de dos temas (tokens)

Conmutación vía `html[data-theme="cielo"|"noche"]`. **Por defecto: `cielo`** (claro/acuarela, dirección Referencia Visual). El botón de tema alterna entre ambos.

Los prototipos usan tokens semánticos propios (`--bg`, `--card`, `--gold`…). En `ope.css` se **mapean a los nombres heredados** (`--iron`, `--paper`, `--ember`…) para no reescribir miles de reglas. Durante el portado, cambiar valores en `:root` y añadir el selector `[data-theme="noche"]`.

### 3.1 Tema `cielo` (claro — default)

| Token prototipo | Valor | Mapeo `ope.css` | Uso |
|---|---|---|---|
| `--bg` | `#eef4f8` | `--iron` (fondo body) | Fondo base |
| `--bg-2` | `#f6f3ea` | capa secundaria body | Gradiente cálido |
| `--wash` | `#dce8f2` | `--concrete` | Velos, wash de sección |
| `--card` | `#ffffff` | `--iron-plate` | Paneles, placas |
| `--card-2` | `#f7f9fb` | `--iron-hi` | Hover, chips |
| `--ink` | `#2f4256` | `--paper` (texto principal en claro) | Títulos, cuerpo |
| `--ink-soft` | `#5b6f83` | `--paper-dim` | Subtítulos |
| `--ink-dim` | `#8ba0b3` | `--ash` | Meta, FIDs |
| `--gold` | `#b7924e` | `--gold`, `--ember` | Acción primaria, marca |
| `--gold-hi` | `#d8b76b` | `--gold-hi`, `--ember-hi` | Realce, hover |
| `--gold-deep` | `#8f6f34` | `--gold-deep` | Enlaces, énfasis |
| `--sky` | `#5ba3d0` | `--sky`, `--patina` | Acción secundaria |
| `--sky-deep` | `#2f6fa0` | `--sea-deep` | Eyebrows, labels |
| `--eter` | `#3bb3ab` | `--patina-hi` | Esencia, chips OK |
| `--line` | `rgba(183,146,78,.35)` | bordes dorados | Filetes de panel |
| `--line-soft` | `rgba(90,111,131,.16)` | bordes sutiles | Separadores |
| `--watermark` | `#cdd8e2` | texto decorativo `.wm` | Watermarks de sección |
| `--shadow` | `0 18px 46px rgba(47,66,86,.12)` | sombras grandes | Cards elevadas |
| `--shadow-sm` | `0 8px 22px rgba(47,66,86,.10)` | sombras pequeñas | Paneles |

### 3.2 Tema `noche` (oscuro)

| Token prototipo | Valor | Mapeo `ope.css` | Uso |
|---|---|---|---|
| `--bg` | `#0e1730` | `--iron` | Cielo nocturno profundo |
| `--bg-2` | `#0b1226` | capa secundaria | Gradiente base |
| `--wash` | `#16244a` | `--concrete` | Velos |
| `--card` | `rgba(22,33,62,.72)` | `--iron-plate` | Paneles semitransparentes |
| `--card-2` | `rgba(14,22,44,.7)` | `--iron-hi` | Hover |
| `--ink` | `#eaf1fb` | `--paper` | Texto principal |
| `--ink-soft` | `#b9c6dd` | `--paper-dim` | Subtítulos |
| `--ink-dim` | `#7f90ad` | `--ash` | Meta |
| `--gold` | `#e2bd6b` | `--gold`, `--ember` | Oro amanecer |
| `--gold-hi` | `#ffd98a` | `--gold-hi`, `--ember-hi` | Realce |
| `--gold-deep` | `#b48f3f` | `--gold-deep` | Enlaces |
| `--sky` | `#5aa9ff` | `--sky`, `--patina` | Cielo frío |
| `--sky-deep` | `#2f74b5` | `--sea-deep` | Labels |
| `--eter` | `#63e6dd` | `--patina-hi` | Brillos Esencia |
| `--line` | `rgba(226,189,107,.32)` | bordes | Filetes |
| `--line-soft` | `rgba(185,198,221,.14)` | separadores | Bordes sutiles |
| `--watermark` | `rgba(202,214,232,.10)` | decorativo | Watermarks |
| `--shadow` | `0 18px 46px rgba(0,0,0,.4)` | sombras | Cards |
| `--shadow-sm` | `0 8px 22px rgba(0,0,0,.34)` | sombras | Paneles |

### 3.3 Tokens transversales (ambos temas)

#### Superficies heredadas (`ope.css`)

Se conservan nombres legacy para compatibilidad durante transición:

```css
/* Superficies — valores en tema cielo; sobreescribir en [data-theme="noche"] */
--iron:        /* fondo body */
--iron-plate:  /* paneles */
--iron-hi:     /* hover / chips */
--iron-edge:   /* cabeceras oscuras (modo noche) */
--rivet:       /* bordes metálicos */
--concrete:    /* wash / velos */
--concrete-2:  /* gradientes secundarios */
--concrete-line:
--paper:       /* texto principal (invertido en claro: ink oscuro) */
--paper-dim:
--ash:
--ember / --ember-hi:  /* oro / acción primaria */
--patina / --patina-hi: /* turquesa Esencia */
--gold / --gold-hi / --gold-deep:
--sky / --sky-hi:
--sea / --sea-deep:
--crack / --red / --red-hi:  /* peligro, Primordial hostil */
```

#### Escala de calor / renombre (`--h1`…`--h9`)

Progresión océano → cielo → oro (sustituye escala marina OP):

| Token | Cielo | Noche |
|---|---|---|
| `--h1` | `#9fb0c0` | `#7f90ad` |
| `--h2` | `#77a4c6` | `#5a86b5` |
| `--h3` | `#5ba3d0` | `#5aa9ff` |
| `--h4` | `#3f8fc0` | `#63b8ea` |
| `--h5` | `#3bb3ab` | `#63e6dd` |
| `--h6` | `#c9a24e` | `#e2bd6b` |
| `--h7` | `#b7924e` | `#ffd98a` |
| `--h8` | `#a8843f` | `#ffe9a3` |
| `--h9` | `#8f6f34` | `#fff6d8` |

#### Elementos OPE (`body[data-element]` en ficha)

| Elemento | Token | Color |
|---|---|---|
| Fuego | `--el-fuego` | `#d9503a` |
| Agua | `--el-agua` | `#2f8fce` |
| Tierra | `--el-tierra` | `#b3862f` |
| Viento | `--el-viento` | `#3fa76a` |
| Luz | `--el-luz` | `#d9b23a` |
| Oscuridad | `--el-oscuridad` | `#7d5bc0` |

Aura del PJ: `--aura` = color del elemento activo (borde avatar, chips, borde pacto).

#### Facciones OPE (reemplazan `--fac-pirata`…)

| Token | Facción |
|---|---|
| `--fac-Aventurero` | Aventureros libres |
| `--fac-imperio` | Imperio |
| `--fac-sociedad` | Sociedad |
| `--fac-orden` | Órdenes |
| `--fac-gremio` | Gremios |
| `--fac-libre` | Independientes |

Cada facción define `--fac-*`, `--fac-*-hi`, `--fac-*-ink` (mismo patrón que OP en `:root` de `ope.css` L37–47).

#### Tags de cronología (sin cambio estructural)

`--tag-mision`, `--tag-trama`, `--tag-viaje`, `--tag-fic` + `-ink` — conservar nombres; retintar valores si procede.

---

## 4. Tipografía

### 4.1 Familias (Google Fonts)

| Rol | Fuente | Variable CSS | Uso |
|---|---|---|---|
| Marca / títulos | **Cinzel** | `--disp` | Logo, H1, renombre, números épicos |
| Elegante / watermark | **Cormorant Garamond** | `--mark` | Subtítulos, botones, nombres de foro, alias |
| Cuerpo | **Spectral** | `--body` | Párrafos, lore, prose |
| Datos | **Space Mono** | `--mono` | Stats, FIDs, fechas, chips, eyebrows |

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Spectral:ital,wght@0,400;0,500;0,600;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
```

Declaración en `:root` (`ope.css` / `ope-index.xml` → `headerinclude`):

```css
--disp:'Cinzel',Georgia,serif;
--mark:'Cormorant Garamond',Georgia,serif;
--body:'Spectral',Georgia,serif;
--mono:'Space Mono',Menlo,Consolas,monospace;
```

### 4.2 Jerarquía tipográfica

| Elemento | Familia | Peso | Notas |
|---|---|---|---|
| Logo navbar | `--disp` | 700 | `One Piece: **Eternal**` |
| H1 hero / nombre PJ | `--disp` | 900 | `clamp()` responsive |
| H2 sección (`.stitle`) | `--disp` | 700 | |
| Eyebrow (`.eyebrow`, `.kick`) | `--mono` | 700 | uppercase, letter-spacing 2–3px |
| Watermark (`.wm`) | `--mark` | 600 | decorativo, no interactivo |
| Botones (`.btn`) | `--mark` | 500–600 | border-radius **8px** (rectangular; ver §4.4) |
| Cuerpo / lore | `--body` | 400–600 | line-height 1.6–1.7 |
| Stats / FIDs | `--mono` | 400–700 | |

### 4.4 Botones — rectangulares (NO pill)

**Decisión cerrada (jul-2026):** el equipo **no** quiere botones tipo pill/cápsula (`border-radius: 24px–30px`, forma de pastilla).

| Uso | Estilo |
|---|---|
| CTA primario (`.ope-btn-hot`, `.btn-hot`) | Rectangular, `border-radius: 8px`, gradiente oro, borde sutil, sombra `--ope-shadow-sm` |
| Secundario (`.ope-btn-ghost`) | Fondo `--ope-card`, borde `--ope-line`, texto `--ope-ink` |
| Discord / enlaces externos | Mismo lenguaje: tarjeta clara + borde; icono de marca en color, no bloque azul pill |
| **Permitido redondo** | Solo avatares, dots del carrusel, badges circulares de feed/staff |

Prohibido en UI OPE: pills con glow exagerado, `#5865F2` sólido tipo app móvil, `border-radius: 30px` en CTAs.

```css
/* Patrón canónico portada / páginas OPE */
.ope-btn{border-radius:8px;padding:10px 22px;font-family:var(--mark);text-transform:none}
```

> El prototipo HTML v3.2 aún muestra pills en `.btn` — **producción diverge** a rectangulares por esta decisión.

### 4.3 Sustitución respecto a OP

| OP (`ope.css` actual) | OPE: Eternal |
|---|---|
| Big Shoulders Display → `--disp` | Cinzel |
| Archivo → `--body` | Spectral |
| — | Cormorant Garamond → `--mark` (nuevo) |
| Space Mono → `--mono` | Space Mono (conservado) |

---

## 5. §7.8 — Scoping de CSS en páginas PHP

> Equivalente OPE del scaffolding de páginas PHP. Fuente única: `docs/themes/ope.css` → `cache/themes/theme13/ope.css`.

### 5.1 Regla de oro

**Todo el CSS vive en el tema.** Prohibido `<style>` estático y `style="..."` con valores fijos en PHP/HTML. Solo se permite `style` con valor **dinámico de PHP** (p. ej. `style="width:<?=$pct?>%"`, `style="--season:<?=$color?>"`).

### 5.2 Scoping por página

**Estado transición:** `body.ope-pg-<pagina>` (actual en `ficha.php`, `crear-personaje.php`, etc.).

**Estado objetivo (post-F1):** `body.ope-pg-<pagina>`.

Durante F2b se puede usar **doble clase** (`class="ope-pg-ficha ope-pg-ficha"`) o mantener `ope-pg-*` hasta la purga; al renombrar, duplicar bloques scopeados o usar selector agrupado:

```css
body.ope-pg-ficha .plate,
body.ope-pg-ficha .plate { /* … */ }
```

### 5.3 GOTCHA nº1 — clases NO globales

Las clases estructurales **`.shead` · `.plate` · `.plate-h` · `.plate-b` · `.reveal` · `.flash` · `.pj-empty` NO son globales**. Cada página las **re-declara** bajo su scope. Si estrenas `<body class="ope-pg-nueva">` y usas esas clases **sin pegar el scaffolding**, la página sale sin borde/fondo/sombra → texto plano.

**Sí son globales** (NO re-declarar): `.wrap`, `.breadcrumb` / `.breadcrumb-in`, `.btn` / `.btn-hot` / `.btn-ghost`, `.ope-prog-ppbar*`, `.ope-prog-hero-bar*`. Tras F1 renombrar prefijos globales progresivamente (`ope-prog-*`).

### 5.4 Scaffolding obligatorio (nueva página)

Pegar en `ope.css` **antes** de reglas específicas. Sustituir `<pagina>` por el slug (`ficha`, `tramites`, `crear-personaje`, …):

```css
body.ope-pg-<pagina> .shead{display:flex;align-items:baseline;gap:14px;margin:8px 0 14px}
body.ope-pg-<pagina> .shead h1,body.ope-pg-<pagina> .shead h2{font-family:var(--disp);font-weight:800;font-size:2rem;color:var(--paper);line-height:1}
body.ope-pg-<pagina> .shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
body.ope-pg-<pagina> .shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}
body.ope-pg-<pagina> .plate{border:1px solid var(--line);background:var(--iron-plate);margin-bottom:12px;box-shadow:var(--shadow-sm);border-radius:14px;overflow:hidden}
body.ope-pg-<pagina> .plate-h{background:linear-gradient(120deg,var(--concrete),var(--iron-plate));padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--line-soft)}
body.ope-pg-<pagina> .plate-h .t{font-family:var(--disp);font-weight:700;font-size:1rem;color:var(--gold-deep)}
body.ope-pg-<pagina> .plate-h .c{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--ash)}
body.ope-pg-<pagina> .plate-b{padding:16px}
body.ope-pg-<pagina> .flash{border:1px solid var(--line);padding:11px 14px;margin-bottom:16px;font-family:var(--mono);font-size:.74rem;font-weight:700;border-radius:8px}
body.ope-pg-<pagina> .flash.ok{background:color-mix(in srgb,var(--patina) 20%,transparent);color:var(--paper)}
body.ope-pg-<pagina> .flash.error{background:var(--crack);color:#fff}
body.ope-pg-<pagina> .pj-empty{border:1px dashed var(--rivet);background:var(--iron-plate);padding:40px 22px;text-align:center;font-family:var(--mono);font-size:.8rem;color:var(--paper-dim);border-radius:12px}
body.ope-pg-<pagina> .reveal{opacity:0;transform:translateY(14px);transition:opacity .5s,transform .5s}
body.ope-pg-<pagina> .reveal.vis{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){body.ope-pg-<pagina> .reveal{opacity:1;transform:none}}
```

> **Atajo:** si la página es visualmente idéntica a otra, reutiliza su scope en el `<body>` (`class="ope-pg-tramites ope-pg-nueva"`) y solo añade diferencias.

> **Transición:** mientras persista `ope-pg-*`, el scaffolding OP (bordes 2px negro) sigue válido en `ope.css` L1042+. Al portar OPE, migrar a bordes `--line` y `border-radius` del prototipo.

### 5.5 Variables

Usar **solo** tokens de `:root` (`--ember`, `--paper`, `--iron-plate`, `--crack`, `--line`, …). No inventar `--plate-bg`, `--ink-dim` sueltos salvo en scope de prototipo ya portado al tema.

### 5.6 Workflow sync-theme (OBLIGATORIO)

1. Editar `docs/themes/ope.css` (fuente).
2. `php scripts/sync-theme.php import`
3. `php scripts/sync-theme.php verify` → **`OK CSS: in sync`**
4. `php scripts/check-inline-styles.php` → limpio
5. Comprobación visual en navegador (el browser sirve `cache/themes/theme13/ope.css`, **no** el fuente)
6. Tras cambios en PHP que afecten grafo: `py -m graphify update .`

---

## 6. Portada (`index.php` / `ope-index.xml`)

Estructura objetivo según prototipo v3.2. Código de referencia actual: `index.php` L741–832 (`$isWorld` → `ope_render_region_cards` + `ope-world-bento`), plantilla `docs/themes/ope-index.xml`.

### 6.1 Árbol de la portada

```
PORTADA
├── NAV (hook ope_rol — fija, 66px)
├── HERO CARRUSEL (100vh, 4 slides, nav lateral + dots)
├── GACETA / BITÁCORA (bento, inmediatamente debajo del hero)
├── EL CIELO (categoría)
│     └── Cielos → paneles-región bento (como ope-world-bento)
│           └── [clic] → forumdisplay.php → islas (subforos)
├── OFF TOPIC (categoría, debajo de El Cielo)
│     └── Filas ope-forum / ope-forum en placa (ope-slab / ope-slab)
├── EL PUERTO (ope-harbor — censo, presencia, afiliados)
└── FOOTER
```

### 6.2 Hero carrusel (4 slides)

Reemplaza el `ope-hero` estático actual (`ope-index.xml` L76–86). Altura `100vh`, `min-height: 600px`.

| Slide | Clase BG | Imagen sitio | Contenido |
|---|---|---|---|
| 0 — Portada | `.g-portada` | `hero-mundo.jpg` | Emblema, tagline, CTAs "Zarpar" / "Explorar" |
| 1 — El Cielo | `.g-mundo` | `hero-mundo.jpg` | Ambientación: mundo sobre las nubes |
| 2 — Pueblos | `.g-pueblos` | `hero-pueblos.jpg` | Razas del cielo |
| 3 — Primordiales | `.g-primal` | `hero-primal.jpg` | Bestias Legendarias, pactos |

**Controles:** flechas laterales (`.hero-side`, 9% ancho), dots inferiores, teclado ←/→, swipe táctil. **Sin** scroll horizontal largo; **sin** pin GSAP en hero.

**Nota:** `images/gbe/hero-*.jpg` son **arte del sitio**, no banners de personaje.

### 6.3 Gaceta bento (debajo del hero)

Grid equivalente a `ope-tablon` / `.bento` del prototipo:

| Área grid | Panel | Contenido PHP |
|---|---|---|
| `onrol` | Calendario on-rol | `$ope_rol_season`, día/año, barra progreso |
| `lore` | El mundo ahora | `$ope_lore_title`, `$ope_lore_text` |
| `feed` | Últimas historias | `$ope_latest_posts` |
| `news` | Gaceta del Cielo | Mundo Vivo / noticias rotativas |
| `staff` | El equipo | `$ope_staff_list` |

En OP el tablón comparte fila con el hero (`ope-top`); en OPE el tablón va **debajo** del carrusel full-viewport (un solo scroll vertical).

### 6.4 Cielos — bento de regiones

Misma mecánica que `ope-world-bento` (`ope.css` L719–756). Detección en `index.php`: categoría con islas hijas o nombre conteniendo "mundo"/"cielo" → `$isWorld`.

**Grid objetivo** (clases prototipo → portar como `ope-regions` o renombrar `ope-world-bento`):

```
grid-template-areas:
  "pg pg nh nh"
  "zp zp au au"
  "es es es es"
  "es es es es"
```

| Modifier | Cielo | Asset |
|---|---|---|
| `--verdepuerto` | Verdepuerto | `hero-mundo.jpg` |
| `--ventisquero` | Ventisquero | `hero-pueblos.jpg` |
| `--cielo eléctrico` | Cielo Eléctrico | `Cielo-cielo eléctrico.jpg` (pendiente) |
| `--solsticio` | Solsticio | `hero-primal.jpg` |
| `--estalucia` | Thule | `Cielo-estalucia.jpg` (pendiente) |

Cada panel: imagen 16:9, velo gradiente, nombre `--disp`, descripción expandible en hover, meta `<b>N</b> islas · <b>N</b> temas`.

**Regla:** las islas **no** se listan en portada; viven dentro del Cielo (`forumdisplay.php`).

### 6.7 Estado F2b (julio 2026)

El portado a MyBB exige el **sistema visual completo** del prototipo, no componentes aislados.

| Componente | Prototipo | Producción MyBB | Estado |
|---|---|---|---|
| Tokens `:root` cielo | `--ope-*` claros | `ope.css` `:root` remapeado | ✅ |
| `body.ope-index` | Hero + gaceta bento | `ope-index.xml` + overrides | ✅ |
| Navbar / breadcrumb | OPE global | `#ope-navbar`, `#ope-breadcrumb` | ✅ |
| `ficha.php` Referencia Visual | Stage + tabs | `body.ope-pg-ficha` + modal Gestionar | ✅ |
| `personajes.php` | Grid formación | Layout Referencia Visual sin barra PLAYER | ✅ |
| Tema cielo/noche | `html[data-theme]` | Toggle en `ope_rol.php` + cookie | ✅ |
| `forumdisplay` / `showthread` / editor | Mismo lenguaje OPE | Overrides `body[data-ope-page]` | ✅ |
| Páginas PHP core | Tokens OPE | `body[class*="ope-pg-"]` wave 4 + overrides | ✅ |
| Assets Cielo | `images/gbe/Cielo-*.jpg` | 5 paneles (3 hero + 2 GD) | ✅ |
| Purga codename `ope`→`gbe` | — | Scripts listos, no aplicados | ⏳ F1 |

**Fix jul-2026:** bloques `:root` sin scope en `alertas`/`mensajes`/`revisar-personaje` filtraban tokens OP al resto del sitio — movidos a `body.ope-pg-*`.

**Verificación:** `sync-theme verify` + hard refresh + comparación visual foro/postbit.

### 6.5 Off Topic — slab

Categorías sin islas hijas → `$isWorld = false` → filas `.ope-forum` dentro de `.ope-slab` (`index.php` L794–830). Misma estructura en prototipo: `.ope-slab` + `.ope-forum`.

### 6.6 El Puerto (harbor / censo)

Sección `ope-harbor` (`ope-index.xml` L160+). Cuatro contadores + último Aventurero:

| Métrica | Fuente |
|---|---|
| Aventureros | `$stats['numusers']` |
| Historias | `$stats['numthreads']` |
| Mensajes | `$stats['numposts']` |
| Último Aventurero | `$ope_last_char` (`index.php` L835–849) |

Prototipo: count-up animado con GSAP + `[data-count]` (ver §11).

---

## 7. Cuádrupla visual del personaje

Cada Aventurero tiene **cuatro assets propios**. Configurables por el dueño en **Gestionar** (`ficha.php`).

| Asset | Campo BD | Uso | Ratio |
|---|---|---|---|
| **Banner** | `datos.banner` (JSON en `rol_personajes.datos`) | Cabecera full-bleed de la ficha | **16:9** (1920×1080 recomendado) |
| **Retrato** | `datos.retrato` (JSON) | Columna izquierda ficha Referencia Visual + grid formación | PNG alto transparente (~280×450) |
| **Avatar** | `rol_personajes.avatar` | **Postbit del foro** (cajetilla en cada post) | **280×450** |
| **Icono** | `rol_personajes.icono` | Mini icono en postbit, feed portada, relaciones | 1:1 (64×64) |

**Implementación actual:** modal Gestionar en `ficha.php` (banner, retrato, avatar, icono, firma); `$ficha_art = retrato ?: avatar` para compatibilidad con datos antiguos.

> **NO confundir:** `images/gbe/hero-*.jpg` son decoración del **sitio**. El banner de cada PJ es URL propia en `datos.banner`. Retrato ≠ avatar: el avatar es exclusivo del postbit.

**Placeholder banner:** gradiente por `--aura` (elemento) hasta que el jugador suba URL (`ficha.php` → `.forge-banner-placeholder`).

---

## 8. Ficha (`ficha.php`) — layout v4

Prototipo: `docs/Prototypes/Granblue/ficha.html` v4. Scope CSS: `body.ope-pg-ficha` (→ `body.ope-pg-ficha`).

### 8.1 Estructura vertical

```
bc (breadcrumb, debajo nav 66px)
├── char-banner (16:9 full-bleed, max-height ~420px)
│     ├── img (datos.banner) o placeholder por elemento
│     ├── char-banner-veil (gradiente inferior)
│     └── char-banner-edge (línea --aura / --gold)
├── char-id (margin-top: -48px — tarjeta superpuesta)
│     ├── avatar 72×72 (rol_personajes.avatar)
│     ├── identidad: nombre, alias, chips (elemento, clase, arma, crew, estado, nivel)
│     └── lateral: renombre + vitales PV/EN/PA
├── tabs-wrap (sticky top: 66px)
│     └── 5 pestañas (sin cambio funcional)
└── panes (contenido por tab)
```

### 8.2 Pestañas (conservadas del foro real)

| Tab | ID | Contenido |
|---|---|---|
| Atributos | `tab-crisol` | Stats 12×3, virtudes/defectos, descripción |
| Crónica | `tab-cronica` | Timeline, arcos, posts vinculados |
| Combate | `tab-combate` | Técnicas, estados, heridas, pacto |
| Equipo | `tab-equipo` | Inventario, equipo, nave |
| Relaciones | `tab-relaciones` | Mapa SVG; nodos usan **icono**, no avatar |

`ficha.php` L896–901 — **no renombrar IDs** salvo coordinar JS inline.

### 8.3 Campos visibles en cabecera (OPE)

**Mantener:** elemento, clase, arma, raza, renombre, nivel/rango, crew, PV/Esencia/PA, estado.

**Reskin copy:** berries→Monedas, wanted→renombre, haki→clase, fruta→pacto (opcional), facción OP→gremio/crew.

### 8.4 Descartado respecto a OP

- Retrato grande 240×300 estilo bounty poster
- Poster "WANTED" como layout principal (sustituido por carta de Aventurero + banner)

---

## 9. Convención de imágenes (`images/gbe/`)

### 9.1 Reglas

- Estilo base en prompts: *"painterly anime fantasy, luminous sky, floating islands, sea of clouds, warm golden light, cel-shaded, high detail"*.
- Nomenclatura: `images/gbe/<seccion>-<detalle>.<ext>`
- Referencia **solo** vía `background-image` en CSS o `<img>` — nunca `style` estático
- Prompts se entregan en chat al tocar UI; no auto-generar salvo petición explícita

### 9.2 Aspect ratios

| Tipo | Ratio | Ejemplo |
|---|---|---|
| Fondos sitio / hero slide | 16:9 | `hero-mundo.jpg` |
| Panel Cielo | 16:9 | `Cielo-verdepuerto.jpg` |
| Banner PJ | 16:9 | URL externa por jugador |
| Avatar PJ | 3:4 o 1:1 | URL externa |
| Icono PJ | 1:1 | URL externa |
| Logo / sello | 1:1 PNG α | `crest-eternal.png` |

### 9.3 Inventario de assets globales

| Archivo | Estado | Uso |
|---|---|---|
| `crest-eternal.png` | ✅ | Logo navbar, emblema hero |
| `hero-mundo.jpg` | ✅ | Slide portada/mundo, Verdepuerto |
| `hero-pueblos.jpg` | ✅ | Slide pueblos, Ventisquero |
| `hero-primal.jpg` | ✅ | Slide primals, Solsticio |
| `cloud-layer-1.png` | ✅ reserva | No usado en hero v3.2 |
| `Cielo-cielo eléctrico-Cielo.jpg` | ✅ | Panel Cielo Eléctrico (gradiente GD) |
| `Cielo-estalucia.jpg` | ✅ | Panel Thule (gradiente GD) |
| `fondo-amanecer.jpg` | ⏳ | Body background (sustituir `images/ope/fondo.jpg`) |

### 9.4 Prompt plantilla (nuevo asset)

```
[subject específico], painterly anime fantasy,
luminous sky, floating islands, sea of clouds, warm golden light, cel-shaded,
high detail, 16:9, no text, no watermark
```

Ejemplo Cielo: *"Cielo Eléctrico Cielo, frozen archipelago with ether crystal spires rising from cloud sea, aurora borealis, painterly anime fantasy style…"*

---

## 10. Patrones de reskin (OP → OPE)

### 10.1 Los 5 patrones de diseño

| Patrón OP (legado) | Reskin OPE | Página / componente |
|---|---|---|
| Bounty Poster (ficha) | **Carta de Aventurero** (banner 16:9 + identidad compacta) | `ficha.php` |
| Bitácora de navegación (posts) | **Diario de vuelo** | postbit, hilos RP |
| Mapa del mundo (categorías) | **Carta celeste de Cielos** | `index.php`, bento regiones |
| Documento World Government | **Edicto Imperial / Archivo de la Orden** | facciones, lore staff |
| Tablón de contratos (misiones) | **Órdenes del Cielo** | `tablon-misiones.php` |

### 10.2 Terminología y sistemas

| One Piece: Eternal | One Piece: Eternal |
|---|---|
| Bounty / recompensa | Renombre de Aventurero |
| Berries | Monedas |
| Fruta del Diablo | Pacto Primordial (opcional, raro) |
| Haki | Clase / Job |
| Tripulación | Crew |
| Barco | Aeronave (`nave_json`) |
| Yonko / Marines | Cielos / Imperio / Órdenes |
| Mar / Grand Line | Cielo / Cielos / Thule |
| Islas (mares) | Islas (dentro de Cielo) |
| Calor (heat) | Renombre / fama |
| OPE Eternal (bot) | Lyria |
| `images/ope/` | `images/gbe/` |
| `--fac-pirata`, `--fac-marine`… | `--fac-Aventurero`, `--fac-imperio`… |

### 10.3 Clases CSS (portado gradual)

| OP (actual) | OPE (objetivo) | Notas |
|---|---|---|
| `ope-hero` | `ope-hero` + carrusel | Reestructura `ope-index.xml` |
| `ope-world-bento` | `ope-regions` | Misma grid, nuevos modifiers |
| `ope-region--east-blue` | `ope-region--verdepuerto` | Por Cielo |
| `ope-slab` / `ope-forum` | `ope-slab` / `ope-forum` | Off Topic |
| `ope-harbor` / `ope-census-*` | conservar o alias `ope-*` | Censo |
| `forge-banner` | `char-banner` | Alinear con prototipo v4 |
| `ope-pg-ficha` | `ope-pg-ficha` | Tras F1 |

---

## 11. Librerías JS y animación

### 11.1 Permitido

| Librería | Uso | Dónde |
|---|---|---|
| **GSAP 3** + **ScrollTrigger** | Reveals on scroll (`.reveal`), count-up censo (`[data-count]`) | Portada (`index.php` / inline post-`ope-index.xml`) |
| **jQuery** | MyBB core | Heredado |
| JS vanilla | Carrusel hero (slides, dots, teclado, swipe) | Portada |

**Patrón reveal (prototipo index L444–454):**

```javascript
gsap.registerPlugin(ScrollTrigger);
gsap.utils.toArray('.reveal').forEach(function(el){
  gsap.to(el, {opacity:1, y:0, duration:.8, ease:'power2.out',
    scrollTrigger:{trigger:el, start:'top 90%'}});
});
```

Respetar `prefers-reduced-motion: reduce` → mostrar elementos sin animar.

### 11.2 Prohibido / descartado

| Librería / efecto | Motivo |
|---|---|
| **Lenis** (smooth scroll) | Scroll pesado, sensación artificial |
| **GSAP pin** en hero | Scroll hijacking |
| **Efecto nubes** separándose al scroll | Descartado en prototipo v3.2 |
| **VanillaTilt** en ficha v4 | No usado; solo reconsiderar en cards opcionales |
| Parallax agresivo | Anti-AI / anti-slop; priorizar CSS |

### 11.3 Tema claro/oscuro (JS)

Toggle en navbar: alterna `document.documentElement.dataset.theme` entre `cielo` y `noche`. Persistir en `localStorage` (`ope-theme`) al portar a MyBB.

---

## Apéndice A — Checklist pre-ship (página o portada)

- [ ] Clases HTML resuelven (global o `body.ope-pg-*` / `body.ope-pg-*`)
- [ ] Scaffolding pegado si página nueva
- [ ] Sin `<style>` ni `style="..."` estáticos
- [ ] `php scripts/check-inline-styles.php` limpio
- [ ] `php scripts/sync-theme.php import && verify` → OK CSS in sync
- [ ] Comparación visual con prototipo o página hermana
- [ ] Si portado desde prototipo: checklist `docs/AGENTES-Y-HERRAMIENTAS.md` §2 completo
- [ ] `prefers-reduced-motion` respetado
- [ ] Tríada PJ no usa assets `hero-*.jpg` del sitio
- [ ] Prompts de imagen documentados si faltan assets

---

## Apéndice B — Referencias de archivos

| Archivo | Rol |
|---|---|
| `docs/AGENTES-Y-HERRAMIENTAS.md` | Protocolo agentes (Cursor, OpenCode, Antigravity) |
| `docs/ANTIGRAVITY.md` | Prompt arranque Antigravity |
| `AGENTS.md` | Resumen reglas raíz del repo |
| `docs/Prototypes/Granblue/index.html` | Prototipo portada v3.2 |
| `docs/Prototypes/Granblue/ficha.html` | Prototipo ficha v4 |
| `docs/themes/ope.css` | Tema fuente (4215+ líneas) |
| `docs/themes/ope-index.xml` | Plantilla MyBB portada |
| `index.php` | Lógica categorías Cielo/Off Topic |
| `ficha.php` | Ficha personaje, cuádrupla visual, tabs Referencia Visual |
| `inc/plugins/ope_rol.php` | Navbar, hooks, helpers región |
| `scripts/sync-theme.php` | Import/verify CSS → cache |
| `scripts/check-inline-styles.php` | Linter estilos inline |
| `images/gbe/` | Assets globales del sitio |

---

*Última actualización: julio 2026 — v1.2 + F2b cerrado + §7 cuádrupla visual.*
