# DESIGN.md — One Piece Eternal

**Versión:** 2.0
**Proyecto:** One Piece Eternal — Foro de Rol Play-by-Post
**Tema central:** Libertad
**Competidor:** One Piece Gaiden (OPG)
**Plataforma:** MyBB
**Fecha:** Julio 2026

---

## 1. Contexto del Producto

### 1.1 Qué es One Piece Eternal

Un foro de rol play-by-post ambientado en el universo de One Piece. Cada jugador controla uno o más personajes que navegan por el Grand Line, completan misiones, combaten, forman tripulaciones y dejan su marca en un mundo vivo que evoluciona cada 15 días.

**Sistema de juego:** 12 stats en 3 pilares (Cuerpo, Mente, Espíritu), cartas de técnica con sistema de tags, combate con reglas específicas de One Piece (Logia, Haki, Frutas del Diablo, Kairōseki, agua de mar), progresión por PP y Wanted/Bounty, sistema de Mundo Vivo "La Balanza" con NPCs autónomos y cascadas de eventos.

### 1.2 Usuarios

Roleplayers hispanos 18+. Comunidad adulta que busca un universo de One Piece con lore original (universo alternativo). Vienen a:
- Contar historias colaborativas por escrito
- Gestionar personajes con stats, inventario, economía
- Participar en un sistema de juego con mecánicas de One Piece
- Vivir en un mundo que reacciona a sus acciones

### 1.3 Qué hace bien OPG (competidor)

- Estructura del mundo One Piece (East Blue → Grand Line → New World)
- Imágenes custom por cada sección del foro
- Sistemas RPG automatizados (combate, crafteo, viajes)
- ~590 usuarios activos, ~39k mensajes

### 1.4 Qué OPG NO hace bien (oportunidad)

- CSS genérico (Arial, MyBB base)
- Dependencia total de imágenes (sin imágenes, el diseño se desvanece)
- Sin sistema de diseño CSS propio
- Sin identidad visual más allá de las imágenes

### 1.5 Ventaja de One Piece Eternal

- CSS-first (funciona sin imágenes, las imágenes son bonus)
- Neobrutalismo como framework distintivo
- Sistema de tokens reutilizable
- Identidad temática "Libertad" que permea todo el diseño

---

## 2. Dirección Estética: "Libertad"

### 2.1 Concepto: El Mar Abierto

En lugar del genérico "pergamino medieval" o el "pirate flag" cliché, el diseño se inspira en la **vastedad del océano** y la **rebelión contra la autoridad**. Cada elemento del foro se siente como un documento del mundo: fichas de personaje como carteles de recompensa, hilos como bitácoras de navegación, categorías como regiones del mapa mundial.

**Escena física:** La cubierta de un barco pirata en alta mar, de noche. Madera oscura bajo los pies, cuerdas tensas, el sonido de las olas, el cielo estrellado sobre ti. Una linterna de aceite proyecta luz cálida sobre un mapa desplegado sobre la mesa del capitán. Libertad absoluta: cualquier rumbo, cualquier isla, cualquier destino.

### 2.2 Principios de Diseño

1. **El espacio es libertad.** Los layouts respiran. El whitespace no es vacío, es el mar entre las islas.
2. **El contraste es tensión.** La oscuridad del océano contra la luz de la linterna. El negro del Gobierno Mundial contra el oro de los sueños.
3. **Cada elemento tiene un referente.** Nada es decorativo. Si un color, una textura o un patrón no tiene un "por qué" en el mundo de One Piece, no existe.
4. **La tipografía habla como el mundo.** Los títulos gritan como un capitán, el texto fluye como una bitácora, los datos se marcan como un cartel de recompensa.
5. **La identidad es CSS, no imágenes.** El diseño funciona sin imágenes. Las imágenes son bonus, no muletas.

### 2.3 Lo que NO somos

- No somos un foro genérico con imágenes de One Piece pegadas
- No somos una copia de OPG con diferente CSS
- No somos un template de neobrutalismo con colores pirate
- No somos AI slop

---

## 3. Sistema de Color

### 3.1 Escena Física (la que define los colores)

