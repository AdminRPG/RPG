---
target: crear-personaje.php (Foundry Brutalism)
total_score: 21
p0_count: 2
p1_count: 2
timestamp: 2026-07-07T14-31-38Z
slug: crear-personaje-php
---
# Impeccable Critique — I-Forge "Foundry Brutalism"

**Method: dual-agent (A: ses_0c306e615ffeFKzxGTY4jfMuPj · B: detect.mjs CLI scan)**

---

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Sin indicadores de carga, sin autoguardado en el wizard, sin tiempo estimado de revisión |
| 2 | Match System / Real World | 3 | La metáfora de fragua funciona. Referencias a "one piece eternal" y el sistema Berry rompen la inmersión de "lore original" |
| 3 | User Control and Freedom | 2 | Sin guardar borrador en creación de personaje. Sin navegación en móvil (<640px) |
| 4 | Consistency and Standards | 3 | Tokens consistentes entre forge.css e iforge.css. Pero DESIGN.md compite con la dirección Foundry Brutalism |
| 5 | Error Prevention | 2 | Validación live de PC balance. Pero sin confirmación antes de submit final, sin autosave, sin chequeo de nombre duplicado hasta round-trip |
| 6 | Recognition Rather Than Recall | 2 | 12 stats con códigos de 3 letras sin tooltips. Fórmulas derivadas invisibles. Virtudes/Defectos: 30+ opciones sin búsqueda |
| 7 | Flexibility and Efficiency | 1 | Sin atajos de teclado, sin acciones masivas, sin paleta de comandos. Wizard lineal sin saltar pasos |
| 8 | Aesthetic and Minimalist Design | 3 | Identidad fuerte, escala de calor genuinamente original. Pero: textura de rejilla 26px decorativa, dos patrones de eyebrow compitiendo |
| 9 | Error Recovery | 2 | Errores descriptivos en español, pero agrupados al inicio sin anclaje al campo ofensor. Sin sugerencias de corrección |
| 10 | Help and Documentation | 1 | Sin tooltips contextuales. Sin explicaciones inline de mecánicas. Sin ayuda visible en el wizard |
| **Total** | | **21/40** | **Acceptable** (20-27). Base sólida en identidad e IA; déficits importantes en feedback, flexibilidad y ayuda |

---

## Anti-Patterns Verdict

### Does this look AI-generated? **Sí, parcialmente.**

**LLM assessment — "El slop sofisticado":**

La ironía central del diseño es que **sabe las reglas y las rompe igual**. El documento `ESTILO-FOUNDRY-BRUTALISM.md` lista explícitamente los tres clústeres estéticos de IA como anti-patrones a evitar, menciona por su nombre los eyebrow patterns, el near-black + single accent, los marcadores 01/02/03. Luego la implementación viola sus propias reglas en al menos cuatro puntos. Esto genera la señal más peligrosa: **"slop sofisticado"** — el diseño que dice "no soy como otras IAs" mientras sigue siendo exactamente eso.

**Hallazgos concretos de slop (Assessment A):**

