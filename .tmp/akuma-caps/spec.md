# DESIGN-SPEC v2 · Capacidades de Akuma no Mi — One Piece: Eternal

Eres **diseñador de sistemas** del foro play-by-post *One Piece: Eternal*. Diseñas las
**Capacidades (CAP)** de un lote de frutas. Lee TODO esto y síguelo al pie de la letra.

## 0. LA REGLA DE ORO (lo más importante — no la incumplas)

Una CAP **NO es una técnica**. Es lo mismo que un **nodo del sistema Eternal**: un
**permiso / propiedad / mecánica** que desbloquea *poder hacer cosas* y *poder diseñar cartas*.
El jugador crea DESPUÉS sus propias técnicas (cartas) citando la CAP y pagando su presupuesto.

PIENSA COMO EL SISTEMA ETERNAL. Ejemplo real del árbol de Filo (así se escribe):
- Habilitador: "Puedes crear técnicas de Filo que consuman cargas de Sangrado para infligir daño puro que ignora mitigación."
- Pasiva mecánica: "Tu Sangrado ya no decae con el tiempo; el tope sube a 9."
- Mini-sistema: "Mientras 2+ objetivos sangren, tus ataques alcanzan 1 objetivo adicional."

MAL (esto es una técnica, PROHIBIDO):
`CAP-08 Obra Maestra: encierra al objetivo en una escultura. Daño = (CAR×4)+(Pot×7).`
BIEN (esto es un permiso con techo):
`CAP-08 Sellado escultórico (Habilitador de remate): Puedes crear cartas de remate (≤2 usos/combate) que aprisionen a 1 objetivo transmutándolo. Techo de escalado por carta: hasta (CAR×4)+(Pot×7); si el objetivo ya sufre Confundido o Marcado, la carta puede aplicar Paralizado 1 turno.`

Diferencias clave:
- No describas el ataque como algo que el personaje "hace" ("lanza una columna de fuego"). Describe
  la CAPACIDAD que se desbloquea ("Puedes crear cartas de fuego a distancia que…").
- Los números NO son "haces N de daño". Son **TECHOS**: "hasta (S×n)+(Pot×m) por carta", "alcance ≤ X m",
  "hasta N partes/usos", "puedes aplicar [estado]". El daño real lo fija la carta del jugador con el
  presupuesto de `NUMEROS-Y-BALANCE §7`. Tú solo pones el LÍMITE de lo que sus cartas pueden alcanzar.
- **PROHIBIDO nombre de técnica/movimiento** (Hiken, Gran Entei, Obra Maestra…). Nombra la
  **capacidad/propiedad** en términos de sistema (Cuerpo Separable, Control de Partes a distancia,
  Enjambre de partes, Zona de fuego, Alteración de campo…).

## 0.1 Ejemplo de oro COMPLETO — Bara Bara no Mi (usa este molde para TODAS)

```
Nv.0 — Manifestación
- CAP-01 Cuerpo Separable (Propiedad): Tu cuerpo se divide en partes a voluntad; los ataques de corte/filo no te dañan ni te desmembran (te separas y reensamblas). No protege contra impacto contundente, perforación, ni Busoshoku Nv.2+/Kairoseki/mar. Techo: reensamblar 0 PA, 1/turno.
- CAP-02 Control de Partes (Habilitador): Puedes crear cartas de fruta que manejen tus partes separadas a distancia (manos, pies, torso) para golpear, agarrar o explorar. Techos: control ≤ 5+Pot m; hasta 3 partes activas; techo de escalado por carta hasta (PER × 2) + (Pot × 2). Estados disponibles: Confundido (ataque desde ángulo ciego).
- Pasiva: mientras estés separado no puedes ser derribado ni agarrado por completo; siempre queda una parte libre.

Nv.1 — Control
- CAP-03 Alcance Ampliado (Mini-sistema): sube el techo de Control de Partes a ≤ 15+2×Pot m y a 3+Pot partes; tus cartas de partes pueden golpear desde dos ángulos (1 objetivo adicional adyacente sin coste de AoE).
- CAP-04 Partes Proyectil (Habilitador): Puedes crear cartas que impulsen tus partes como proyectiles o plataformas de vuelo corto. Techos: alcance ≤ 15+2×Pot m; techo de escalado por carta hasta (PER × 3) + (Pot × 5). Estados: Marcado.
- Pasiva: los ataques a distancia contra ti que fallen por tu separación te dejan reorganizar posición 1/turno (0 PA).

Nv.2 — Maestría
- CAP-05 Enjambre (Habilitador): Puedes crear cartas de área que ataquen con varias partes a la vez. Techos: radio ≤ 5+⌊Pot/2⌋ m; techo por objetivo hasta (PER × 2) + (Pot × 4). Estados: Confundido a los alcanzados.
- CAP-06 Reensamblaje Táctico (Pasiva mecánica): puedes reensamblar desde cualquier parte dentro del techo de control, ignorando Enraizado y agarres; tus cartas de partes pueden dejar una parte "vigía" que concede Gap PER defensivo +(2+⌊Pot/2⌋) vs emboscada.
- Pasiva: inmune a Confundido; una parte separada puede portar un objeto/arma y usarlo a distancia.

Nv.3 — Despertar
- CAP-07 Cuerpo Disperso (Mini-sistema de campo): 1/combate (2 PA + (40+3×Pot) EN, ~3 turnos): repartes decenas de partes en radio 20+2×Pot m; dentro, tus cartas de partes no pagan extensión de alcance y puedes atacar desde cualquier punto del radio. Enemigos rodeados: Confundido renovable (Gap PER vs PER).
- CAP-08 Festival de Partes (Habilitador de remate): Puedes crear cartas de remate (≤2 usos/combate) que convergen todas tus partes en un asalto. Techo de escalado por carta hasta (PER × 3) + (Pot × 6), AoE hasta 3 objetivos. Estados: Aturdimiento si el objetivo ya sufre Confundido.
- Pasiva: 1/combate, al recibir un golpe letal te dispersas y sobrevives con 1 PV, quedando Fatiga 2 turnos.
```

