---
name: I-Forge — Estilo "Foundry Brutalism" (carpeta NeoBrutalism)
role: Fuente de verdad de ESTE estilo visual (equivale a DESIGN.md + PRODUCT.md, pero de esta dirección)
status: activo
implementacion: forge.css (tokens y componentes reales)
fonts:
  display: "Big Shoulders Display (600–900)"
  data: "Space Mono (400/700)"
  body: "Archivo (400–700)"
colors:
  iron: "#1b1d22"          # base estructural oscura (hierro fundido frío)
  iron-plate: "#24272e"    # placa elevada
  iron-hi: "#31353d"       # placa clara / relieve
  iron-edge: "#0d0e11"     # borde profundo / cabeceras
  rivet: "#565b64"         # líneas de relieve / remaches
  concrete: "#d7d3c6"      # superficie de LECTURA clara (hormigón)
  concrete-2: "#cbc6b6"    # hover de hormigón
  concrete-line: "#b3ad9c" # divisores sobre hormigón
  ink: "#161512"           # texto sobre hormigón
  paper: "#e9e6dd"         # texto sobre hierro
  paper-dim: "#a9a599"     # texto secundario sobre hierro
  ash: "#7f7a6d"           # texto terciario / metadatos
  ember: "#e0641f"         # acento de marca / interacción (brasa)
  ember-hi: "#f2842f"      # hover de brasa
  patina: "#5f8a6a"        # estado positivo (pátina de bronce)
  crack: "#c14a29"         # estado negativo / peligro (grieta al rojo)
heat_scale:                # ESCALA DE CALOR = escala de RANGO (elemento firma)
  E: "#6b6f78"   # h1  frío (hierro apagado)
  D: "#9a6b4e"   # h2
  C: "#c14a29"   # h3  rojo mate
  B: "#e0641f"   # h4
  A: "#ef8b1e"   # h5
  S: "#f4b02f"   # h6  amarillo
  SS: "#f8cf4f"  # h7
  M: "#fbe488"   # h8
  "M+": "#fdf4cf" # h9  blanco incandescente
geometry:
  radius: "0 (recto). Solo círculo (50%) en avatares/medallones."
  border: "2–3px sólido #000"
  shadow: "offset sólido sin blur, p.ej. 3px 3px 0 #000 / 4px 4px 0 #000"
  grid_texture: "rejilla sutil 26px (líneas a rgba(255,255,255,.015))"
---

# I-Forge · Estilo "Foundry Brutalism"

> **Para el agente (yo mismo en el futuro):** cuando el usuario pida algo "en este estilo",
> "estilo de la carpeta NeoBrutalism", "estilo forja / foundry brutalism", "como la ficha/index/foro
> que hicimos", etc. → **lee este archivo entero y síguelo al pie de la letra.** Los tokens vivos
> están en `forge.css` (misma carpeta): enlázalo y usa **solo** sus `var(--...)`, nunca colores ni
> fuentes nuevas. Este documento es a este estilo lo que `docs/DESIGN.md` + `docs/PRODUCT.md` son al
> proyecto general.
>
> **Nota de coherencia:** esta dirección es DISTINTA de la de `docs/DESIGN.md` y
> `docs/frontend/token-system.md` (pergamino + verde bosque + dorado + Permanent Marker). Nació de un
> encargo con "rienda suelta". Comparte con ellos el **producto, la personalidad de marca, las
> mecánicas y el suelo de accesibilidad**, pero **reemplaza** paleta y tipografía. Si el usuario pide
> "el estilo del foro/pergamino", usa aquellos documentos; si pide "este/foundry/NeoBrutalism", usa este.

---

## 1. Concepto — por qué "fragua"

El proyecto se llama **I-Forge**. El estilo se ancla ahí: el foro es una **fragua/fundición** donde se
**forjan** personajes e historias. Ese anclaje da un vocabulario material propio (hierro colado, acero
estampado, remaches, brasa, yunque, colada, temple) y evita el "brutalismo genérico". Es
**neutral de ambientación**: funciona para cualquier mundo oscuro (fantasía, post-apocalíptico,
espionaje, etc.), porque la metáfora es sobre *crear el personaje*, no sobre el lore concreto.

Dos materiales, una temperatura:
- **HIERRO** (oscuro) = estructura: nav, cabeceras, héroes, paneles de meta, pies.
- **HORMIGÓN** (claro) = lectura: posts, descripciones de foro, prosa, biografías.
- **CALOR** = poder/intensidad/riesgo/rango, codificado como color (ver §5).

---