| Señal | Ubicación | Gravedad |
|--------|-----------|----------|
| **Textura de rejilla decorativa 26px en body** | `iforge.css:26-27`, `forge.css:43`, `crear-personaje.php:358` | ALTA — El tell #1 de Codex: `linear-gradient(...1px, transparent 1px)` × 2 ejes con `background-size`. Racionalizado como "papel técnico" pero visualmente indistinguible de decoración generada por IA. |
| **Eyebrow pattern** (`.iforge-eyebrow`, `.iforge-panel-kicker`) | `iforge.css:151,323` | ALTA — El mismo patrón que el skill lista como "AI grammar". Pequeño texto uppercase monospace con letter-spacing de 3px sobre cada sección. El nombre de la clase es literalmente "eyebrow". |
| **Census bar como hero-metric template** | `iforge.css:164-171` | MEDIA — Número grande + label pequeño + grid de 4 columnas. Estructuralmente idéntico al patrón prohibido, aunque contextualizado como stats del foro. |
| **Border-left como acento de color** | `iforge.css:203` (`.thread.pin`) y `224` (`.iforge-post-staff`) | MEDIA — El patrón "side-stripe border" está en la lista de bans absolutos. |
| **Paleta near-black + single accent** | `--iron (#1b1d22)` + `--ember (#e0641f)` | MEDIA — Es exactamente el clúster estético #2 que el style guide lista como anti-patrón #1. La escala de calor añade 7 colores más, pero como data encoding, no como acentos de superficie. |
| **Letter-spacing -1px en título con clamp(2rem, 5vw, 3.2rem)** | `iforge.css:265` | BAJA — A 375px viewport, equivale a -0.053em, violando el suelo de -0.04em. Letras se tocan en móvil. |
| **Shead rule con repeating-linear-gradient** | `iforge.css:124` | BAJA — Divisor decorativo con gradiente rayado, no información. |

**Brand reflex check:**
- **First-order:** ¿Se adivina la paleta de "foro RPG oscuro"? **Sí.** Near-black + naranja/rojo es el default del training data para fantasía oscura.
- **Second-order:** Con anti-referencias declaradas, ¿sigue siendo reconocible como template? **Sí.** Near-black body + acento vermilion-like + UI monospace + display condensada ES el carril "dark brutalist AI". El forge metaphor aporta un wrapper conceptual, pero visualmente lee como dark-brutalist-template-with-good-craft. La escala de calor es el único elemento genuinamente defensible.

**Deterministic scan (Assessment B):**

El `detect.mjs` detectó cientos de hallazgos, casi todos **falsos positivos** causados por el problema documental #5:

- **Todos los colores del Foundry Brutalism** (`#000`, `#fff`, `#1b1d22`, `#e0641f`, `#d7d3c6`, etc.) se marcan como "fuera de DESIGN.md" porque DESIGN.md define la paleta pergamino/verde/dorado.
- **Todas las fuentes** (Big Shoulders Display, Space Mono, Archivo) se marcan como "fuera de DESIGN.md" porque DESIGN.md define Permanent Marker, Georgia, etc.
- **1 hallazgo real**: `ficha.php:253` — `transition: width` anima una propiedad de layout (layout thrash).

**Conclusión del scan:** El detector confirma que el proyecto tiene dos documentos de diseño compitiendo. Si se actualizara DESIGN.md a la paleta Foundry Brutalism, el 99% de estos hallazgos desaparecerían.

**Visual overlays**: No disponibles — sin navegador ni servidor de desarrollo activo en esta sesión.

---

## Overall Impression

Un diseño con identidad real que se sabotea a sí mismo. La escala de calor, el contraste hierro/hormigón y el vocabulario de fragua son decisiones excelentes. Pero la textura de rejilla, los eyebrow patterns y la paleta near-black+orange meten el proyecto justo en el territorio que su propio style guide dice evitar. El mayor problema no es estético — es estructural: dos DESIGN.md compitiendo, cero navegación móvil, cero persistencia en el wizard de creación de personaje. Arreglar esos tres items subiría la puntuación de 21 a ~28 de un solo golpe.

---

## What's Working