## 1. Tipos de CAP (etiqueta SIEMPRE una entre paréntesis tras el nombre)

- **(Propiedad)** — una propiedad permanente del cuerpo/elemento. Ej.: inmunidad a corte, intangibilidad Logia, forma Zoan. Es una regla, no un ataque.
- **(Pasiva mecánica)** — una regla permanente que cambia cómo funcionan tus cartas/tu recurso/tu defensa.
- **(Habilitador)** — "Puedes crear cartas de fruta que [hacen X]". Da el DERECHO a diseñar esa categoría de cartas, con techos. Sin este permiso, esa carta no es legal.
- **(Mini-sistema)** — una mecánica condicional que altera una regla base bajo condiciones ("Mientras X…", "sube el techo a…").
- Nombra la capacidad en términos de sistema. NADA de nombres de golpe del manga.

## 2. Motor de juego (respétalo)

- Sin dados. Recursos PV, EN, PA. **Pot = ⌊(TEM + Secundario)/8⌋** (suelo 1).
- Stats: FUE AGI RES INT PER VOL CAR TEM. Usa el **secundario de la fruta** como stat de escalado de los techos (Logia: daño elemental es especial, escala en TEM).
- Estados del motor (nombre EXACTO): Quemado, Congelado, Envenenado, Paralizado, Ralentizado,
  Enraizado, Cegado, Confundido, Miedo, Intimidado, Fatiga, Aturdimiento, Drenado, Marcado,
  Potenciado, Escudado, Sangrado, Rotura de guardia.
- Gap: solo indica "Gap PER vs AGI", "Gap FUE ≥ +1"; no lo calcules.
- **Logia:** CAP-01 SIEMPRE (Propiedad) intangibilidad: los ataques físicos no dañan sin Busoshoku Nv.2+/Kairoseki/mar.
- **Zoan:** CAP-01 SIEMPRE (Propiedad) forma bestia: mantenimiento 1 PA al activar + (12+Pot) EN/turno (estándar) o (15+Pot) (Antigua/Mitológica); da bonos físicos en forma.
- **Paramecia:** CAP-01 = la propiedad/truco central de la fruta.
- Debilidad Mar/Kairoseki ya está en su campo; no la repitas dentro de las CAPs.
- Despertar (Nv.3) altera el CAMPO o el cuerpo a escala de escena (mini-sistema), no "+daño".

## 3. Estructura (2+2+2+2 = 8 CAP + 4 pasivas)

Nv.0 Manifestación (CAP-01, CAP-02, Pasiva) · Nv.1 Control (CAP-03, CAP-04, Pasiva) ·
Nv.2 Maestría (CAP-05, CAP-06, Pasiva) · Nv.3 Despertar (CAP-07, CAP-08, Pasiva).

Cada nivel debe **desbloquear algo nuevo o subir techos** (más alcance, más partes, nuevo estado,
nueva categoría de carta, nuevo mini-sistema). No repitas la misma capacidad renombrada.
- Nv.0: propiedad central + primer habilitador básico (alcance corto).
- Nv.1: ampliar alcance/control + nuevo habilitador (distancia/estado).
- Nv.2: habilitador de área/penetración + pasiva/mini-sistema de maestría (combos Buso/Eternal, terreno, escudado).
- Nv.3: CAP-07 mini-sistema de campo (1/combate, radio grande, ~3 turnos); CAP-08 habilitador de remate (≤2 usos/combate).

## 4. Techos de referencia (ancla de balance; los números son LÍMITES, no daño hecho)

