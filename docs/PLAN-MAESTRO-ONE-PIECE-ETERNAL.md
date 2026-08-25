# PLAN MAESTRO — ONE PIECE: ETERNAL

> **Propuesta de reconversión total** del foro (hoy *One Piece: Eternal*, codename `ope`, restos `iforge`) a **One Piece: Eternal**: un foro de rol play-by-post ambientado en un **cielo de islas flotantes** (Cielos, Aventureros, Primordial Beasts) con **historia y personajes propios**, conservando lo que hace grandes a los foros de One Piece.
>
> **Los 6 pilares que NO se negocian:** `Personalización · Combate · Tramas · Misiones · Social · Aventuras`.
>
> **Fuentes de verdad del diseño:** `docs/themes/ope.css` (servido desde `cache/themes/theme13/ope.css`) + `docs/DESIGN-ONE-PIECE-ETERNAL.md`.
>
> **Documentos hermanos:** `docs/DIRECCION-LORE-Y-SISTEMAS.md` (lore + sistemas alto nivel), `docs/PRODUCT.md`.
>
> **Estado:** v3.3 — **F1 cerrado** (purga codename `ope`/`iforge` → `gbe`: archivos legacy eliminados, BD migrada, plugin `ope_rol` activo). **F2b cerrado** (portada, ficha Referencia Visual, personajes, foro). Prototipos HTML cerrados.
>
> **Prototipos:** `docs/Prototypes/Granblue/index.html` · `docs/Prototypes/Granblue/ficha.html`
>
> **Referencia visual oficial capturada:** `docs/references/relink.granbluefantasy.jp/`

---

## 0.2 Estado actual (julio 2026)

### Hecho ✅

| Área | Entregable | Notas |
|---|---|---|
| Decisiones | §0.1 cerrado | Cuestionario resuelto |
| Documentación | `DESIGN-ONE-PIECE-ETERNAL.md`, `PRODUCT.md` OPE | Sustituye docs OP |
| Referencia web | Captura Referencia Visual + `resumen.md` | Dirección claro/acuarela |
| **Prototipo index v3.2** | Hero-carrusel · Gaceta bento · Cielos · Off Topic · censo | `docs/Prototypes/Granblue/index.html` |
| **Prototipo ficha v4** | Banner 16:9 · avatar pequeño · 5 pestañas | Coherente con `ficha.php` |
| Assets globales | `crest-eternal.png`, `hero-*.jpg`, `cloud-layer-1.png` | Decoración sitio, no PJs |
| BD foros | El Cielo → Cielos → islas + Off Topic OPE | `restructure-Cielos.php` |
| Rebrand visible | `bbname`, lore portada | `rebrand-gbe.php` |
| **F2b Portado visual MyBB** | Paridad portada + ficha Referencia Visual + personajes + foro + scaffolding PHP core | `ope.css`, `ope-index.xml`, `index.php`, `ficha.php`, `personajes.php`, templates foro, sync-theme |
| **F2b Foro MyBB** | `forumdisplay` / `showthread` con tokens GBE (postbit 280×450, cabecera hilo, sidebar) | Overrides `body[data-ope-page]` en `ope.css` |
| **F1 Purga codename** | Archivos legacy `inc/ope_*`, `docs/themes/ope*` eliminados; BD migrada; rutas `gbe/deco/*`; scripts one-shot de migración borrados | Codename único `gbe` en código activo. |

### Pendiente ⏳

| Área | Bloqueo / siguiente paso |
|---|---|
| Wizard OPE | F3 — razas, clases, elemento, arma en `crear-personaje.php` |
| BD mecánicas | `rol_clases`, `rol_renombre`, `rol_pactos`, `elemento`, `nave_json` |
| Guías | Stub vacío | Rellenar F7 |
| Trámites / Zona Staff | Stubs vacíos; PHP viejas borradas | Rebuild 0 — ver `docs/SISTEMAS-Y-LORE-GBE.md` |

### Descartado en prototipo ❌

- Nubes que se apartan al scroll (GSAP pin) — sensación artificial
- Lenis smooth scroll — scroll pesado
- Listado de islas en portada — las islas viven **dentro** del Cielo (subforos), no en el índice
- Retrato grande 240×300 en ficha — sustituido por banner 16:9 + avatar pequeño

---

## 0.3 Identidad visual del personaje (CUÁDRUPLA — cerrado)

Cada Aventurero tiene **cuatro assets propios**, configurables por el dueño en Gestión (`ficha.php` → botón **Gestionar**):

