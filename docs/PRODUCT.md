# Product — Granblue Fantasy: Eternal

## Register

product

## Users

Roleplayers hispanos 18+, comunidad adulta que busca un foro de rol play-by-post con ambientación de fantasía aérea (Skydoms, skyfarers, Bestias Primarias) y lore original. Vienen a crear personajes, unirse a crews, narrar historias colaborativas y usar mecánicas de combate, progresión, misiones e inventario. Buscan una experiencia pulida, inmersiva y visualmente coherente — referencia de nivel: [Granblue Fantasy Relink](https://relink.granbluefantasy.jp/) (claridad acuarela, oro/bronce, serif elegante).

## Product Purpose

**Granblue Fantasy: Eternal** es un foro de rol play-by-post ambientado en un cielo de islas flotantes inspirado en Granblue Fantasy, con historia y personajes propios. Combina MyBB (foro, autenticación, narrativa) con un backend propio de mecánicas RPG (fichas, inventario, economía en rupies, técnicas, combate por turnos, crews, misiones).

El objetivo es ofrecer la profundidad mecánica que tenía One Piece Eternal (combate, tripulación/crew, progresión, mundo vivo) con una identidad visual y narrativa nueva: cielo, éter, Gremio, Skydoms.

## Brand Personality

Luminoso, épico, acuarela-sobre-nubes. Nunca infantil saturado ni corporativo genérico.

- **Voz:** cronista del Gremio — elegante, evocadora, con urgencia de aventura. El foro habla como quien narra rutas entre islas.
- **Tono:** serio pero esperanzador. Peligro en el horizonte, pero el cielo sigue abierto.
- **Copy:** verbos de navegación (zarpar, explorar, sellar, reclamar). Errores claros sin disculparse. Estados vacíos invitan a crear el primer skyfarer.

## Anti-references

- No neobrutalismo OP (bordes negros 2px, sombras duras, Big Shoulders Display)
- No glassmorphism genérico ni Bootstrap azul
- **No botones pill/cápsula** (`border-radius` > 12px en CTAs) — usar rectangulares 8px (§4.4 DESIGN)
- No copiar personajes canónicos de Granblue Fantasy (solo Skydoms/universo reconocible + historia propia)
- No parecer plantilla MyBB sin identidad

## Design Principles

1. **Relink, no plantilla:** paleta cielo claro + oro + acuarela; tipografía Cinzel / Cormorant / Spectral (ver `DESIGN-GRANBLUE-ETERNAL.md`).
2. **Un elemento firma por pantalla:** hero carrusel en portada, banner 16:9 en ficha, bento de Skydoms en índice.
3. **Tríada visual del personaje:** avatar + banner 16:9 + icono por skyfarer (no reutilizar `hero-*.jpg` del sitio como banner de PJ).
4. **Cohesión por scope CSS:** cada página PHP usa `body.ope-pg-<slug>`; portada usa `body.gbe-index`.
5. **Botones rectangulares:** sin pills/cápsulas — `border-radius: 8px` en CTAs (DESIGN §4.4).
6. **Degradación elegante:** si falla una API de juego, el foro sigue legible.

## Themes

| Tema | Uso |
|---|---|
| **cielo** (default) | Fondo claro, acuarela, día en el puerto |
| **noche** | Oscuro índigo, faros de éter, UI nocturna |

Selector visual en navbar (migración pendiente de `eternal/rojo/...` OP a `cielo/noche`).

## Accessibility & Inclusion

- Contraste mínimo AA en texto sobre fondo claro
- Foco visible por teclado
- `prefers-reduced-motion` respetado
- Navegación funcional sin JavaScript (lectura de posts)

## Documentación relacionada

| Doc | Contenido |
|---|---|
| `docs/AGENTES-Y-HERRAMIENTAS.md` | Protocolo anti-portado parcial (Cursor, OpenCode, Antigravity) |
| `docs/ANTIGRAVITY.md` | Prompt de arranque para Gemini IDE |
| `docs/DESIGN-GRANBLUE-ETERNAL.md` | Fuente de verdad visual + §5 scaffolding PHP |
| `docs/PLAN-MAESTRO-GRANBLUE-ETERNAL.md` | Visión, fases, mecánicas |
| `docs/Prototypes/Granblue/index.html` | Referencia visual portada aprobada |
| `docs/references/relink.granbluefantasy.jp/` | Capturas oficiales Relink |