1. **La escala de calor es genuinamente original.** El espectro de 9 pasos desde hierro frío (#6b6f78) hasta blanco incandescente (#fdf4cf) como sistema de rango visual no podría salir de un template genérico. La implementación CSS con `repeat(9, 1fr)` y estado activo (`outline: 3px solid var(--paper)`) es limpia y reutilizable. Es el elemento firma que el design brief pide.

2. **El contraste hierro/hormigón crea jerarquía real.** La división binaria — hierro oscuro para estructura (nav, headers, paneles), hormigón claro para lectura (posts, descripciones) — resuelve el problema de legibilidad del dark mode y crea división de IA sin etiquetas explícitas. Es más efectivo que la mayoría de diseños de una sola paleta.

3. **El copy de estados vacíos funciona.** "El yunque espera tu primera colada" (`iforge.css:341`), "Aún no hay coladas — el yunque espera tu historia" (`iforge.css:290`) — voz activa, temática, invita a actuar. El vocabulario de fragua se gana su lugar aquí.

---

## Priority Issues

### [P0] Sin navegación en móvil — usuarios atrapados
- **Qué:** A viewports < 640px, `.iforge-nav-links` desaparece con `display: none` sin reemplazo. Sin hamburger, sin drawer, sin bottom nav. Un usuario en móvil no puede navegar el sitio.
- **Por qué importa:** Bloquea la realización de cualquier tarea. Para Casey (móvil), abandono inmediato. Es un showstopper.
- **Fix:** Implementar barra de navegación inferior con 4-5 iconos estilo fragua, o drawer con hamburger menu.
- **Suggested command:** `$impeccable adapt` targeting mobile navigation

### [P0] Sin autoguardado en creación de personaje
- **Qué:** El wizard de 7 pasos en `crear-personaje.php` no tiene persistencia entre sesiones. Crash de navegador = pérdida total tras 30-60 min de trabajo.
- **Por qué importa:** Es la acción de mayor inversión en el producto. Perder progreso en el paso 6 es rage-quit garantizado. Para Jordan (novato), destruye la confianza. Para Casey (móvil, interrumpido), hace el wizard inutilizable.
- **Fix:** Implementar autoguardado con localStorage en cada cambio de campo. Al cargar, detectar borrador y ofrecer "Continuar personaje en progreso". Botón visible de "Guardar borrador".
- **Suggested command:** `$impeccable harden` targeting character creation state persistence

### [P1] La textura de rejilla decorativa es un tell de Codex
- **Qué:** El `body` de los tres archivos usa el patrón de rejilla decorativa prohibido: `linear-gradient(...1px, transparent 1px)` × 2 ejes, `background-size: 26px 26px`. Es el defecto #1 de Codex.
- **Por qué importa:** Añade ruido visual persistente a cada píxel, reduce ligeramente el contraste, y es la señal más fuerte de "AI made this" en todo el diseño. Socava la sensación artesanal que el resto del diseño intenta construir.
- **Fix:** Eliminar la textura de rejilla del body. Si se desea textura "papel técnico", usar un PNG de ruido sutil que lea como material, no como código. O simplemente dejar el fondo de hierro sólido — ya tiene suficiente personalidad.
- **Suggested command:** `$impeccable quieter` targeting body background texture

### [P1] Eyebrow patterns implementados a pesar de la prohibición explícita
- **Qué:** `.iforge-eyebrow` (`iforge.css:151`) y `.iforge-panel-kicker` (`iforge.css:323`) son el patrón eyebrow canónico de 2023: texto pequeño uppercase monospace, letter-spacing 2-3px, color acento, como pre-headers de sección. El style guide los prohíbe explícitamente.
- **Por qué importa:** Más allá del slop: crean jerarquía redundante. El usuario lee "FORJA TU LEYENDA" antes de leer el título real. Si todo tiene eyebrow, nada lo tiene. Añade pasos cognitivos innecesarios.
- **Fix:** Eliminar `.iforge-eyebrow` y `.iforge-panel-kicker`. Dejar que los display headings (Big Shoulders Display, ya fuertes) lleven la jerarquía solos. Si se necesita contexto de sección, usar una sola palabra en Space Mono al tamaño de `.plate-h .c`.
- **Suggested command:** `$impeccable quieter` targeting eyebrow/kicker patterns

### [P2] Virtudes/Defectos es un muro de sobrecarga cognitiva
- **Qué:** El paso 4 del wizard presenta 30+ checkboxes de virtudes y defectos simultáneamente, con categorías expandidas por defecto. Cada item tiene: checkbox, nombre, coste/reembolso, descripción, y a veces un input de especificación. La barra de PC balance requiere trackear selecciones entre categorías.
- **Por qué importa:** Es el punto de abandono del wizard. Límite de memoria de trabajo: 4 items; aquí hay 30+. Para Jordan (novato), inprocesable. Para Alex (power user), frustrante tener que scrollear en vez de buscar.
- **Fix:** Categorías colapsadas por defecto. Campo de búsqueda/filtro arriba. Sección "Recomendados para tu raza" con 3-5 opciones destacadas. Feedback visual inmediato al cambiar PC balance. Considerar dividir en dos sub-pasos.
- **Suggested command:** `$impeccable distill` targeting the virtues/defects step

### [P2] Documentos de diseño compitiendo crean confusión
- **Qué:** `docs/DESIGN.md` describe una dirección completamente distinta (pergamino #f4f0e6, verde #2d5a27, dorado #c9a84c, Permanent Marker). La dirección activa es Foundry Brutalism (hierro + hormigón + escala de calor). Ambos se presentan como autoritativos. El detector confirma que 99% de sus hallazgos son falsos positivos por estar chequeando contra el DESIGN.md equivocado.
- **Por qué importa:** Cualquier developer o subagente que lea el proyecto se confundirá sobre qué sistema de diseño es canónico. Causará errores de implementación. PRODUCT.md referencia la paleta antigua en sus principios de diseño.
- **Fix:** Archivar `docs/DESIGN.md` como `docs/DESIGN-ARCHIVED-pergamino.md` con aviso de deprecación, o actualizarlo a Foundry Brutalism. Reflejar la dirección activa en PRODUCT.md. Elegir UNA fuente de verdad.
- **Suggested command:** `$impeccable document` to regenerate DESIGN.md from active implementation

---

## Persona Red Flags

### Alex (Power User — conoce foros, quiere eficiencia)
- **Sin atajos de teclado.** No puede presionar N para nuevo post, R para responder, J/K para hilo siguiente/anterior.
- **Wizard lineal.** No puede saltar al paso 7 para editar historia — debe hacer click en "Siguiente" 6 veces.
- **Sin acciones masivas en hilos.** No puede seleccionar múltiples hilos para marcar como leídos o suscribirse.
- **Sin búsqueda rápida.** Debe navegar manualmente para encontrar un foro o hilo específico.
- **Virtudes/Defectos sin filtro.** Sabe lo que quiere y no puede type-ahead.

### Jordan (Novato — nuevo en play-by-post RPG)
- **Sin onboarding.** Aterriza en el index sin guía de por dónde empezar o qué significa "play-by-post".
- **12 stats con códigos de 3 letras** (FUE, DES, VIG...) sin tooltips. No sabe qué afecta mecánicamente "Caudal" o "Ingenio".
- **Fórmulas derivadas invisibles.** PV = VIG × 50 no se muestra en ningún lado del wizard. Asigna stats a ciegas.
- **Referencia a One Piece en el formulario.** "¿Tiene una D. en su nombre?" es un easter egg de lore que cualquier no-fan encontrará incomprensible en un campo obligatorio.
- **Sin preview de la ficha.** Construye a ciegas y solo ve el resultado tras aprobación del staff (días después).

### Casey (Usuario móvil distraído)
- **Navegación móvil DESAPARECIDA.** A <640px, los nav-links desaparecen sin reemplazo. Atrapado en la página actual.
- **Wizard en móvil es brutal.** 7 pasos con tablas de stats, grids de cartas de raza, muros de checkboxes — nada optimizado para pulgar.
- **Sin persistencia al cambiar de pestaña.** Cambia a Discord, vuelve, página refresca, progreso perdido.
- **Botones de navegación del wizard** al fondo de página, requiriendo scroll en cada paso. Deberían estar sticky o en thumb zone.
- **SVG del yunque** ocupa 300px en el grid del hero. A 375px, empuja info crítica por debajo del fold.

### Ferran (Veterano RPG hispano, 25-35, busca inmersión, odia diseños corporativos)
- **La metáfora de fragua resuena.** "Forjar personaje", "colada", "yunque" — se siente auténtico, no traducido-del-inglés.
- **PERO: `// one piece eternal` en el source** y la pregunta de la "D." rompen la inmersión. "Esto no es un mundo original, es un reskin de One Piece."
- **La escala de calor le funciona.** Es el tipo de elemento diegético distintivo que aprecia.
- **El border-left ember del staff** en posts es señalización de estatus bien ejecutada — worldbuilding sutil a través de UI.

---

## Minor Observations

1. **`.iforge-hero-title` text-shadow** usa `--iron-edge (#0d0e11)` como segunda sombra — es casi idéntico a `#000`. La segunda capa es invisible.
2. **`.iforge-panel-mark`** (`iforge.css:328`) referencia un watermark SVG a coordenadas masivas negativas que pueden renderizar off-screen o solaparse en viewports pequeños.
3. **Botón Discord usa `#5865F2` hardcodeado** — el único color sin token en todo el sistema. Se verá alienígena contra la paleta de fragua.
4. **`.iforge-census-in`** usa `border-left: 1px` mientras todo el sistema usa 2-3px. Rompe la regla brutalista de bordes.
5. **Animación `iforge-breathe`** no tiene fallback estático para `prefers-reduced-motion` — el glow desaparece completamente en vez de mostrarse fijo.
6. **`!important` masivo en inputs** necesario para override de MyBB pero hace el sistema frágil.
7. **Indicadores de estado de hilos** usan tres lenguajes distintos: border-left para pineados, cambio de color de ícono para hot, opacidad para locked.
8. **`ficha.php:253` — `transition: width`** anima propiedad de layout (layout thrash). Único hallazgo real del detector.
9. **La `D.` y el sistema Berry** en crear-personaje.php confirman que el sistema de juego deriva de One Piece, contradiciendo el claim de "lore original" en PRODUCT.md.

---

## Questions to Consider

1. **¿Qué pasaría si la escala de calor fuera el ÚNICO elemento firma?** Sin rejilla, sin eyebrows, sin census bar template. Solo: estructura de hierro, lectura en hormigón, la escala de calor, y el resplandor de brasa. ¿Sería más fuerte que el estado actual de "muchas ideas medio-buenas compitiendo"?

2. **¿De verdad necesita ser near-black?** El style guide lista near-black + single accent como anti-patrón #1 y luego lo implementa. ¿Podría el iron ir a un charcoal más claro (#2a2d35) o introducir una segunda superficie (hierro oxidado rojo-marrón) para escapar del clúster sin perder el carácter industrial?

3. **¿Es esto un RPG de One Piece o de世界 original?** Las referencias en código (`one piece eternal`), la pregunta de la "D.", la moneda Berry, los stats copiados de One Piece Gaiden — cada capa técnica dice One Piece. Pero PRODUCT.md dice "lore original." Esta confusión de identidad contaminará cada decisión de diseño hasta que se resuelva.

4. **¿Por qué los nav-links desaparecen en vez de transformarse en móvil?** La estética brutalista ama la estructura visible. Una barra inferior con 4 botones estilo fragua sería MÁS on-brand que esconder la navegación. ¿Miedo a que los usuarios móviles no merezcan navegación?

5. **¿El wizard de creación está diseñado para quienes ya conocen el sistema?** 12 stats sin tooltips, 30+ virtudes/defectos sin búsqueda, fórmulas invisibles. Si el diseño asume que el usuario ya leyó las guías externas, dilo explícitamente con un paso 0 tipo "Antes de forjar, lee X." Si no, el diseño está fallando al usuario nuevo.