| Asset | Campo BD | Uso | Ratio recomendado |
|---|---|---|---|
| **Banner** | `datos.banner` (JSON) | Cabecera de la ficha, **16:9**, custom por PJ | **16:9** (ej. 1920×1080) |
| **Retrato** | `datos.retrato` (JSON) | Columna izquierda ficha Referencia Visual + grid formación (`personajes.php`) | PNG alto transparente (~280×450) |
| **Avatar** | `rol_personajes.avatar` | **Postbit del foro** (cajetilla del personaje en posts) | **280×450** (misma proporción carta) |
| **Icono** | `rol_personajes.icono` | Mini icono superpuesto en postbit, feed portada, chips | 1:1 (ej. 64×64) |

> **No mezclar retrato y avatar:** el retrato es arte de cuerpo completo para la ficha; el avatar es la imagen que aparece en cada post del foro. Compatibilidad: si no hay retrato, la ficha usa avatar como fallback visual.

> Los `hero-*.jpg` de `images/gbe/` son **arte del sitio**, no banners de personaje. El banner de cada PJ es URL propia (Imgur, CDN, etc.) gestionada en Gestión.

**Placeholder en prototipo:** `ficha.html` usa `hero-pueblos.jpg` solo como demo; en producción cada PJ muestra su `datos.banner` o un placeholder con inicial/elemento.


## 0.1 Decisiones cerradas (cuestionario §12 resuelto)

| # | Decisión | Resolución |
|---|---|---|
| A1 | Codename técnico | **`gbe`** (código legado). `ope`/`iforge` → `gbe` |
| A2 | Comunidad | **Reinicio limpio** (truncar PJs OP, empezar de cero) |
| A3 | Orden | **Visual primero** (F2 antes que datos) |
| D1 | Combate | Reskin del motor **+ mecánicas-firma OPE**, con **narrativa apoyada en mecánicas** |
| C2 | Poder único | **Pacto Primordial NO universal** (raro, in-game). Universal = **elemento + arma a elección** |
| B1 | Cielos | **Canónicos de OPE** (Verdepuerto/Mar Ancentral/Ventisquero…) |
| B2 | Islas | **Originales nuestras** (historia y conflictos propios) |
| B3 | Personajes canónicos | **No aparecen** (universo OPE sin su elenco; Lyria solo como bot/lore) |
| B4 | Época | **Presente alternativo**, línea temporal propia e independiente |
| B5 | Moneda | **Monedas** |
| C1 | Razas | **Human, Elfo, Dracónido, Gnomo, Dracónido** (sin raza Primordial jugable) |
| C3 | Elemento en combate | **Mecánica fuerte** (triángulo OPE) que justifica la narrativa |
| C4 | Elemento | **Fijo**, elegido en creación (define identidad del PJ) |
| W | Arma | **Tipo de arma da bonus y define técnicas** disponibles |
| P | Pacto Primordial | **Solo in-game** (pactar/derrotar Primordial en trama, aprobado por staff) |
| E1 | Paleta | **La más fiel al estilo visual OPE** (ver §4.1) + instalar librerías para subir el nivel visual |
| E2 | Tipografía | **Cinzel** (marca) + **Cormorant Garamond** (elegante/watermark) + **Spectral** (cuerpo) |
| E3 | Temas | **Dos temas:** `cielo` (claro, por defecto) + `noche` (oscuro) |
| E4 | Fondo | **Imagen de fondo nueva** (mar de nubes/islas) con velo |
| IMG | Imágenes | **Convención permanente:** en cada entrega, dar **prompts de generación de imágenes** para `/images` y decorar mucho con imágenes (ver §4.6) |

---

## 0. Índice