## 2. Producto y personalidad de marca (heredado, sigue vigente)

- **Qué es:** foro de rol *play-by-post* con ambientación oscura y lore original, con mecánicas de
  juego (ficha, stats, inventario, economía, dados). Público hispano 18+.
- **Personalidad:** oscuro, artesanal, misterioso, **industrial/forjado**. Nunca infantil, corporativo
  ni genérico.
- **Voz:** narrativa y envolvente, con crudeza. El foro "habla" como una fragua/cronista.
- **Densidad sin caos:** las pantallas (sobre todo fichas) son densas como un dashboard, pero con
  jerarquía clara. "No dejar huecos" ≠ amontonar: se llena con estructura, no con ruido.
- **Degradación elegante:** las features de juego son un añadido; si la API falla, se sigue navegando.

---

## 3. Anti-patrones (qué NO hacer)

**Clichés de "brutalismo de IA" a evitar** (motivo por el que elegimos la vía "fragua"):
1. Fondo casi-negro con **un** único acento verde ácido o bermellón.
2. Fondo crema (#F4F1EA) + serif de alto contraste + acento terracota.
3. Maqueta tipo periódico con filos de 1px y columnas densas.

**Anti-referencias de producto:** nada de glassmorphism, azules Bootstrap, fondos blancos puros,
sombras con blur, neón, degradados arcoíris, ni estética infantil/saturada. No copiar la identidad
visual de OnePieceGaiden (es referente de *densidad/pulido*, no de estilo).

**Numeración decorativa:** no uses marcadores "01/02/03" salvo que el contenido sea de verdad una
secuencia (proceso, línea de tiempo). Para foros usa identificadores reales (FID-07, TRA-01, #447).

---

## 4. Paleta (usa los tokens de `forge.css`)

Ver el front-matter y `forge.css`. Reglas de uso:
- **Estructura** sobre `--iron` / `--iron-plate` / `--iron-edge`; texto `--paper` / `--paper-dim`.
- **Lectura** (texto largo) sobre `--concrete`; texto `--ink`. Los foros/posts se leen mucho: fondo claro.
- **`--ember`** es el acento de marca e interacción (CTAs, enlaces activos, hover). Con moderación.
- **`--patina`** = positivo/aprobado/online; **`--crack`** = negativo/peligro/rechazado. (Son los
  únicos "colores de estado" y están anclados a la metalurgia: pátina de bronce / grieta al rojo.)
- **Escala de calor `--h1..--h9`** = intensidad/rango (§5). No uses la escala de calor como mera
  decoración: debe significar algo (rango, riesgo, temperatura de forja).
- **Contraste mínimo AA.** El ember/amarillos no se usan para párrafos largos.

---

## 5. Elemento firma — la ESCALA DE CALOR

> El único elemento que hace reconocible el estilo. Concentra aquí la audacia; lo demás, disciplinado.

**El poder se representa como temperatura del metal**, y esa misma escala **es la escala de rango**:

```
E   D   C   B   A   S   SS  M   M+
frío ────────────────────────► blanco incandescente
h1  h2  h3  h4  h5  h6  h7  h8  h9
```

Se reutiliza de forma coherente en todo el producto:
- **Tira de escala** (E→M+) en la ficha, con el rango actual "encendido" (`.heatscale`/`.hs.on`).
- **Badges de riesgo/rango** en foros, sectores y contratos (`.heat-badge` con `var(--hX)`).
- **El Crisol**: los 12 atributos como barras que arden a la temperatura de su rango (ficha).
- **Temperatura de forja**: media global del personaje como letra + medidor.
- **Línea de temple**: borde vertical con gradiente de calor y marcador (marco del retrato).

Otros motivos firma (secundarios, refuerzan la fragua):
- **Yunque estampado** con brasa detrás (marca del héroe del index).
- **Medallones remachados** (vitales) sobre el borde del retrato.
- **Placa de nombre estampada** y **sellos** (Ejemplar Nº, rango en la esquina biselada).
- **Ticker de brasa** (marquesina de estado) y **rejilla sutil** de fondo (papel técnico).

---

## 6. Tipografía

| Rol | Familia | Uso | Regla |
|---|---|---|---|
| Display | **Big Shoulders Display** (700–900) | Nombres, títulos de sección, héroes, cifras grandes | Mayúsculas, condensada, industrial. Es la voz. |
| Datos | **Space Mono** (400/700) | Stats, seriales, fechas, etiquetas, metadatos, códigos | Todo lo que "se estampa": FID-07, timestamps, valores. |
| Cuerpo | **Archivo** (400–600) | Párrafos, posts, descripciones | Grotesca neutra, legible. |

Reglas: Big Shoulders solo en títulos/cifras (nunca párrafos). Mono solo para datos/etiquetas.
No introducir una cuarta familia. `text-shadow: 0 2px 0 #000` en títulos grandes para "estampado".

---

## 7. Geometría, materiales y elevación

- **Radios:** 0 (recto). Solo `50%` en avatares y medallones circulares.
- **Bordes:** 2–3px **sólidos negros (#000)** en todo lo elevado. Nada de 1px.
- **Sombras:** offset sólido **sin blur** (`3px 3px 0 #000`, hover `4px 4px 0`) → placas de acero apiladas.
- **Hover físico:** `translate(-2px,-2px)` + aparece/crece la sombra sólida (efecto "prensa").
- **Detalles de fragua con moderación:** remaches (puntos `--rivet` en esquinas), esquinas biseladas
  (`clip-path`) en piezas protagonistas, "grabado" con `text-shadow` sutil.
- **Textura:** rejilla de 26px muy tenue en el `body` (papel técnico), no invasiva.
- **Jerarquía por contraste de material** (hierro vs hormigón), no por sombras difusas.

---

## 8. Motion

- **Orquestada, no dispersa.** Menos es más.
- Patrones válidos: `reveal-on-scroll` (IntersectionObserver, fade+translateY), ticker/marquesina,
  hover de prensa (translate + sombra), barras de calor/XP que "se calientan" al cargar, resplandor
  de brasa que respira en el héroe.
- **Siempre** respetar `prefers-reduced-motion` (anular animaciones y transiciones). Ya está en `forge.css`.

---

## 9. Voz y copy

- Español, verbos activos. Nada de "Submit/Success". Errores explican qué pasó sin disculparse.
  Estados vacíos invitan a actuar en tono narrativo.
- **Vocabulario de fragua** (neutral de ambientación) — glosario recomendado:

| Genérico | En I-Forge |
|---|---|
| Registrarse / crear personaje | **Forjar** |
| XP / experiencia | **Temple** |
| Energía / maná / aura | **Flujo** |
| Publicación / novedad | **Colada** |
| Rango (E→M+) | Rango = **temperatura/calor** |
| Ciudad base | **Puerto Yunque** (ejemplo) |
| Moneda | **Marcos**, **Fichas de taller**, **Esquirlas** |
| Gremio | **Los Yunques** (ejemplo) |
| Staff / GM | **Fundidor** / narrador |
| Ficha | **Expediente / Placa forjada** |
| Trámite | **Ventanilla del taller** |

- Personaje de ejemplo para prototipos: **Vania Korr** (operativa/rastreadora, rango C, nivel 5).
  Mundo neutral: Puerto Yunque, Bosque Cenizo, Ría de Escoria, Sierra de Hierro, La Escoria Negra.

---

## 10. Sistema de juego (para fichas y mecánicas)

**3 pilares × 4 atributos = 12 stats base**, cada uno con rango E→M+ (valor 1→9 = calor h1→h9):
- **Cuerpo:** Fuerza (FUE), Destreza (DES), Vigor (VIG), Agilidad (AGI)
- **Mente:** Intelecto (INT), Ingenio (ING), Concentración (CON), Percepción (PER)
- **Espíritu:** Caudal (CAU), Control (CTR), Voluntad (VOL), Sensibilidad (SEN)

**Derivadas (mostrar la fórmula a la vista):**
```
PV (Vida)        = VIG × 50        Def. Física   = VIG + AGI + Arm
PA (Flujo)       = CAU × 30        Def. Flujo    = VIG + AGI + Arm + PA÷4
Energía          = VIG × 20        Def. Mental   = VOL + CON
Espíritu         = VOL + CAU       Prec. Física  = DES + AGI
Iniciativa       = AGI × 2 + PER   Prec. Flujo   = CTR + SEN
Movimiento (m)   = 5 + AGI         Salto vert.(m)= 0.5 + AGI × 0.3
Caída segura (m) = VIG × 2         Salto horiz(m)= 1 + AGI × 0.6
Carga máx.(kg)   = FUE × 15        Carga comb(kg)= FUE × 8
```
Calcúlalas en JS desde los rangos base (motor único, sin números "a mano"). Rango↔calor:
`E=1 … M+=9`, color `--h{valor}`.

---

## 11. Catálogo de componentes (definidos en `forge.css`)

Reutiliza SIEMPRE estas clases; añade CSS extra solo con `var(--tokens)`:

- **Layout:** `.wrap` `.mono` `.reveal`
- **Nav / rutas:** `.nav` `.brand` `.nav-a` `.nav-cta` `.nav-user` `.nav-back` `.crumbs` · `.breadcrumb`
- **Botones:** `.btn` `.btn-hot` `.btn-ghost` `.btn-sm`
- **Etiquetas:** `.tag`(`.rank/.act/.line`) `.chip` `.heat-badge`
- **Placas:** `.plate`(`.light`) `.plate-h`(`.t/.c`) `.plate-b` `.slab` · `.shead`(`.code/.rule`)
- **Escala de calor:** `.heatscale` `.hs`(`.on`)
- **Pestañas:** `.tabs` `.tab` `.panel`(`.on`) · `.subtabs` `.subtab`
- **Rail/meta:** `.mod` `.mod-h` `.mod-b` `.srow`(`.l/.v`) `.online` `.ou`(`.s/.g`)
- **Filtros/paginación:** `.filters` `.filt` · `.pager` `.page`(`.on/.nav`)
- **Foro:** `.forum` (subforos) · `.thread`(`.pin/.hot/.lock/.closed`) (hilos)
- **Tema:** `.post` `.post-author` `.post-body` `.post-head` `.post-content` `.post-sig` `.post-actions` `.qreply`
- **Tarjetas/servicios:** `.cards` `.card` `.card-top` `.card-ic` `.card-title` `.card-code` `.card-body` `.card-foot`
- **Derivadas:** `.der-grid` `.der`(`.n/.v/.fx`)
- **Pie:** `.foot` `.foot-in` `.foot-b` `.foot-links` · `.empty-note` `.foot-note`

**Iconografía:** SVG inline de línea (`viewBox="0 0 24 24"`, `fill:none`, `stroke ~2`), color `--h6`/`--ember-hi`. Arte siempre original (nunca IP con copyright).

---

## 12. Retratos de personaje (PNG)

La imagen del personaje es un **PNG** que se integra en un marco forjado:
- Marco de hierro con esquina biselada (`clip-path`), **retroluz de brasa** (radial `--ember`) para que
  un recorte transparente "encaje" en la fragua, **línea de temple** al lado y **medallones remachados**
  (vitales) sobre el borde.
- Técnica de carga con degradación elegante:
```html
<div class="avatar"><img src="assets/<nombre>.png" alt="Retrato de …" onerror="this.remove()"><span>VK</span></div>
```
  Si el PNG falta, se muestran las iniciales sobre el resplandor. Rutas en `assets/`.
- Proporción ~4:5 para el retrato de ficha; cuadrado para avatares de post.

---

## 13. Suelo de calidad (siempre)

- Responsive hasta móvil (columnas colapsan; nav-links se ocultan < 640px).
- Foco de teclado visible (`:focus-visible` en ember, ya en `forge.css`).
- `prefers-reduced-motion` respetado.
- Contraste AA en texto.
- Navegación/lectura funcional sin JS para el contenido; el JS mejora (filtros, tabs, dados).

---

## 14. Checklist antes de dar por buena una pantalla

- [ ] ¿Enlaza `forge.css` y usa **solo** `var(--tokens)` (sin colores/fuentes nuevos)?
- [ ] ¿Hierro para estructura y hormigón para lectura larga?
- [ ] ¿La escala de calor significa algo (rango/riesgo), no decora?
- [ ] ¿Un único elemento firma protagonista; el resto disciplinado?
- [ ] ¿Big Shoulders solo en títulos/cifras, mono solo en datos, Archivo en cuerpo?
- [ ] ¿Bordes 2–3px negros, radios 0, sombras sólidas sin blur?
- [ ] ¿Copy en español con vocabulario de fragua y verbos activos?
- [ ] ¿Responsive, foco visible, reduced-motion, AA?
- [ ] ¿Arte/íconos originales?

---

## 15. Archivos de referencia (misma carpeta)

- `forge.css` — **tokens y componentes reales** (fuente de verdad de valores).
- `index.html` — portada (fragua): héroe, ticker, censo, foros, sectores por calor, feed, rail.
- `ficha.html` — expediente "placa forjada": retrato PNG ancla + Crisol + derivadas.
- `foro.html` — vista dentro de un foro (lista de hilos con riesgo por calor).
- `tema.html` — vista dentro de un tema (posts de rol + tirada + avatares PNG).
- `tramites.html` — ventanillas de servicio (tienda, petición administrativa, etc.).
- `assets/` — retratos PNG de personaje.