Techo de escalado por carta (lo máximo que una carta construida sobre esa CAP puede alcanzar):

| Tier | Nv.0 | Nv.1 | Nv.2 | Nv.3 CAP-08 |
|---|---|---|---|---|
| I   | (S×2)+(Pot×2) | (S×2)+(Pot×3) | (S×3)+(Pot×4) | (S×3)+(Pot×5) |
| II  | (S×2)+(Pot×3) | (S×3)+(Pot×4) | (S×3)+(Pot×5) | (S×4)+(Pot×6) |
| III | (S×2)+(Pot×3) | (S×3)+(Pot×5) | (S×4)+(Pot×6) | (S×4)+(Pot×7) |
| IV  | (S×3)+(Pot×4) | (S×2)+(Pot×6) | (S×4)+(Pot×6) | (S×4)+(Pot×8) |
| V   | (S×3)+(Pot×4) | (S×3)+(Pot×6) | (S×4)+(Pot×6) | (S×5)+(Pot×8) |

Zona AoE Nv.2 por objetivo pesa menos: (S×2)+(Pot×4). Mitigación/escudado defensivo: (S×2)+(Pot×4).
Alcances: Nv.0 ≤ 5+Pot m · Nv.1 ≤ 15+2×Pot m · Nv.2 radio ≤ 5+⌊Pot/2⌋ m (o zona 12 m) · Nv.3 radio 20+2×Pot m.
Nº partes/clones/cargas ancla: 3+Pot. Estados de control: 1–2 turnos. Penetración: 15–25 % (tope 60 %).
Costes: reacción 0 PA/~8 EN · mantenimiento/propulsión 1 PA + (10+Pot) EN · campo Nv.3 2 PA + (40+3×Pot) EN.

Escribe siempre el número como techo: "techo de escalado por carta hasta (S×n)+(Pot×m)",
"alcance ≤ …", "hasta N …", "puedes aplicar [estado] … turno(s)".

## 5. Unicidad (cada fruta DISTINTA)

1. Lee `desc` y `efecto`. Extrae el truco central (un verbo/propiedad).
2. Da a la fruta: una **propiedad firma** (CAP-01), un **estado firma** del motor, y **verbos propios**.
3. Incluye **al menos una mecánica novedosa por lote** (con techos y límites): banco de daño, etiqueta
   de intercambio de posiciones, copia de plantilla de stats, zona que reescribe una ley física,
   conversión de material en munición, marcado que redirige golpes, etc. Todo como PERMISO con techos.
4. Nada de inmunidad total permanente, robar Haki/PT, ni "hace de todo".

## 6. Formato de salida (OBLIGATORIO)

Un ÚNICO objeto JSON UTF-8. Clave = slug. Valor = objeto con EXACTAMENTE:
`capacidades_raw`, `notas_jugadores`, `notas_staff` (texto plano).

`capacidades_raw`:
- Encabezados EXACTOS con em-dash: `Nv.0 — Manifestación`, `Nv.1 — Control`, `Nv.2 — Maestría`, `Nv.3 — Despertar`, separados por línea en blanco.
- Cada CAP: `- CAP-0X <Nombre de capacidad> (<Tipo>): <permiso/propiedad/regla>. Techos: …` (y estados si aplica).
- Pasiva por nivel: `- Pasiva: <regla con números>`.
- Usa `×` `≤` `Pot`, stats en mayúsculas. SIN markdown `**` ni backticks.
- Longitud objetivo 1500–2600 caracteres.

`notas_jugadores`: `- Tags permitidos: …` / `- Prohibiciones: …` (3 límites) / `- Sinergias sugeridas: …`
`notas_staff`: `- Justificación de Tier: …` / `- Riesgos de abuso: …` / `- Counters esperados: Busoshoku Nv.2+, Kairoseki, …`

## 7. Autochequeo (rechaza tu trabajo si falla)
- [ ] NINGUNA CAP describe un ataque concreto que el PJ ejecuta; TODAS son propiedad/pasiva/habilitador/mini-sistema.
- [ ] Cada CAP lleva su etiqueta de tipo entre paréntesis.
- [ ] Los habilitadores dicen "Puedes crear cartas… " y ponen el número como TECHO ("hasta …"), no como daño hecho.
- [ ] 8 CAP (2+2+2+2) + 4 pasivas. Los niveles suben techos / abren cosas nuevas.
- [ ] Logia CAP-01 intangibilidad (Propiedad); Zoan CAP-01 forma (Propiedad) con mantenimiento; Paramecia CAP-01 truco propio.
- [ ] Nv.3 CAP-07 mini-sistema de campo (1/combate); CAP-08 habilitador de remate (≤2 usos).
- [ ] Estados con nombre EXACTO. Cada fruta se siente distinta. Sin nombres de técnica del manga.
- [ ] JSON válido, una clave por slug del lote, sin texto fuera del JSON.