1. [Visión y posicionamiento](#1-visión-y-posicionamiento)
2. [Los 6 pilares → sistemas](#2-los-6-pilares--sistemas)
3. [Identidad de marca y dirección estética](#3-identidad-de-marca-y-dirección-estética)
4. [Transformación visual: de ope.css a OPE](#4-transformación-visual-de-opecss-a-ope)
5. [El mundo: Cielos y estructura del foro](#5-el-mundo-Cielos-y-estructura-del-foro)
6. [Personalización: razas, clases, poder único](#6-personalización-razas-clases-poder-único)
7. [Combate: el motor que conservamos](#7-combate-el-motor-que-conservamos)
8. [Tramas, misiones y aventuras](#8-tramas-misiones-y-aventuras)
9. [Social: crew, nave y renombre](#9-social-crew-nave-y-renombre)
10. [Arquitectura técnica y purga](#10-arquitectura-técnica-y-purga)
11. [Plan de ruta por fases](#11-plan-de-ruta-por-fases)
12. [CUESTIONARIO — cerrar antes de ejecutar](#12-cuestionario--cerrar-antes-de-ejecutar)

---

## 1. Visión y posicionamiento

### 1.1 Qué es One Piece: Eternal

Un foro de rol PBP donde cada jugador es un **Aventurero**: un aventurero que surca un cielo infinito de islas flotantes a bordo de una aeronave, persiguiendo un sueño. El mundo, la terminología y la mitología son las del universo OPE (Cielos, Primordial Beasts, las razas, Thule como tierra prometida), pero **la historia, la cronología y todos los personajes son originales** — igual que un foro de One Piece usa el mundo de Oda sin jugar con Luffy.

### 1.2 Por qué el universo OPE es el lienzo perfecto para nuestro motor

| Lo que ya tenemos (motor OP) | Encaja en OPE como |
|---|---|
| Combate PV/EN/PA + heridas + snapshots | Combate de Aventureros (igual de riguroso) |
| Tripulación + barco | Crew + **aeronave** (pilar central de OPE) |
| Fruta del Diablo (poder único) | **Pacto con Primordial Beast** (poder único, más flexible) |
| Haki (progresión por tiers) | **Clases / Jobs** (identidad de build de OPE) |
| Wanted/Bounty | **Renombre de Aventurero** (fama por Cielo) |
| Facciones | Imperio / Sociedad / Órdenes / Gremios |
| Mundo Vivo "La Balanza" | El equilibrio del cielo entre Ancestrales, Primordiales y humanos |
| Escala de poder por rangos | Rangos de Aventurero (Novato → Leyenda) |

### 1.3 El diferencial

- **CSS-first** (heredado): funciona sin imágenes, identidad en el sistema de tokens.
- **Automatización real**: creación de PJ, combate calculado, snapshots anti-godmod, mundo vivo — algo que casi ningún foro de OPE hispano tiene.
- **Nicho vacío**: apenas hay foros de rol serios de este estilo en español con sistema de combate propio.

---

## 2. Los 6 pilares → sistemas

Cada pilar se ancla a código existente. La regla: **el motor se conserva, el skin cambia.**

### 2.1 Personalización
- Wizard de creación (`crear-personaje.php`) → razas OPE, clases, elemento, pacto, virtudes/defectos.
- 12 stats en 3 pilares (Cuerpo/Mente/Espíritu) → se conservan; se re-etiquetan si procede.
- Ficha como **carta de Aventurero**: **banner 16:9** + retrato + avatar postbit + icono (cuádrupla por PJ, §0.3).
- Poder único vía **Pacto Primordial** (opcional, panel en ficha; la mayoría sin pacto).

### 2.2 Combate
- Motor `inc/ope_rol_system.php` intacto (PV/EN/PA, heridas localizadas, estados).
- Añadido opcional: **ventaja elemental** (6 elementos OPE) y **Charge/Chain**.
- Parser `[rpgsys]` + dados bloqueados = justicia percibida.

### 2.3 Tramas
- Mundo Vivo `rol_mv_*` reskineado: "El Equilibrio del Cielo".
- Cronología (`--tag-trama`, `--tag-mision`, `--tag-viaje`, `--tag-fic`) se conserva.
- Arco maestro de largo plazo: el secreto de los Ancestrales / Thule.

### 2.4 Misiones
- Tablón de contratos (`tablon-misiones.php`, patrón §8.5 del DESIGN) → **Órdenes del Cielo**.
- Tiers T1–T5 por Cielo (dificultad creciente hacia el Alto Cielo).

### 2.5 Social
- Tripulaciones (`rol_tripulaciones`) → **Crews** con **aeronave propia** (ficha de nave).
- Acompañantes/NPCs secundarios (`rol_npcs_secundarios`) → tripulantes y **summons**.
- Renombre compartido de crew, rivalidades entre crews.

### 2.6 Aventuras
- Estructura del foro por **Cielos** (regiones) con progresión geográfica.
- Viajes entre islas (`inc/ope_rol_viajes.php`) → rutas de aeronave.
- Eventos de mundo vivo que abren nuevas islas.

---

## 3. Identidad de marca y dirección estética

### 3.1 Nombre y concepto

- **Nombre:** One Piece: Eternal
- **Tagline candidato:** *"El cielo no tiene fin. Tu leyenda, tampoco."*
- **Tema central:** de "Libertad" (OP, mar) → **"Horizonte"** (OPE, cielo). El motor emocional pasa de *la rebeldía del mar* a *la vastedad del cielo y el sueño de llegar más lejos*.

### 3.2 La escena física que define el diseño

> En lugar de "la cubierta de un barco pirata de noche" (OP), la escena es: **el amanecer visto desde la cubierta de una aeronave**, sobre un mar de nubes doradas, con islas flotantes recortándose contra un cielo índigo que se vuelve turquesa y oro hacia el horizonte. Latón pulido, madera clara, cristal de Esencia brillando. Vasto, luminoso, épico — no oscuro e íntimo.

### 3.3 Principios (evolución de los de OP)

1. **El cielo es horizonte.** El whitespace es aire, no mar.
2. **Luz cálida sobre índigo profundo.** Contraste amanecer, no noche.
3. **Cada elemento tiene un referente en OPE.** Esencia, Primordiales, aeronaves, Cielos.
4. **La tipografía es aventura clásica.** Épica y legible, no wanted-poster.
5. **La identidad es CSS.** (Se conserva el principio CSS-first.)

### 3.4 Lo que NO somos
- No somos un clon de One Piece con nubes.
- No usamos a Gran/Djeeta/Lyria/personajes canónicos como PJs jugables.
- No somos AI slop (se conserva el Anti-AI Checklist §11 del DESIGN).

---

## 4. Transformación visual: de ope.css a OPE

> El CSS real hoy (`ope.css`) usa una **paleta océano** (`--op-ocean:#10477B`, acentos melocotón `--ember:#FFCB93`) y fuentes **Big Shoulders Display / Space Mono / Archivo**, con scoping por página `body.ope-pg-*`. La reconversión visual toca `:root`, fuentes y copy — la **estructura de componentes se conserva**.

### 4.1 Sistema de dos temas (CERRADO)

Inspirado en la referencia visual (ver `docs/references/relink.granbluefantasy.jp/resumen.md`): índigo cielo + oro amanecer + turquesa Esencia + crema pergamino. Se implementan **dos temas** conmutables reusando el sistema de variables `:root` de `ope.css` (se conservan los nombres de variable, cambian los valores).

**Tema A — "Amanecer" (oscuro, por defecto):** el cielo antes del alba visto desde una aeronave.

```css
:root{
  /* Cielo profundo → superficies (índigo nocturno azulado, no negro) */
  --iron:#131b33; --iron-plate:#1a2547; --iron-hi:#28356b; --iron-edge:#0c1226;
  --rivet:#3f4d86;
  /* Esencia / turquesa (acción secundaria, brillos) */
  --patina:#3ad6cf; --patina-hi:#79f0ea;
  /* Oro amanecer (acción primaria / marca) */
  --ember:#f4bd51; --ember-hi:#ffdd84; --gold:#f4bd51; --gold-hi:#ffdd84; --gold-deep:#c98f2e;
  /* Cielo (secundario frío) */
  --sky:#5aa9ff; --sky-hi:#8ac6ff; --sea:#2f74b5; --sea-deep:#123a66;
  /* Lectura: pergamino de carta celeste */
  --concrete:#f4efe1; --concrete-2:#e7ddc6; --concrete-line:#d3c7a8;
  --paper:#f6f2e7; --paper-dim:#cdc6b2; --ink:#211b10; --ink-2:#4a4030; --ash:#9aa6c2;
  /* Peligro / Primordial hostil */
  --crack:#e0503a; --red:#e0503a; --red-hi:#ff6f57;
}
```

**Tema B — "Cielo Diurno" (claro):** islas flotantes a plena luz, paneles crema con filete dorado.

```css
:root{
  --iron:#dfeaf7; --iron-plate:#eef5fd; --iron-hi:#ffffff; --iron-edge:#c9dbf0;
  --rivet:#a9c2e0;
  --patina:#1fa9a0; --patina-hi:#38d6cf;
  --ember:#e0a12e; --ember-hi:#f4bd51; --gold:#e0a12e; --gold-deep:#b07d1f;
  --sky:#2f8fe8; --sky-hi:#5aa9ff; --sea:#1f6bb0; --sea-deep:#0f4b86;
  --paper:#16233a; --paper-dim:#3d5170; --ink:#16233a; --ash:#5f7794;
  --crack:#cf3f2b; --red:#cf3f2b;
}
```

> Nota: los tonos exactos se afinan en implementación contra una página hermana; estos son el punto de partida canónico.

### 4.2 Tipografía (implementada en prototipo)

| Rol | Fuente | Uso |
|---|---|---|
| Marca / títulos | **Cinzel** | Logo, H1, números de renombre |
| Elegante / watermark | **Cormorant Garamond** | Subtítulos, botones, nombres de foro |
| Cuerpo | **Spectral** | Párrafos, lore |
| Datos | **Space Mono** | Stats, FIDs, fechas, chips |

**Librerías de animación (prototipo index/ficha):**
- **GSAP + ScrollTrigger** — reveals, count-up (sin pin en hero).
- **VanillaTilt** — solo si se reintroduce tilt en cards (no en ficha v4).

**Descartado:** Lenis (scroll lento), efecto nubes separándose al scroll.

### 4.7 Prototipos HTML (fuente de verdad visual hasta portar)

| Archivo | Versión | Estructura clave |
|---|---|---|
| `docs/Prototypes/Granblue/index.html` | v3.2 | Hero-carrusel 4 slides (portada + 3 ambientación) · flechas laterales · Gaceta bento · **Cielos bento** (como `ope-world-bento`) · **Off Topic slab** · censo |
| `docs/Prototypes/Granblue/ficha.html` | v4 | **Banner 16:9** · tarjeta identidad (avatar 72px + chips + renombre/vitales) · tabs: Atributos / Crónica / Combate / Equipo / Relaciones |

**Index — mapa del foro en portada (igual que `index.php` real):**
1. Categoría **El Cielo** → foros hijos = **Cielos** → paneles-región con imagen (`ope_render_region_cards`).
2. Al entrar en un Cielo → **islas** como subforos (`forumdisplay.php`).
3. Categoría **Off Topic** → filas `ope-forum` en placa (`ope-slab`), debajo de El Cielo.

**Ficha — campos visibles en cabecera (OPE):**
- Mantener: elemento, clase, arma, raza, renombre, nivel/rango, crew, PV/Esencia/PA, estado.
- Quitar/reskin: berries→Monedas, wanted→renombre, haki→clase, fruta→pacto (opcional), facción OP→gremio/crew.
- Gestión dueño: avatar + icono + banner (ya en `ficha.php`).

### 4.2b Tipografía legacy (referencia pre-prototipo)

| Rol | Variable | OP actual | **OPE: Eternal** |
|---|---|---|---|
| Display / títulos | `--disp` | Big Shoulders Display | **Cinzel** (serif épico, remates de marca OPE) |
| Subtítulos / alt | — | — | **Marcellus** (serif elegante, para nombres/labels) |
| Cuerpo | `--body` | Archivo | **Spectral** (serif de lectura) o conservar Archivo para UI |
| Datos / números | `--mono` | Space Mono | **Space Mono** (se conserva) |

**Librerías (Google Fonts) — reemplazan el `<link>` de §14 del DESIGN:**

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Marcellus&family=Spectral:ital,wght@0,400;0,600;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
```

Se declaran en `:root`:
```css
--disp:'Cinzel','Marcellus',Georgia,serif;
--disp-alt:'Marcellus',Georgia,serif;
--body:'Spectral',Georgia,serif;
--mono:'Space Mono',Menlo,Consolas,monospace;
```

> **Iconografía:** SVG inline lineal (se conserva la regla del DESIGN). Motivos OPE: brújula celeste, ala, cristal de Esencia, sello de Primordial.

### 4.6 Flujo de imágenes decorativas (CONVENCIÓN PERMANENTE)

El juego vive de su arte. En **cada entrega** que toque UI, el asistente entrega **prompts de generación de imágenes** listos para usar, y el usuario los coloca en `images/gbe/`. Reglas:

- Estilo base para todos los prompts: *"painterly anime fantasy, luminous sky, floating islands, sea of clouds, warm golden light, cel-shaded, high detail"*.
- Nomenclatura: `images/gbe/<seccion>-<detalle>.<ext>` (ej. `images/gbe/fondo-amanecer.jpg`, `images/gbe/Cielo-verdepuerto.jpg`).
- Formatos: fondos `jpg` (grandes), ornamentos/sellos `png` con transparencia.
- Cada imagen se referencia solo con `background-image` en CSS o `<img>`; nunca `style` estático (regla §7.8 del DESIGN).
- Aspect ratios: fondos sitio 16:9, **banner PJ 16:9**, avatar 3:4 o 1:1, **icono 1:1**, sellos globales 1:1, paneles Cielo 16:9.

Los prompts se entregan en el chat (no se generan salvo que el usuario lo pida explícitamente).

### 4.3 Reskin de patrones (los 5 del DESIGN)

| Patrón OP | Reskin OPE |
|---|---|
| Bounty Poster (ficha) | **Carta de Aventurero** (registro del gremio) |
| Bitácora de navegación (posts) | **Diario de vuelo** |
| Mapa del mundo (categorías) | **Carta celeste de Cielos** |
| Documento del World Government | **Edicto Imperial / Archivo de la Orden** |
| Tablón de contratos (misiones) | **Órdenes del Cielo** |

### 4.4 Colores de facción (reemplazan las de OP en `:root`)

Hoy: `--fac-pirata`, `--fac-marine`, `--fac-revolucionario`, `--fac-gobierno`, `--fac-cazarrecompensas`, `--fac-civil`.
Propuesta OPE: `--fac-Aventurero`, `--fac-imperio`, `--fac-sociedad`, `--fac-orden`, `--fac-gremio`, `--fac-libre`.

### 4.5 Proceso técnico del reskin visual
1. Editar `:root` en `docs/themes/ope.css` (paleta + fuentes + facciones).
2. Cambiar el link de Google Fonts (§14 del DESIGN) a las nuevas familias.
3. Renombrar scope `body.ope-pg-*` → `body.<codename>-pg-*` (ver §10).
4. `php scripts/sync-theme.php import && verify` → `OK CSS: in sync`.
5. `php scripts/check-inline-styles.php` limpio.
6. ~~Reescribir `DESIGN-ONE-PIECE-ETERNAL.md` (completado).~~ ✅

---

## 5. El mundo: Cielos y estructura del foro

**Decisiones cerradas (§0.1):** Cielos **canónicos** (Verdepuerto, Ventisquero, Cielo Eléctrico, Solsticio, Thule…), islas **originales** dentro de cada uno.

### 5.1 Portada (`index.php`) — implementación objetivo

```
PORTADA
├── HERO (carrusel ambientación — opcional en PHP o estático)
├── GACETA / BITÁCORA (calendario, lore, feed, news, staff)
├── EL CIELO (categoría)
│     └── Cielos como paneles-región (bento, igual que los 8 mares)
│           └── [clic] → forumdisplay del Cielo → islas (subforos)
├── OFF TOPIC (categoría, debajo de El Cielo)
│     └── Filas de foro (Cafetería, Arte, Sugerencias…)
├── EL PUERTO (censo, presencia, afiliados)
└── FOOTER
```

Código de referencia: `index.php` L741–832 (`$isWorld` → `ope_render_region_cards` + `ope-world-bento`).

### 5.2 Árbol conceptual del foro

```
ONE PIECE: ETERNAL
├── 📰 GACETA DEL CIELO (Noticias / Anuncios / Mundo Vivo)
├── 🌅 [Cielo INICIAL] — Zona de inicio (T1)
│     Islas puerto, gremio, tutorial, presentaciones
├── ☁️ [Cielo MEDIO] — Intermedio (T2–T3)
│     Islas de conflicto, Primordiales menores
├── ⚡ [ALTO CIELO] — Avanzado (T4+)
│     Islas legendarias, Primarchs, umbral de Thule
├── 🏛️ IMPERIO / ÓRDENES (facciones de poder)
├── 📋 Cofradía de Aventureros (misiones, combates, torneos)
├── 👤 REGISTRO DE AventureroS (fichas, crews, aeronaves)
└── 🍺 OFF TOPIC (debajo de El Cielo — charla libre, revisión de PJs)
```

### 5.3 Assets globales del sitio (en `images/gbe/`)

| Archivo | Estado | Uso |
|---|---|---|
| `crest-eternal.png` | ✅ | Logo navbar |
| `hero-mundo.jpg` | ✅ | Slide ambientación / Cielo Verdepuerto |
| `hero-pueblos.jpg` | ✅ | Slide pueblos / Cielo Ventisquero |
| `hero-primal.jpg` | ✅ | Slide primals / Cielo Solsticio |
| `cloud-layer-1.png` | ✅ | Reserva (no usado en hero actual) |
| `Cielo-cielo eléctrico.jpg` | ⏳ | Panel Cielo Eléctrico |
| `Cielo-estalucia.jpg` | ⏳ | Panel Thule |
| `cloud-layer-2.png` | ⏳ | Opcional decoración |

---

## 5 (legacy). Árbol antiguo

```

## 6. Personalización: razas, clases, poder único

### 6.1 Razas jugables (reemplazan `ope_rol_razas()`)

| Raza OPE | Rasgo | Afinidad de stats sugerida |
|---|---|---|
| **Human** | Versátil | Equilibrado |
| **Elfo** | Sentidos agudos, orejas/cola animal | Percepción / Mente |
| **Dracónido** | Cuernos, fuerza física | Cuerpo / Vigor |
| **Gnomo** | Pequeños, longevos, mágicos | Ingenio / Espíritu |
| **Dracónido (Dhoromir)** | Sangre de dragón | Afinidad elemental |

> **Cerrado:** las 5 razas de §0.1 están activas. Sin raza Primordial jugable.

### 6.5 Tríada visual del personaje

Ver **§0.3**. El wizard (`crear-personaje.php`) debe pedir o sugerir avatar + icono; el banner se configura después en Gestión. Placeholder de banner: gradiente por elemento hasta que el jugador suba URL.

### 6.2 Clases / Jobs (reskin de Haki → `inc/ope_rol_clases.php`)

OPE tiene un sistema de clases icónico. Propuesta: cada PJ elige una **clase** (ramas × niveles) que define técnicas disponibles y bonus. Reutiliza la mecánica de progresión por tiers del Haki.

Ejemplos: Sable, Lancero, Arquero, Místico, Sanador, Berserker, Espadachín Oscuro…

### 6.3 Poder único: **Pacto Primordial** (reskin de Fruta del Diablo)

El elemento de **personalización estrella**. Cada PJ puede sellar un **Pacto con una Primordial Beast** (menor), que otorga un poder temático único (como una Fruta, pero:
- ligado a un **elemento** (fuego/agua/tierra/viento/luz/oscuridad),
- con **coste de Esencia** (EN) al invocar,
- escalable por nivel de pacto).

Tabla nueva `rol_pactos`. Reemplaza el sistema de Frutas y su biblioteca.

### 6.4 Elementos (nuevo eje transversal)

Los **6 elementos de OPE** con triángulo de ventaja. **Fijo en creación** (§0.1 C4). Mecánica fuerte en combate (§0.1 C3).

> **Cerrado** — ver §0.1.

---

## 7. Combate: el motor que conservamos

**Principio: no se reescribe el motor.** `inc/ope_rol_system.php` (PV/EN/PA, heridas localizadas `ope_combat_herida_*`, estados `ope_combat_estados`) se conserva y se renombra de codename. Lo que cambia:

| Elemento | Acción |
|---|---|
| PV / EN / PA | Se conservan (EN se re-narrativiza como **Esencia**) |
| Heridas localizadas | Se conservan |
| Estados alterados | Se conservan (reskin de nombres si procede) |
| Snapshots anti-godmod | Se conservan |
| **Ventaja elemental** | **NUEVO** (opcional, ver §6.4) |
| **Charge Attack / Chain Burst** | **NUEVO** (fase posterior): técnica cargada tier-S + combo de crew |

> **Cerrado (§0.1 D1):** reskin + mecánicas-firma OPE con narrativa apoyada en mecánicas.

---

## 8. Tramas, misiones y aventuras

### 8.1 Mundo Vivo → "El Equilibrio del Cielo"
`rol_mv_*` se conserva. Reskin narrativo: la tensión entre **Ancestrales** (precursores), **Primordial Beasts** y las naciones humanas. Eventos cada X días que abren islas, despiertan Primordiales, cambian el mapa.

### 8.2 Misiones → Órdenes del Cielo
`tablon-misiones.php` reskineado. Tiers por Cielo. Recompensa en **Monedas** (reemplaza Berries) + reputación de gremio.

### 8.3 Arco maestro
El gancho de largo plazo (equivalente al "One Piece" como misterio): **Thule y el legado de los Ancestrales**. Objetivo mítico end-game del mundo vivo.

---

## 9. Social: crew, nave y renombre

### 9.1 Crew + aeronave (pilar diferencial de OPE)
`rol_tripulaciones` gana `nave_json`: la **aeronave** con nombre, tipo, mejoras y ficha propia (nueva página `astillero.php`). El barco deja de ser metáfora y se vuelve entidad jugable (mejoras, daño, rutas).

### 9.2 Summons
NPCs secundarios (`rol_npcs_secundarios`) pueden marcarse como **summon** invocable 1×/combate.

### 9.3 Renombre (reskin de Wanted)
`inc/ope_rol_wanted.php` → `renombre`. La fama del Aventurero por Cielo, visible en la carta, con rangos (Novato → Leyenda del Cielo).

---

## 10. Arquitectura técnica

F1 (purga `ope`/`iforge` → `gbe`) está **cerrada**. Direcciones de lore/sistemas: `docs/DIRECCION-LORE-Y-SISTEMAS.md`.

- **Codename:** solo `ope_` / `ope-`.
- **BD:** conservar prefijo `rol_`; próximas tablas/campos en F3+ (`rol_clases`, `rol_pactos`, `elemento`, `nave_json`…).
- **Validación habitual:** `check-inline-styles` + `sync-theme verify` + `graphify update`.

---

## 11. Plan de ruta por fases

| Fase | Nombre | Estado | Contenido | Entregable |
|---|---|---|---|---|
| **F0** | Decisiones | ✅ | Cuestionario §12 | §0.1 |
| **F2a** | Prototipo HTML | ✅ | index v3.2 + ficha v4 + assets globales | `docs/Prototypes/Granblue/` |
| **F2b** | Portado visual MyBB | ✅ | `ope.css`, templates foro, `index.php`, `ficha.php`, `personajes.php`, sync-theme | Portada, ficha Referencia Visual, foro GBE, scaffolding PHP core |
| **F2c** | DESIGN + PRODUCT OPE | ✅ | `DESIGN-ONE-PIECE-ETERNAL.md`, `PRODUCT.md` | Fuente de verdad §5 |
| **F1** | Purga codename | ✅ | Borrado `inc/ope_*` + `docs/themes/ope*`; BD `ope_*`; scripts one-shot eliminados | Codename `gbe` único; plugin `ope_rol` |
| **F3** | Datos y catálogos | ⏳ | razas, facciones, clases, elementos, Cielos en wizard | `crear-personaje.php` OPE |
| **F4** | Poder único + combate | ⏳ | `rol_pactos`, triángulo elemental en motor | Build OPE |
| **F5** | Mundo y misiones | ⏳ | Mundo Vivo reskin, Órdenes del Cielo | Aventuras |
| **F6** | Social | ⏳ | aeronave, summons, renombre | Crews con nave |
| **F7** | Contenido | ⏳ | reseed lore, guías, gaceta | Mundo poblado |
| **F8** | QA y lanzamiento | ⏳ | validación §10, reinicio comunidad | Foro listo |

**Orden acordado:** visual primero (F2) → datos (F3) → purga puede ir en paralelo tras aprobar prototipos.

> **Reinicio limpio (A2):** truncar PJs OP antes de F8; no migrar personajes One Piece.

---

## 12. CUESTIONARIO — RESUELTO ✅

> Todas las decisiones de esta sección están **cerradas** y consolidadas en [§0.1](#01-decisiones-cerradas-cuestionario-12-resuelto). Se mantiene el listado como índice de lo que se decidió.

**A. Marca y alcance**
- A1. Codename técnico (`ope`→ ?).
- A2. ¿Reinicio de comunidad o migrar PJs existentes?
- A3. Orden de ejecución (visual primero vs purga primero).

**B. Mundo y ambientación**
- B1. Cielos canónicos vs originales.
- B2. Islas canónicas vs originales.
- B3. Personajes canónicos OPE: ¿existen como lore/NPC o no aparecen?
- B4. Época/punto de partida de la cronología.
- B5. Moneda (Monedas u otra).

**C. Personalización**
- C1. Razas jugables (selección múltiple).
- C2. Sistema de poder único (Pacto Primordial / Weapon Grid / Clases / híbrido).
- C3. Elementos: ¿mecánicos o narrativos?
- C4. ¿Elemento fijo o cambiable?

**D. Combate**
- D1. Solo reskin del motor vs añadir mecánicas-firma OPE (elemento+charge/chain).

**E. Visual**
- E1. Dirección de paleta.
- E2. Tipografía (serif épico vs conservar sans).
- E3. Modo claro/oscuro.
- E4. Imagen de fondo vs CSS-first puro.

---

## 13. Próximos pasos (elección del equipo)

> **Reglas de portado visual:** `docs/DESIGN-ONE-PIECE-ETERNAL.md` §0 — evitar portados parciales (incidente jul-2026).

Tras cerrar prototipos, el camino natural es **F2b (portar a MyBB)**. Alternativas válidas:

1. **Portar index + ficha al tema real** (`ope.css`, templates, `ficha.php`) siguiendo prototipos.
2. **Purga codename `ope`→`gbe`** antes del portado (más trabajo upfront, menos deuda).
3. **Generar assets Cielo faltantes** (Cielo Eléctrico, Thule) y revalidar prototipo.
4. **Reestructurar foros en BD** (categorías El Cielo + Cielos + islas + Off Topic).
5. **Wizard OPE** (`crear-personaje.php`) con razas/elemento/clase/arma.

---

*Última actualización: julio 2026 — prototipos index v3.2 + ficha v4. Tríada PJ: avatar + banner 16:9 + icono (custom por personaje).*