Una noche en el Grand Line. El mar profundo es casi negro (#0a1628). La luna proyecta un camino de plata sobre el agua (#c0c0c0). La linterna del barco proyecta una luz dorada cálida (#d4a017). El madera del casco es oscura y rica (#3e2723). Los carteles de recompensa tienen papel amarillento (#e8dcc8). La sangre de una batalla reciente es roja oscura (#8b0000). El Haki brilla con un púrpura profundo (#4a148c).

### 3.2 Paleta Base

```css
:root {
  /* === SUPERFICIES === */
  --sea-abyss: #070d1a;       /* Mar abisal — body bg, fondo más profundo */
  --sea-deep: #0a1628;        /* Mar profundo de noche — paneles principales */
  --sea-mid: #132238;         /* Mar con luz de luna — cards, módulos */
  --sea-surface: #1a2d4a;     /* Superficie del mar — hover states, elevación */
  --parchment: #e8dcc8;       /* Papel de bounty poster — superficies de lectura */
  --parchment-dark: #d4c5a9;  /* Papel envejecido — fondos de tarjeta secundarios */
  --parchment-light: #f5f0e6; /* Papel limpio — texto sobre fondos oscuros */
  
  /* === ACENTOS === */
  --bounty-gold: #d4a017;     /* Oro del Grand Line — títulos, acentos principales */
  --bounty-gold-light: #e8c547; /* Oro claro — hover, highlights */
  --marine-red: #8b0000;      /* Rojo oscuro de la Marina — peligro, badges de recompensa */
  --marine-red-bright: #c0392b; /* Rojo brillante — alertas, estados negativos */
  --wano-vermillion: #e0641f; /* Bermellón de Wano — CTAs, hover */
  --haki-purple: #4a148c;     /* Púrpura del Haki — badges especiales, Haoshoku */
  --haki-purple-light: #7b1fa2; /* Púrpura claro — hover Haki */
  --sea-green: #1e8449;       /* Verde bosque — éxito, aprobado, vida */
  --sky-blue: #2196f3;        /* Azul cielo — Skypiea, información */
  --sand-gold: #c9a84c;       /* Arena dorada — Alabasta, secundarios */
  
  /* === TEXTO === */
  --ink: #1a1410;             /* Tinta de escribano — texto principal sobre parchment */
  --ink-light: #4a4035;       /* Tinta desvanecida — texto secundario */
  --paper-white: #f5f0e6;    /* Papel limpio — texto sobre fondos oscuros */
  --paper-dim: #a9a599;       /* Papel desvanecido — texto terciario, metadatos */
  
  /* === BORDES Y SOMBRAS === */
  --rope: #8b7355;            /* Cuerda de barco — bordes medios */
  --rope-light: #a89070;      /* Cuerda clara — bordes sutiles */
  --wood-dark: #2c1a0e;       /* Madera oscura — bordes gruesos, sombras */
  --wood-mid: #3e2723;        /* Madera media — fondos de panel */
  --iron: #2c2c2c;            /* Hierro — bordes oscuros, estructura */
  
  /* === SOMBRAS (sólidas, sin blur) === */
  --shadow-sm: 2px 2px 0 var(--wood-dark);
  --shadow-md: 3px 3px 0 var(--wood-dark);
  --shadow-lg: 4px 4px 0 var(--wood-dark);
  --shadow-gold: 3px 3px 0 var(--bounty-gold);
}
```

### 3.3 Significado de Cada Color

| Color | Hex | Referente One Piece | Uso |
|-------|-----|---------------------|-----|
| --sea-abyss | #070d1a | El mar en la noche sin luna | Body background |
| --sea-deep | #0a1628 | El océano profundo | Paneles principales, nav |
| --sea-mid | #132238 | El mar bajo la luz de la luna | Cards, módulos |
| --parchment | #e8dcc8 | El papel de un bounty poster | Superficies de lectura |
| --bounty-gold | #d4a017 | El oro del tesoro, los sueños | Títulos, acentos |
| --marine-red | #8b0000 | La justicia del Gobierno Mundial | Peligro, Wanted |
| --haki-purple | #4a148c | La manifestación de la voluntad | Haki, poder especial |
| --sea-green | #1e8449 | La vida, la naturaleza | Éxito, salud |
| --wano-vermillion | #e0641f | La tierra de Wano | CTAs, hover |

### 3.4 Tema Claro vs Oscuro

**Tema Oscuro (predeterminado):** Para la experiencia principal del foro. El mar de noche, la cubierta del barco, la intimidad de la narrativa.

**Tema Claro:** Para sesiones de lectura largas. El papel del bounty poster como fondo, con acentos del mundo.

### 3.5 Colores de Rango (para badges de facción y Wanted)

```css
:root {
  /* === RANGOS DE WANTED (Piratas) === */
  --rank-grumete: #8b949e;     /* Gris — inicio */
  --rank-conocido: #6e7681;    /* Gris medio */
  --rank-notorio: #58a6ff;     /* Azul — reconocimiento */
  --rank-afamado: #a371f7;     /* Violeta — fama */
  --rank-supernova: #f0883e;   /* Naranja — peligro */
  --rank-peligroso: #e0641f;   /* Naranja oscuro */
  --rank-mundial: #c0392b;     /* Rojo — amenaza global */
  --rank-yonkou: #d4a017;      /* Oro — emperador */
  --rank-rey: #fdf4cf;         /* Oro brillante — leyenda */
  
  /* === RANGOS MARINE === */
  --rank-soldado: #8b949e;
  --rank-oficial: #6e7681;
  --rank-teniente: #58a6ff;
  --rank-capitan: #a371f7;
  --rank-comodoro: #f0883e;
  --rank-contralmirante: #e0641f;
  --rank-vicealmirante: #c0392b;
  --rank-almirante: #d4a017;
  --rank-flota: #fdf4cf;
  
  /* === RANGOS DE STAT (Escala de poder One Piece) === */
  --stat-F: #6b6f78;  /* Pésimo — Civil, grumete */
  --stat-E: #8b7355;  /* Muy bajo — Novato */
  --stat-D: #58a6ff;  /* Bajo — Competente */
  --stat-C: #1e8449;  /* Normal — Marine raso */
  --stat-B: #d4a017;  /* Bueno — Oficial */
  --stat-A: #e0641f;  /* Notable — Supernova */
  --stat-S: #c0392b;  /* Excepcional — Vicealmirante */
  --stat-SS: #4a148c; /* Legendario — Almirante */
  --stat-M: #d4a017;  /* Máximo — Yonko */
  --stat-Mplus: #fdf4cf; /* Trascendente — Leyenda */
}
```

---

## 4. Tipografía

### 4.1 Familias Tipográficas

```css
:root {
  /* Display: títulos de sección, nombres de personaje, banners, carteles de recompensa */
  --font-display: 'Pirata One', 'Impact', sans-serif;
  /* Pirata One = Google Fonts, estilo western/wanted poster */
  /* Evoca los carteles de recompensa del mundo One Piece */
  
  /* Body: texto de posts, descripciones, contenido legible */
  --font-body: 'Special Elite', 'Georgia', serif;
  /* Special Elite = máquina de escribir vieja */
  /* Evoca los documentos del World Government, las bitácoras de navegación */
  
  /* UI: botones, labels, metadatos, navegación */
  --font-ui: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
  /* System stack = legible, no llama la atención, desaparece en la tarea */
  
  /* Data: estadísticas, números de recompensa, cantidades, stats */
  --font-data: 'JetBrains Mono', 'Consolas', 'Monaco', monospace;
  /* Monospace con personalidad, para números de bounty y stats */
}
```

### 4.2 Reglas Tipográficas

| Elemento | Familia | Tamaño | Peso | Transform | Spacing |
|----------|---------|--------|------|-----------|---------|
| H1 (título de página) | --font-display | clamp(2rem, 5vw, 3.5rem) | 400 | uppercase | -0.03em |
| H2 (sección) | --font-display | clamp(1.5rem, 3vw, 2.5rem) | 400 | uppercase | -0.02em |
| H3 (subsección) | --font-ui | 1.125rem | 700 | none | 0 |
| Body text | --font-body | 1rem (16px) | 400 | none | 0 |
| Small / metadatos | --font-ui | 0.8125rem (13px) | 400 | none | 0 |
| Datos numéricos | --font-data | 1rem | 700 | none | 0 |
| Botones | --font-ui | 0.875rem (14px) | 600 | none | 0.5px |
| Labels | --font-ui | 0.75rem (12px) | 600 | uppercase | 1px |
| Badges de recompensa | --font-data | 1.25rem | 700 | none | 0 |

### 4.3 Jerarquía Visual

La jerarquía se logra con **peso de fuente** y **tamaño**, no con uppercase masivo.

- **Uppercase:** Solo en títulos de sección (H1, H2) y badges de recompensa/Wanted
- **Letter-spacing amplio (>1px):** Solo en labels de formulario y badges
- **El body text NUNCA es uppercase**
- **Los links NUNCA son uppercase** (a menos que sean parte de un título)

### 4.4 Line Length

- **Body text:** máximo 75ch (ideal 65ch)
- **Posts del foro:** máximo 70ch
- **Datos / tablas:** sin límite
- **Headings:** sin límite, pero testear en móvil

---

## 5. Espaciado y Layout

### 5.1 Escala de Espaciado

```css
:root {
  --space-1: 4px;    /* Entre icono y texto inline */
  --space-2: 8px;    /* Entre elementos relacionados */
  --space-3: 12px;   /* Padding interior de chips, badges */
  --space-4: 16px;   /* Padding de tarjetas */
  --space-5: 20px;   /* Entre bloques menores */
  --space-6: 24px;   /* Entre bloques mayores */
  --space-8: 32px;   /* Separación de secciones */
  --space-10: 40px;  /* Separación grande */
  --space-12: 48px;  /* Separación máxima */
}
```

### 5.2 Layout Base

```css
.wrap {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

/* Grid principal: contenido + sidebar */
.grid-main {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: var(--space-6);
  padding: var(--space-6) 0;
}

@media (max-width: 960px) {
  .grid-main {
    grid-template-columns: 1fr;
  }
}

/* Grid de tarjetas: auto-fit sin breakpoints */
.grid-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--space-4);
}
```

### 5.3 Responsive

- **Desktop:** 1200px max-width, grid 2 columnas (contenido + sidebar)
- **Tablet (960px):** 1 columna, sidebar debajo del contenido
- **Móvil (640px):** 1 columna, sidebar al final, tipografía reducida

---

## 6. Elevación y Formas

### 6.1 Sombras

Sin blur. Sombras sólidas que simulan capas de papel/madera apiladas.

```css
.elevation-0 { box-shadow: none; }
.elevation-1 { box-shadow: var(--shadow-sm); }
.elevation-2 { box-shadow: var(--shadow-md); }
.elevation-3 { box-shadow: var(--shadow-lg); }
```

### 6.2 Border Radius

```css
:root {
  --radius-sm: 2px;   /* Botones, inputs */
  --radius-md: 4px;   /* Tarjetas, paneles */
  --radius-lg: 6px;   /* Modales, diálogos */
  --radius-full: 50%; /* Avatares, badges circulares */
}
```

**Regla:** Nada supera los 6px de border-radius excepto avatares y badges circulares.

### 6.3 Bordes

```css
:root {
  --border-thin: 1px solid var(--rope);
  --border-medium: 2px solid var(--rope);
  --border-thick: 3px solid var(--wood-dark);
}
```

---

## 7. Componentes

### 7.1 Navbar

```css
.nav {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--sea-deep);
  border-bottom: 3px solid var(--wood-dark);
  height: 56px;
}

.nav-link {
  font-family: var(--font-ui);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--paper-dim);
  text-decoration: none;
  padding: var(--space-2) var(--space-3);
  border-bottom: 2px solid transparent;
  transition: color 0.15s ease-out, border-color 0.15s ease-out;
}

.nav-link:hover {
  color: var(--bounty-gold);
  border-bottom-color: var(--bounty-gold);
}

.nav-link.active {
  color: var(--paper-white);
  border-bottom-color: var(--marine-red);
}
```

**NOTA:** Los links de navegación NO son uppercase. El peso de fuente (600) da jerarquía.

### 7.2 Botones

```css
/* Primario — acción principal */
.btn-primary {
  font-family: var(--font-ui);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--sea-deep);
  background: var(--bounty-gold);
  border: 2px solid var(--wood-dark);
  box-shadow: var(--shadow-sm);
  padding: var(--space-2) var(--space-4);
  cursor: pointer;
  transition: background 0.15s ease-out, box-shadow 0.15s ease-out;
}

.btn-primary:hover {
  background: var(--bounty-gold-light);
  box-shadow: var(--shadow-md);
}

/* Secundario — acción alternativa */
.btn-secondary {
  font-family: var(--font-ui);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--paper-white);
  background: transparent;
  border: 2px solid var(--rope);
  padding: var(--space-2) var(--space-4);
  cursor: pointer;
  transition: border-color 0.15s ease-out, color 0.15s ease-out;
}

.btn-secondary:hover {
  border-color: var(--bounty-gold);
  color: var(--bounty-gold);
}

/* Peligro — acción destructiva */
.btn-danger {
  font-family: var(--font-ui);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--paper-white);
  background: var(--marine-red);
  border: 2px solid var(--wood-dark);
  box-shadow: var(--shadow-sm);
  padding: var(--space-2) var(--space-4);
  cursor: pointer;
}

.btn-danger:hover {
  background: var(--marine-red-bright);
}
```

**NOTA:** Los botones NO tienen hover lift (translateY). El cambio de color y sombra es suficiente.

### 7.3 Tarjetas

```css
.card {
  background: var(--sea-mid);
  border: 2px solid var(--rope);
  box-shadow: var(--shadow-md);
  transition: border-color 0.15s ease-out;
}

.card:hover {
  border-color: var(--bounty-gold);
}

.card-header {
  background: var(--sea-deep);
  border-bottom: 2px solid var(--rope);
  padding: var(--space-3) var(--space-4);
}

.card-header h3 {
  font-family: var(--font-ui);
  font-size: 1rem;
  font-weight: 700;
  color: var(--bounty-gold);
  text-transform: none;
}

.card-body {
  padding: var(--space-4);
  color: var(--paper-white);
  font-family: var(--font-body);
}
```

**NOTA:** Las tarjetas NO tienen hover lift (translateY). Solo cambia el color del borde. Los headers de tarjeta usan `--font-ui` con peso 700, NO `--font-display` con uppercase.

### 7.4 Badges

```css
/* Badge de Wanted / Recompensa */
.badge-wanted {
  font-family: var(--font-data);
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--sea-deep);
  background: var(--bounty-gold);
  border: 2px solid var(--wood-dark);
  padding: var(--space-1) var(--space-3);
  display: inline-block;
}

/* Badge de facción */
.badge-faction {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--paper-white);
  border: 2px solid var(--rope);
  padding: var(--space-1) var(--space-2);
  display: inline-block;
}

/* Badge de stat */
.badge-stat {
  font-family: var(--font-data);
  font-size: 1rem;
  font-weight: 700;
  color: var(--paper-white);
  background: var(--stat-C); /* Color según rango */
  border: 2px solid var(--wood-dark);
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
```

### 7.5 Inputs y Formularios

```css
.input {
  font-family: var(--font-body);
  font-size: 1rem;
  color: var(--ink);
  background: var(--parchment);
  border: 2px solid var(--rope);
  padding: var(--space-2) var(--space-3);
  transition: border-color 0.15s ease-out;
}

.input:focus {
  outline: none;
  border-color: var(--bounty-gold);
}

.input::placeholder {
  color: var(--ink-light);
}

textarea.input {
  min-height: 120px;
  resize: vertical;
  line-height: 1.6;
}
```

### 7.6 Tabs

```css
.tabs {
  display: flex;
  border-bottom: 2px solid var(--rope);
  gap: 0;
}

.tab {
  font-family: var(--font-ui);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--paper-dim);
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  padding: var(--space-2) var(--space-4);
  cursor: pointer;
  margin-bottom: -2px;
  transition: color 0.15s ease-out, border-color 0.15s ease-out;
}

.tab:hover {
  color: var(--paper-white);
}

.tab[aria-selected="true"] {
  color: var(--bounty-gold);
  border-bottom-color: var(--bounty-gold);
}
```

### 7.7 Tooltip

```css
.tooltip {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  color: var(--paper-white);
  background: var(--sea-deep);
  border: 2px solid var(--rope);
  box-shadow: var(--shadow-md);
  padding: var(--space-2) var(--space-3);
  max-width: 250px;
}
```

---

## 8. Patrones de Diseño para One Piece Eternal

### 8.1 Patrón: Bounty Poster (Cartel de Recompensa)

Para fichas de personaje, perfiles, y cualquier vista centrada en un individuo.

```
┌─────────────────────────────────────────────┐
│  WANTED       DEAD OR ALIVE     RECOMPENSA  │
│  ┌─────────────────┐      ╔═══════════╗    │
│  │                 │      ║ ฿ 50,000  ║    │
│  │   [RETRATO]     │      ║           ║    │
│  │                 │      ╚═══════════╝    │
│  └─────────────────┘                       │
│  ─────────────────────────────────────────  │
│  NOMBRE: Monkey D. Luffy                   │
│  ALIAS: "Sombrero de Paja"                 │
│  FACCIÓN: Pirata                           │
│  RAZA: Humano                              │
│  ─────────────────────────────────────────  │
│  [Stats, técnicas, equipo...]              │
└─────────────────────────────────────────────┘
```

**Elementos clave:**
- Borde grueso (3px solid var(--wood-dark)) con sombra sólida
- Fondo --parchment para el área de lectura
- Header con "WANTED · DEAD OR ALIVE" en --font-display
- Recompensa en badge grande con --font-data
- Retrato con borde de madera
- Datos en --font-body (Special Elite)

### 8.2 Patrón: Bitácora de Navegación (Posts)

Para hilos de rol, posts, y contenido narrativo.

```
┌─────────────────────────────────────────────┐
│  [Avatar] Nombre del Personaje    #001      │
│  Hace 2 horas · East Blue · Foosha Village  │
│  ─────────────────────────────────────────  │
│                                             │
│  El viento soplaba con fuerza cuando        │
│  Luffy subió a la cubierta del barco.       │
│  El olor a sal y aventura llenaba el        │
│  aire mientras el Going Merry cortaba       │
│  las olas del East Blue...                  │
│                                             │
│  ─────────────────────────────────────────  │
│  [Responder] [Citar] [Reportar]             │
└─────────────────────────────────────────────┘
```

**Elementos clave:**
- Fondo --parchment para el contenido (lectura cómoda)
- Header con fondo --sea-deep
- Borde de cuerda (--rope)
- Texto en --font-body (Special Elite)
- Metadatos en --font-ui pequeño

### 8.3 Patrón: Mapa del Mundo (Categorías del Foro)

Para la página principal y las categorías del foro.

```
┌─────────────────────────────────────────────┐
│  EAST BLUE                                  │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐      │
│  │ Foosha  │ │ Logue-  │ │ Syrup   │      │
│  │ Village │ │ town    │ │ Village │      │
│  │ [desc]  │ │ [desc]  │ │ [desc]  │      │
│  │ 45 msgs │ │ 128 msgs│ │ 67 msgs │      │
│  └─────────┘ └─────────┘ └─────────┘      │
├─────────────────────────────────────────────┤
│  GRAND LINE — PARADISE                      │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐      │
│  │ Alabasta│ │ Skypiea │ │ Water 7 │      │
│  │ ...     │ │ ...     │ │ ...     │      │
│  └─────────┘ └─────────┘ └─────────┘      │
└─────────────────────────────────────────────┘
```

**Elementos clave:**
- Header de región en --font-display, uppercase, --bounty-gold
- Cards con fondo --sea-mid, borde --rope
- Sin hover lift, solo cambio de borde a --bounty-gold
- Grid auto-fit con minmax(280px, 1fr)

### 8.4 Patrón: Documento del World Government (Facciones)

Para secciones oficiales: reglas, facciones, sistemas. SOLO para este patrón.

```
┌─────────────────────────────────────────────┐
│  [Sello del Gobierno Mundial]               │
│                                             │
│  GOBIERNO MUNDIAL                           │
│  Departamento de Inteligencia               │
│  ─────────────────────────────────────────  │
│                                             │
│  Clasificación: CONFIDENCIAL               │
│  Distribución: Solo personal autorizado     │
│                                             │
│  [Contenido del documento...]               │
└─────────────────────────────────────────────┘
```

**Elementos clave:**
- Fondo --parchment (como un documento oficial)
- Borde doble: `border: 2px solid var(--wood-dark); outline: 1px solid var(--rope);` — SOLO para este patrón
- Header con sello decorativo
- Tipografía --font-body para el contenido

### 8.5 Patrón: Tablón de Contratos (Misiones)

Para la sección de misiones, contratos, y aventuras disponibles.

```
┌─────────────────────────────────────────────┐
│  TABLÓN DE CONTRATOS                        │
│  ─────────────────────────────────────────  │
│  ┌─────────────────────────────────────────┐│
│  │ [T1] Investigación en las minas        ││
│  │ Contratante: Alcalde de Syrup Village  ││
│  │ Recompensa: ฿ 500,000                 ││
│  │ Peligro: Bajo                          ││
│  │ [Ver detalles] [Aceptar]               ││
│  └─────────────────────────────────────────┘│
│  ┌─────────────────────────────────────────┐│
│  │ [T3] Escolta de mineral valioso        ││
│  │ Contratante: Gremio de Comerciantes    ││
│  │ Recompensa: ฿ 2,000,000               ││
│  │ Peligro: Alto                          ││
│  │ [Ver detalles] [Aceptar]               ││
│  └─────────────────────────────────────────┘│
└─────────────────────────────────────────────┘
```

**Elementos clave:**
- Cards apiladas verticalmente
- Badge de tier (T1-T5) con color de stat
- Recompensa en --font-data grande
- Nivel de peligro con color (verde/amarillo/naranja/rojo)

---

## 9. Estructura del Foro

### 9.1 Organización por Regiones del Mundo

```
ONE PIECE ETERNAL
├── 📰 WORLD ECONOMY NEWS (Noticias / Anuncios)
│   ├── Anuncios oficiales
│   ├── Eventos del foro
│   └── Actualizaciones del sistema
│
├── 🗺️ EAST BLUE (Zona de inicio — T1)
│   ├── Foosha Village (Tutorial / Presentaciones)
│   ├── Loguetown (Misiones T1)
│   ├── Syrup Village (Misiones T1)
│   └── Baratie (Misiones T1)
│
├── 🌊 GRAND LINE — PARADISE (Zona intermedia — T2-T3)
│   ├── Alabasta
│   ├── Skypiea
│   ├── Water 7
│   ├── Thriller Bark
│   └── Sabaody Archipelago
│
├── 🔥 GRAND LINE — NEW WORLD (Zona avanzada — T4+)
│   ├── Whole Cake Island
│   ├── Wano Country
│   ├── Egghead
│   └── Laugh Tale
│
├── ⚓ MARINE / WORLD GOVERNMENT (Facción marina)
│   ├── Marines HQ
│   ├── Impel Down
│   └── Mariejois
│
├── 📋 GESTIÓN DE AVENTURAS
│   ├── Registro de misiones
│   ├── Revisión de recompensas
│   └── Combates & Torneos
│
├── 👤 PERFILES & CREACIONES
│   ├── Fichas de personaje (bounty posters)
│   ├── Organizaciones / Tripulaciones
│   └── Objetos & Crafteo
│
└── 🍺 OFF-TOPIC
    ├── La taberna (Off-topic general)
    └── Sugerencias
```

---

## 10. Errores del Diseño Actual a Eliminar

El diseño actual de I-Forge (CSS en `docs/themes/iforge.css`) tiene los siguientes tells de AI que deben corregirse:

### 10.1 Grid decorativo de fondo (LÍNEA 26-27)

**Actual:**
```css
body{
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);
  background-size:26px 26px;
}
```

**Problema:** Grid de fondo decorativo. La skill lo prohíbe explícitamente.

**Corrección:** Eliminar el `background-image` del body. El fondo debe ser sólido `--sea-abyss`.

### 10.2 Eyebrow/kicker genérico (LÍNEA 151-152)

**Actual:**
```css
.iforge-eyebrow{font-family:var(--mono);font-size:.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--ember-hi);...}
```

**Problema:** Eyebrow con uppercase + letter-spacing 3px. Es el tell #1 de AI.

**Corrección:** Eliminar el componente `.iforge-eyebrow` o reemplazarlo con un badge del mundo (ej: "WANTED · DEAD OR ALIVE").

### 10.3 Hero heading enorme + letter-spacing apretado (LÍNEA 153)

**Actual:**
```css
.iforge-hero-title{...font-size:clamp(3.4rem,11vw,7.5rem);...letter-spacing:-1px;...}
```

**Problema:** 7.5rem es demasiado grande. -1px a 7.5rem equivale a -0.013em, por debajo del floor de -0.04em.

**Corrección:** Reducir a `clamp(2rem, 5vw, 3.5rem)` y cambiar letter-spacing a `-0.03em`.

### 10.4 Radial gradient decorativo (LÍNEA 149)

**Actual:**
```css
.iforge-hero::before{...background:radial-gradient(120% 90% at 82% 40%,rgba(224,100,31,.20),transparent 60%);...}
```

**Problema:** Radial gradient decorativo. Es un tell de Codex/GPT-4o.

**Corrección:** Eliminar el `::before` del hero o reemplazar con un gradiente horizontal que evoque mar→orizonte.

### 10.5 Animación breathe (LÍNEA 161)

**Actual:**
```css
@keyframes iforge-breathe{0%,100%{opacity:.6;transform:scale(.95)}50%{opacity:1;transform:scale(1.05)}}
```

**Problema:** Animación breathe genérica. La skill dice "No bounce, no elastic."

**Corrección:** Eliminar la animación breathe.

### 10.6 repeating-linear-gradient decorativo (LÍNEA 124)

**Actual:**
```css
.iforge-shead-rule{...background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}
```

**Problema:** Línea punteada decorativa. La skill lo prohíbe.

**Corrección:** Reemplazar con línea sólida: `background: var(--rope);`

### 10.7 Hover lift en tarjetas (LÍNEA 286, 388, 410)

**Actual:**
```css
.iforge-card:hover{...transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.iforge-sector:hover{transform:translate(-3px,-3px);box-shadow:6px 6px 0 #000...}
.iforge-region:hover{transform:translate(-3px,-3px);box-shadow:7px 7px 0 #000...}
```

**Problema:** Hover lift idéntico en todas las tarjetas. Es un patrón de AI.

**Corrección:** Eliminar el `transform: translateY`. Solo cambiar el color del borde: `border-color: var(--bounty-gold);`

### 10.8 Uppercase masivo (MÚLTIPLES LÍNEAS)

**Actual:** Navigation links, tags, badges, section headers, labels — todo es uppercase.

**Problema:** El uppercase aparece en más del 50% del UI. La skill dice que debe ser solo en títulos de sección y badges de recompensa.

**Corrección:** Quitar uppercase de: navigation links, tags, badges de facción, labels de formulario. Mantener solo en: H1, H2, badges de recompensa/Wanted.

### 10.9 Card-header con uppercase + display font (LÍNEA 287, 528)

**Actual:**
```css
.iforge-card-header{...font-family:var(--disp);...text-transform:uppercase;...}
.iforge-mod-h-t{font-family:var(--disp);...text-transform:uppercase;...}
```

**Problema:** Si cada tarjeta del foro tiene un header con `--font-display` + uppercase, es el "eyebrow en cada sección".

**Corrección:** Los headers de tarjeta deben usar `--font-ui` con `font-weight: 700` sin uppercase. Solo los headers de REGIÓN del mundo usan `--font-display` + uppercase.

### 10.10 Colores de stat de I-Forge (escala de calor)

**Actual:** Los colores de stat vienen de la "escala de calor" de I-Forge (la fragua).

**Problema:** One Piece Eternal no tiene una "escala de calor". Los colores deben evocar el mundo One Piece.

**Corrección:** Redefinir la escala de stat con colores del mundo One Piece (ver sección 3.5).

---

## 11. Anti-AI Checklist

Antes de entregar cualquier prototipo, verificar:

| # | Check | Pass/Fail |
|---|-------|-----------|
| 1 | ¿El body bg es warm-parchment (#f4f0e6 o similar)? | ❌ → Cambiar a --sea-abyss |
| 2 | ¿Hay grid de fondo (linear-gradient pattern)? | ❌ → Eliminar |
| 3 | ¿Hay eyebrow/kicker genérico (uppercase + wide tracking)? | ❌ → Eliminar o reemplazar con badge del mundo |
| 4 | ¿El heading principal tiene letter-spacing < -0.04em? | ❌ → Corregir a -0.03em |
| 5 | ¿Hay radial gradient decorativo? | ❌ → Eliminar o reemplazar con gradiente con significado |
| 6 | ¿El uppercase aparece en más del 30% del UI? | ❌ → Reducir a solo badges de recompensa |
| 7 | ¿Hay más de 3 familias tipográficas? | ❌ → Consolidar a 3 |
| 8 | ¿Las tarjetas tienen hover lift idéntico (translateY)? | ❌ → Cambiar a hover de borde |
| 9 | ¿Hay animación breathe o pulse genérica? | ❌ → Eliminar |
| 10 | ¿Hay `repeating-linear-gradient` decorativo? | ❌ → Reemplazar con línea sólida |
| 11 | ¿Alguien diría "AI hizo esto" en los primeros 5 segundos? | ❌ → Iterar |

---

## 12. Especificaciones para los 5 Prototipos HTML

### Prototipo 1: Index / Página Principal

**Archivo:** `prototype-index.html`
**Patrón:** Mapa del Mundo + Bounty Board
**Secciones:**

1. **Navbar:** Logo "One Piece Eternal" + links (Inicio, Mundo, Foros, Personajes, Misión) + avatar de usuario
2. **Hero:** Estilo "Wanted" con el nombre del foro, subtítulo "Foro de Rol Play-by-Post", CTAs "Explorar el Mundo" y "Crear Personaje"
3. **Barra de estado:** Presente on-rol (ej: "Verano · Año 725"), usuarios online, último post
4. **Categorías del foro:** Organizadas por regiones (East Blue, Grand Line Paradise, Grand Line New World, Marines, Off-Topic)
5. **Sidebar:** Últimos posts, eventos activos, tirada de dado (D20)
6. **Footer:** Links, créditos

**Elementos específicos de One Piece:**
- El hero NO tiene eyebrow genérico. En su lugar: "WANTED · DEAD OR ALIVE" como parte del diseño del bounty poster
- Las categorías del foro son regiones del mundo, no secciones genéricas
- La barra de estado muestra el "Presente On-Rol" (estación + año del mundo)
- El sidebar incluye un "Tablón de Contratos" con misiones disponibles

**CSS específico del hero:**
```css
.hero {
  background: var(--sea-deep);
  border-bottom: 3px solid var(--wood-dark);
  padding: var(--space-8) var(--space-4);
  position: relative;
  overflow: hidden;
}

.hero-wanted {
  font-family: var(--font-display);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--marine-red);
  border-top: 2px solid var(--marine-red);
  border-bottom: 2px solid var(--marine-red);
  padding: var(--space-2) 0;
  margin-bottom: var(--space-4);
  text-align: center;
}

.hero-title {
  font-family: var(--font-display);
  font-size: clamp(2rem, 5vw, 3.5rem);
  letter-spacing: -0.03em;
  text-transform: uppercase;
  color: var(--bounty-gold);
  text-shadow: 2px 2px 0 var(--wood-dark);
  line-height: 0.95;
  margin-bottom: var(--space-4);
}

.hero-sub {
  font-family: var(--font-body);
  font-size: 1rem;
  color: var(--paper-dim);
  max-width: 65ch;
  line-height: 1.6;
  margin-bottom: var(--space-6);
}
```

### Prototipo 2: Ficha de Personaje (Bounty Poster)

**Archivo:** `prototype-ficha.html`
**Patrón:** Bounty Poster + Dossier
**Secciones:**

1. **Navbar:** Igual que el index
2. **Breadcrumb:** Inicio > East Blue > Foosha Village > [Nombre del personaje]
3. **Bounty Poster:** Retrato + nombre + alias + recompensa + facción + raza
4. **Stats:** 12 stats en 3 pilares (Cuerpo, Mente, Espíritu) con barras de progreso y badges de rango
5. **Técnicas:** Lista de cartas de técnica con tags visibles
6. **Equipo:** Inventario con slots de equipo
7. **Tripulación:** Si pertenece a una, mostrar info
8. **Historia:** Background del personaje

**Elementos específicos de One Piece:**
- El bounty poster es el elemento central, no un dashboard genérico
- Los stats usan la escala de rangos del sistema (F a M+) con colores específicos
- Las técnicas muestran los tags del sistema de cartas ([Akuma], [Haki], [Ittoryu], etc.)
- La recompensa se muestra en --font-data grande con el símbolo de berries (฿)
- El retrato tiene un borde que simula el papel envejecido del bounty poster

### Prototipo 3: Categoría del Foro (Región del Mundo)

**Archivo:** `prototype-categoria.html`
**Patrón:** Región del Mapa + Lista de Hilos
**Secciones:**

1. **Navbar:** Igual
2. **Breadcrumb:** Inicio > East Blue > Loguetown
3. **Header de región:** Nombre de la región + descripción + estadísticas (hilos, mensajes)
4. **Subforos:** Si la región tiene sub-lugares (ej: Loguetown tiene "El Árbol del Golpe", "La Tienda de Armas")
5. **Lista de hilos:** Threads con avatar, título, autor, último post, respuestas
6. **Sidebar:** Información de la región, NPCs importantes, misiones disponibles

**Elementos específicos de One Piece:**
- El header de región tiene un estilo de "cartel de zona" (como los letreros de las islas en One Piece)
- Los hilos muestran el tier de la misión (T1-T5) con color
- El sidebar incluye "NPCs de la región" y "Misiones activas"
- La descripción de la región es narrativa, no funcional

### Prototipo 4: Hilo de Rol (Bitácora de Navegación)

**Archivo:** `prototype-hilo.html`
**Patrón:** Bitácora de Navegación + Posts
**Secciones:**

1. **Navbar:** Igual
2. **Breadcrumb:** Inicio > East Blue > Loguetown > [Título del hilo]
3. **Header del hilo:** Título + tags (tier, región, estado) + participantes
4. **Posts:** Lista de posts con avatar, nombre, fecha, contenido
5. **Formulario de respuesta:** Editor de texto para nuevo post
6. **Sidebar:** Info del hilo (participantes, misiones relacionadas, objetos en escena)

**Elementos específicos de One Piece:**
- Los posts tienen fondo --parchment (como páginas de una bitácora)
- El header del hilo muestra el tier de la misión y la región
- El sidebar incluye "Objetos en escena" (lo que hay en el entorno)
- El formulario de respuesta tiene un botón "Tirar Dados" integrado

### Prototipo 5: Tablero Mundial (La Balanza)

**Archivo:** `prototype-tablero.html`
**Patrón:** Mapa del Mundo + Dashboard de Estado
**Secciones:**

1. **Navbar:** Igual
2. **Header:** "La Balanza — Estado del Mundo" + ciclo actual + fecha
3. **Mapa:** Representación visual de las 13 regiones con indicadores de tensión
4. **Facciones:** Estado de las 9 facciones (reputación, tensión entre facciones)
5. **Eventos activos:** Lista de eventos mundiales en curso
6. **Última crónica:** Extracto de la crónica del mundo (generada por IA)
7. **NPCs activos:** Lista de NPCs importantes y su ubicación actual

**Elementos específicos de One Piece:**
- El mapa NO es una imagen genérica. Es una representación CSS del mundo con nodos y conexiones
- Los indicadores de tensión usan colores (verde = paz, amarillo = tensión, rojo = guerra)
- Las facciones muestran su relación con el jugador (aliado, neutral, enemigo)
- La crónica del mundo se presenta como un "periódico" (World Economy News style)

---

## 13. Diferenciación vs OPG

| Aspecto | OPG | One Piece Eternal |
|---|---|---|
| Enfoque visual | Imágenes custom por sección | CSS-first con imágenes como bonus |
| Estilo | Genérico MyBB + imágenes | Neobrutalismo temático |
| Organización | Por tipo de contenido | Por regiones del mundo |
| Fichas | Sistema RPG propio | Bounty poster + dossier |
| Tipografía | Arial/Open Sans | Pirata One + Special Elite |
| Color | #FFE3A0 (oro claro) + #1E1224 (púrpura oscuro) | Mar profundo + parchment + bounty gold |
| Fortaleza | Automatización, sistemas RPG | Identidad visual, inmersión |
| Debilidad | Diseño sin imágenes = vacío | Más trabajo de diseño inicial |

---

## 14. Recursos

### Google Fonts necesarias

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Special+Elite&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
```

### Iconografía

Usar SVG inline con stroke, sin fill. Estilo lineal, minimalista. No iconos de FontAwesome ni similares.

### Imágenes

Las imágenes son bonus, no requisito. El diseño debe funcionar sin imágenes. Las imágenes deben ser:
- Retratos de personaje: estilo manga/anime, no realismo
- Fondos de región: ilustraciones abstractas o atmosféricas
- Iconos de facción: SVGs simples, no escudos detallados

---

## 15. Notas para el Equipo de Diseño

1. **Este documento es la fuente de verdad.** Si algo contradice lo que ves en los prototipos actuales, este documento gana.

2. **El diseño es CSS-first.** Las imágenes son bonus. Si quitas todas las imágenes, el diseño debe seguir funcionando y siendo identificable.

3. **Cada color tiene un "por qué".** Si no puedes explicar por qué un color está ahí en términos del mundo de One Piece, no lo uses.

4. **La tipografía habla como el mundo.** Los títulos gritan como un capitán, el texto fluye como una bitácora, los datos se marcan como un cartel de recompensa.

5. **El espacio es libertad.** No llenes cada pixel. El whitespace es el mar entre las islas.

6. **Neobrutalismo no es "todo grueso".** Es intencional. Los bordes gruesos y las sombras sólidas son para dar peso y presencia, no para decorar.

7. **One Piece es aventura.** Usa el mar, los sueños, la libertad, los nakama como referentes visuales.

8. **El sistema de juego informa el diseño.** Los stats, las técnicas, las facciones, los NPCs — todo esto debe ser visible y comprensible en la interfaz.

9. **El mundo está vivo.** El "Tablero Mundial" no es un dashboard genérico. Es una ventana a un mundo que cambia cada 15 días.

10. **Libertad es el tema.** Cada decisión de diseño debe pasar el test: "¿Esto se siente libre o se siente genérico?"

---

*Documento generado para el equipo de diseño de One Piece Eternal. Julio 2026.*
