# MANUAL DEL STAFF

> Manual operativo del equipo de moderación y administración · Sistema de rol por foro (One Piece) · MyBB
>
> **Estado:** documento vivo en construcción. Se completa a medida que cada sistema se cierra.
> **Versión:** 0.19 (Sesión 11, 2026-08-27)

---

## Índice

| Capítulo | Contenido | Estado |
|---|---|---|
| 1 | El papel del staff y el flujo de trámites | ✅ disponible |
| 2 | Razas: revisión y validación | ✅ disponible |
| 3 | Atributos: economía, fórmulas y validación | ✅ disponible |
| 4 | Dominios (bélicos y de oficio) | ✅ disponible |
| 5 | Dotes y defectos | ✅ disponible |
| 6 | Rasgos de personalidad: validación del karma | ✅ disponible |
| 7 | Progresión y niveles | ✅ disponible |
| 8 | Técnicas: moderación | ✅ disponible |
| 9 | Inventario, objetos y equipo | ✅ disponible |
| 10 | Economía y tiendas: fluctuación y tiendas de jugador | ✅ disponible |
| 11 | Combate: supervisión y resolución | ✅ disponible |
| 12 | NPCs: supervisión y gestión | ✅ disponible |
| 13 | Facciones: catálogo y ascensos | ✅ disponible |
| 14 | Bajo mundo e información: rumores | ✅ disponible |
| 15 | Mundo Vivo: análisis y publicación | ✅ disponible |
| 16 | Conquista y control territorial | ✅ disponible |
| 17 | Navegación: trámites y oráculos | ✅ disponible |
| 18 | Barcos | ✅ disponible |
| 19 | Akuma no Mi | ✅ disponible |
| 20 | Haki | ✅ disponible |
| 21 | Narradores y auto-narradas | ✅ disponible |
| 22 | Trámites: catálogo y prompts | ✅ disponible |
| 23 | Sistemas opcionales: cibernética y familias legendarias | ✅ disponible |
| — | Anexo A: capa de implementación técnica (MyBB) | ✅ completo |
| — | Anexo B: catálogo de skills de IA | ✅ completo (8 skills implementadas) |

---

# 1. El papel del staff y el flujo de trámites

## 1.1 Qué hace el staff

El sistema está diseñado para que la mayoría del rol ocurra entre usuarios, sin supervisión en tiempo real. Tu trabajo no es estar en cada mesa: es **garantizar que el mundo responda** y que las reglas se apliquen con coherencia. En la práctica, el staff:

- **Revisa y valida** fichas de personaje (razas, atributos, y en el futuro dominios, dotes y técnicas).
- **Modera los trámites** de los usuarios (cierre de temas, creación de técnicas, navegación, auto-narradas).
- **Ejecuta las rondas de Mundo Vivo**: analiza los temas presentes de la ronda, revisa el resultado de la IA, y publica misiones, recompensas, cambios de islas y el periódico.
- **Publica** los resultados de cada proceso (puntos de progreso, fichas de técnicas, narrativas de travesía), que el sistema automatiza en cuanto das a publicar.
- **Mantiene el catálogo** del mundo: razas, islas, NPCs, barcos, frutas.

## 1.2 El flujo de los trámites (staff ↔ IA)

El mecanismo central del foro es el trámite, y el patrón es siempre el mismo — memorízalo porque se repite en todos los procesos:

1. El usuario (o el propio staff) genera una solicitud desde un formulario de trámite.
2. El sistema genera automáticamente un **prompt/orden** con los identificadores relevantes (IDs de temas, personajes, técnicas…).
3. El staff copia ese prompt y lo pega en su sesión de IA, usando la **skill** correspondiente (ver Anexo B).
4. La IA procesa la solicitud (con acceso a los manuales y, cuando aplica, a la base de datos) y devuelve un resultado.
5. El resultado vuelve a la **zona staff**, donde puedes revisarlo o editarlo antes de publicar. **Nada se publica sin tu visto bueno.**
6. Al publicar, el sistema automatiza el resto: posteo, actualización de históricos, notificación al usuario.

**Regla de oro:** la IA propone, el staff decide. Todos los resultados de la IA son editables y revisables; tu criterio es la última palabra.

## 1.3 Reglas de oro (operativas)

- **No hay dados en el sistema.** Nunca se resuelve combate ni narrativa por azar. La única excepción confirmada es la tirada de obtención del Haki del Conquistador (5.19), y solo para eso.
- **El Mundo Vivo es el pilar.** Toda acción en un tema presente debe tener peso real: si no tiene consecuencias medibles, la ronda de Mundo Vivo no está completa.
- **Coherencia por encima de velocidad.** Si un resultado de la IA contradice el sistema o el lore, corrígelo antes de publicar.
- **Registro de decisiones.** Cualquier decisión de balance o interpretación que tomes como staff debe quedar registrada (histórico del proyecto), para que no se pierda entre sesiones.

---

# 2. Razas: revisión y validación ✅

> Sección operativa correspondiente al capítulo 2 del Manual del Jugador. **Confirmada en Sesión 3**: raciales, modificadores, físicos y reglas del híbrido cerrados. **Ampliada en Sesión 6 (5.1-bis):** Las Tribus — reglas y catálogo confirmados (sección 2.5).

## 2.1 Qué revisar en una ficha

Al recibir una ficha de personaje (creación o edición), comprueba en este orden:

1. **La raza está en la lista cerrada** (11: Humano, Mink, Gyojin, Sirena, Tontatta, Skypiean, Lunarian, Gigante, Oni, Bucaner e Híbrido). No hay razas fuera de catálogo.
2. **Las raciales asignadas son correctas:** cada raza tiene exactamente 1 primaria y 1 secundaria. Un puro recibe ambas; un híbrido recibe únicamente las primarias de sus dos razas progenitoras.
3. **El cálculo del híbrido es correcto:** media de los modificadores de atributos de sus dos razas (entero más cercano, mitades a favor del jugador) y sin acceso a secundarias. Cualquier combinación de razas es válida: no la rechaces por "rara", solo valida el cálculo.
4. **Los datos físicos son coherentes** con la raza (altura, edad mínima, vida media): un personaje de 2 metros no puede ser un Tontatta.
5. **Los modificadores raciales son correctos** (tabla definitiva en `diseno/5.1_razas_y_raciales.md` §3.12; el sistema los aplica solo, por encima del techo de nivel — aquí solo se audita que no haya errores manuales).
6. **Los triggers condicionales declarados por el jugador se verifican en el momento, sin exigir crunch** (directiva Sesión 3): "Furia de batalla" (Oni, <30% PV), "Eco del Dios Perdido" (Bucaner, 1 vez/tema-trama), requisitos de técnicas (empuñadura de titán...). El jugador los escribe en su post; tú compruebas que la condición se cumple y das el visto bueno. No exijas justificación automática ni cálculo en el post.

## 2.2 Checklist de validación (skill-validacion-personajes)

La skill de validación (confirmada como independiente vía trámite) genera un informe por ficha. Verifica que el informe declare:

- [ ] Raza(s) correctas y en catálogo.
- [ ] Híbrido con dos razas progenitoras y media bien calculada.
- [ ] Sin raciales secundarias en híbridos.
- [ ] Una primaria y una secundaria presentes en puros.
- [ ] Físicos coherentes con la raza.

Si el informe marca algo en rojo, no aceptes la ficha hasta corregirlo.

## 2.3 Estructura de datos (MyBB)

- **`razas`**: nombre, lore, físicos (altura, vida media, edad mínima), modificadores (JSON, se rellena al cerrar 5.2), flag de híbrido.
- **`raciales`**: raza, tipo (primaria/secundaria), nombre, descripción, efecto mecánico estructurado (JSON — el formato se completa al cerrar 5.8/5.10; 5.19 ya está cerrado y no toca este formato).
- **`personajes`**: `raza_id`, `raza_hibrida_id` (NULL si puro). El sistema asigna raciales y calcula la media automáticamente al crear/editar la ficha.
- **Panel:** "Catálogo de razas" (CRUD) con aviso automático si una raza se queda sin primaria o secundaria; "Informe de validación" por ficha.

## 2.4 Decisiones confirmadas (Sesión 3, regla 2.4)

Estas decisiones quedaron **confirmadas por el usuario el 2026-08-25** y son regla cerrada:

1. **Sulong (Mink)** → reservado como rama de dotes en 5.4, no como racial.
2. **Lunarian, secundaria** → "Carne de dios" (reducción de daño); se descartó la resistencia elemental al fuego por redundancia.
3. **Nombres y efectos de las raciales** → los del Manual del Jugador (cap. 2).
4. **Datos físicos** (alturas, vidas medias, edades mínimas) → los publicados.
5. **Híbrido con acceso futuro a secundarias** → diferido como decisión no urgente; hoy no se permite.

## 2.5 Las tribus: validación (operativo)

> Ampliación de 5.1 (5.1-bis), confirmada en Sesión 6. Las tribus son **variantes de raza pura que sustituyen la racial secundaria** — primaria y atributos intactos; el coste es renunciar a la secundaria estándar.

Qué verificar en una ficha con tribu:

- **Pureza:** solo razas puras — los híbridos no tienen tribu, y la **Genética Alterada** (5.4, Revisión 10) no la abre: esa dote abre *dotes* de la segunda raza, no tribus.
- **Sustitución:** la tribal **reemplaza** la secundaria estándar de la raza (no se suman). Verificar que la ficha no tenga ambas.
- **Unicidad y coherencia:** una sola tribu y que pertenezca a la raza del personaje (`tribu.raza_id = personajes.raza_id`).
- **Creación y permanencia:** elegida **solo al crear**; no se cambia ni se pierde (no existe trámite de cambio).
- **Sin coste ni aval:** no cuesta PP ni balanza y el acceso es libre. La visibilidad (jugar la tribu en secreto) es elección del jugador — tinte narrativo, no ventaja.
- **Catálogo cerrado (7 tribus · 3 razas):** Humanas (Kuja · Piernas Largas · Brazos Largos) · Gigante (Los Ancestrales) · Skypieans (Shandia · Birka · Caminantes de Nubes). El resto de razas sin tribus (conservan su secundaria estándar) — ampliable en el futuro. **La Línea D. NO es una tribu**: queda reservada para el futuro sistema de Familias Legendarias.
- **Magnitudes calibrables:** los números de cada tribal se tratan como cualquier racial (ajustables); lo cerrado es la estructura.

**Checklist de validación (skill-validacion-personajes):**

- [ ] Raza pura (sin híbrido).
- [ ] Una sola tribu, y de su raza.
- [ ] Secundaria estándar sustituida (no acumulada con la tribal).
- [ ] Elegida al crear (nunca adquirida después).

**Estructura de datos:** tabla **`tribus`** (`id` · `raza_id` · `nombre` · `descripcion` · `racial_nombre` · `racial_efecto` JSON · `sustituye_a`) · **`personajes.tribu_id`** (FK NULL; validación de pureza, unicidad y coherencia) · **hooks:** al validar la ficha comprobar pureza/unicidad/coherencia; al crear, sustituir la secundaria estándar por la de la tribu en el cálculo de la ficha (el resto del sistema no cambia).

**Panel:** catálogo de tribus (CRUD) e informe de validación.

---

# 3. Atributos: economía, fórmulas y validación ✅

> Sección operativa correspondiente al capítulo 3 del Manual del Jugador. **Confirmada en Sesión 3**: presupuesto 120, anclas y fórmulas de secundarios cerradas.

## 3.1 La economía de puntos (flujo confirmado por el usuario)

1. Los jugadores ganan **PP** cerrando temas (5.6).
2. Con PP compran **puntos de atributo** a una tasa según su rango/nivel (5.6).
3. Los puntos comprados se colocan **libremente** en cualquier atributo. **No hay coste creciente por nivel de atributo.**

**Consecuencia operativa:** el único freno a la especialización extrema es el techo por atributo según nivel, ya definido en 5.6 (`diseno/5.6_progresion.md`): curva `20 + 1,6 × (nivel − 1)`, tope 100 en nivel 50, y transición de nivel a **10 puntos comprados**. El sistema aplica el techo automáticamente al colocar puntos; tú solo auditas que no haya errores manuales.

## 3.2 Fórmulas de secundarios (confirmadas)

| Secundario | Fórmula propuesta |
|---|---|
| Vida (PV) | `100 + RES × 6 + FUE × 2 + VOL × 1 + Nivel² × 0,5` |
| Energía (PE) | `50 + VOL × 4 + INT × 3 + CAR × 1 + Nivel² × 0,4` |
| Velocidad (m/s) | `3 + AGI × 0,08 + FUE × 0,02 + Nivel² × 0,01` |
| Sprint (m/s) | `Velocidad × 1,6` |
| Salto vertical (m) | `0,3 + FUE × 0,015 + AGI × 0,015 + Nivel² × 0,004` |
| Salto horizontal (m) | `vertical × 1,5` |
| Carga (kg) | `40 + FUE × 4` · levantamiento puntual `× 2,5` |
| Resistencia pasiva | `RES × 0,15` |
| Lanzamiento (m) | `FUE × 0,4 + DES × 0,2` |
| Recuperación | `(RES × 0,1 + VOL × 0,1)% del máximo por hora` |
| PA | se define en combate (5.10), base Agilidad |

**Confirmadas en Sesión 3 (directiva del usuario):** cada secundario bebe de **varios primarios** y el **nivel crece acelerado** (`Nivel² × coeficiente`) — sensación Barbablanca/Rayleigh. Los coeficientes podrán afinarse con la curva de daño (5.10) sin cambiar el diseño; cuando cambien, se actualiza esta tabla y el sistema recalcula todo automáticamente.

## 3.3 Checklist de validación

- [ ] La suma de puntos gastados coincide con el presupuesto de creación (120, calibrado en 5.6) más las compras con PP (10 puntos = subida de nivel, tasa PP según tramo).
- [ ] Ningún atributo supera el techo de su nivel (techo a definir en 5.6; hasta entonces, revisión manual con las anclas de la escala).
- [ ] Los bonus de raza/dotes/técnicas están aplicados **aparte** de la base (no inflan la suma comprada).
- [ ] Los secundarios coinciden con las fórmulas (el sistema los recalcula solo; aquí se audita el desglose base + racial + dotes + técnicas).

## 3.4 Estructura de datos y automatismos

- **`personajes`**: campos `fue, des, agi, res, per, int, car, vol` (SMALLINT 1–100) + bonus calculados.
- **`atributos_secundarios`**: materializada por recálculo (no se edita a mano): `pv, pe, velocidad, sprint, salto_v, salto_h, carga, resistencia_pasiva, lanzamiento, recuperacion`.
- **Hooks:** recálculo automático ante cualquier cambio en primarios (compra, nivel, raciales, dotes); compra de puntos con PP descuenta según tasa de rango y añade a la reserva, y la colocación respeta el techo por nivel.
- **Panel:** ficha del personaje con desglose auditable; informe de validación (skill-validacion-personajes) que comprueba sumas, techos y secundarios.

---

# 4. Dominios: revisión y validación ✅

> Sección operativa correspondiente al capítulo 4 del Manual del Jugador. **Confirmada en Sesión 3**: 6 dominios bélicos, 11 oficios, costes y especializaciones cerrados. **Ampliada en Sesión 4:** oficio n.º 12 Comerciante (obligatorio para tiendas de jugador, 5.9). **Ampliada en Sesión 6 (Revisión 10):** armas arrojadizas en «a distancia» · INT limita dominios/oficios adicionales (1 por cada 50 INT) · Maestría Suprema del oficio (nv5).

## 4.1 Qué revisar en una ficha

1. **Los 2 puntos de dominio iniciales** están bien repartidos (Opción A: 2 dominios nv1 · Opción B: 1 dominio nv2). La primera adquisición es gratis; todo lo demás paga la tabla de costes.
2. **Niveles mínimos de personaje** (nv2→10 · nv3→20 · nv4→35 · nv5→45) y **coste progresivo**: 60/120/240/400 PP para el primer dominio, ×1,5 el segundo y ×2 el tercero. El cronómetro de 15 días es independiente del de atributos (5.6); no puede haber dos dominios en entrenamiento a la vez. **Cupo de INT (Revisión 10):** los dominios/oficios **adicionales** están limitados a **1 por cada 50 INT** (solo adquisiciones nuevas; los 2 puntos de creación y las subidas de categoría ya adquirida no consumen cupo).
3. **Los dominios bélicos** desbloquean tiers de técnica (nv1→tier 1 … nv5→tier 5), bono de manejo (+1 DES efectiva por nivel con ese arma) y −1 PA en básicos desde nv2. La Maestría nv5 (ignorar la mitad de reducciones planas) se declara y verifica en combate (5.10), no se calcula sola. El dominio **a distancia** incluye armas arrojadizas (cuchillos, shurikens, agujas, bombas de mano) — Revisión 10.
4. **Los oficios**: niveles 1–2 generales; al llegar a **nv3 la ficha queda bloqueada hasta elegir rama** (decisión permanente). Los niveles 4–5 dependen de la rama. Curas y raciones se expresan en **% del máximo** (10/15/20/25/30/40/50%) — autoescalan con la PV/PE de 5.2. El **nv5 de rama** otorga el título de **Maestría Suprema del oficio** (Revisión 10; único por rama, registrado en la ficha y en el panel) y es su hito de impacto global.
5. **El atributo rey del oficio** es la vara de los trámites comparados (5.21): un Médico con INT alta opera mejor; no es requisito duro. Oficios: **12** (Comerciante incluido desde Sesión 4 — obligatorio para abrir tienda de jugador, 5.9).

## 4.2 Checklist de validación (skill-validacion-personajes)

- [ ] Puntos iniciales correctos (2, reparto A o B).
- [ ] Niveles mínimos y coste PP progresivo correctos (×1,5 / ×2).
- [ ] Rama de oficio elegida en nv3 (sin elegir → ficha bloqueada).
- [ ] Tiers de técnica coherentes con el nivel del dominio bélico.
- [ ] Curas/raciones en % del máximo, coherentes con el nivel del oficio.
- [ ] Dominios/oficios adicionales dentro del cupo de INT (1 por cada 50 INT; creación y subidas no cuentan).
- [ ] Maestría Suprema registrada en el nv5 de rama (título único por rama).

## 4.3 Estructura de datos (MyBB)

- **`dominios`**: catálogo (nombre, tipo bélico/oficio, atributo rey, descripción) — cerrado a 6 bélicos + **12 oficios (Comerciante incluido, Sesión 4)**.
- **`dominios_personaje`**: `personaje_id`, `dominio_id`, `nivel` (1–5), `rama` (NULL hasta nv3).
- **`especializaciones`**: catálogo de ramas (oficio, nombre, efectos nv3/4/5 en JSON).
- **Hook de rama:** al llegar a nv3 de un oficio, el panel pide elegir rama y bloquea la ficha hasta elegirla.
- **Panel:** catálogo de dominios (CRUD) e informe de validación.

---

# 5. Dotes y defectos: revisión y validación ✅

> Sección operativa correspondiente al capítulo 5 del Manual del Jugador. **Confirmada en Sesión 3**: catálogos, reglas y puntuaciones cerrados. **Ampliada en Sesión 6 (Revisión 10):** Genética Alterada (híbridos) · dotes raciales con requisito de raza pura · el defecto Secreto.

## 5.1 Qué revisar en una ficha

1. **La balanza suma exactamente 0** (dotes +1…+5, defectos −1…−5, sin límite de cantidad). Si no suma 0, la ficha no se valida.
2. **Requisitos cumplidos** (atributo/dominio/nivel/raza) y **cadenas respetadas** (Sulong I→II→III, por ejemplo).
3. **Sin beneficios redundantes**: dos dotes del mismo efecto no se acumulan (se aplica la mayor) y una dote que repite un beneficio racial tampoco. **Las evoluciones declaradas** (marcadas «Requiere: racial/dote») sí son válidas — Furia Desatada evoluciona Furia de batalla, no la duplica.
4. **Híbridos:** solo dotes generales + dotes de la **raza dominante** (primera de la ficha). **Excepción — Genética Alterada (Revisión 10):** un hito post-creación permite **UNA dote racial de la segunda raza** (elegida al adquirirla; sin combinar con dotes de la dominante — la no-acumulación sigue intacta). Verificar que solo hay una y que entró por hito con trámite.
5. **Parejas espejo incompatibles**: Músculo Entrenado ↔ Curva Lenta · Oportunista ↔ Recompensa Menguada · Labia de Capitán ↔ Torpeza Social.
6. **Origen registrado**: creación o narrativo (hito de trama con trámite). Las narrativas **no tocan la balanza**.
7. **Raza pura (Revisión 10):** las dotes marcadas «Requisito: raza pura» (p. ej. **Sulong III — Rey de Zou**) no las toman híbridos, ni siquiera con Genética Alterada.
8. **Defecto Secreto (Revisión 10):** el defecto **Secreto** lleva definidos en la ficha el secreto y su **consecuencia mecánica temporal**; la revelación es trama (5.14/5.21), nunca castigo permanente.

## 5.2 Checklist de validación (skill-validacion-personajes)

- [ ] Balanza = 0 exacto.
- [ ] Requisitos y cadenas de prerrequisitos correctos.
- [ ] Sin redundancias (dote+dote, dote+racial) salvo evolución declarada.
- [ ] Híbrido con dotes solo de la raza dominante.
- [ ] Parejas espejo respetadas; incompatibilidades declaradas.
- [ ] Origen (creación/narrativo) registrado en la ficha.
- [ ] Genética Alterada: máximo 1 dote de la segunda raza, por hito, sin combinar con la dominante.
- [ ] Dotes de raza pura sin híbridos (ni con Genética Alterada).
- [ ] Defecto Secreto con secreto y consecuencia definidos al crear.

## 5.3 Estructura de datos (MyBB)

- **`dotes` / `defectos`**: catálogo (nombre, efecto JSON, puntuación ±, tipo general/racial, raza, requisitos, prerrequisitos, incompatibilidades).
- **`personaje_dotes`**: `personaje_id`, `dote_id`/`defecto_id`, `origen` (creación/narrativo), `tema_origen`, `fecha`.
- **Hook de balanza:** al validar la ficha de creación, si la suma ≠ 0 la ficha se bloquea.
- **Panel:** catálogo CRUD e informe de validación.

---

# 6. Rasgos de personalidad: validación del karma ✅

> Sección operativa correspondiente al capítulo 6 del Manual del Jugador. **Confirmada en Sesión 3**: catálogo, balanza y mecanismo de validación cerrados (checklist ítems 71–75).

## 6.1 Qué revisar en una ficha

1. **La balanza de rasgos suma exactamente 0** (positivos +1 a +3, negativos −1 a −3, sin límite de cantidad). Si no suma 0, la ficha no se valida — igual que la balanza de dotes (5.4).
2. **Sin parejas antagónicas**: Valiente↔Cobarde · Honesto↔Mentiroso · Generoso↔Tacaño · Compasivo↔Cruel · Calculador↔Impulsivo · Idealista↔Pesimista.
3. **Todos los rasgos son del catálogo cerrado** (12 + 12). No se inventan rasgos fuera de catálogo; el staff puede ampliarlo por decisión de foro, nunca a mitad de ficha.
4. **Mínimo recomendado 1 positivo y 1 negativo** — no es regla dura, pero se anota en la ficha si no se cumple.
5. **Origen registrado**: los rasgos posteriores a la creación solo entran por **evolución por hito** (sección 6.3), nunca por decisión libre del jugador.

## 6.2 El karma en el cierre de temas (skill-cierre-temas)

Al cerrar un tema, el análisis de `skill-cierre-temas` incluye el **informe de rasgos** por participante. La IA propone, rasgo por rasgo:

- **Jugado de verdad → +1 de karma acumulado** (tanto si es positivo como negativo: jugar bien un defecto recompensa igual).
- **No jugado → sin cambio** (no es falta: no hubo ocasión).
- **Contradicho sin justificación → +1 al contador de contradicciones** de ese rasgo.

El staff **firma o ajusta** el informe (igual que el desglose de PP) y el sistema aplica los cambios al cerrar. El karma es **público en la ficha** y **no es moneda**: no da PP ni compra nada — la economía de 5.6 no se toca.

## 6.3 El arraigo, la evolución y las contradicciones

- **Umbral de arraigo:** al llegar a **5 de karma**, el rasgo pasa a estado `arraigado` (automático al cerrar el tema que lo confirma). El arraigo habilita la **evolución por hito**: un positivo arraigado puede convertirse en dote (5.4), un negativo puede superarse y sustituirse por un rasgo equivalente o su antagónico. Todo por trámite, con el **motivo del hito** registrado.
- **Contradicciones:** a las **3 seguidas** sin justificación narrativa → estado `en_duda` (aviso visible al jugador). A las **5** → pérdida o cambio por trámite con rebalanceo (el jugador elige un rasgo equivalente de la misma puntuación).
- **Justificación narrativa:** una contradicción declarada en trámite y validada por el staff (un hito de cambio, una situación extrema) **no cuenta** como contradicción. El sistema castiga la incoherencia silenciosa, no la evolución del personaje.

## 6.4 Checklist de validación (skill-validacion-personajes)

- [ ] Balanza de rasgos = 0 exacto.
- [ ] Sin parejas antagónicas.
- [ ] Todos los rasgos del catálogo activo.
- [ ] Mínimo recomendado 1 positivo y 1 negativo (anotar si no).
- [ ] Origen registrado (creación/hito); sin rasgos post-creación sin trámite de hito.

## 6.5 Estructura de datos y automatismos (MyBB)

- **`rasgos`**: catálogo cerrado (`id`, `nombre`, `tipo` positivo/negativo, `puntuacion` −3…+3, `descripcion`, `pareja_incompatible_id`, `activo`).
- **`personaje_rasgos`**: `personaje_id`, `rasgo_id`, `origen` (creación/hito), `karma_acumulado` (público), `estado` (activo/arraigado/en_duda), `contador_contradicciones`, `tema_ultima_contradiccion_id` (para contar «seguidas»).
- **Hook de balanza:** al validar la ficha de creación, si la balanza de rasgos ≠ 0 (o hay antagónicos) la ficha se bloquea.
- **Hook de cierre de tema:** al firmar el informe de rasgos, el sistema aplica los +1 de karma, incrementa contadores y actualiza estados (`activo → arraigado` a los 5 · `activo → en_duda` a las 3 seguidas · `en_duda → perdido` a las 5), con posteo automático del cambio de estado y aviso al jugador.
- **Hook de hito:** al aprobar una evolución por trámite, añade/retira rasgos con origen `hito` y recalcula la balanza actual (post-creación, sin tocar la de creación).
- **Panel «Rasgos y karma»:** lista de personajes con sus rasgos, karma, estado y contador; filtros por estado (`en_duda`, `arraigado`, `activo`); acciones: marcar/desmarcar «en duda», aprobar evolución por hito (con motivo), aprobar pérdida/cambio (con rebalanceo); histórico de karma por rasgo (qué temas lo alimentaron).

---

# 7. Progresión: revisión y validación ✅

> Sección operativa correspondiente al capítulo 7 del Manual del Jugador. **Confirmada en Sesión 3**: esqueleto numérico y fórmula de PP de `skill-cierre-temas` cerrados. **Sesión 4**: calendario de temas presentes/pasados (congelación con instantánea de ficha) y capa técnica completa cerrados (checklist ítems 103–107).

## 7.1 Qué revisar en una ficha

1. **La transición de nivel:** se sube al acumular **10 puntos de atributo comprados** desde el último nivel (ajuste del «40» del maestro, justificado en `diseno/5.6_progresion.md`).
2. **Los techos por atributo:** curva `20 + 1,6 × (nivel − 1)`, tope 100 en nivel 50. Los bonus de raza/dotes/técnicas se suman **por encima** del techo, sin contarlo.
3. **Los bloques y el cronómetro:** 5 puntos = 7 días · 10 = 13 días · un entrenamiento a la vez. Los sobrantes se arrastran al siguiente nivel. El cronómetro de atributos es independiente del de dominios (15 días, 5.3).
4. **Los tramos de coste PP:** I 10 · II 15 · III 25 · IV 40 · V 60 PP por punto. Al validar una compra, el PP se descuenta según el tramo actual del personaje.
5. **El presupuesto inicial** (120 puntos, media 15, bajo el techo de nivel 1 = 20) — ya cerrado en 5.2.

## 7.2 Los PP al cerrar temas (skill-cierre-temas)

Fórmula confirmada en `diseno/skill-cierre-temas.md`:

`PP = Base(T) × F_fidelidad × F_peso × F_calidad × F_extensión × F_tiempo × F_riesgo × F_perfil`

- **Base(T)** = 5 × coste del punto del tramo del participante: **50 / 75 / 125 / 200 / 300 PP**.
- **Factores** con bandas (1,00 = estándar): fidelidad 0,90–1,20 · peso 0,90–1,25 · calidad 0,90–1,20 · extensión 0,85–1,10 (mínimo 350 palabras/post) · **tiempo 0,70–0,90 (pasado) / 1,00–1,30 (presente)** — banda ampliada en Sesión 4: un presente de alto impacto rinde ~1,9× un pasado sin consecuencias · riesgo 0,90–1,35 · perfil 1,00–1,05.
- **Reglas:** redondeo al entero más cercano (mitades a favor del jugador) · **techo 2× la base** · **suelo 0,5×** si el tema se cerró correctamente · temas de grupo: factores de mundo en conjunto, de personaje por participante · **cada participante con su tramo**.
- **Flujo:** la skill propone un desglose justificado (cada factor con su banda y su frase) → el staff **firma o ajusta con motivo** (queda en el histórico) → se publica el desglose en la ficha. Los ajustes del staff calibran la skill; el jugador nunca calcula nada.

## 7.3 Checklist de validación

- [ ] Suma de puntos gastados correcta (presupuesto 120 + compras con PP).
- [ ] Ningún atributo supera el techo de su nivel (los bonus van por encima, aparte).
- [ ] Bloques y cronómetro correctos (5→7d / 10→13d, uno a la vez, sobrantes arrastrados).
- [ ] PP descontados según el tramo correcto del personaje.
- [ ] Desglose de PP del cierre de tema con sus factores justificados (skill-cierre-temas), con la banda de tiempo ampliada (7.2).
- [ ] Al abrir un tema: tipo declarado (presente/pasado) · fecha correcta (presente = actual del foro; pasado = fecha biográfica coherente) · **un presente a la vez por personaje** · instantánea de ficha tomada (7.5).

## 7.4 Estructura de datos (MyBB)

- **`personajes`**: `nivel` (1–50), `puntos_comprados` (acumulado desde el último nivel, al llegar a 10 → sube y se reinicia), `reserva` (puntos comprados sin colocar), `entrenamiento_fin` (fin del cronómetro).
- **`temas`** (ampliación): `tipo` (presente/pasado), `fecha_foro` (ancla on-roll), `fecha_real_apertura`, `estado`, `invadible` (bool = presente), `zona`.
- **`temas_participantes`**: `tema_id`, `personaje_id`, `congelado_desde` (fecha anclada), `salio_en`, `tramo`, **`ficha_instantanea`** (JSON con atributos/técnicas/estados **al abrir** el presente — la resolución del tema usa estos valores, no los actuales).
- **`calendario_foro`**: `fecha_foro_actual` (fecha on-roll presente), `ratio` (1 real = 2 on-roll), `ultima_actualizacion_real`, histórico de avances.
- **`historico_pp`**: cada otorgamiento con su desglose, participante, tema y tramo.
- **Hooks y cron:** cron del calendario (avanza +2 días on-roll por día real; revisa `entrenamiento_fin` vencidos → reserva) · al abrir tema (valida tipo/fecha/un-presente-a-la-vez, toma la instantánea) · al cerrar tema (skill-cierre-temas, libera la congelación, histórico) · compra de bloque (descuenta PP, arranca el cronómetro, bloquea compras) · subida de nivel (recalcula techos y secundarios, notifica) · **muerte de personaje**: el trámite 62 (capítulo 22) cierra el presente con la instantánea de ficha, libera la congelación y aplica la herencia al nuevo personaje.
- **Paneles:** «Progresión» (cronómetros, subidas, gastos de PP por concepto, saldos y reservas, histórico) · «Calendario» (fecha on-roll actual, presentes activos con su ancla y sus congelados, histórico de aperturas/cierres, avisos de coherencia de pasados).

## 7.5 El calendario de temas presentes y pasados (operativo)

Confirmado en Sesión 4 (checklist ítems 103–107):

1. **Al abrir un tema** (trámite, capítulo 22): el jugador declara el tipo. El sistema asigna la **fecha_foro** (presente = la actual del foro; pasado = la declarada, ≤ actual y con sentido biográfico — edad, edades mínimas, hitos) y comprueba la regla de **un presente a la vez** por personaje (bloquea el segundo). Si es presente, **toma la instantánea** de la ficha del participante (atributos, técnicas, estados).
2. **Durante el tema**: el personaje sigue ganando PP y comprando fuera (los pasados no congelan), pero **la instantánea no cambia**: la resolución del presente usa la ficha del ancla. Verifica que no se usen técnicas/atributos posteriores al ancla dentro del tema congelado.
3. **Presentes = visibles e invadibles**: cualquier jugador con presencia narrativa en la zona puede unirse o invadir (detalle con Mundo Vivo, capítulo 15). Los pasados nunca se invaden y no generan consecuencias en el mundo, recompensas materiales ni ventaja informativa.
4. **Al cerrar**: la skill aplica la banda de tiempo ampliada (pasado 0,70–0,90 · presente 1,00–1,30), libera la congelación (`salio_en`) y registra el desglose en `historico_pp`.
5. **El tiempo dentro del tema** lo declara la narrativa; verifica la coherencia al cierre (un presente no puede durar on-roll más que la ventana real en la que se jugó, redondeada con generosidad) y que el primer post muestra la fecha anclada.

---

# 8. Técnicas: moderación ✅

> Sección operativa correspondiente al capítulo 8 del Manual del Jugador. **Confirmada en Sesión 3**: tiers, tipos, catálogo de efectos, costes y reglas de uso cerrados (checklist ítems 85–91). **Ampliada en Sesión 6 (Revisión 10):** duales con requisitos de atributos duplicados · Sobrecarga (extra-límite).

## 8.1 El flujo del trámite (conexión con 5.21)

1. El usuario rellena el formulario (nombre, descripción, tier deseado).
2. El sistema genera el prompt con los IDs del personaje y del trámite; llega a la zona staff.
3. **`skill-creacion-tecnicas`** modera: genera la ficha completa (requisitos, dominio, descripción depurada, efectos del catálogo dentro del presupuesto, tipo, PA, PE, reposo, puerta de turno) **aplicando el criterio de originalidad** (tiene en cuenta la justificación narrativa del concepto y construye la ficha alrededor de la idea).
4. El staff revisa; si la técnica excede el presupuesto o se queda corta, se ofrecen **alternativas** al usuario ("quita este efecto o sube el coste — ¿qué prefieres?").
5. Ciclo usuario ↔ staff hasta aceptar.
6. Al aceptar: el sistema descuenta PP, comprueba el cupo de INT y publica la técnica en la librería del personaje.

## 8.2 Qué revisar en la moderación

1. **Tier coherente con el presupuesto**: multiplicador del tier + efectos dentro de los slots (T1: 1 · T2: 1 · T3: 2 · T4: 2 · T5: 3). Efectos extra: +25% del coste, máx 1 slot sobre el presupuesto.
2. **Puertas de tier respetadas**: cada efecto del catálogo tiene su tier mínimo (estado I desde T2, área desde T3, controles desde T4, terreno desde T4...).
3. **Requisitos cumplidos**: atributos escalados (T1: 25 → T5: 70/55/40, adaptados al concepto) · dominio ≥ tier (bélico u oficio) · dotes si aplican (Empuñadura de Titán, Estilo Exótico para duales — **las duales llevan requisitos de atributos duplicados**, Revisión 10).
4. **Costes correctos**: PP 60/120/240/400/600 · PA `2 + tier` · PE 10–40% del máx · reposo 1–4 turnos (Épica: 1 vez/tema-trama) · puerta de turno (Maestra 3º, Épica 5º).
5. **Sin duplicados**: una técnica no puede duplicar (o ser casi idéntica) a otra del mismo personaje — se rechaza en automático.
6. **La originalidad como criterio de moderación (directiva del usuario)**: NO es un bono mecánico. La skill evalúa el concepto completo — historia, dominio, raza, técnicas previas y la justificación narrativa de la idea — y construye la ficha alrededor de ella: un humano que gira para incendiar su pierna lleva daño de fuego, con el giro convertido en condición (Carga) y la Quemadura dentro de su tier y presupuesto. La creatividad abre puertas narrativas, no mecánicas; el fallo favorece al jugador.
7. **No-crunch**: los requisitos condicionales de la técnica se declaran en el post del jugador y se verifican en el momento — la moderación no exige cálculos automáticos.
8. **Sobrecarga (Revisión 10):** si el jugador la declara al usar una técnica, verificar el **límite** (1 uso por tema-trama por cada 50 INT, máx 2), el **PE doble** (redondeo hacia arriba) descontado al cierre, y que **no eluda** puertas de turno ni reposos. El +25% se aplica al daño real del intercambio; si el golpe se esquiva o se bloquea, el PE extra se pierde igual.

## 8.3 Checklist de moderación (skill-creacion-tecnicas)

- [ ] Ficha completa generada (nombre, tier, tipo, dominio, requisitos, efectos, costes, puerta).
- [ ] Efectos dentro del presupuesto del tier (o +25% justificado, máx 1 extra).
- [ ] Puertas de tier de cada efecto respetadas.
- [ ] Requisitos (atributos, dominio ≥ tier, dotes) coherentes con el personaje.
- [ ] Costes correctos (PP, PA, PE, reposo, puerta de turno).
- [ ] Sin duplicados con la librería del personaje.
- [ ] Justificación creativa del concepto evaluada y registrada (nota de moderación: cómo se integró la idea en la ficha).
- [ ] Duales con requisitos de atributos duplicados (Revisión 10).
- [ ] Sobrecarga dentro de límite (INT 1/50, máx 2), PE doble y sin eludir puertas/reposos (Revisión 10).

## 8.4 Estructura de datos y automatismos (MyBB)

- **`tecnicas`**: librería del personaje — `personaje_id`, `nombre`, `tier`, `tipo`, `dominio_id` + nivel, `requisitos` (JSON), `efectos` (JSON: multiplicador + efectos), `coste_pp`, `pa`, `pe_pct`, `reposo`, `puerta_turno`, `origen` (creacion/upgrade), `nota_moderacion` (justificación creativa y cómo se integró).
- **`catalogo_efectos`**: el catálogo de 15 efectos — `puerta_tier`, `slots`, `tipo_tecnica_permitido`.
- **Hook de aceptación**: descuenta PP, comprueba el cupo de INT (≤ INT/4), añade a la librería y publica la ficha.
- **Hook de validación de ficha**: nº de técnicas ≤ INT/4 · requisitos cumplidos · sin duplicados.
- **Hook de cierre de combate** (5.10): descuenta PE, aplica reposos y puertas según lo declarado.
- **Panel «Técnicas»**: trámites pendientes (prompt listo) · catálogo de efectos (CRUD) · búsqueda por jugador/tier/tipo · histórico de bonos de originalidad.

---

# 9. Inventario, objetos y equipo: supervisión ✅

> Sección operativa correspondiente al capítulo 9 del Manual del Jugador. **Confirmado (Sesión 4)**: capas del inventario con robo solo del equipado, carga solo por ranuras, escala de 7 calidades Común → Meitou, bono de calidad, efectos por tipo, compensatorio desarmado, consumibles con su coste de PA y saqueo (checklist ítems 97–102 ✅). Los precios en berries se cierran en el capítulo 10 (5.9).

## 9.1 El papel del staff en el inventario

El inventario es de los pocos sistemas que tocas **en dos momentos distintos**: al **validar la ficha** (ranuras, calidades, cupos, consumibles) y al **cerrar temas de combate** (consumibles gastados, integridad, robos). En vivo no intervienes: los jugadores declaran qué usan y qué llevan, y el sistema — con tus verificaciones al cierre — lo registra. Tu trabajo es que las fronteras se respeten: lo equipado es lo único usable y lo único robable, y los cupos mundiales de las Meitou son sagrados.

## 9.2 Qué verificar en la ficha

- **Ranuras**: equipado `3 + FUE/10` + mochila `8 + FUE/4` (redondeo hacia abajo), con los costes por objeto (arma 1/2 manos — Empuñadura de Titán reduce a 1 —, armadura 1, escudo 1, cinturón 1, medianos 1, grandes 2–3) y los modificadores (Tontatta mochila ×2, Herida Permanente −1 de equipado).
- **Calidades**: el bono del arma es el de su calidad (+0…+25); ningún arma inventada ni modificada salta la escala.
- **Cupos mundiales**: las Wazamono+ respetan 50/21/12; el sistema bloquea duplicados, pero tú revisas el traspaso (robo/herencia) de una Meitou — solo válido si su portador cayó en combate.
- **Consumibles**: cantidad en el cinturón ≤ su límite; el `precio_base` queda vacío hasta que 5.9 lo cierre.
- **Malditas**: la penalización activa (equivalente a un defecto) y el estado "domada" (falsa hasta que la trama lo justifique y se registre el hito).

## 9.3 La supervisión del combate y el saqueo

Al cerrar un tema de combate (capítulo 11) verifica y registra:

- **Consumibles declarados y usados**: se descuentan; **el interrumpido se gasta sin efecto** (regla del botiquín).
- **Efectos por calidad**: Hemorragia (cortante, grado según calidad — Superior I · Wazamono I–II · Meitou II–III) y Vulneración (contundente, +1/+2/+3, 1–2 turnos) aplicados a los objetivos que conectaron; desarme de flexibles resuelto con la Tabla 2 (FUE/RES).
- **Integridad**: descuenta los puntos de armaduras y escudos que absorbieron golpes (y las armas que chocaron); un escudo que detiene una técnica puede dañarse o romperse — regístralo.
- **Robo si hubo derrota**: solo lo equipado, nunca mochila ni almacén; una Meitou solo si hubo combate real. El arma robada queda "recuperable por el vencedor" y el cupo se transfiere.

## 9.4 La forja y la producción (trámites de oficio)

- **Herrero** (5.3): forja hasta Superior sin control especial; Wazamono+ exige nivel, materiales, hito narrativo de forja y cupo mundial. Revisa la extensión y la coherencia del hito antes de aprobar.
- **Químico / Médico / Cocinero / Ingeniero**: producen consumibles y artilugios con las curas en % calibradas en 5.3 y los efectos del catálogo — la ficha del objeto sale con su coste de PA (1–3) ya fijado.
- **Carpintero**: repara la integridad de armaduras y escudos (y del barco, capítulo 18).
- El **kairoseki** como añadido a armas de calidad queda reservado hasta 5.18: no lo apruebes por ahora fuera de ese flujo.

## 9.5 Estructura de datos y automatismos (MyBB)

- **`objetos`**: catálogo editable — `id`, `nombre`, `categoria` (arma/armadura/escudo/consumible/herramienta/dial), `calidad`, `efecto_json` (daño, reducción, estado, cura %/PE %, condición), `coste_pa` (número o fórmula), `cantidad_disponible`, `reutilizable`, `precio_base` (hueco para 5.9), `cupo_mundial` (NULL o 50/21/12), `dureza`, `notas`.
- **`inventario_personaje`**: `personaje_id`, `objeto_id`, `zona` (arma1/arma2/armadura/escudo/cinturon/mochila), `cantidad`, `integridad`, `vinculado` (Meitou con nombre).
- **`almacen`**: `personaje_id`, `objeto_id`, `cantidad`.
- **`arma_meito`**: `objeto_id`, `nombre_propio`, `portador_id`, `pasiva` (JSON: efectos del catálogo dentro del presupuesto del grado — Ryo 1 slot · O 2 · Saijo 3 + posible rotura de regla), `maldita`, `domada`, `penalizador` (JSON), `cupo` — el control de cupos mundiales vive aquí.
- **Hooks**: al equipar (valida ranuras y cupos, bloquea duplicados de Meitou) · al cerrar tema de combate (descuenta consumibles, aplica Hemorragia/Vulneración por calidad, descuenta integridad, registra el robo) · al producir (crea el objeto, descuenta materiales, comprueba cupo) · al derrotar a un portador de Meitou (arma "recuperable por el vencedor").
- **Panel «Objetos»**: catálogo CRUD (todo calibrable) · control de **cupos mundiales** (quién porta las 50/21/12) · inventario/almacén por jugador · log de robos y Meitou robadas · histórico de forjas (extensión y materiales).

## 9.6 Abusos a vigilar

- **Cupos mundiales**: un duplicado de Meitou es el error más grave de este sistema — el hook lo bloquea, pero verifica los traspasos manuales (robos, herencias).
- **Pasivas de grado**: la Saijo "rompe una regla puntualmente" por diseño, pero solo si su ficha lo justifica y está registrada — sin ficha, no hay rotura. Aplica el criterio de originalidad de 5.7 (capítulo 8) al aprobar la pasiva.
- **Malditas domadas sin trama**: una maldita no se doma con un post suelto; exige hitos como el arraigo de 5.5 (capítulo 6) y registro del hito.
- **Consumibles en combate**: coste de PA de su ficha (1–3), interrupción con gasto sin efecto y límite del cinturón — revisa que nadie beba pociones ilimitadas.
- **Desarmados**: el bono `FUE × 0,06` no se acumula con un arma (si usas guantelete, aplicas su calidad, no el bono).

## 9.7 Los catálogos menores (operativo)

Tres catálogos cerrados (Sesión 11, `diseno/5.8_catalogos_menores.md`) que reutilizan reglas existentes — sin sistemas nuevos: los venenos usan el estado Envenenado de 5.10 (capítulo 11), los diales las fichas de objeto con integridad (5.8), los materiales el trámite 6 de producción (capítulo 22). **Qué comprobar por catálogo:**

**Venenos y antídotos** (consumibles, 5.8/5.9):

| Objeto | Grado | Efecto | Fabrica | Rareza | Precio base |
|---|---|---|---|---|---|
| Veneno I | I | Envenenado 1% PV/turno + Entumecido o Confundido | Toxicólogo nv1 | Común | 500 ฿ |
| Veneno II | II | Envenenado 2% + Parálisis parcial | Toxicólogo nv3 | Poco común | 2.000 ฿ |
| Veneno III | III | Envenenado 3% + Dormido | Toxicólogo nv5 | Raro | 10.000 ฿ |
| Antídoto I | I | Cura Envenenado I (+ estado extra) | Farmacéutico nv1 | Común | 300 ฿ |
| Antídoto II | II | Cura Envenenado I–II | Farmacéutico nv3 | Común | 1.500 ฿ |
| Antídoto III | III | Cura Envenenado I–III | Farmacéutico nv5 | Poco común | 5.000 ฿ |

Verificar: el **grado** declarado (I–III, la ficha del objeto lo lleva) · el **estado extra** es del catálogo de 5.10 (no-crunch: lo declara el jugador, tú lo verificas al cierre) · el **veneno entra solo si el golpe conecta** (bandas de delta, 5.10) · **no acumulación**: un solo Envenenado con refresco · el **antídoto cubre por grado** (un I no limpia un III) y cuesta su PA en combate (interrupción con gasto sin efecto). El veneno **nunca se sacude** (5.10): sin antídoto o purificación, sigue.

**Diales de las islas del cielo** (objetos, 5.8):

| Tier | Dials | Mecánica | Rareza | Precio base |
|---|---|---|---|---|
| T1 | Bola · Luz · Nubes · Sonido · Imagen | Utilidad (sin combate; el Bola mitiga travesías 5.16 con oficio) | Común | 500–1.000 ฿ |
| T2 | Viento · Agua · Fuego · Calor · Frío · Relámpagos · Corte · Impacto | Absorben y reemiten su elemento — **integridad 300/600/1.000** de su elemento; defensa ante su elemento (+1 tier, 5.7) | Poco común/Raro | 5.000–20.000 ฿ |
| T3 | Propulsión · Hierro | Tácticos: posicionamiento/velocidad · muro de cobertura | Raro | 50.000 ฿ |
| T4 | Rechazo | Devuelve el daño **×2** con **retroceso por comparación FUE/RES** (5.10) | Único (Mercado Negro) | 500.000 ฿ |

Verificar: la **absorción** es una defensa ante el elemento del dial (+1 tier, 5.7) — no absorbe cualquier cosa · la **integridad** se descuenta con cada uso y roto no absorbe hasta repararlo (Ingeniero/Inventor) · el **Rechazo** se resuelve por comparación (sin tiradas) y el retroceso daña al usuario si su FUE/RES no lo cubre · el **acceso Skypiean** (1 T1 gratis a nv3) se valida con el trámite ligero y la raza/tribu del cielo (5.1/5.1-bis) · un dial T4 no se encuentra en una tienda: Mercado Negro (5.13), eventos (5.14) o recompensas.

**Materiales y fabricación** (materias primas, trámite 6):

- Catálogo: maderas (5 calidades de 5.17, 50–5.000 ฿) · minerales por grado (100/500/2.000 ฿) · cuero/textiles (100 ฿) · hierbas curativas (50/200/1.000 ฿) · víveres/especias (50 ฿) · pólvora (200 ฿) · diales en bruto (1.000 ฿) · kairoseki (precio de 5.18) · legendarios (sin precio: eventos 5.14, conquista 5.15, Mercado Negro 5.13).
- **Recolección por escena** (sin trámite): el veredicto al cierre (`skill-cierre-temas`) decide cuánto y qué según la zona (peligrosidad/desarrollo de 5.14); una escena rinde **materiales para 1–2 recetas**; más riesgo mejora el grado, no la cantidad.
- **Fabricación por el trámite 6** (producción de oficio): recetas de **100 unidades** (espada: 50 mineral + 30 madera + 20 cuero · botiquín: 50 hierbas + 30 cuero + 20 alcohol · raciones: 80 víveres + 20 especias · cartuchos: 50 acero + 50 pólvora · dial tallado: dial en bruto + Ingeniero); el atributo rey del oficio es la vara (5.3). *(Confirmado por el usuario: fabricación por el trámite 6 con recetas — sin trámites nuevos.)*
- **Abusos a vigilar**: recolección sin tope (más de 1–2 recetas por escena) · materiales legendarios comprados en tienda (no se compran: se ganan) · venenos acumulados sobre el mismo objetivo (no acumulan) · antídoto de grado insuficiente · dial que absorbe sin integridad.

---

# 10. Economía y tiendas: fluctuación y tiendas de jugador ✅

> Sección operativa correspondiente al capítulo 10 del Manual del Jugador. **Confirmado (Sesión 4)**: berries como única divisa con escala por pisos alineada con la obra (un Yonkou = mín. 3.000.000.000 ฿), cartera/bóveda/cofre de tripulación, fluctuación por zona y ronda con desglose, tiendas NPC al 50%, tiendas de jugador con oficio Comerciante obligatorio y local o módulo de barco (checklist ítems 108–113 ✅). Los precios base de 5.8 (capítulo 9) se cierran aquí.

## 10.1 El papel del staff en la economía

La economía se autogestiona en vivo — los jugadores compran, venden y negocian — pero tiene **tres momentos de intervención del staff**: revisar la **fluctuación** que el Mundo Vivo propone cada ronda, aprobar las **tiendas de jugador** (apertura, ítems bélicos, cierre forzoso) y vigilar los **abusos** del mercado. No calculas precios en vivo: el sistema computa con la fórmula y tú verificas coherencia y motivo antes de publicar. La regla de oro: **nunca hay un precio misterioso** — cada cambio se publica con su motivo y su histórico.

## 10.2 La fluctuación del mercado (qué verificar cada ronda)

Al cierre de cada ronda de Mundo Vivo (capítulo 15), la skill `skill-mundo-vivo` propone los precios por zona con la fórmula:

`precio actual = precio_base × F_oferta × F_demanda × F_suceso`

- **F_oferta** (0,80–1,20): cuánto hay en la zona (producción local, bloqueos, minas agotadas).
- **F_demanda** (0,80–1,20): cuántos quieren (personajes presentes por el calendario de 5.6, guerras, eventos).
- **F_suceso** (0,50–1,50): los sucesos de la ronda (epidemia → suben pociones y antídotos; cosecha → bajan raciones; invasión).
- **Techo y suelo globales 0,5× – 2×** sobre el precio base; redondeo a la decena. Nunca se rompe la economía aunque el mundo arda.

**Checklist operativo en cada ronda:**

- [ ] Los tres factores por zona tienen **motivo narrativo plausible** (no inventes un suceso para justificar tu precio; el precio refleja el suceso).
- [ ] Ningún precio salió de la banda 0,5×–2× (o si salió, es un evento de excepción que justificas).
- [ ] El **desglose y el motivo** se incluyen en el boletín antes de publicar ("los precios de las pociones suben un 30% en X: el almacén de la marina fue bombardeado").
- [ ] El histórico por zona/objeto queda registrado (`precios_mercado`).
- [ ] Los precios de **consumo** (objetos, pociones) y los de **tasación** (Wazamono+) se mantienen en su piso: las armas de grado no entran en el boletín de tienda.

## 10.3 La escala de valores (referencia rápida para moderar)

Los precios base viven en **dos pisos**, y ninguno puede estar en el sitio del otro:

- **Piso de consumo** (objetos, pociones, armas comprables): Inferior 100 · Común 500 · **Superior 2.500** (la compra máxima de tienda) · consumibles 150–3.000 ฿ · herramientas 1.000–15.000 · **rareza** Común/Poco común/Raro/Mercado Negro (200 / 1.000 / 5.000 / 20.000 ฿).
- **Piso de fama** (las Wazamono+ solo se **tasan**, no se venden): Wazamono 25.000 · Ryo 100.000 · O 400.000 · Saijo 1.500.000 · y la cima: **un Yonkou vale mínimamente lo de su cabeza — 3.000.000.000 ฿** (directiva del usuario, Sesión 4).

**Qué vigilar:** que nadie venda una Wazamono+ en tienda como si fuera un arma común, que los precios de tasación no se cuelen en los boletines de consumo, y que la escala alta (recompensas, sueldos, caudales) respire en millones/miles de millones cuando toquen los sistemas de 5.12/5.13/5.14 — no en miles.

## 10.4 Las tiendas de jugador (el trámite, operativo)

Al recibir un trámite de apertura de tienda (capítulo 22), verifica **en este orden**:

1. **Requisito de oficio — Comerciante (obligatorio, directiva Sesión 4).** El jugador debe tener el oficio **Comerciante (5.3 §4.12, nuevo oficio n.º 12, atributo rey CAR)**. **Sin el oficio, rechazas la apertura** — es la regla dura que hace del comercio una especialización. El nivel del oficio habilita el modelo: nv1 tienda estándar + reventa · nv3+ rama **Mercader** (rutas) o **Tasador** (Mercado Negro, tasación).
2. **Los ítems justificados por el oficio.** Si vende *producción* (Herrero la forja, Cocinero la comida, Químico las pociones), el oficio productor justifica el producto y el Comerciante permite montar el negocio. Si es *reventa pura*, debe tener el Comerciante que habilite su modelo. **Rechaza vender fuera de su área de conocimiento.**
3. **Requisito de local o módulo de barco.** Un puesto narrado, un local en territorio conquistado (5.15) o **el barco con módulo de tienda** (directiva Sesión 4, detalle en 5.17). **Vender desde el barco sin el módulo se rechaza**; vender desde tierra no exige módulo. Verifica el local declarado contra el territorio (5.15/5.16) si aplica.
4. **Capital y stock inicial.** Dinero suficiente (o stock producido) en la ficha; el stock sale de producción o del almacén (5.8). La tienda **vende desde el almacén**: lo vendido sale del inventario real del vendedor.
5. **Ítems bélicos del catálogo validado.** Armamento, venenos, somníferos: **solo del catálogo validado (5.8/5.7)**, sin re-moderar mecánicas — solo compruebas que la ficha es válida y que el vendedor tiene derecho. Los ítems inventados al vender se rechazan.

**Reglas de surtido y margen (verificar al aprobar y al auditar):** máx **10 ítems activos** · margen dentro de la banda **−20%/+30%** sobre el precio de mercado de la zona (fuera de banda, rechaza o corrige) · stock máx **10 consumibles / 3 armas-por-ítem** · clasificación Normal/Bélico con los bélicos del catálogo.

**Cierres:** voluntario (ítems al almacén) · forzoso por asalto al local (pierde el stock **expuesto**, no el almacén), conquista de la isla (5.15) o captura del barco (5.17). Al cerrar, suspende la tienda hasta reabrir con trámite; verifica que el stock expuesto de un local saqueado se descuente y que el almacén quede intacto.

## 10.5 Estructura de datos y automatismos (MyBB)

- **`economia_config`**: moneda (berries), banda de fluctuación (0,5×–2×), banda de margen (−20%/+30%), límites de stock (10 ítems, 10 consumibles / 3 armas), redondeo (decenas).
- **`objetos.precio_base`**: el campo reservado en 5.8, ahora con valor (precio de referencia por calidad/rareza).
- **`precios_mercado`**: `zona_id`, `objeto_id`, `precio_actual`, `factores` (JSON: oferta/demanda/suceso), `motivo`, `ronda`, `fecha_foro` (5.6) — histórico acumulativo.
- **`carteras`**: `personaje_id`, `cartera` (en mano, robable), `boveda` (segura). `cofre_tripulacion` aparte (barco/tripulación, 5.17).
- **`tiendas`**: `dueno_id`, `zona_id`, `tipo` (oficio/reventa), `local` (ref. 5.15/5.17 o narrativo), `estado` (activa/suspendida/cerrada), `capital`, `banda_margen`, `notas`.
- **`tienda_items`**: `tienda_id`, `objeto_id`, `precio_venta`, `stock`, `clasificacion` (Normal/Bélico), `origen` (producción/compra).
- **`transacciones`**: `fecha`, `zona_id`, `vendedor`, `comprador` (jugador/NPC), `objeto_id`, `cantidad`, `precio_unitario`, `tienda_id` — el histórico anti-abuso y la memoria del mercado.
- **Cron de ronda (5.14):** al cerrar la ronda, recalcula `precios_mercado` y publica el boletín + motivo + histórico.
- **Hooks:** compra/venta (valida saldo, stock, banda de margen, registra la transacción, mueve el objeto del almacén del vendedor al del comprador) · saqueo (5.8: la cartera es robable al derrotado; la bóveda no) · tienda (apertura/cierre por trámite con validación de oficio/local/ítems bélicos) · anti-abuso (rechaza compraventa consigo mismo, alerta de ventas circulares y precios fuera de banda).
- **Paneles:** **«Mercado»** (precios por zona con desglose y motivo · histórico · boletín editable antes de publicar) · **«Tiendas»** (trámites pendientes · validación de oficio y ítems bélicos · stock y márgenes · alertas anti-abuso) · **«Economía»** (saldos cartera/bóveda por jugador · cofres de tripulación · KPIs: inflación por zona, ítems más vendidos, tiendas activas — alimentan el dashboard de Mundo Vivo, 5.14).

## 10.6 Abusos a vigilar

- **Vender armas de grado en tienda**: las Wazamono+ solo se tasan, no se venden — un arma superior a Superior en un escaparate es un error grave.
- **Precios fuera de banda**: un margen de +80% "porque es raro" se rechaza; la banda es −20%/+30% sobre el mercado de la zona.
- **Ítems bélicos inventados**: un veneno que no está en el catálogo validado se rechaza; si el jugador quiere algo nuevo, pasa por el trámite de creación (5.7) primero.
- **Stock imposible**: vender 50 espadas sin producción ni compra que lo respalde — el stock sale del inventario real.
- **Auto-compra para mover dinero**: registrar ventas a ti mismo o en círculo para inflar el historial — el hook lo marca, tú lo revisas y sancionas con la regla anti-abuso.
- **Compraventa sin oficio/módulo**: una tienda a bordo sin módulo de barco, o una apertura sin el oficio Comerciante — faltan requisitos duros, y sin ellos se rechaza.

---

# 11. Combate: supervisión y resolución ✅

> Sección operativa correspondiente al capítulo 11 del Manual del Jugador. **Confirmado (Sesiones 3–4)**: presupuesto de PA (ancla `6 + AGI/10 + Nivel/5`, redondeo al entero más cercano), tabla de costes por acción, catálogo de 8 defensas diferenciadas y presupuesto típico de turno (checklist ítems 92–96 ✅) · **tablas de delta** (ítems 81–84 ✅) · **catálogo de estados alterados** (ítems 76–80 ✅). Completado (Sesión 5): reglas 2v2/1vN/naval y formato de posteo — secciones 11.8 a 11.11.

## 11.1 El papel del staff en el combate

El combate ocurre **entre jugadores**: el staff no es juez en vivo (no hay nadie detrás de cada mesa). Tu papel es doble y ambos se ejercen **al cierre del tema** (resolución automática de 5.10, no-crunch — los jugadores declaran, el sistema verifica):

1. **Verificar la legalidad de los turnos**: que el presupuesto de PA declarado no se excedió, que las defensas se pagaron y se usaron donde debían, que las técnicas respetaron reposos y puertas, y que ningún turno dictó resultados.
2. **Firmar el veredicto de la resolución**: la IA computa los intercambios con las tablas de delta y los estados; tú revisas y das el visto bueno antes de publicar (el flujo staff ↔ IA de 5.21). Nada se publica sin tu firma.

**Reglas que nunca debes olvidar al revisar un turno:** sin dados · nunca se dicta el resultado ("le acierto el golpe" se invalida) · las reacciones se pagan del presupuesto del turno · las técnicas no se esquivan con un salto (P4: técnica defensiva, racial potente o Haki, salvo ventaja clara de velocidad) · máximo un ataque a un mismo objetivo por turno (P10) · el primer post del combate es introductorio, sin acciones (P9).

## 11.2 El presupuesto de PA (qué calcular y qué verificar)

`PA por turno = 6 + AGI/10 + Nivel/5` — redondeo al entero más cercano (se suman AGI/10 y Nivel/5 y se redondea una sola vez). Referencias rápidas: nv1/AGI 20 → 8 PA · nv25/AGI 58 → 17 PA · nv50/AGI 100 → 26 PA.

Los **modificadores** se aplican sobre el total cuando dicen "por turno", y sobre cada acción cuando dicen "por acción" (se suman, no se multiplican):

| Fuente | Efecto | Origen |
|---|---|---|
| Dote **Preparación** | +1 PA por turno | 5.4 (requisito AGI 40) |
| Estado **Tambaleante** | −1 PA por turno | 5.10 (golpe > 3×VOL) |
| Estado **Parálisis parcial** | +2 PA por acción | 5.10 |
| Estado **Acelerado** | −1 PA en desplazamientos | 5.10 |
| Estado **Desplazado** | No ataca cuerpo a cuerpo ni se desplaza; recuperar posición +1 PA | 5.10 |
| Estado **Congelación** | +1 PA por acción (II: +2) | 5.10 |
| Dominio bélico nv2 | −1 PA en básicos con ese arma | 5.3 |
| Defecto **Herida Permanente** | +1 PA en una acción concreta | 5.4 |
| 1vN (P5, coraje del solitario) | +3 PA por turno (interpretación del "acción extra" confirmado) | P5 adoptada |

## 11.3 Qué revisar en un turno (checklist operativo)

- [ ] **Presupuesto no excedido**: Σ costes declarados ≤ PA total (con modificadores del momento). El sistema marca el post para revisión si excede; tú confirmas.
- [ ] **Defensas pagadas y legales**: cada reacción defensiva declarada tiene su coste (ver 11.4) y pasó por su resolución (Tabla 1 de delta; Tabla 2 si conecta).
- [ ] **P4 respetada**: una técnica no se negó con un salto o una guardia básica — solo técnica defensiva, racial potente, Haki o ventaja clara de velocidad (Tabla 1, defensor −10/−20).
- [ ] **P10 respetado**: máximo un ataque a un mismo objetivo por turno (los multigolpes son técnicas duales o de área, 5.7).
- [ ] **Técnicas**: reposo cumplido, puerta de turno cumplida (Maestra 3º, Épica 5º), PE descontado, sin más límite de número que el presupuesto.
- [ ] **Consumibles**: el coste de PA declarado coincide con el de la ficha del consumible (1–3 PA típico); el interrumpido se gastó sin efecto (botiquín interrumpido).
- [ ] **Primer post** (si aplica): introductorio, sin acciones ni daño (P9).
- [ ] **Sin resultados dictados**: ninguna acción declaró su éxito; las declaraciones dejan abierta la respuesta del rival.

## 11.4 El catálogo de defensas (qué verificar de cada una)

| Defensa | Coste | Verifica al revisar | Contra técnicas (P4) |
|---|---|---|---|
| **Aguantar** | 0 PA | Sin Tabla 1 (renunció a verlo venir): Tabla 2 + umbral del dolor + daño completo − reducciones | Igual (el umbral del dolor manda) |
| **Guardia** | 1 PA | Reduce a la mitad si la Tabla 1 le dio tiempo; si Tabla 2 muy desfavorable: guardia rota/abierta | No vale (P4); reduce solo con ventaja clara de velocidad |
| **Parar** | 1 PA | Tabla 1 con DES: anula si lo para; el peso (Tabla 2) puede empujarlo o dejar las armas trabadas | No vale (P4) |
| **Desviar** | 2 PA | Tabla 1 con DES: anula sin Tabla 2 (no lo recibe); si falla, daño completo | No vale (P4) |
| **Esquivar** | 2 PA | Tabla 1 (PER+AGI): anula si gana, parcial en paridad; contra áreas no basta | Solo con ventaja clara de velocidad (Tabla 1 −10/−20) o técnica defensiva/racial/Haki |
| **Evadir** | 3 PA | Tabla 1 + reposicionamiento 2–4 m incluido | Igual que esquivar (P4) |
| **Bloquear con escudo** | 2 PA | Tabla 1 + integridad del escudo (5.8): absorbe según su ficha; un golpe enorme lo daña/rompe | Un escudo de calidad puede detener una técnica arriesgando su integridad |
| **Técnica defensiva** | 2 + tier (3–7) | Regla de 5.7: anula hasta +1 tier; +2 tiers reduce a la mitad; más, apenas; Épica solo con defensiva superior o Haki | **SÍ — la vía estándar** |

**La textura de la "reacción tardía"** (banda +10 a +19 de la Tabla 1): el defensor puede *cubrirse* (guardia, escudo) pero no *quitarse* (esquiva, parada) — revisa que el veredicto distinga ambos casos.

## 11.5 Estructura de datos y automatismos (MyBB)

- **`acciones_pa`**: catálogo editable — `id`, `nombre`, `categoria` (movimiento/ataque/defensa/objeto/mente/gratuita), `coste_pa` (número o fórmula JSON, p. ej. `{"tipo":"tecnica","formula":"2+tier"}`), `regla` (JSON: qué resuelve — tabla de delta, P4, condición de uso), `notas`. Todo calibrable desde el panel; la tabla del manual se genera desde aquí.
- **`turnos_combate`**: registro por turno/post — `combate_id`, `personaje_id`, `turno`, `pa_total` (recalculado con estados/dotes/1vN), `pa_gastado`, `acciones` (JSON: lo declarado), `reserva`, `veredicto` (pendiente de la resolución de cierre).
- **Hook de posteo**: calcula `pa_total` y marca para revisión los turnos que exceden el presupuesto (no los bloquea: declara, verifica, corrige al cierre).
- **Hook de cierre de combate** (5.10): confirma presupuestos, descuenta reacciones e interrupciones (botiquín gastado sin efecto), aplica PE/reposos/puertas de técnicas y registra el histórico.
- **Anti-abusos**: aviso por excesos sistemáticos de PA (fidelidad de `skill-cierre-temas`) y verificación del P10 (1 ataque por objetivo) en la resolución.
- **Panel «Costes de PA»**: catálogo CRUD de acciones · vista de turnos con `pa_total` vs `pa_gastado` por combate · detección de excesos y encadenamientos imposibles (técnica en reposo, puerta no cumplida, P10) para revisión al cierre.

## 11.6 La resolución de los intercambios (las tablas de delta, operativo)

Cómo se resuelve un combate **al cerrar el tema** (resolución automática de 5.10 — no-crunch: los jugadores declaran, el sistema verifica):

1. El sistema toma las **declaraciones estructuradas** de cada turno (quién atacó, con qué; quién defendió, con qué).
2. Aplica los **matices narrativos** (tabla abajo) a los valores efectivos — siempre *antes* de calcular el delta, nunca después.
3. Computa los **deltas** (atacante − defensor) en el orden fijo de las cuatro preguntas (tabla abajo).
4. Genera el **veredicto** de cada intercambio (conecta / esquiva / bloquea / choque / estado entra o se niega / retroceso / derribo...).
5. Actualiza PV/PE/estados y lo deja listo para tu **revisión y firma** antes de publicar (el flujo staff ↔ IA de 5.21).

| Orden | Pregunta | Comparación | Decides |
|---|---|---|---|
| 1 | ¿Lo ves venir? | DES/AGI del atacante vs **PER + AGI** del defensor | Conecta, se esquiva, se bloquea o choca |
| 2 | ¿Lo aguantas? | **FUE** del atacante vs **RES** del defensor | Retroceso, guardia rota, derribo o plantarse |
| 3 | ¿Te afecta la mente? | **CAR/INT** del atacante vs **VOL** del defensor | Un estado mental entra o se niega |
| 4 | ¿Cuánto te cuesta? | **Daño** del golpe vs **VOL** del defensor | Sacudido → Tambaleante → Desorientado (umbral fijo) |

**Las bandas** son unificadas en las tres tablas: Δ ≥ +20 dominación · +10 a +19 ventaja clara · +5 a +9 ventaja leve · −4 a +4 paridad · −5 a −9 desventaja leve · −10 a −19 desventaja clara · Δ ≤ −20 dominación en contra.

**Qué verificar en cada tabla (resumen operativo):**

| Tabla | Claves | Puntos de revisión |
|---|---|---|
| 1 — ¿Lo ves venir? | DES/AGI vs PER+AGI | Banda correcta · **P4**: una técnica solo se niega con técnica defensiva, racial potente o Haki — salvo ventaja clara (defensor −10 → técnicas básicas/medias; −20 → avanzadas) · daño +25% (pleno) y +50% (aplastante) en bandas altas del atacante · **choque** en paridad si ambos atacaron |
| 2 — ¿Lo aguantas? | FUE vs RES | Guardia rota (bloqueo sin reducción) / abierta (siguiente golpe ignora la mitad) · **Desplazado** · derribo (+25% al remate) · retroceso (+1 PA) · plantarse / inamovible / como una roca — conexión con el estado Desplazado |
| 3 — ¿Te afecta la mente? | CAR/INT vs VOL | Entrada plena / media / paridad / resistido (insistir cuesta doble PE) / negado (defensa del atacante +1 PA; en ≤ −20, el intento se vuelve contra el atacante: Sacudido mental 1 turno) |
| Umbral del dolor | Daño vs VOL (fijo) | Daño > VOL → Sacudido (interrumpe cargas) · > 3×VOL → Tambaleante (−1 PA) · > 5×VOL → Desorientado (Confundido) |

**Fórmula de daño (la computa el sistema al cierre; verificar que el desglose coincide):** cuerpo a cuerpo `FUE×0,2 + DES×0,1 + Nv²×0,012` · a distancia `DES×0,2 + FUE×0,1 + Nv²×0,012` · + bono de calidad del arma (5.8) o bono de desarmado `FUE×0,06` · mínimo **1 PV**. El resultado alimenta la pregunta 4 (umbral del dolor) y los daños residuales de estados.

**El choque:** paridad de Tabla 1 + ambos declararon ataque → las técnicas se cancelan, nadie recibe daño; FUE compara (Tabla 2, ambos como atacantes): quien gana por +10 empuja (Desplazado 1 turno); paridad total → quedan trabados (agarre de armas: empujar, romper o desengancharse gastando la acción).

**Los matices** (se aplican a los valores efectivos *antes* del delta; regla de oro: **el matiz afina, no invalida**):

| Matiz | Efecto | Tabla |
|---|---|---|
| Distancia | Cuerpo a cuerpo (< 3 m): −3 PER del defensor · media: normal · larga (> 15 m): +3 PER para reaccionar, −2 AGI para esquivar proyectiles | 1 |
| Entorno | Niebla/humo/oscuridad: −4 PER (Cegado si es total) · terreno inestable: −2 AGI · viento fuerte: −2 DES a proyectiles | 1 y 2 |
| Estados del defensor | Tambaleante −1 PA · Confundido −AGI · En guardia +defensa · Cegado: PER a 0 (no hay Tabla 1) | 1 |
| Sorpresa | Emboscado: no hay Tabla 1 — solo técnica defensiva/Haki/Mantra niega el primer golpe | 1 |
| Ventaja numérica (1vN, P5) | El solitario +2 PER/AGI por cada enemigo que no le ataca ese turno | 1 |
| Terreno táctico | ±2 a ±5 según lo roleado y validado (altura, cobertura, ángulo ciego) | 1 y 2 |

**Anti-abuso:** el sistema marca intercambios donde la narrativa declarada contradice el veredicto de la tabla (alguien escribió "le acierto el golpe" en un delta donde el defensor lo lee) — se corrige en el cierre, no en vivo (regla del maestro: no se dicta el resultado).

**Estructura de datos y panel:** `resoluciones_combate` (log por intercambio: tabla, delta computado con matices, banda, resultado, veredicto narrativo) y `matices_combate` (catálogo editable de matices — ver Anexo A) · Panel **«Resolución»**: detalle intercambio a intercambio con ajuste de matices y regeneración antes de publicar · histórico de bandas (¿los deltas del foro están sanos?) como input para recalibrar.

## 11.7 Los estados alterados (operativo)

Los estados se **declaran en el post del jugador** ("mi técnica conecta y aplica Entumecido") y tú los **verificas al cierre**. Qué comprobar:

1. **Aplicación**: el estado entra cuando la técnica u objeto conecta (bandas de delta) y, si es ofensivo, hace daño; las técnicas de soporte lo aplican directamente. **Surte efecto desde el siguiente turno** del golpe.
2. **P4**: un estado que viaja en una técnica no se negó con un básico — solo técnica defensiva, racial potente, Haki o ventaja clara de velocidad.
3. **Resistencia**: mentales → VOL vs CAR/INT (Tabla 3); físicos → evitados con la defensa o aguantados con RES (comparación FUE/RES).
4. **Sacudida**: mentales → acción de concentración (2 PA) comparando VOL contra el valor que aplicó el estado; físicos → acciones concretas o tratamiento (Médico, Cocinero, Químico, objetos — 5.3/5.8). **El veneno nunca se sacude**: exige antídoto o purificación.
5. **No acumulación y anti-spam**: el mismo estado refresca duración (no suma); grados I→III suben con técnicas superiores; los **controles** (Dormido, Parálisis total, Encantado) solo 1 vez por combate por técnica — el hook lo bloquea por `tema_id` + técnica.
6. **Daño residual**: en % de PV máx (1–3%/turno según grado), **ignora reducciones planas**; se computa por turno al cierre, y lo gastado sin efecto (botiquín interrumpido) no se devuelve.
7. **Terminales y umbrales de vida**: 0 PV = KO (no muerte) · muerte en PV ≤ −(VOL×2) o PE ≤ −RES · umbrales 80/50/20% (el <20% "al límite" añade +1 turno de reposo a las técnicas) · **Eco del Dios Perdido** (Bucaner), **Segunda Oportunidad** y **Mártir** (5.4) se declaran aquí · **la muerte abre el trámite 62** (capítulo 22) al cierre del tema: la confirmación del umbral, la banda de calidad (descuidada/digna/leyenda) y la herencia son veredicto con motivo — con la ventana de reanimación del **Fénix despertado** (5.18) dentro del mismo tema.

**Integración rápida (raciales y oficios → estados):** Electro (Mink) → Entumecido · Canto hipnótico (Sirena) → Encantado · Furia de batalla (Oni) → Furioso · Constitución demoníaca (Oni) → resiste Sacudido/Miedo · Toxicólogo → Envenenado/Confundido · Hipnotizador → Dormido · Chef de Batalla → Motivado · Médico → cura residual y estados · Alquimista → elixires (5.3). Haki (5.19): Armadura reduce y resiste elementales · Mantra niega Emboscado y anticipa · Conquistador → Terror/Dormido.

**Abusos a vigilar:** spam de control (más de una aplicación del mismo control en el combate), estados declarados que la técnica no porta (su ficha, 5.7), daño residual mal contabilizado, técnicas usadas en reposo o antes de su puerta de turno.

**Estructura de datos y panel:** `estados` (catálogo) y `estados_activos` (lo que un personaje tiene en combate, con el valor que aplicó el estado para las sacudidas — ver Anexo A) · hooks: al postear (sugerir estados activos y descontar turnos), al cerrar (computar qué estados entraron, se resistieron o se interrumpieron + daño residual), anti-spam de control · Panel **«Estados»**: catálogo CRUD (todo calibrable), registro por combate con su veredicto, detección de abusos.

## 11.8 El combate en grupo: la sala, los turnos y la cobertura (operativo)

Cuando un combate involucra a más de un combatiente por lado, el sistema reutiliza todo lo confirmado (PA, defensas, tablas de delta) y añade reglas de orden y de escala. Qué supervisar:

**La sala — tope de 5 (ítem 121, confirmado).** Un combate de grupo es una sala con **máximo 5 combatientes** en cualquier reparto (2v2, 2v3, 1v4, naval). Es un tope de sala: si más gente quiere entrar, se resuelve con **salas paralelas** o por **invasión de tema (5.14/5.21)**, no metiendo a todos en el mismo duelo. El hook de `sala_combate` (Anexo A) debe bloquear la sexta entrada en un combate abierto — y el staff, ante un intento, redirige a sala paralela o invasión.

**Regla de foco.** Un ataque individual va contra **un solo objetivo**. Pegar a dos a la vez es territorio del solitario (§11.9) o de técnicas de **área permitida** — el hook valida la ficha de la técnica (5.7) para que un corte simple no golpee en abanico.

**Turnos individuales intercalados (ítem 115, confirmado).** No hay ronda de bando: cada jugador postea por turno, alternando entre bandos, viendo la Zona B del post anterior. **No hay PA compartido de equipo**: cada personaje tiene su propio presupuesto y su ficha; la coordinación es táctica, no una bolsa común. Las técnicas **combinadas (5.7)** se declaran en turnos consecutivos y se resuelven al cierre como una única acción con su reposo global.

**Orden de resolución al cierre.** Las acciones que exigen reacción del rival (ataques) se resuelven **antes** que las que no la exigen (interactuar, curar, moverse sin riesgo): un ataque puede interrumpir un botiquín en el mismo turno. Quienes actúan contra el mismo objetivo se resuelven **por pares** con las tablas de delta, en el orden en que se postearon (ver flujo de cierre en 11.6).

**Cobertura de aliados — interponerse (ítem 118, confirmado).** Un aliado puede recibir una técnica individual dirigida a un compañero: **cuesta los PA de la propia reserva del turno** (si ya gastó, no puede cubrir) y **la narrativa manda** (puede llegar a tiempo y cubrir al compañero). Cuando se interpone, **el ataque se resuelve contra él** (RES/aguantar en Tabla 2, su estado final). Verificar: que quien se interpuso tuviera PA disponible, que la narrativa lo permitiera, y que el anti-spam de control y la lógica de sala eviten cadenas infinitas de "yo me interpongo".

## 11.9 El solitario (1 contra varios): las palancas (operativo)

El jugador rodeado por número tiene tres palancas **confirmadas (ítems 116–117)**. Qué verificar:

1. **+3 PA por turno.** El solitario suma **3 PA** a su presupuesto base `6 + AGI/10 + Nivel/5` (es el "acción extra" de P5 interpretado en la tabla de PA, ítem 95). No es un bono de daño: es capacidad de seguir jugando. El hook de validación de turno debe sumar esos +3 solo cuando hay más de un oponente activo contra él.
2. **Golpe a 2 / cobertura de 2, con área.** El solitario puede **dañar a 2 oponentes con un ataque de área permitida** y **cubrir 2 ataques con una defensa** (onda expansiva, barrido, giro). Cada objetivo se resuelve **con su propia tabla de delta** — no es daño bonificado gratis. Verificar que la técnica o básico de área usado sea explícitamente de área o que el combate lo permita narrativamente; un corte simple no pega a dos.
3. **Reducción del daño recibido.** El solitario recibe menos daño por desventaja numérica:

| Desventaja | Reducción del daño que recibe el solitario |
|---|---|
| 1v2 | −10% |
| 1v3 | −20% |
| 1v4 o más | −30% |

   La reducción **solo aplica al daño que recibe**,nunca al que causa, y **no se acumula** con otras fuentes de reducción de la misma naturaleza. Es supervivencia, no poder. Al cierre, el sistema debe aplicar la reducción según el conteo de oponentes activos (no reclamada por el jugador — se computa en cierre).

**Qué NO cambia en el 1vN:** el solitario no gana técnicas, no reduce costes de PA, y su ficha es la misma (solo +3 PA). Y el **matiz 1vN** de las tablas de delta (ítem 84) sigue vigente: el rodeado pierde opciones de huida y visión. Ambos se suman sin contradecirse — no dobles penalizar ni dobles premiar.

## 11.10 El combate naval: la capa sobre el combate normal (operativo)

El combate naval es una **capa sobre el combate normal** (ítem 119, confirmado): no inventa un mini-juego. Qué supervisar:

**Las personas se combaten igual.** PA, defensas y tablas de delta se aplican sin cambio; la cubierta y el balanceo son matices narrativos. **Matiz fijo en cubierta: −2 AGI efectiva** a quien no está acostumbrado a luchar sobre las olas (afina, no invalida).

**El barco es un aliado sin PA propio.** El barco aporta su ficha (casco/PV, armamento, maniobra, módulos — 5.17) pero **no tiene PA propios**: sus acciones las ejecuta la tripulación **gastando su propio PA**. El Timonel maniobra con su PA; el Artillero dispara con su PA. Supervisar que el gasto de barco salió del PA del personaje correcto y que ese PA no se usó dos veces.

**Cañones como "técnicas del barco":** disparar **2 PA** · recargar **1 PA** (un cañón pesado no dispara cada turno). El **apuntado se resuelve con PER del Artillero contra la maniobra del Timonel rival en la Tabla 1** (percepción/velocidad): si el delta favorece al cañón, impacta el casco; si favorece al timón, se esquiva/se rompe la línea de tiro. **El daño va al casco (PV de estructura, 5.17), no a los personajes** — salvo matiz narrativo de metralla en cubierta.

**Hundir el barco NO ejecuta a la gente (regla clave).** Cuando el casco llega a 0, el barco queda inservible (se hunde o naufraga), pero **quién sobrevive se decide con los estados terminales normales** (PV del personaje, marea como matiz peligroso). Hundir el barco del rival es una **puerta de victoria**, no la matanza automática de su tripulación. Vigilar el abuso inverso: que nadie "mata" a toda la tripulación con un solo cañonazo al casco — la reducción/protección del sistema lo impide.

**Maniobra y abordaje.** El Timonel ejecuta la maniobra (ángulos de tiro, alejarse, perseguir, acercarse al abordaje) con su PA. El **abordaje** lleva el duelo naval al combate de personas: al entrecruzar barcos, cualquiera puede saltar a la otra cubierta con una acción de movimiento (1 PA/2 m + matiz de riesgo si el mar está picado), y desde ahí es **un combate de grupo normal** con cada barco como sala invadida.

**Quién está en la sala.** El combate naval es una **sala de máximo 5 como las demás**; los tripulantes fuera de la sala quedan **fuera de combate por rol**: manejan el barco fuera de las acciones del duelo o quedan expuestos a invasión y al Mundo Vivo (5.14) si el casco se daña. El capitán decide quién sube a la sala — no es una restricción del sistema.

**Sin progresión de barco.** El barco no tiene economía ni ficha de progreso propia: todo lo que se gana en un combate naval lo gana el personaje que lo protagoniza. El daño al barco, las maniobras y los abordajes **sí pesan** en `skill-cierre-temas` (el barco arriesga algo real) — ver Anexo B.

## 11.11 El formato de posteo y el botón (operativo)

El combate se postea en **dos zonas** (ítem 114, confirmado; detalle de diseño en `diseno/5.10_reglas_grupo_naval_posteo.md` §5). Qué supervisar y qué verifica cada automatismo:

**Zona A — narrativa libre.** El rol puro: el jugador declara la acción, **jamás el resultado** (regla nº 3). Respeto del mínimo de 350 palabras si el post aspira a puntuar en cierre.

**Zona B — las cartas del turno.** Debajo de la narrativa, el jugador compone su turno jugando cartas: una por técnica (nombre, tier, tipo, PA/PE, puerta de turno, reposo), una por consumible (coste de PA en su propia ficha — 5.8), una por estado activo, panel de modificadores del turno (raciales, dotes, Haki, 1vN, naval) y panel de contadores (PV/PE/PA resultantes, reposos, grados, integridad del barco).

**El botón: validar + publicar.** Dos funciones en vivo y una en cierre:
1. **Valida el turno en vivo** — presupuesto de PA (incluido el +3 del 1vN), coherencia de las cartas con la ficha, incompatibilidades evidentes y limitaciones (P10: 1 ataque por objetivo · puertas y reposos de 5.7). Los excesos (p. ej. gastar 8 PA de 6) se marcan como **avisos para el staff**, no se bloquean — no se frena la narrativa.
2. **Publica la Zona B junto al post** — la mecánica es **pública y legible por el rival**, que actúa en consecuencia. No hay cartas escondidas.
3. **Persiste la acción para el cierre** — el botón NO es un motor de resolución en vivo; rees la de veredicto al cierre del tema (11.6): el staff/IA computa las cartas con las tablas de delta (quién conecta, quién invalida, qué interrumpe un botiquín si el rival lo corta en su turno) sobre los datos ya publicados.

**Primer post de posicionamiento (ítem 120, confirmado).** El **primer post del tema de combate — 1v1, grupo o naval — no lleva acciones bélicas**: describir el lugar, desenfundar, amenazar, fijar el orden de la sala y declarar estados iniciales. **Nadie recibe daño en el primer round de nadie.** Verificar que no se ejecutan técnicas ni daño en ese primer round. Excepciones de invasión (emboscada en marcha) las resuelven 5.14/5.21, no esta regla.

**Anti-abuso a vigilar:** gasto de PA doble (personaje y barco del mismo pool contado dos veces), solitario que reclama la reducción de su propio daño causado, más de 5 en una sala, técnicas de área usadas como daño individual (o al revés), otorgar daño a personajes con cañonazos al casco, y cartas que no corresponden a la ficha del jugador.

---

# 12. NPCs: supervisión y gestión (operativo) ✅

> Sección operativa correspondiente al capítulo 12 del Manual del Jugador. **Confirmado (Sesión 6)**: estructura (checklist ítems 122–125 ✅) · interpretaciones 2.4 resueltas (reclutamiento como uso · tipos fijos sin ascenso · conocimientos bestiario vs. reclutado · hordas diferidas a 5.15) · capa técnica y panel.

## 12.1 El papel del staff en los NPCs

Los NPCs son **del foro**: los crea y los mueve el equipo. En la práctica:

- **Creación exclusiva:** solo el staff y los narradores (5.20) escriben fichas de NPC — los jugadores no. La creación es interna, no pasa por el trámite de jugador (5.21).
- **El primario es un personaje:** se crea con la misma ficha que un jugador (5.1–5.9, `es_NPC = true`) y pasa la misma validación (`skill-validacion-personajes` no distingue). Un primario mal balanceado es un primario mal creado.
- **El bestiario se genera desde el panel:** secundarios uno a uno, terciarios por lotes. El sistema calcula PV/PE/PA al guardar; el staff fija nivel, atributos y acciones.
- **No-crunch intacto:** los efectos de ficha del NPC se declaran y verifican como los de un jugador; el NPC no progresa con PP (foto fija) y su tipo es fijo (sin ascenso — sección 12.6).

## 12.2 La capa oculta del primario: los 7 campos (operativo)

El primario lleva, además de la ficha visible, un bloque **solo-staff** (`npc_primario`). Para qué sirve cada campo:

1. **Análisis de personalidad** — la guía para que cualquier narrador lo interprete con coherencia en escenas no preparadas.
2. **Triggers** (`situación → conducta`) — la base de sus reacciones sorprendentes pero coherentes.
3. **Intenciones ocultas** — el material de las tramas de 5.14 y de los giros del periódico.
4. **Historia completa** — la verdad, descubrible narrativamente por los jugadores (ese descubrimiento es trama).
5. **Decisiones predefinidas (físicas y psicológicas)** — derrota, mutilación, duelo, soborno, familia, autoridad, traición: decididas de antemano para que la presión no lo vuelva improvisado.
6. **Vínculos con el Mundo Vivo (5.14)** — su agenda variable y qué eventos lo moverían: es lo que el hook de ronda lee para decidir si reacciona.
7. **Notas de moderación** — límites de uso, protección narrativa de su muerte, qué puede hacer un narrador con él.

**Regla de visibilidad:** el jugador ve la ficha visible completa y la historia pública; la capa oculta no se sirve al público. Verificar que ningún hook ni panel filtre datos de `npc_primario` a la vista de jugador.

## 12.3 El bestiario: qué comprueba el panel (operativo)

- **Atributos reales obligatorios:** sin ellos no funcionan las tablas de delta de 5.10 (comparan PER/Velocidad, FUE/RES, Mente/VOL) ni el PA (`6 + AGI/10 + Nivel/5`). El panel calcula PV/PE/PA al guardar con las fórmulas de 5.2/5.10 — el staff no los inventa.
- **Acciones con coste de PA (2–6):** básicos con la fórmula de daño de 5.10, técnicas de 5.7 (2 + tier, PE, reposo, puertas) si el jefe las lleva, maniobras especiales en prosa y las defensas del catálogo que usa. Verificar al cierre que el NPC gastó el PA que le corresponde y respetó reposos y puertas.
- **Pseudo-personalidad + fases de jefe:** la conducta por turno (2–3 rasgos) y, en los jefes, el cambio de táctica por umbrales de PV. Al cierre, la conducta debe haberse respetado (o justificado narrativamente).
- **Terciarios por lotes:** desde el panel; cada instancia lleva su PV/PE actual en `npc_apariciones`.

## 12.4 Elegir un NPC justo (operativo)

- **El nivel exacto es la verdad:** de él se calcula todo — PV/PE/PA, daño, desnivel en las tablas de delta — con la escala 1–50. Las bandas (terciarios ~1–15 · secundarios ~15–35 · primarios ~25–50+) son orientativas de catálogo: un terciario de nivel 30 existe si la historia lo pide.
- **Desnivel ~30+ = tier terciario** (esqueleto de 5.10): a esa diferencia el débil cae en un intercambio. Elegir la ficha con el desnivel que la trama quiere: terciario de tu nivel = obstáculo leve · secundario +3 a +8 = jefe serio · primario de nivel similar = duelo entre iguales.
- **Peso en el cierre:** el tipo y el nivel del NPC alimentan el factor de riesgo de `skill-cierre-temas` y la recompensa de cazarrecompensas (5.13) — cualitativamente: terciario = riesgo bajo · secundario de tu nivel = desafío serio · primario cercano = enfrentamiento entre personajes.

## 12.5 El reclutamiento: trámite de uso (operativo)

- **Uso, no creación (2.4 confirmado):** reclutar = **asignar el uso de una ficha existente del catálogo**. Se aprueba por trámite (5.21); la ficha la mantiene el staff.
- **El reclutado es un tripulante sin ficha de combate (2.4 corregido):** deja de jugarse como bestiario en los combates (5.10). Su ficha se marca «reclutado» y queda fuera del catálogo activo mientras dura el vínculo. Si una trama lo arrastra a la pelea, se juega puntualmente con ficha de bestiario como a cualquier NPC — no es su función.
- **Utilidades reales:** el reclutado aporta en **navegación (5.16)**, **crafteo/producción (5.3/5.8/5.9)** y **entrenamiento (5.6)** con las reglas normales — **algunos son muy buenos** (un navegante veterano reclutado aporta de verdad). A diferencia del bestiario (sin conocimientos efectivos), la ayuda del reclutado es mecánica real.
- **Sin progresión y con retirada:** no crece con PP (foto fija) y el foro puede retirarlo si la historia lo pide. Registrar el vínculo (tripulación, barco) para 5.16/5.17 y su histórico.

## 12.6 Reglas de uso y anti-abuso (operativo)

1. **Quién los maneja:** staff y narradores (5.20). Un jugador nunca dicta el resultado de un enfrentamiento con un NPC: se resuelve con 5.10 y el NPC se juega con su ficha.
2. **Bestiario sin conocimientos efectivos:** un NPC del bestiario no resuelve tramas con oficios (5.3) ni produce/enseña para ventajas mecánicas — un herrero terciario no forja gratis una Wazamono. Su ayuda es escena (tinte narrativo). Los primarios (ficha completa) y los reclutados sí tienen oficios reales, con las reglas normales de 5.3/5.8/5.21.
3. **Tipos fijos (2.4 corregido):** sin ascenso de tipo. El cambio de tipo es **reescritura editorial** del staff, motivada por la historia — no una progresión del NPC.
4. **Derrota y muerte:** veredicto de 5.10 (KO a 0 PV, muerte a −VOL×2), nunca por decisión de un post. Terciarios caen normal · secundarios con consecuencias (5.12/5.13/5.14) · **primarios como evento de trama**: su muerte la aprueba el staff y 5.14 la procesa (la facción lo venga, el periódico lo cuenta).
5. **Propiedad y continuidad:** las fichas son del foro; un NPC derrotado no reaparece por inercia — si vuelve, con trama que lo justifique.
6. **Hordas diferidas a 5.15:** la masa de terciarios se resuelve con fichas individuales o la escala de sala de 5.10; si la conquista (5.15) necesita combates de ejércitos, la horda se retomará allí.

## 12.7 Ciclo de vida y Mundo Vivo (operativo)

1. **Creación (staff)** → 2. **Publicación** (catálogo de primarios o bestiario) → 3. **Uso en temas** (staff/narrador; si reclutado, miembro de utilidades) → 4. **Reacción de la ronda (5.14)**: el hook de ronda lee `vinculos_mundo_vivo` de los primarios y el estado de los bestiarios; mueve agendas, publica misiones derivadas y ajusta recompensas (con motivo e histórico) → 5. **Reescritura editorial** (cambio de tipo, solo staff) → 6. **Derrota o cierre** (veredicto de 5.10; huella en la matriz de islas y en el periódico).

## 12.8 Estructura de datos y panel (MyBB)

- **`personajes`** — ampliada con `es_NPC` (bool) y `tipo_npc` (`'primario'` | NULL). La ficha visible es la pública; la capa oculta no se sirve al público.
- **`npc_primario`** (1:1 con `personajes`) — la capa oculta: `personalidad` · `triggers` (JSON) · `intenciones_ocultas` · `historia_completa` · `decisiones` (JSON físicas/psicológicas) · `vinculos_mundo_vivo` (JSON) · `notas_moderacion`. Acceso solo staff/narradores.
- **`bestiario`** — `nombre` · `tipo` ('secundario'|'terciario') · `origen_faccion` · `zona` · `nivel` · `atributos` (JSON: 8 valores efectivos) · `pv_max`/`pe_max`/`pa` (calculados al guardar) · `acciones` (JSON con coste PA + técnicas 5.7 referenciadas) · `defensas_usa` · `pseudo_personalidad` (JSON con fases si jefe) · `nota_narrativa`.
- **`npc_apariciones`** — la instancia por tema: `bestiario_id` · `tema_id` · `pv_actual`/`pe_actual` · `estados` (JSON) · `manejado_por` · `estado` (activo/derrotado/retirado/reclutado).
- **Hooks:** validación de ficha de primario (reutiliza `skill-validacion-personajes`) · cálculo de secundarios al guardar · veredicto de cierre de combate (5.10) sobre apariciones · **hook de ronda de 5.14** que lee `vinculos_mundo_vivo` para mover primarios y ajustar su recompensa.
- **Panel «NPCs»:** gestión de primarios (capa visible + oculta), editor de bestiario, apariciones por tema (incluida la marca «reclutado») e integración con los paneles de 5.14 (agenda variable) y 5.20 (quién narra qué).

---

# 13. Facciones: catálogo y ascensos (operativo) ✅

> Sección operativa correspondiente al capítulo 13 del Manual del Jugador. **Confirmado (Sesión 7)**: catálogo canon de 8 facciones + subfacciones de élite (Gorosei/Shichibukai) solo-staff, las 4 capas de fama, los rangos y el procedimiento de ascenso, los sueldos (solo Marines y Gobierno Mundial), el peso en el Mundo Vivo y las reglas de cambio, deserción e infiltración.

## 13.1 El papel del staff en las facciones

Las facciones son **el andamiaje político del mundo**, y el staff es quien les da vida y orden. Concretamente:

- **Gestiona el catálogo** de facciones y sus rangos (CRUD desde el panel «Facciones»), incluidos los **cupos** de la cúspide.
- **Custodia las 4 capas de fama** de cada personaje (renombre, fama/infamia, reputación, Wanted): los valores los propone el sistema al cerrar temas/rondas, pero **el staff los confirma** antes de que se apliquen (no-crunch).
- **Firma todos los ascensos** (especialmente los de rango con cupo), los cambios de facción, las deserciones y las infiltraciones.
- **Concede las subfacciones de élite** (Gorosei, Shichibukai) — y solo el staff.
- **Revisa y publica los sueldos** de Marines y Gobierno Mundial por ronda (actividad + rango), enlazado con la economía de 5.9.

**Directiva de fama (5.12):** hay **cuatro capas** que se leen distinto — renombre (magnitud), fama/infamia (signo), reputación interna y Wanted (criminales). En este foro **no se usan nombres canon como contenido de personajes** salvo para las facciones (directiva del usuario, Sesión 7): el nombre del grupo es canon, pero los **linajes, personajes y eventos** del mundo son originales.

## 13.2 El catálogo: qué supervisar (operativo)

Las 8 facciones jugables y sus características de supervisión:

| Facción | Termómetro dominante (para subir) | ¿Sueldo? | Qué vigilar en el panel |
|---|---|---|---|
| **Piratas** | infamia + proezas | No | Wanted y cupo de la cúspide |
| **Marines** | servicio + poder | **Sí** | sueldos y rama Inspector |
| **Gobierno Mundial** | resultados + confianza | **Sí** | sueldos e inteligencia |
| **Revolucionarios** | lealtad + liberaciones | No | células y traiciones |
| **Bajo Mundo** | red + solvencia | No | encargos, red y mercado (5.13) |
| **Cazadores** | cobras + capturas | No | cobras y capturas |
| **Civiles** | influencia + economía | Solo nobleza | nobleza y renombre |
| **Aventurero libre** | logros + renombre | No | (puerta de entrada) |

**Aventurero libre** es además la **puerta de entrada** (un personaje empieza libre y entra en una facción cuando la historia lo manda): se resolvió que no se fusiona con Civiles para no solaparse con la rama Nobleza (ítem 135-bis, decisión 2.4).

### Subfacciones de élite (solo-staff)

- **Gorosei:** cúpula del Gobierno, **solo NPC** (evento de Mundo Vivo, 5.14). Nunca un personaje jugador: si un usuario lo pidiera, se trata como contenido de Maestro/Staff (5.11/5.20), no como personaje.
- **Shichibukai:** nombramiento de legitimación que **puede recaer en un jugador**; se **concede** (no se pide) por oferta del Gobierno, con motivo de Mundo Vivo. Cupo **7**. Romper las condiciones revoca el título y **crece el Wanted** del personaje (5.13).

## 13.3 Las 4 capas de fama (operativo)

Al cerrar temas (`skill-cierre-temas` §8, ver Anexo B) el sistema propone valores de fama por personaje. El staff revisa la **bandeja de fama** y confirma o ajusta **cada capa por separado**:

1. **Renombre global** — escala 1–8 (1. Desconocido → 8. Mito); lo alimenta la magnitud y frecuencia de las acciones ponderadas.
2. **Fama / Infamia** — signo + magnitud; el sabor moral de esa fama.
3. **Reputación de facción** — entero acumulado; el que más decide ascensos.
4. **Wanted** — para criminales; lo cuantifica 5.13, pero la tendencia nace aquí del grado de infamia.

**Regla:** estos cuatro son valores de interpretación, que **no se calculan en vivo ni se compran**. El jugador ve su ficha, pero nunca "optimiza" nada más allá de vivir su historia.

## 13.4 Supervisar los ascensos (operativo)

El ascenso sigue el procedimiento del Jugador (13.4) y el detalle de `diseno/5.12_facciones.md` §4.0. Tu papel al firmarlo:

1. **Comprueba el termómetro** (¿ha hecho el tipo de acciones que su facción premia? según §13.2). Si no hay el tipo de acción real, **ni el mejor historial de PP asciende**: eso es antifarm. **Bajo Mundo (5.13):** el termómetro «red + solvencia» se verifica en los paneles «Redes» y «Bajo Mundo» — red activa (mantenimiento al día), rumores contrastados vendidos con fiabilidad (histórico de `rumor_operaciones`) y capital solvente. Sin red real ni ventas verificadas, no hay ascenso, por muchos PP que tenga. **Cazadores (5.13):** el termómetro «cobras + capturas» se verifica en el histórico de carteles cobrados (`recompensas_historico`) y en los temas de captura resueltos con veredicto de 5.10 — matar sin entrega no es captura, ni captura sin cobro registrado es cobra.
2. **Cruza el umbral del rango** (cualitativo) con el expediente: reputación de facción (requisito duro), renombre, y poder donde la facción lo exige (facciones bélicas).
3. **Verifica el cupo**: en rangos con cupo, si no hay plaza → `espera de cupo` (lo decides por mérito/antigüedad/evento) o revocación/rotación motivada.
4. **Firma** aprobando, corrigiendo (a otro rango/condiciones) o denegando con **motivo escrito** (obligatorio; queda en `cambios_faccion`).
5. **Aplica y registra** (hook): cambia `rango_id`, actualiza el peso en la matriz de 5.14, y deja que el periódico lo cuente si es notorio.

**La skill propone, tú decides.** El veredicto automático (procede / no procede todavía / espera de cupo) es una propuesta justificada — nunca un ascenso automático. El detalle de cómo `skill-cierre-temas` pondera méritos y fama está en su **§8 (anexo)**: expediente, termómetro por facción, umbrales por rango y el flujo en 4 pasos.

## 13.5 Sueldos (operativo)

- **Quién cobra:** solo **Marines** y **Gobierno Mundial**. El resto se financia con botín (piratas), cobras (cazadores, 5.13), encargos y venta de información (bajo mundo, 5.13) o misiones (no hay que inventárselo por el sistema).
- **Cómo se paga:** por **ronda y rango**, condicionado a **actividad roleada** (tramos de posts o temas al mes en `sueldos`). Debajo del mínimo, sin paga; ritmo normal, íntegra; exceso, plus. El cómputo lo hace el hook de sueldo al cierre de ronda (5.14) y queda pendiente de tu revisión en el panel antes de postear.
- **Escala:** ancla en la fama de la obra (5.9): la cúspide vive en millones, el piso de consumo en miles.

## 13.6 Facción y Mundo Vivo (operativo)

- Cada **facción + rango** tiene un **coeficiente de peso por familia de acción** (orden / crimen / política / economía) en `facciones.coeficientes_mv`. No es una unidad nueva: es **cómo 5.14 pesa** lo que haces según quién eres.
- `skill-mundo-vivo` lo usa al ponderar la ronda (matriz de islas, recompensas, periódico) — e.g. un marine golpeando a un civil pesa muy distinto que un pirata haciéndolo.
- **Guárdalo coherente:** al subir a alguien de rango, su peso crece; el efecto se refleja en la siguiente ronda. El hook de ascenso y el de ronda se comunican.

## 13.7 Cambio, deserción e infiltración (operativo)

1. **Cambio voluntario:** trámite, transición narrada y — si procede — **equivalencia de facción** por el staff (a qué rango entra alguien que deserta de alto rango). El cambio no arrastra antigüedad.
2. **Deserción:** sin baja on-rol → **criminal** (sube infamia/Wanted, la institución persigue); con baja legal → retirada sin criminalizar. Los rangos altos que desertan traen **consecuencias de Mundo Vivo** (5.14 las procesa).
3. **Infiltración:** rango honorario + **capa oculta solo-staff** (5.11). La ficha visible muestra la lealtad falsa; la real no se filtra. El descubrimiento se juega sin dados (5.10/5.14); el staff autoriza y registra cada infiltración.
4. **Límite:** un jugador no tiene más de un personaje en la misma facción (anti-abuso bloquea duplicados).
5. **Subfacciones de élite:** Gorosei/Shichibukai, nunca por carrera ni cambio — solo concesión staff.

## 13.8 Estructura de datos y panel (MyBB)

- **`facciones`** — `nombre` · `familia` ('pirata'|'institucional'|'criminal'|'civil'|'libre') · `rango_inicial` · `tiene_sueldo` · `coeficientes_mv` (JSON por familia de acción) · `cupo_max`.
- **`rangos_faccion`** — `faccion_id` · `nombre` · `orden` · `requisitos` (JSON: méritos/poder cualitativos) · `beneficios` (JSON) · `cupo` · `es_cuspide`.
- **`personajes` (extendida)** — `faccion_id` · `rango_id` · `fama_global_grado` · `fama_infamia_expo` · `rep_faccion` · `wanted_base` (→ 5.13).
- **`subfaccion_elite`** — `nombre` (Gorosei/Shichibukai) · `personaje_id` · `concedida_por` · `fecha` · `activo`.
- **`cambios_faccion`** — histórico inmutable: alta/promoción/deserción/infiltración/baja con `motivo` y `firmado_por`.
- **`sueldos`** — `personaje_id` · `ronda` · `posts_del_mes` · `monto` · `estado`.
- **`npc_faccion`** — recursos NPC que el rango pone a disposición (escuadra/recurso/mentor).
- **Hooks:** cierre de tema → propuesta de fama (bandeja, no automática) · ascenso (valida cupo y abre firma) · sueldo (por ronda) · anti-abuso (1 personaje por facción; subfacción élite solo concesión) · concesión/revocación de élite (recalcula Wanted al revocar un Shichibukai).
- **Panel «Facciones»:** tablero de rangos y cupos por facción · bandeja de ascensos (con la propuesta de la skill y firma) · bandeja de fama · gestión de sueldos · subfacciones de élite (concesión/retirada) · histórico de `cambios_faccion`.

---

# 14. Bajo mundo e información: rumores (operativo) ✅

> Sección operativa correspondiente al capítulo 14 del Manual del Jugador. **Confirmado (Sesión 9, dos tandas)**: cuatro canales de información, ficha de rumor (fiabilidad publicada + veracidad interna + alcance + frescura), contraste pagado con límites, red de espías con capacidad (sin azar), carteles de recompensa y circuito de caza. **Sin skill nueva**: los rumores de ronda los genera `skill-mundo-vivo`; los trámites de 5.13 van por el flujo 5.21 (capítulo 22).

## 14.1 El papel del staff en el bajo mundo

El bajo mundo es la **cara informativa del Mundo Vivo**, y el staff es quien le pone verdad y orden. Concretamente:

- **Fija la veracidad interna** de cada rumor (verdadero/dudoso/falso) al crearse — según su origen: un suceso real de la ronda (5.14) es verdadero; una especulación de la skill, dudoso; un rumor fabricado por un jugador, falso. Este dato es **solo-staff** y nunca se publica como tal (solo aflora vía contraste a Sólido).
- **Resuelve los contrastes con veredicto** (coste cobrado, fiabilidad afina un grado, y en Sólido revela la veracidad), respetando el límite "lo que nadie sabe no se verifica".
- **Supervisa los cuatro canales** y sus anti-abuso (sección 14.2).
- **Publica y gestiona los carteles** (quién, cuánto, paradero, caducidad) y **firma los cobros** (sección 14.6).
- **Coordina con `skill-mundo-vivo`** la generación de rumores de ronda por isla y los ajustes de recompensa con motivo (sección 14.7).

**Regla de oro:** la veracidad se fija al nacer y no se reescribe por presión. Si una trama pide matizar un rumor (el falso resultó tener un fondo de verdad), se hace **con un nuevo rumor**, no mutando el viejo en silencio.

## 14.2 Los cuatro canales: qué supervisar

| Canal | Qué vigilar | Anti-abuso |
|---|---|---|
| **Rumores de la ronda** (gratis) | Que la skill los genere con la matriz de 5.14 y fiabilidad coherente (rumorosa/plausible salvo hechos públicos) | Que no se "vendan" como sólidos; el tablón es público y gratuito |
| **Mercado de rumores** (compra) | Precios anclados a 5.9 (Suspiro 1.000 → Alto Susurro 1.000.000; ×1,5–×2 en el mercado) con multiplicadores de fiabilidad (Rumoroso ×0,6 · Plausible ×1,0 · Sólido ×1,5) y frescura (Fresco ×1,2 · Familiar ×1,0 · Frío ×0,5) + fluctuación de zona, con el mismo **techo global de la economía (0,5×–2×)** — nadie paga el cuádruple por un rumor | Reventa inmediata del mercado a precio inflado (se revisa el histórico de `rumor_operaciones`) |
| **Brokers y red** (inversión) | Capacidad real de cada espía (Novato→Supremo), contratos y mantenimientos cobrados por ronda, límite de 4 espías | Redes "fantasma" (sin mantenimiento) que no se desactivan; espías por encima de su capacidad |
| **Intercambio entre jugadores** (venta) | Que se venda con la fiabilidad publicada; la copia registrada del vendedor | Vender un rumor dudoso como sólido (trama de reputación — la reputación de facción de 5.12 lo cobra) |

**Veredicto de la red (no-crunch):** cuando un trámite pide investigar un rumor, la capacidad del espía decide si *puede* y el tiempo (1/1/2/3 rondas, +1 si un Experimentado intenta Alto Susurro); el contenido exacto lo fija la veracidad del rumor y el contexto. Nunca hay tirada.

## 14.3 La ficha del rumor: lo que fijas tú

Cada rumor nace con cinco campos (cap. 14 del Jugador): contenido · **veracidad interna** (solo-staff) · **fiabilidad publicada** (se muestra en la ficha: Rumoroso/Plausible/Sólido) · **alcance** (Local/Regional/Mundial) · **frescura** (Fresco/Familiar/Frío).

**Tu decisión al generarlo:** veracidad según origen (arriba) y fiabilidad según cómo circula (un suceso con testigos nace Plausible o Sólido; un chisme de taberna, Rumoroso). El alcance nace del peso del suceso en la matriz (5.14) y la frescura siempre nace Fresco.

## 14.4 El contraste: cómo resolverlo

- **Coste** por alcance (Local 1.000–5.000 · Regional 5.000–25.000 · Mundial 50.000–250.000 ฿) × **sensibilidad** del objetivo (Persona común ×1 · Figura pública ×2 · Criminal buscado ×3 · Identidad oculta ×5 · Entidad ×10).
- **Tiempo:** 1 ronda (Local/Regional) · 2 (Mundial).
- **Efecto:** fiabilidad afina un grado; en **Sólido** se revela la **veracidad interna** al solicitante.
- **Límites que debes aplicar:** lo que nadie sabe no se verifica (sin pistas, no llega a Sólido); contrastar a ciegas un falso bien sembrado puede confirmarlo (la fuente fue engañada); un contraste sobre algo ya Sólido no revela nada nuevo salvo capas más profundas de trama si existen.
- **Registro:** todo contraste queda en `rumor_operaciones` con motivo, cobro y veredicto — el jugador siempre puede saber por qué.

## 14.5 Redes y espías: validación y anti-abuso

- **Conexión con 5.12:** la red y los rumores contrastados vendidos son el **termómetro de la facción Bajo Mundo** («red + solvencia», cap. 13): los paneles «Redes» y «Bajo Mundo» alimentan la propuesta de ascenso (13.4) — verifica red activa, mantenimiento al día, fiabilidad de lo vendido y solvencia real del mercado.
- **Validación del trámite de red:** contrato pagado, mantenimiento por ronda, límite de 4 espías en combos equivalentes, y **capacidad coherente** (un Novato no investiga Alto Susurro; un Supremo no se "rebaja" a local sin motivo).
- **Desactivación:** sin mantenimiento, la red se desactiva al cierre de ronda (los espías se van, sin muertes forzadas ni azar).
- **Espía descubierto:** es una trama (5.11) que tú decides cómo explotar — delación, contrainformación, chantaje. Nunca una tirada.
- **Ataques a redes:** se juegan por trámite con veredicto (sabotaje sin dados): el atacante declara su método, tú decides qué descubre o estropea según capacidad y narrativa.

## 14.6 Carteles y cobros: verificación

- **Publicación:** los carteles los propone `skill-mundo-vivo` (a partir del Wanted de 5.12 y los sucesos de la ronda) y **tú los firmas**: nombre/alias · delito · cifra (escala 5.9: cientos de miles → 3.000M) · paradero publicado con fiabilidad · nivel aproximado · ronda de emisión · estado.
- **Caducidad del paradero:** 3 rondas sin avistamiento actualizado → el cartel sigue vigente pero deja de ser cazable hasta re-contrastar. Verifica que nadie cobre con paradero frío.
- **Cobro:** exige entrega verificada (tema presente resuelto con veredicto de 5.10) y firma tuya; registra en `recompensas_historico` (misma disciplina que 5.14 §8) y actualiza el Wanted (5.12).
- **Retirada/indulto:** narrativa (pago, redención, orden del Gobierno) — se retira el cartel con motivo, nunca en silencio.
- **Conexión con 5.12:** las **cobras y capturas** son el **termómetro de la facción Cazadores** (cap. 13): el histórico de carteles cobrados (`recompensas_historico`) y las capturas con veredicto alimentan la propuesta de ascenso (13.4) — verifica que cada ascenso de un cazador tenga capturas reales y cobros registrados detrás.
- **Anti-abuso:** sin entrega no hay cobro; matar por dinero a un PJ exige el mismo respeto que 5.10/5.11 (sin ejecución por cartel); el cobro de un cartel propio (autocaza) es abuso y se persigue en el histórico.

## 14.7 Rumores, recompensas y Mundo Vivo

- `skill-mundo-vivo` **genera los rumores de ronda por isla** (el "qué se sabe/rumorea aquí" de la matriz de 5.14) y los vuelca al tablón público y al puesto de rumores.
- Los rumores **usados** de la ronda alimentan tus ajustes de recompensa (subidas/bajadas con motivo e histórico — criterio libre, como en 5.14 §8), la peligrosidad de las islas y las misiones (5.20). Un falso de gran alcance puede generar un Wanted injusto: eso es trama, y tú decides cómo se procesa.
- El periódico «News Coo» **cita** rumores sin resolverlos: verifica que la redacción nunca publique veracidad interna.

## 14.8 Estructura de datos, hooks y panel (MyBB)

- **`rumores`** — `isla_id` · `tipo` (suceso/tesoro/persona/facción) · `contenido` · `veracidad` (verdadero/dudoso/falso, solo-staff) · `fiabilidad` (rumoroso/plausible/sólido) · `alcance` · `frescura` · `ronda_origen` · `creador_id` · `precio_base` · `estado` (activo/contrastado/vendido/frío).
- **`fuentes_informacion`** — catálogo del puesto de rumores por isla y ronda (canal 2.2).
- **`red_espionaje`** + **`espias`** — `dueno_id` · `espia_id` · `tipo` (novato/avanzado/experimentado/supremo) · `capacidad` (máx. alcance/categoría) · `coste` · `mantenimiento` · `estado` (activo/descubierto).
- **`rumor_operaciones`** — histórico inmutable de compra/venta/contraste/propagación con `motivo` y `veredicto` (auditable).
- **`carteles_recompensa`** — `wanted_id` · `personaje_id` · `cifra` · `paradero_publicado` (con fiabilidad) · `estado` (vigente/cobrado/retirado) · `ronda_emision` · `ronda_caducidad_paradero`.
- **`rumor_isla_ronda`** — qué se sabe/rumorea en cada isla cada ronda (salida de `skill-mundo-vivo`).
- **Hooks:** cierre de ronda → generar rumores por isla y desactivar redes sin mantenimiento · contraste/propagación → afinar fiabilidad/alcance/frescura · cobro de cartel → `recompensas_historico` + actualización del Wanted (5.12) · anti-abuso (límite de espías, paradero frío, autocaza).
- **Paneles:** **«Bajo Mundo»** (rumores activos por isla, fiabilidad, operaciones) · **«Redes»** (espías, capacidad, mantenimientos) · **«Carteles»** (vigentes, cobros, histórico).
- **Trámites 5.21 (capítulo 22):** solicitar rumor a la red · comprar rumor · contrastar · vender · montar/ampliar red · publicar cartel (staff) · cobrar recompensa · crear un rumor falso (propaganda). El prompt de cada uno lleva los IDs (rumor/personaje/isla/cartel) y la IA devuelve resultado editable — tú firmas.

---

# 15. Mundo Vivo: análisis y publicación (operativo) ✅

## 15.1 El papel del staff en el Mundo Vivo

El Mundo Vivo es el pilar del foro (principio 2), y el staff es quien lo sostiene. Tu trabajo no es *inventar* consecuencias, sino **recoger lo que ya pasó y darle forma pública**: analizar la ronda, decidir qué se atiende y qué no, y publicar — siempre con revisión humana antes de tocar el mundo. El sistema (la `skill-mundo-vivo`) propone; tú firmas.

Reglas operativas clave:
- **Nada se publica sin tu revisión.** El dashboard, las misiones, las recompensas y el periódico pasan por tus manos antes de ver la luz.
- **Separación de piezas:** el **dashboard** es tu taller interno (no se publica); el **periódico** es la pieza pública; las **misiones** salen a su tablón solo-staff y las publicas tú cuando quieras.
- **Motivo e histórico en todo.** Toda recompensa y todo cambio de isla lleva motivo escrito y queda registrado (misma disciplina que en economía, capítulo 10).

## 15.2 El flujo de la ronda mensual (operativo, cron)

La ronda es **mensual** y es una sola: al cerrarla se aplica el análisis del Mundo Vivo **y** la fluctuación de precios (5.9). Secuencia operativa del cron y del personal:

1. **Cola de análisis:** el sistema marca los temas presentes sin analizar (5.6) y las misiones/aventuras de la ronda (5.20).
2. **«Comenzar análisis»:** se genera el **prompt con los IDs**; lo pegas en la IA (`skill-mundo-vivo`, con acceso a la BD y a los manuales).
3. **Contexto adicional opcional:** antes de la salida, la IA puede preguntarte si quieres añadir contexto que influya en la ronda (una decisión de tu lado, un suceso que quieras priorizar).
4. **Salidas:** dashboard + misiones + recompensas + periódico + **rumores de ronda y carteles (5.13)**. **Tú las revisas y editas.**
5. **Cierre conjunto:** se aplican cambios de isla, recompensas y `precios_mercado` (5.9), y se archiva el periódico. El widget del índice muestra la última edición publicada.

> **Verifica:** que el cron no marque nada como publicado sin tu acción de publicación. El cierre aplica lo decidido; la visibilidad es manual.

## 15.3 La matriz de peso (qué revisar, operativo)

La `matriz_peso` es la herramienta interna que pondera el peso de cada acción de la ronda en rangos 0,5×–3× por cinco ejes (nivel del autor, mar, facción —que hereda la tabla de 5.12—, escala y signo de fama).

**Qué comprobar al validar la propuesta de la skill:**
- Que el **rango de peso** sea coherente con la tabla (no inventar números fuera de 0,5×–3×).
- Que el **factor de facción** venga de la tabla de 5.12 (no rediseñar aquí).
- Que la **escala** corresponda al esfuerzo real (ver 15.4): el peso de "destruir una aldea" debe reflejar cuánto costó, no solo lo narrado.
- El **desglose** es **dato interno**: no se publica punto por punto. Solo el **efecto** público (recompensa, cambio de isla, periódico) con su motivo.

## 15.4 La escala de destructividad: el veredicto (operativo)

Para destruir el entorno, cada elemento tiene un **daño de ruptura** (umbral que una técnica conectada debe igualar) calibrado contra PV/daño de 5.10/5.7: árbol 30 · casa 90 · bosque 180 · aldea 320 · fuerte 650 · ciudad 1.300 · montaña 2.600 · isla 5.000+.

**Cómo dictaminar (sin dados):**
- Toma el **daño de técnica conectada** del implicado (básico conectado de 5.10 × el tier de su técnica, 5.7).
- Si supera el umbral → **derrumba en ese impacto**. Si no lo alcanza → **acumula** por impactos de la escena (un post con la técnica conectada ≈ un impacto).
- Maquina las tablas de delta (5.10) como contexto de si la técnica encaja; decide tú el resultado con el peso correcto.
- **Regla de oro:** un novato no arrasa una aldea por narración; un título de élite sí. La escala impide que el mundo se caiga con un gesto y da peso a quien legítimamente lo destruye.

## 15.5 La matriz de islas y el panel (operativo)

Cada isla tiene una **ficha viva** con 13 parámetros (peligrosidad 1–50, afiliación, fuerza defensiva, desarrollo, población/orden, recursos y comercio, oferta/demanda, clima/log pose, lugares clave, sucesos, hitos, recompensas/tesoros, presencia de facciones). La **zona del foro es por mares** con **subforos por isla** (decisión confirmada).

**Qué comprueba el panel por ronda:**
- Que los cambios de **peligrosidad/afiliación/recursos** sean coherentes (misiones cumplidas bajan peligrosidad; amenazas ignoradas la suben; conquistas cambian afiliación).
- Que cada cambio quede en `isla_estado_historico` **con motivo** (¿qué misión, trámite, conquista o suceso lo produjo?).
- Que la **peligrosidad** sea un nivel 1–50 comparable al de un personaje (para que 5.16 la use igual que a un NPC).
- Que los **sucesos** de la isla alimenten `F_suceso` de 5.9 (el desglose de precios publica el motivo).

## 15.5-bis El catálogo inicial de islas (operativo)

El mundo tiene un **catálogo de arranque** de **17 islas** (documento `diseno/5.14_catalogo_islas.md`) que combina **nombres canon** reinventados y **nombres originales** del foro, repartidos por los 4 Blues, Paraíso, Nuevo Mundo y Zona restringida. Es el punto de partida del mapa del mundo que la matriz de 5.14 administra.

**Qué comprobar al usarlo:**
- Que **cada ficha cumpla los 13 parámetros** de la matriz (5.14) — no basta el nombre; la peligrosidad, afiliación, defensas, recursos, clima, lugares, sucesos, hitos, tesoros y facciones deben estar poblados y ser coherentes.
- Que los **números de peligrosidad** respeten los tramos de la navegación (5.16): Blues tranquilos (1–22 → +0/+1), Paraíso medio (21–30 → +1/+2), Nuevo Mundo alto (28–40 → +2/+3), Zona restringida extrema (45 → +3). La peligrosidad debe hablar el lenguaje de nivel 1–50 del sistema.
- Que la **afiliación/control** sea coherente con la conquista (5.15): reinos afiliados a Gobierno son sumisos/conquistables con guarnición; las salvajes son tierra de nadie; las de consejo local tienen defensas modestas.
- Que los **recursos raros** (obsidiana, acero de Wano, maderas de Elbaf, perla negra) alimenten la escala de calidad de 5.8 y la economía de 5.9 (oferta/demanda) — y que ningún material de tope rompa el cupo por mundo.
- Que las **islas canon respecten el principio 7**: nombre y **ADN** (clima, oficio, identidad) permitidos, pero **sin personajes ni eventos de la obra**. Dos casos clave fuera del catálogo: **Marineford** (la guerra es un evento) y **Enies Lobby** (el tribunal es un escenario de la obra). El staff debe mantener esa línea al añadir o editar islas.
- Que el estado inicial de cada una alimente `isla_estado` y `isla_estado_historico` (motivo de arranque) antes de abrir ventanas temáticas.
- Que los **modos de viaje especiales** (Skypiea, Isla Gyojin, Celeste-Faro) exijan los utensilios/barcos correctos al validar el trámite de navegación (5.16/5.17) — no deben poder llegar todos con un bote de pino.

**Las 17 fichas del lote inicial (operativo).** Resumen operativo de cada isla para validar trámites, conquistas y veredictos. Los números son **propuesta de arranque** (ajustables por ronda con motivo en `isla_estado_historico`). `(canon)` = nombre + ADN de la ambientación, reiniciado sin personajes ni eventos de la obra (principio 7).

### Dawn — Blue Este `(canon)`
- **Peligrosidad:** 4 · **Control:** Local · **Fuerza defensiva:** 2 (patrulla local, alcaldesa) · **Desarrollo:** Aldea · **Población/orden:** pequeño · estable
- **Recursos:** grano, huerta, pesca (importa herramientas y telas) · **Oferta/demanda:** media / media
- **Clima/log pose:** templado, Log Pose fácil · **Lugares:** pueblo costero, viejo molino, colina del faro
- **Sucesos:** ninguno (arranque) · **Hito:** tormenta que cambió la costa · **Recompensas:** naufragio modesto · **Facciones:** presencia local ínfima

### Vila Seleno — Blue Este `(original)`
- **Peligrosidad:** 6 · **Control:** Local · **Fuerza defensiva:** 1 (pescadores con arpones) · **Desarrollo:** Aldea · **Población/orden:** pequeño · estable/tenso en invernada
- **Recursos:** pesca de luna, sal, algas (importa madera y metal) · **Oferta/demanda:** alta en pescado salado / demanda de herramientas navales
- **Clima/log pose:** nieblas súbitas, Log Pose con paciencia (−1 con utensilio) · **Lugares:** muelle, salazón, cueva de las luces
- **Sucesos:** banco de peces-luna atrae cazadores · **Hito:** disputa de pesca ganada · **Recompensas:** perlas negras · **Facciones:** broker de North Blue

### Alabasta — Blue Sur `(canon)`
- **Peligrosidad:** 22 · **Control:** Gobierno (reino afiliado) · **Fuerza defensiva:** 14 (guardia real + guarnición afiliada) · **Desarrollo:** Reino · **Población/orden:** grande · tenso (sequía)
- **Recursos:** cereales de oasis, minerales, especias (importa agua y carnes) · **Oferta/demanda:** alta en especias / demanda crítica de agua
- **Clima/log pose:** desierto, tormentas de arena, Log Pose de seca difícil · **Lugares:** capital oasis, puerto fluvial, templo hundido
- **Sucesos:** banda corta las caravanas de agua · **Hito:** guerra civil terminada por pacto · **Recompensas:** santuario subterráneo · **Facciones:** Marines + Revolución + mafia

### Isla Baterilla — Blue Sur `(canon)`
- **Peligrosidad:** 10 · **Control:** Local (tribus de aldea) · **Fuerza defensiva:** 4 (aldeanos cazadores) · **Desarrollo:** Aldea · **Población/orden:** pequeño · estable
- **Recursos:** fruta, miel, madera blanda (importa metal) · **Oferta/demanda:** estacional en miel/fruta / demanda de herramientas
- **Clima/log pose:** tropical, selva densa, Log Pose fácil · **Lugares:** selva, árbol ancestral, fuente de agua dulce
- **Sucesos:** animal de presa ataca los sembrados · **Hito:** caverna con pinturas antiguas · **Recompensas:** tesoro pirata en la selva · **Facciones:** presencia tribal, sin Marina

### Reino Lvneel — Blue Norte `(canon)`
- **Peligrosidad:** 12 · **Control:** Gobierno (reino comercial) · **Fuerza defensiva:** 9 (guardia portuaria + flotilla) · **Desarrollo:** Reino (capital ciudad) · **Población/orden:** tenso por aduanas
- **Recursos:** mercancías del Norte, talento naviero (importa casi todo) · **Oferta/demanda:** media-alta en manufacturas / demanda de materias primas
- **Clima/log pose:** frío húmedo, Log Pose medio · **Lugares:** puerto mercante, lonja, astillero
- **Sucesos:** contrabandista vs. la aduana · **Hito:** gran feria que enriqueció el reino · **Recompensas:** barco hundido con mercancía · **Facciones:** Marina + comerciantes + bajo mundo medio

### Puerto Gavia — Blue Norte `(original)`
- **Peligrosidad:** 18 · **Control:** Local con base Marina · **Fuerza defensiva:** 12 (guarnición naval) · **Desarrollo:** Ciudad · **Población/orden:** media · estable (bajo mundo bulle)
- **Recursos:** astilleros, carpintería naval, vela (importa madera dura) · **Oferta/demanda:** alta en servicios navales / demanda de madera (5.17)
- **Clima/log pose:** ventoso, corrientes del Norte, Log Pose medio-alto · **Lugares:** astillero principal, academia, mercado flotante
- **Sucesos:** pedido de fragatas de la Marina + alijo clandestino · **Hito:** motín naval aplastado · **Recompensas:** planos de barco raro (5.17) · **Facciones:** Marina fuerte + astilleros + contrabando

### Archipiélago Cendra — Blue Oeste `(original)`
- **Peligrosidad:** 15 · **Control:** Salvaje · **Fuerza defensiva:** 5 (tribus autónomas) · **Desarrollo:** Aldea (territorios dispersos) · **Población/orden:** varias aldeas · estable
- **Recursos:** obsidiana, sal, ceniza volcánica (importa metal, sin forja) · **Oferta/demanda:** alta en obsidiana / demanda de herramientas forjadas
- **Clima/log pose:** caluroso, vientos de ceniza, Log Pose inestable cerca de volcanes · **Lugares:** volcán dormido, mercado de obsidiana, cuevas
- **Sucesos:** incendio mina la salina · **Hito:** alianza de tribus contra una conquista · **Recompensas:** veta de obsidiana rara (5.8) · **Facciones:** bajo mundo compra la obsidiana, sin Marina

### Península Cóncava — Blue Oeste `(original)`
- **Peligrosidad:** 20 · **Control:** Salvaje · **Fuerza defensiva:** 6 (bandas que se disputan refugios) · **Desarrollo:** Aldeas dispersas · **Población/orden:** pocos · tenso
- **Recursos:** madera de roca, caballos salvajes, rumores de hierro · **Oferta/demanda:** baja y cara / demanda de seguridad y armas
- **Clima/log pose:** vientos azotados, Log Pose difícil en la costa (arrecifes) · **Lugares:** bosque de piedra, cala de naufragios, fortín abandonado
- **Sucesos:** banda nueva desafía a la de la cala · **Hito:** salvador local unió los refugios · **Recompensas:** hierro de buena calidad oculto · **Facciones:** cazadores de recompensas merodean

### Skypiea — Paraíso `(canon, isla del cielo)`
- **Peligrosidad:** 26 · **Control:** Local (consejo de ancianos) · **Fuerza defensiva:** 16 (guardia del cielo con diales) · **Desarrollo:** Ciudad del cielo · **Población/orden:** grande · estable/tenso
- **Recursos:** diales, cultivos de nube, joyería de cristal · **Oferta/demanda:** alta en diales (5.8) / demanda de mercancías de abajo
- **Clima/log pose:** imposible por mar (corriente ascendente o balsa; Log Pose de nube) · **Lugares:** mercado del cielo, campanario, borde de las nubes
- **Sucesos:** incursión de hombres de abajo busca diales · **Hito:** la «Caída» · **Recompensas:** forja celestial de diales raros · **Facciones:** consejo + mercaderes de diales, sin Marina

### Water Seven — Paraíso `(canon)`
- **Peligrosidad:** 24 · **Control:** Local (alcalde y cuerpos de agua) · **Fuerza defensiva:** 11 (guardia ciudadana + bomberos-acuáticos) · **Desarrollo:** Ciudad · **Población/orden:** grande · estable
- **Recursos:** astilleros de élite, carpintería, velas (importa madera y metal de calidad) · **Oferta/demanda:** alta en barcos / demanda de madera dura
- **Clima/log pose:** mar calmo canalizado, Log Pose medio · **Lugares:** gran astillero, coliseo de la acuática, muelles bajos
- **Sucesos:** encargo de un barco insignia · **Hito:** marejada que reconstruyó la ciudad · **Recompensas:** barco de modelo antiguo (posible Meitou naval) · **Facciones:** astilleros + Marina + contrabando

### Isla Gyojin — Paraíso `(canon)`
- **Peligrosidad:** 30 · **Control:** Local/Gobierno (reino submarino) · **Fuerza defensiva:** 18 (guardia del reino del mar) · **Desarrollo:** Reino · **Población/orden:** grande · tenso (presión del exterior)
- **Recursos:** perlas, corales, criaturas marinas · **Oferta/demanda:** alta en perlas/corales / demanda de alimentos de superficie
- **Clima/log pose:** inaccesible salvo burbuja o submarino (navegación especial 5.16/5.17) · **Lugares:** palacio de la concha, mercado del fondo, jardines de coral
- **Sucesos:** pez cartógrafo descubre una bolsa de cría · **Hito:** conflicto con el exterior · **Recompensas:** perla negra grande + criaturas · **Facciones:** reino + comerciantes + traficantes

### Archipiélago Coro — Paraíso `(original)`
- **Peligrosidad:** 21 · **Control:** Salvaje · **Fuerza defensiva:** 7 (grupos autónomos) · **Desarrollo:** Aldea/pueblo disperso · **Población/orden:** varios asentamientos · estable
- **Recursos:** hierbas medicinales, pájaros, madera rara · **Oferta/demanda:** oferta en hierbas raras / demanda de alimentos
- **Clima/log pose:** marejada inestable, Log Pose difícil · **Lugares:** santuario de la colina, mercado de hierbas, garganta de musgo
- **Sucesos:** sanador famoso atrae pacientes · **Hito:** erección del santuario · **Recompensas:** plantas medicinales raras (5.8) · **Facciones:** Marina intermitente, curiosidad de la Corona

### Dressrosa — Nuevo Mundo `(canon)`
- **Peligrosidad:** 34 · **Control:** Gobierno (reino afiliado) · **Fuerza defensiva:** 20 (guardia del reino + milicia) · **Desarrollo:** Reino · **Población/orden:** grande · estable
- **Recursos:** forja, piedra, vino de especias (importa madera y fruta) · **Oferta/demanda:** alta en armas / demanda de alimentos y madera
- **Clima/log pose:** seco y cálido, meseta, Log Pose medio · **Lugares:** coliseo, meseta del martillo, barrios de forjadores
- **Sucesos:** torneo de forjadores · **Hito:** un forjador famoso tiene su taller en la meseta · **Recompensas:** materiales nobles (adán 5.17, armas 5.8) · **Facciones:** Marina + titanes de la forja + bajo mundo

### Wano — Nuevo Mundo `(canon)`
- **Peligrosidad:** 38 · **Control:** Local/Gobierno (país aislado) · **Fuerza defensiva:** 26 (maestros del acero + milicia feudal) · **Desarrollo:** Reino (autogobierno feudal) · **Población/orden:** grande · tenso (facciones internas)
- **Recursos:** acero de calidad, hierro, arroz (importa casi todo lo exterior) · **Oferta/demanda:** alta en acero de élite / demanda de alimentos y pertrechos
- **Clima/log pose:** costas altas, Log Pose difícil · **Lugares:** castillo del alto, valles de forja, tierras bajas
- **Sucesos:** disputa interna entre casas por el acero · **Hito:** cierre del país y su pacto con las facciones · **Recompensas:** el «acero del país» (Meitou 5.8) · **Facciones:** casas del acero + Marina al borde + resistencia

### Elbaf — Nuevo Mundo `(canon)`
- **Peligrosidad:** 40 · **Control:** Local (consejo de clanes gigantes) · **Fuerza defensiva:** 28 (guerreros gigantes) · **Desarrollo:** Aldea (reino clánico) · **Población/orden:** clan grande · estable
- **Recursos:** madera de Elbaf, caza, miel (importa metal y telas) · **Oferta/demanda:** alta en maderas nobles / demanda de bienes a escala
- **Clima/log pose:** bosque frío y húmedo, Log Pose medio · **Lugares:** gran roble, salón de los clanes, bosque-laberinto
- **Sucesos:** un clan desafía por el gran roble · **Hito:** gran batalla sellada por pacto de honor · **Recompensas:** ramas del gran roble (5.17) · **Facciones:** clanes + comerciantes de maderas

### Isla Rei — Nuevo Mundo `(original)`
- **Peligrosidad:** 28 · **Control:** Gobierno (afiliado) · **Fuerza defensiva:** 19 (guardia real + embajadas armadas) · **Desarrollo:** Reino (capital ciudad) · **Población/orden:** medio · tenso (cambio de gobernante)
- **Recursos:** vino, especias, información · **Oferta/demanda:** oferta en vino/especias / demanda de información (5.13)
- **Clima/log pose:** mediterráneo, Log Pose medio · **Lugares:** palacio de las embajadas, lonja del vino, laberinto de los espías
- **Sucesos:** la corona por decidir, embajadas presionando · **Hito:** tratado fundacional · **Recompensas:** secretos de Estado (5.13) · **Facciones:** todas las potencias con embajada

### Isla Celeste-Faro — Zona restringida `(original)`
- **Peligrosidad:** 45 · **Control:** Gobierno (puesto fronterizo de élite) · **Fuerza defensiva:** 32 (guarnición de élite + fortaleza marítima) · **Desarrollo:** Ciudad-fortaleza · **Población/orden:** guarnición y colonos · tenso
- **Recursos:** materiales de guerra, defensas, información estratégica · **Oferta/demanda:** oferta en defensas / demanda de víveres y relevos
- **Clima/log pose:** mar agresivo, corrientes extremas (Log Pose de élite o barco de Zona restringida) · **Lugares:** faro, muralla del mar, arsenal
- **Sucesos:** expedición perdida más allá del faro · **Hito:** defensa que detuvo una incursión · **Recompensas:** mapas y coordenadas de la ZR · **Facciones:** Marina de élite + exploradores

**Ampliación:** el catálogo es abierto. Cada lote nuevo (nuevas islas, canon u originales) se diseña con las tres referencias, se valida contra estos parámetros y la coherencia transversal (reglas 3.7–3.8), y se registra su motivo en `isla_estado_historico`. El panel «Mundo Vivo» lista el catálogo y las fichas vivas.

## 15.6 Los ajustes de recompensas (operativo)

Criterio **libre del staff** (confirmado): el foro elige qué subir o bajar cada ronda, sin límite duro por jugador, pero con **motivo escrito e histórico obligatorio**.

**Qué decidir al validar la propuesta de la skill:**
- ¿La subida/bajada se justifica con un hecho de la ronda? (Si no hay hecho, no hay ajuste.)
- ¿El **signo** es correcto? (gesta arriba el renombre/fama; crimen abajo la infamia y, si toca, el Wanted).
- ¿Recalcula el hook de 5.12 `wanted_base` cuando se toca una recompensa criminal? (5.13 lo consume.)
- Motivo y histórico: cada variación queda en `recompensas_historico`. Es auditable: un jugador siempre puede saber por qué.

## 15.7 El periódico «News Coo» (operativo)

El periódico es la **pieza pública** del Mundo Vivo y va **separado del dashboard**. Título fijado por el usuario: **«News Coo»** (sin lema; ver ambigüedad de originalidad abajo). Maqueta real (cabecera con n.º de edición y fecha, portada de 3 columnas, secciones, indicadores, pie).

**Flujo operativo:**
- La skill genera el **HTML autocontenido** de la edición en `historico_periodicos`.
- **Lo editas** (corriges, recortas, matizas; tú decides qué se publica y cómo).
- Lo **marcas publicado**: el widget del índice muestra la última edición y el catálogo las anteriores.
- **Verifica:** sin tu marca de publicado, la edición no aparece (la pieza pública siempre pasa por ti).

> **Nombre resuelto (Sesión 9):** «News Coo» es un nombre canon y el usuario **aceptó mantenerlo por directiva explícita** (derogación puntual del principio 7 solo para este título, igual que ya ocurre con los nombres de facción). No queda ambigüedad pendiente: se registra y publica tal cual, sin lema.

## 15.8 Las misiones de la ronda (operativo)

Las misiones/aventuras propuestas aparecen en un **tablón solo-staff** (no se autopublican). Tú decides si las **publicas** (al tablón de misiones de 5.20), las **cambias** o las **descartas/archivas** con motivo.

**Qué vigilar:**
- Que la propuesta **nazca del estado del mundo** (un NPC desplazado, una amenaza crecida, un vacío por un contrato vencido), no de la nada.
- Que el **rubro de la ficha de misión** sea el de 5.20 (objetivo, facción, condiciones).
- Que las **no llevadas a cabo** tengan destino: desembocan en otra cosa (rumor, precio, peligrosidad) o se archivan con motivo.

## 15.9 Abusos a vigilar (operativo)

- **No publicar sin revisión:** nada del Mundo Vivo sale solo; el cron aplica lo decidido, la publicación es manual.
- **Favoritismo en recompensas:** cada ajuste debe tener hecho real y motivo; el histórico auditable lo protege.
- **Peligrosidad inflada / deflactada:** que los cambios de isla respondan a hechos, no a conveniencia narrativa puntual.
- **Periódico sin sesgo editorial:** el redactor del universo tiene voz, pero el contenido debe reflejar hechos de la ronda, no invenciones que alteren el mundo sin base.

## 15.10 Estructura de datos y panel (MyBB) — operativo

**Tablas nuevas (ver Anexo A):** `mares` · `islas` · `isla_estado` · `isla_estado_historico` · `rondas` · `matriz_peso` · `dashboard_acciones` · `recompensas_historico` · `sucesos` · `historico_periodicos` (apoyadas en `temas`/`calendario_foro` de 5.6 y `precios_mercado` de 5.9).

**Automatismos:**
- **Cron de ronda mensual:** genera la cola, aplica cambios (islas, recompensas, `precios_mercado`) y archiva el periódico al cierre.
- **Hooks:** cierre de tema presente (marca cola + posible suceso) · trámite resuelto (impacto a la matriz) · conquista (5.15: afiliación) y abandono de conquista (5.15: revuelta) · misión (5.20: peligrosidad/recursos) · recompensa aplicada (recalcula `wanted_base`).
- **Panel «Mundo Vivo»:** cola de análisis · botón «comenzar» y show del prompt · dashboard interno por ronda con KPIs y «Acciones detectadas» · bandeja de recompensas (motivo/firma) · edición y publicación del periódico · histórico de rondas · muestra `afiliacion`/`fuerza_defensiva` por isla (con guarnición y fortificaciones de 5.15) y enlaza con los paneles de conquista («Conquista»/«Guerras»/«Ejércitos»).

**Integración con economía (5.9):** el cierre de ronda recalcula `precios_mercado` con la fluctuación, usando los `sucesos` de la ronda como `F_suceso`; el desglose publicado lleva el motivo.

---

# 16. Conquista y control territorial (operativo) ✅

> Sección operativa correspondiente al capítulo 16 del Manual del Jugador. **Confirmado (Sesión 9, dos tandas)**: objetivos según el control previo, cinco fases regladas, defensa y contraataque, ocupación, y ejércitos/hordas. **Sin skill nueva**: la resolución la hacen `skill-mundo-vivo` (matriz de peso, sucesos, periódico) + `skill-cierre-temas` (veredictos de los temas del asedio) + trámites 5.21 (capítulo 22).

## 16.1 El papel del staff en la conquista

La conquista es la **cara territorial del Mundo Vivo**, y el staff es quien la mantiene reglada. Concretamente:

- **Recibe y publica los anuncios** (trámite): valida la justificación de presencia, el objetivo (isla o zona) y el bando.
- **Arma el escenario del asedio** con la matriz de 5.14: fuerza defensiva (nivel, quien manda), fortificaciones, zonas implicadas.
- **Resuelve por rondas**: al cierre de ronda pondera las acciones del asedio (matriz de peso), aplica el veredicto de combate (5.10) y propone el estado de la defensa.
- **Firma el registro**: cambio de `afiliacion`/`fuerza_defensiva` con **motivo** en `isla_estado_historico` (fuente `conquista`).
- **Vigila la ocupación y el abandono** (cron de ronda: 2 rondas sin actividad → revuelta propuesta, 3.ª ronda se aplica).
- **Coordina la guerra** con facciones (5.12), rumores (5.13), tiendas (5.9) y el periódico (5.14).

**Regla de oro:** la conquista nunca se resuelve en el anuncio. El proceso dura rondas y cada veredicto queda con motivo. Si un bando "conquista" sin fases, no es conquista: es un suceso de tema, no un cambio de control.

## 16.2 Qué supervisar (objetivos)

| Control previo | Qué verificas | Anti-abuso |
|---|---|---|
| **Isla salvaje** | Que exista justificación de presencia (llegar, estar cerca) y que el registro de ocupación sea coherente con la peligrosidad (5.14) | Ocupar "salvaje" sin narrativa; picos de peligrosidad ignorados |
| **Guarnición NPC** | El asedio por fases contra la fuerza defensiva real (nivel) · duración según la tabla (nv1–15: 1 · nv16–30: 2 · nv31–45: 3 · nv46–50: 4+) · fortificaciones +1 | Saltarse fases (resolver en el anuncio); desniveles absurdos contra fortalezas |
| **Territorio de jugadores** | Que el defensor jugador sea **invitado y pueda responder** (participación garantizada) · veredicto con su defensa real | Resolver sin dar la palabra al defensor; invasión exprés de territorios activos |

**Zonas:** la conquista puede ser de la isla o de una zona/recurso. Verifica que el registro distinga: conquista parcial → afiliación "mixta" (esa zona) · control de la isla → zona principal o todas. Los `lugares_clave` de 5.14 definen las 1–3 zonas por isla.

## 16.3 Las cinco fases: tu flujo

1. **Anuncio** (trámite): validas objetivo/bando/motivo, publicas el suceso y **invitas al defensor**.
2. **Asedio** (temas presentes, salas 5.10, misiones 5.20): supervisas los temas del asedio como cualquier cierre; al cierre de ronda, `skill-mundo-vivo` pondera y propone desgaste.
3. **Resolución** (veredicto): matriz de peso + combate + fortificaciones (Daño de Ruptura de 5.14). Sin tiradas.
4. **Registro**: hook aplica `afiliacion`/`fuerza_defensiva` con motivo; periódico y rumores lo recogen.
5. **Ocupación**: tiendas (5.9), mantenimiento de guarnición/fortificaciones, orden; el cron vigila el abandono.

**Duración mínima:** el anuncio y el primer asalto son momentos distintos. Un asedio nunca se resuelve en la misma ronda del anuncio (salvo isla salvaje: declarar + ocupar).

## 16.4 La fuerza defensiva y la defensa activa

- **Fuerza defensiva** (`isla_estado.fuerza_defensiva`): `nivel` + `quien_manda` — es la ancla de dificultad (5.14). Úsala para calibrar la duración del asedio y los desniveles (5.10).
- **Guarnición:** los NPCs defensores (5.11) — los juegas tú (secundarios/terciarios) con veredicto, sin dados.
- **Defensores jugadores:** el defensor responde con su personaje y su guarnición. **Participación garantizada:** invítalo siempre; el proceso por rondas le da tiempo.
- **Fortificaciones:** estructuras con integridad resueltas con el Daño de Ruptura de 5.14 — no rediseñes destructividad; consúmela.
- **Rendición/negociación:** decisión narrativa del defensor, nunca veredicto forzado.

## 16.5 Contraataque y abandono: verificación

- **Reconquista:** es una conquista nueva con las mismas cinco fases; la ventaja del defensor es su fuerza defensiva ya instalada — no apliques bonus artificiales.
- **Abandono (pérdida de control):** el cron detecta **2 rondas sin actividad de ocupación** (sin posts de defensa/administración ni mantenimiento pagado) → propone la revuelta; en la **3.ª ronda** se aplica (afiliación → local/salvaje o la reclama otro bando) **con motivo** en `isla_estado_historico`. Verifica que nadie "conserve" territorio sin presencia: el abandono se declara, nunca en silencio.
- **Guerras abiertas:** entre facciones (5.12) — el peso de facción multiplica las consecuencias (peligrosidad, rumores, carteles). Usa el panel «Guerras» para seguir el conflicto.

## 16.6 La ocupación: tiendas, mantenimiento y recursos

- **Tiendas (5.9):** el territorio conquistado habilita locales de tienda de jugador; al ser conquistado por otro bando, las tiendas del anterior **se suspenden** (cierre forzoso de 5.9) — verifica el hook de derrota.
- **Mantenimiento:** guarnición y fortificaciones pagan mantenimiento por ronda; sin pago → desactivación/abandono (misma lógica que las redes de 5.13).
- **Recursos y orden:** la explotación abusiva sube el desorden (5.14) y alimenta la resistencia — la resistencia es un anuncio de reconquista en ciernes; procésalo como trama, no como castigo.
- **Fama:** conquistar sube renombre/infamia (5.12) y puede tocar el Wanted (5.13); la skill lo propone con motivo, tú firmas.

## 16.7 Ejércitos y hordas: validación y anti-abuso

- **Unidades** (Infantería 10.000/1.000 · Élite 50.000/5.000 · Especialistas 25.000/2.500 — contrato/mantenimiento por ronda): valida **máx 4 por bando** y que más de 2 exija rango alto (5.12). Sin mantenimiento → se van.
- **Capacidad coherente:** la Infantería no rompe fortificaciones; los Especialistas sí (Daño de Ruptura). Un bando que "usa" lo que no tiene es abuso.
- **Hordas** (Mara nv1–10/10.000 · Masa nv15–25/50.000 · Marea nv30–40/200.000): factor del escenario, **no combatiente de sala**. Verifica que se resuelvan con veredicto colectivo — un personaje puede partir una horda si su nivel/historia lo respaldan (5.10/5.14). Las genera el Mundo Vivo o las contrata un bando (una vez por asedio).
- **Anti-abuso:** sin fases no hay conquista · sin participación del defensor no hay veredicto · sin mantenimiento no hay ocupación · sin motivo no hay registro.

## 16.8 Estructura de datos, hooks y panel (MyBB)

- **`conquistas`** — `isla_id`/`zona_id` · `atacante_id` · `bando_atacante` · `defensor_id` · `tipo` (isla/zona) · `fase` (anuncio/asedio/resolucion/registro/ocupacion) · `ronda_inicio` · `rondas_asedio` · `estado` (activa/ganada/perdida/abandonada/tregua).
- **`asedios`** — log por ronda: acciones ponderadas de ambos bandos, desgaste de la fuerza defensiva, veredictos parciales.
- **`fuerza_defensiva`** — integrada en `isla_estado`: `nivel` · `quien_manda` · `guarnicion` (JSON unidades) · `fortificaciones` (JSON con integridad).
- **`unidades`** — `tipo` (infanteria/elite/especialista) · `coste` · `mantenimiento` · `capacidad` · `dueno_id` · `isla_id`.
- **`hordas`** — `isla_id` · `tamaño` (mara/masa/marea) · `fuerza` (nivel equivalente) · `estado` · `veredicto_ronda`.
- **`zonas`** — `isla_id` · `nombre` · `afiliacion` · `recursos` · `fuerza_defensiva` parcial.
- **Hooks:** anuncio → publicar suceso/rumor · cierre de ronda de asedio → proponer veredicto y desgaste · registro → actualizar `afiliacion`/`fuerza_defensiva` con motivo (fuente `conquista`) · abandono → detectar inactividad y proponer revuelta · derrota de un conquistador → cierre forzoso de tiendas (5.9).
- **Paneles:** **«Conquista»** (conquistas activas por isla, fases, rondas, veredictos) · **«Guerras»** (conflictos entre facciones, peso de facción aplicado) · **«Ejércitos»** (unidades y hordas por bando, mantenimientos, capacidad).
- **Trámites 5.21 (capítulo 22):** anunciar conquista · responder al asedio (defensor) · resolver/registrar · declarar reconquista. El prompt de cada uno lleva los IDs (isla/zona, personajes, fuerzas) y la IA devuelve resultado editable — tú firmas.

---

# 17. Navegación: trámites y oráculos (operativo) ✅

> Sección operativa correspondiente al capítulo 17 del Manual del Jugador. **Confirmado (Sesión 9, dos tandas)**: IRT (índice de riesgo sin dados) · tiempo off-roll 72/48/36 · toda travesía es tema presente (decisión del usuario) · víveres/daños · transportes y utensilios. **Skill**: `skill-navegacion` (especificación en §17.8) — ya en el catálogo; sin skill nueva.

## 17.1 El papel del staff en la navegación

La navegación es un trámite más con el flujo estándar (capítulo 22): el jugador solicita → el sistema arma el prompt (trámite + matriz de 5.14 + oficios/barco) → la IA (`skill-navegacion`) genera la **ficha de travesía** editable → tú la revisas y publicas → se abre el tema presente. Tu papel: **validar el trámite** (17.2), **revisar el riesgo y los oráculos** (17.3–17.4), **vigilar el plazo** (17.5) y **firmar el veredicto al cierre** (17.6). Nunca hay dados: el IRT es cálculo interno de la skill, y los oráculos son propuesta que tú puedes matizar (un personaje famoso atrae patrullas; un barco rápido esquiva tormentas) y firmas.

## 17.2 Verificar el trámite (requisitos)

- **Ubicación:** el hook rechaza el trámite si `personajes.ubicacion ≠ isla de origen` (origen autocompletado, no editable).
- **Un presente a la vez (5.6):** rechaza si el personaje ya tiene un tema presente abierto — la travesía *es* su presente.
- **Campos completos:** destino (isla del catálogo de 5.14), barco (de la flota del personaje) o transporte (17.7), acompañantes (oficios 5.3 y NPCs reclutados 5.11) y utensilio (17.7).
- **Pago de transporte** (si aplica): verifica cartera (5.9) y el recargo por Wanted antes de abrir el tema.

## 17.3 El IRT: cómo se calcula y qué revisar

La skill calcula el **Índice de Riesgo de Travesía** (interno):

- **Base del mar** (orden de región de 5.14): Blue 1 · Paraíso 2 · Nuevo Mundo 3 · Zona restringida 4 · **+1** si la ruta cruza una zona en guerra (5.15/5.14).
- **Peligrosidad del destino** (`isla_estado.peligrosidad` 1–50): 1–10 → 0 · 11–25 → +1 · 26–40 → +2 · 41–50 → +3.
- **Estado del Mundo Vivo**: +0 a +2 (techo): sucesos en la ruta +1 · recompensas/facciones hostiles +1 · guerra/asedio +1.
- **Mitigadores** − : Navegante 5.3 (nv1 −1 · nv2 −2 · Timonel nv3+ −1 · Cartógrafo nv4 −1) · NPC reclutado navegante −1 · barco 5.17 −1 a −3 · utensilio adecuado −1.

**Resultado → oráculos:** 0–2 tranquila (0–1 incidente menor) · 3–5 uno o dos · 6–8 dos o tres (alguno grave) · 9+ muy peligrosa (3+, daño asegurado y víveres garantizados).

**Qué comprobar:** que la skill no invente números fuera de estas bandas · que el mitigador del barco use la clase real de 5.17 (aún sin sistema de barcos, usa el tipo declarado con su nota) · que el **desglose del IRT no se publique** — al jugador solo le llega la ficha de travesía (oráculos y tiempo), como la matriz de peso del capítulo 15.

## 17.4 Oráculos y catálogo de incidentes

Los oráculos son incidentes con ficha, resueltos con veredicto al cierre — nunca tiradas. Catálogo (7 tipos con nombres propios del foro): **Ala de tormenta** · **Asalto** · **Patrulla del Gobierno** · **Coloso del abismo** · **Maremoto** · **Remolino** · **Huracán**.

- **Menor:** +12 h · víveres −1 · posible daño leve.
- **Media:** +24 h · víveres −2 · daño moderado · posible desvío.
- **Grave:** +48 h · víveres −3 · daño grave · desvío o encuentro obligado.

**Qué revisar:** que el momento y el tipo del oráculo encajen con la ruta y el contexto (un Remolino en un Blue tranquilo solo si el Mundo Vivo lo justifica) · que los encuentros se resuelvan por 5.10 (salas, veredicto) · que la **Patrulla del Gobierno** contraste el Wanted del buscado (5.12/5.13) — un famoso es parado; un desconocido, no.

## 17.5 El tiempo off-roll y el vencimiento

Escala 72/48/36 h por tramo (Blue/Paraíso/Nuevo Mundo; Zona restringida 36 con requisitos), tramos sumables. Modificadores: utensilio adecuado −12 h · navegante **Maestre** (Maestría Suprema 5.3) −25 % · incidentes +12/+24/+48 h · transporte +24 h.

**El plazo es el vencimiento del tema:** si se agota sin cierre, la travesía se resuelve por veredicto (desvío, retraso o incidente no jugado). El panel «Navegación» marca vencimientos; no dejes que caduquen en silencio — propón la resolución y regístrala.

## 17.6 Cierre de la travesía: veredicto y anti-abuso

Al cerrar el tema presente, el hook aplica el veredicto: **víveres gastados** (según duración on-roll y oráculos: 1 ración/persona/día on-roll +1/+2/+3 por incidente; Cocinero 5.3 puede haber recuperado — verifica lo declarado), **daños al barco** (grado leve/moderado/grave → integridad de 5.17 cuando exista) y **cambio de ubicación** (`personajes.ubicacion = destino`).

**Qué vigilar (anti-abuso):**
- **Viajar sin víveres** — sin stock, el veredicto empeora (desvío +24 h o incidente mayor); no lo pases por alto solo porque "nadie lo declaró".
- **Recargo por Wanted** — un buscado no viaja en civiles al precio de un civil; verifica el cobro en el trámite.
- **Transporte gratis en Gobierno** — solo miembros en servicio; los buscados pagan soborno o se quedan fuera (el engaño es trámite de Ladrón/5.13).
- **Travesías que "se olvidan"** — el vencimiento es vencimiento: cierra con veredicto, no acumules presentes fantasma.
- **Invasión de ronda (5.14)** — un suceso puede interceptar la travesía en curso; si el cron lo marca, intégralo al tema.

## 17.7 Transportes y utensilios: validación

**Transportes (por persona y tramo):** civil 1.000/5.000/15.000 (+1.000 ฿/millón de Wanted) · clandestino ×2 sin recargo (solo piratas/revolucionarios, verifica afiliación 5.12) · Gobierno gratis en servicio (buscados: soborno 5.000 + 500 ฿/millón o engaño). Todos: ruta segura (máx. 1 incidente menor) y +24 h. El barco no es del jugador: los daños no tocan su flota.

**Utensilios (objetos 5.8 con rareza):** Brújula Común 200 · Log Pose Poco común 1.000 (el clima correcto de `isla_estado.clima_logpose`) · Eternal Pose Raro 5.000 (retorno) · Log Pose NM Raro 5.000 (Nuevo Mundo, fija 3 islas). Verifica que el utensilio declarado exista en el inventario del personaje antes de aplicar su mitigador (−1 IRT y −12 h).

## 17.8 Estructura de datos, hooks y panel (MyBB)

- **`travesias`** — `tema_id` (presente 5.6) · `origen_isla_id`/`destino_isla_id` · `ruta` (JSON mares) · `barco_id` (5.17) o `transporte_tipo` · `utensilio_id` · `tripulacion` (JSON ids) · `irt` (interno) · `oraculos` (JSON: tipo/gravedad/momento/estado) · `tiempo_disponible_h` · `tiempo_on_roll` · `viveres_gastados` · `estado` (planificada/en_travesia/resuelta/abortada) · `veredicto` (JSON).
- **`oraculos_catalogo`** — config: tipo, gravedad, efectos (daño barco grado, víveres, desvío, encuentro).
- **`incidentes_travesia`** — log por incidente con post/momento y resolución.
- **`transportes`** — config: tipo, tarifa por mar, reglas de acceso por facción (5.12).
- **Hooks:** validación de origen (ubicación + presente) → rechazo o paso · resolución del trámite → genera ficha y crea el tema · cierre del tema → veredicto (víveres, daños, `ubicacion = destino`) o resolución por vencimiento · invasión de ronda (5.14) → intercepta travesías en curso.
- **Panel «Navegación»:** travesías activas por isla y jugador · fichas editables antes de publicar · oráculos por tema con estado · tiempos y vencimientos · víveres/daños pendientes al cierre · histórico de travesías resueltas.
- **Skill `skill-navegacion`:** entradas (trámite + matriz 5.14 + oficios/barco) · salidas (inicio narrativo, tiempo off-roll, oráculos, gasto de víveres) · IRT interno, desglose solo-staff, ficha pública editable, tú firmas.

---

# 18. Barcos (operativo) ✅

> Sección operativa correspondiente al capítulo 18 del Manual del Jugador. **Confirmado (Sesión 9, dos tandas)**: ficha de 5 campos (sin PA propio ni progreso — ya confirmado en 5.10) · espacio por raza · 8 tipos con niveles N1–N3 · madera como límite de mar · 10 módulos + personalizados del Carpintero · capa técnica. **Sin skill nueva**: los trámites de compra/construcción/mejora van por 5.21 con los oficios de 5.3; la navegación y el combate naval ya tienen sus skills.

## 18.1 El papel del staff en los barcos

El barco es un **medio con ficha**, no un personaje: no tiene PA propio ni progreso (5.10, ítem 119 — las acciones las ejecutan los tripulantes con su PA). Tu papel: **validar fichas y trámites** (compra, construcción, mejora N, módulo, reparación), **verificar los límites** (madera vs. mar, plazas vs. tripulación, ranuras vs. módulos) y **firmar los veredictos** de daño/hundimiento. Todo sin dados: construir, dañar y reparar es oficio + veredicto.

## 18.2 Verificar la ficha y el espacio por raza

- **Ficha de 5 campos:** Casco (PV) · Armamento (cañones) · Maniobra (gobierno/vela) · Espacio (plazas) · Ranuras de módulo — contra `tipos_barcos` y `maderas_casco`.
- **Espacio por raza:** Tontatta 0 · Humana/Kuja/Piernas Largas/Brazos Largos/Skypiean/Mink 1 · Gyojin/Sirena 0 en el agua / 1 a bordo · Lunarian 2 · Oni 3 · Bucaner 3 · **Gigante 5** (exige barco grande o refuerzo de casco). Verifica que la tripulación declarada **quepa** en las plazas del barco (el capitán decide quién sube; si no caben, alguien se queda).
- **Sin PA propio ni progreso:** rechaza cualquier intento de "subir de nivel" el barco como personaje — las mejoras son módulos y madera, nunca PP ni niveles.

## 18.3 Tipos, niveles y estadísticas: validación

**8 tipos × N1–N3** (tabla completa en el capítulo 18 del Jugador): Bote de remos (gratis) → Balandro → Goleta → Carabela → Velero → Corbeta de guerra → Galeón pesado → **Acorazado insignia** (solo facciones/NPC — un particular no compra un acorazado; es patrimonio de imperio, a la escala de los 3.000M ฿ de un Yonkou).

**Qué comprobar:**
- **Estadísticas** contra la tabla: casco 200→18.000, maniobra 10–50, cañones 0–20 (daño ×30–×150), ranuras 0–16, mitigador IRT 0 a −3, precios gratis→120M. No inventar números fuera de banda.
- **Cañones:** la cadencia es de 5.10 (disparo 2 PA · recarga 1 PA); el daño va al casco con apuntado PER vs maniobra (Tabla 1 de delta); la salva = cañones × daño.
- **Mejora N1→N2→N3:** la construye el **Astillero** (5.3) por la diferencia de precio + materiales (madera de 5.8) — trámite 5.21 con verificación de oficio.

## 18.4 La madera: límite de mar y mitigador del IRT

**5 calidades** con rareza 5.8 y precio para barco medio (×0,5 botes/balandros/goletas · ×2 corbetas · ×3 galeones · ×5 acorazados + 25 % mano de obra si no es de la tripulación): Pino de marea (Común, incluida — Blues) · Roble del sur (Poco común, 5.000 — Blues/Paraíso) · Corazón de tormenta (Raro, 15.000 — Paraíso/Calm Belt) · Madera de Adán (Raro, 50.000 — Nuevo Mundo) · Madera de Eva (Mercado Negro, 200.000 — Zona restringida).

**Qué verificar:** un barco **no entra** en un mar que su madera no habilita (rechaza el trámite de navegación de 5.16 sin la madera adecuada) · la **clase + la madera** son el mitigador del IRT (−1 a −3 según tipo/nivel) · la madera de Eva es Mercado Negro (5.8): no se compra en tiendas NPC.

## 18.5 Módulos: catálogo, personalizados y oficios

**Catálogo (10 módulos, 1 ranura c/u):** tienda 25.000 (5.9, directiva Sesión 4: sin el módulo, vender desde el barco se rechaza) · batería 20.000 (+2 cañones o +25 % salva) · bodega 15.000 (+50 % carga) · cocina/laboratorio/enfermería 10.000 (efectos en travesía 5.16) · refuerzo 30.000 (+500 PV) · resina 40.000 (inmersión) · kairoseki 50.000 (oráculos marinos −1 grado) · velas mecánicas 60.000 (calmas).

**Qué verificar:**
- **Ranuras:** el número de módulos instalados no puede superar las ranuras del tipo/nivel; cada módulo ocupa 1 ranura.
- **Requisitos de oficio:** tienda exige Comerciante · resina exige Astillero nv4 · kairoseki es Mercado Negro · el resto, Astillero/Maquinista/Carpintero/Cocinero/Químico/Médico según ficha.
- **Módulos personalizados del Carpintero:** cuando el Astillero diseña un módulo a medida, valida que el efecto se **calibre contra el catálogo** (un módulo vale lo que su equivalente, ni más ni menos) con el criterio de originalidad de 5.7 — la idea se integra, el poder no se infla.
- **Efectos de oficio en la navegación:** la Cocina/Laboratorio/Enfermería reducen oráculos de 5.16 (víveres, estados, clima) — verifica que `skill-navegacion` los pondere en la ficha de travesía y que no se dupliquen bonos.

## 18.6 Daños, reparaciones y mantenimiento

- **Daños (3 grados de 5.16):** leve / moderado / grave → reducen la integridad (PV del casco). Los aplica el veredicto de un oráculo o de una bala de cañón (5.10), nunca un post unilateral.
- **Reparación:** oficio Carpintero/Astillero (5.3) + materiales (madera 5.8); log en `reparaciones` con materiales, coste y veredicto. La Maestría Suprema del Maquinista Naval repara en pleno combate (una acción, 5.10).
- **Hundimiento:** a 0 PV el barco está hundido o inutilizado — es un **veredicto** (5.10/5.14): genera suceso de Mundo Vivo, y la tripulación sobrevive según los estados terminales normales (hundir no ejecuta a la gente). El hundimiento dispara el aviso de transporte alternativo (5.16).

**Qué vigilar (anti-abuso):**
- **Barcos que "suben de nivel" solos** — toda mejora N1→N2→N3 es trámite con Astillero y coste; sin trámite, el barco es su nivel declarado.
- **Módulos sin ranuras** — instalar más módulos de los que caben se rechaza.
- **Madera que no corresponde** — un barco de pino no navega el Nuevo Mundo, por mucha tripulación que declare.
- **Gigantes en botes** — un gigante (5 plazas) no cabe en un bote (2 plazas); verifica el espacio al embarcar.
- **Compras de acorazados** — solo facciones/NPC; un jugador que "compra" un acorazado sin rango ni facción se rechaza (5.12).

## 18.7 Estructura de datos, hooks y panel (MyBB)

- **`barcos`** (la `barco_ficha` de 5.10 ampliada): `id` · `nombre` · `tipo_id` · `nivel` (N1/N2/N3) · `madera_id` · `casco_pv`/`pv_actual` · `maniobra` · `armamento` (JSON cañones) · `espacio_max` · `ranuras` (JSON módulos) · `dueno_id`/`tripulacion_id` · `estado` (activo/danado_leve/danado_moderado/danado_grave/hundido/en_reparacion).
- **`tipos_barcos`** — catálogo: plazas, casco/maniobra/ranuras por N1–N3, cañones, mitigador IRT, precio, madera mínima.
- **`maderas_casco`** — catálogo: mares que habilita, precio, rareza (5.8).
- **`modulos_barcos`** — catálogo: efecto (JSON), ranura, precio, requisito de oficio.
- **`reparaciones`** — log: barco, grado de daño, materiales, coste, oficio, veredicto.
- **Hooks:** daño → actualizar `pv_actual`/`estado` · módulo → validar ranuras y oficio · reparación → aplicar materiales/coste · hundimiento → suceso 5.14 + aviso de transporte 5.16.
- **Panel «Barcos»:** flota por jugador/tripulación · fichas editables (tipo, nivel, madera, módulos) · estados de daño y reparaciones pendientes · astillero (construcción/mejora con oficio) · integración con la capa naval (5.10) y las travesías (5.16).
- **Trámites 5.21 (capítulo 22):** comprar barco · construir (Astillero) · mejorar N1→N3 · instalar/desinstalar módulo · reparar · personalizar módulo (Carpintero). El prompt lleva los IDs (tipo, madera, módulos, oficio) y la IA devuelve el resultado editable — tú firmas.

---

# 19. Akuma no Mi (operativo) ✅

> Sección operativa correspondiente al capítulo 19 del Manual del Jugador. **Confirmado (Sesión 10, dos tandas)**: plantilla de ficha de 8 bloques · la fruta no da técnicas, abre puertas del catálogo de 5.7 + efectos fuera del catálogo con condiciones · intangibilidad Logia con contadores · despertar con requisitos · influencia en la ficha (balanza a 0) · obtención a 3 vías (tirada aleatoria nv3+ · compra PP con matriz · recompensas) · kairoseki cerrado · 6 fichas canon · capa técnica + panel «Akumas» · **skill `skill-adaptacion-akumas`** (Anexo B) con su **guía maestra** `diseno/5.18_guia_adaptacion_frutas.md`.

## 19.1 El papel del staff en las frutas

Las frutas son el poder más visible del foro y el que más abusos puede generar: tu papel es **moderar fichas** (la plantilla de 8 bloques es el contrato entre jugador, rival y staff), **verificar la obtención** (las tres vías, con su anti-abuso) y **firmar los despertares** (trámite, como las técnicas). Dos reglas que nunca se rompen: **sin dados** (la única tirada decide qué fruta obtienes, jamás resuelve una acción) y **personajes y eventos de la obra fuera** (la fruta canon sí; Luffy, Ace y Marineford no — principio 7, corregido en Sesión 10).

## 19.2 Verificar la ficha de fruta (los 8 bloques)

Cada fruta del catálogo se documenta con los 8 bloques de la plantilla (capítulo 19 del Jugador). Al validar una ficha comprueba:

- **Identidad:** nombre canon, familia, rareza (Zoan: común/ancestral/mitológica) — sin personajes ni eventos de la obra.
- **Mecánica base:** pasivas y subsistemas con sus **límites**; las **rupturas de regla se declaran con su condición** (nada de "rompe la regla porque sí").
- **Puertas de poder:** solo efectos del catálogo de 5.7 (Anexo A: `puertas` de `akumas`) **+ efectos no registrados con condiciones** — declarados en la ficha, **calibrados contra el catálogo** (mismo tier y presupuesto de slots) y moderados con el criterio de originalidad de 5.7 (se busca el «sí con condiciones»).
- **Debilidades propias:** el **enemigo natural** publicada (toda fruta tiene una; una fruta "sin debilidad" se rechaza).
- **Requisitos del portador:** piso de atributos; las técnicas pedirán más vía 5.7.
- **Influencia en la ficha:** defectos exigidos, dotes exclusivas (inventadas y calibradas) y prohibiciones — **la balanza de 5.4/5.5 queda a 0** (valida con `skill-validacion-personajes`).
- **Despertar:** requisitos y efectos propios (sección 19.4).
- **El fruto en el mundo:** tier (tabla por tipo/rareza de la guía maestra), precio 5.9, vías de obtención, cupo mundial.

**La ficha es el contrato:** todo lo que no está en la ficha no existe; todo lo que está se respeta. Rechaza cualquier intento de inventarse una puerta a mitad de combate y respeta las rupturas declaradas (esa es la condición de la fruta).

## 19.3 Familias y mecánicas: qué comprobar

- **Paramecia:** sin intangibilidad por defecto (si la ficha la declara, tiene sus contadores como una Logia) · **vulnerabilidad al propio poder** salvo que la ficha diga lo contrario.
- **Zoan (común/ancestral/mitológica):** bonos por forma (híbrida/completa) **sujetos a la no-acumulación de buffs (la regla anti-spam de 5.10 — no se apila un bono del mismo tipo)** · cambiar de forma cuesta PA · **las heridas persisten entre formas** (transformar no cura) · garras/colmillos como arma natural de 5.8 · las mitológicas añaden su mito (segunda puerta) — verifica que el mito no sea un poder gratis sin coste.
- **Logia:** intangibilidad = **defensa especial con contadores publicados** (kairoseki 5.8 · Haki Armadura 5.19 · elemento antagónico · agua/estados) — nunca inmunidad absoluta · **control del elemento presente** (el terreno es su fortaleza) · no crean seres vivos · **armas imbuidas exigen O Wazamono+** (5.8) · no combinan su poder con el Haki Armadura en cuerpo elemental (5.19, cerrado: **Toque sólido** N1 anula la intangibilidad para ese golpe — el contador del cap. 20).

## 19.4 El despertar: requisitos y moderación

- **Requisitos (de nakama, adaptados sin abrir un atributo nuevo):** nivel alto (T1–T2 desde nv 25 · T3–T4 desde nv 32 · Logia/mitológica desde nv 40), **antigüedad como portador** (calendario de 5.6) y **temas cerrados usándola** (histórico) · **VOL como moneda**.
- **Trámite (5.21):** el jugador solicita, la skill propone el despertar de *esa* fruta según su ficha, tú firmas (o devuelves con alternativas).
- **Efectos por fruta:** Zoan colosal + regeneración · Paramecia transmuta el entorno · Logia alteración climatológica insular — **el despertar de una Logia es suceso de ronda (5.14)**: anótalo en la matriz y que el periódico lo cuente.
- **Mantenimiento:** el despertar sostiene las técnicas (sin PE extra) con su coste de PE por turno y sus reposos/puertas intactos — un límite real, no un botón de ganar. Anti-spam: una vez por tema-trama donde aplique.

## 19.5 Obtención: validación y anti-abuso

**Tres vías** (capítulo 19 del Jugador), todas con cupo mundial (`akumas.portador_id` único — un fruto, un portador):

1. **Tirada aleatoria (nv3+):** comprueba **nivel ≥ 3** y el **pool por nivel** (nv3+ T1–T2 · nv15+ T3 · nv30+ T4 · **T5 nunca por tirada**) · aplica la **afinidad natural** (−10 % PE en las técnicas de esa fruta) · **debe comerse** (no revender ni regalar) · **anti-abuso**: matar al personaje para repetir → sin tirada hasta **nv7**. La tirada solo decide qué fruta obtienes.
2. **Compra con PP:** descuenta la **matriz** (base 150/300/600/1.000/1.500 ×1 familia · ×2 concepto · ×3 concreta — T5 concreta 4.500 PP) · nivel ≥ 3 · **irreversible** (no devuelve PP si la fruta se pierde o cambia) · las técnicas se pagan aparte (5.7). Verifica el PP disponible en `historico_pp` (5.6).
3. **Recompensas de eventos/aventuras:** Mundo Vivo (5.14 — hallazgo/misión/tesoro con motivo), facciones (5.12), conquista (5.15), bajo mundo (5.13 — precio y rareza Mercado Negro). **Renacimiento:** al morir el portador, libera el cupo y genera el suceso de 5.14.

**Anti-abuso común:** duplicados (el hook bloquea dos portadores de la misma fruta) · frutas sin consumir que **ocupan ranuras como objeto de tamaño** (mediano 1 · grande 2–3, 5.8 — no hay "ranura por tier") · una T5 que "aparece" sin evento ni PP se rechaza · vender o regalar una fruta obtenida por tirada se rechaza (debe comerse).

## 19.6 Kairoseki: verificación (hueco de 5.8/5.9/5.17 cerrado)

El kairoseki ya vivía reservado en 5.8/5.9/5.17; aquí se cierra su uso contra portadores:

- **Añadido a armas de calidad (máx. O Wazamono) y munición** (5.8): el arma daña y **debilita** al portador (atributos efectivos y PA reducidos mientras dura el contacto — la magnitud en la ficha del añadido, calibrada contra las reducciones de 5.10).
- **Contención** (esposas, celdas, jaulas): contacto prolongado **anula el poder** — el portador es un cuerpo débil encerrado.
- **Acceso restringido:** no se vende en tiendas — Marina/Gobierno por rango (5.12) o bajo mundo (5.13, Mercado Negro de 5.9).
- **Fondo de barco (5.17):** su efecto es de 5.16 (oráculos marinos −1 grado); el matiz narrativo de la cubierta hostil lo resuelve el mar, no una regla nueva.
- **Anti-abuso:** rechaza armas de kairoseki de calidades superiores a O Wazamono · rechaza el kairoseki "que anula frutas" en un simple toque (anula solo en contacto prolongado) · el acceso de un jugador sin rango ni bajo mundo se rechaza.

## 19.7 Estructura de datos, hooks y panel (MyBB)

- **`akumas`** — catálogo: `id` · `nombre_propio` (canon) · `familia` (paramecia/zoan/logia) · `rareza` · `tier` (1–5) · `aspecto` · `mecanica_base` (JSON: pasivas, límites, rupturas con condición) · `puertas` (JSON: efectos de 5.7 + no registrados con calibración) · `debilidades` (JSON: enemigo natural) · `requisitos_portador` · `influencia_ficha` (JSON: dotes/defectos — balanza) · `despertar` (JSON) · `precio_base` (5.9) · `coste_pp` (matriz) · `portador_id` (cupo único) · `origen` (tirada/compra/recompensa) · `estado` (sin portador/con portador/renacida).
- **`akuma_pool_tirada`** — pool por nivel (`tier_max`) y por mar/región (el Mundo Vivo decide el sabor: no se encuentra una T5 en un Blue).
- **`personajes.akuma_id`** — la fruta del personaje (0 o 1).
- **`despertares`** — histórico: fruta, personaje, trámite que lo firmó, fecha.
- **`akuma_historico`** — la vida del fruto: portadores anteriores, vía y coste de obtención, renacimientos (alimenta 5.14).
- **Hooks:** tirada aleatoria → valida nivel ≥ 3 y sanción, elige del pool, asigna + afinidad −10 % PE · compra PP → descuenta matriz, valida nivel/cupo, asigna · consumo → valida cupo, asigna, aplica defectos a la balanza, desbloquea dotes · muerte del portador → libera cupo, marca renacida, genera suceso 5.14 · validación de ficha → 1 fruta por personaje, balanza a 0, requisitos.
- **Panel «Akumas»:** catálogo CRUD con la plantilla de 8 bloques · **control de cupos mundiales** (quién porta cada fruto) · histórico de renacimientos · moderación de frutas nuevas y **adaptación bajo demanda** (trámite 5.21 → `skill-adaptacion-akumas` con la guía maestra).
- **Trámites 5.21 (capítulo 22):** tirada aleatoria · comprar con PP · comer una fruta · solicitar despertar · adaptar una fruta de la obra (skill) · (moderación de frutas nuevas).

---

# 20. Haki (operativo) ✅

> Sección operativa correspondiente al capítulo 20 del Manual del Jugador. **Confirmado (Sesión 11, tandas 1 y 2 + catálogo en bloque)**: 5 niveles por tipo acumulativos · Conquistador 3/8/15/25/40 % por tirada nv5+ cada 10 niveles · subida 6+200 / 8+300 / 10+400 / 12+500 usos+PP con VOL 55/70/85/95 · despertar automático de Armadura/Mantra a nv10 · todos pueden Haki (fruta y no-fruta) · Ryou e infusión del Rey incluidos · el Conquistador barre la masa · **catálogo de pasivas/activas confirmado EN BLOQUE con 5 ajustes de diseño** (Soberanía Δ≤−20 · Koka +5/10/15 % · Visión parcial 1/combate · Presión Miedo/Terror · N3 +1 PA) · sin skill nueva (trámites por el motor del cap. 22) · **revisión de calibración numérica (PP/PE vs 5.7/5.6) verificada**.

## 20.1 El papel del staff en el Haki

El Haki es el último gran sistema de poder individual y casi todo su peso cae en la **verificación al cierre de tema** — no hay cálculo en vivo (no-crunch, Sesión 3): el jugador declara qué Haki usa y cómo en su post (Zona A); tú compruebas contra su ficha al cerrar el tema (Zona B). Tienes tres responsabilidades concretas:

1. **Contar usos.** Cada tema donde un personaje declaró y usó Haki de forma **satisfactoria** cuenta **un uso por tipo** (máximo 1 por tipo y por tema — la unidad es el tema, como el uso de un objeto). El hook de cierre de tema (Anexo A) actualiza `usos_acumulados`; tú verificas que el uso fue real y coherente con el post.
2. **Resolver la tirada del Conquistador.** Cada 10 niveles (nv5, 15, 25, 35, 45) el jugador puede intentar despertarlo: aplicas la probabilidad de la tabla (3/8/15/25/40 %), registras el intento y, si acierta, creas el nivel 1 + suceso de Mundo Vivo (5.14 — el mundo oye al Rey). **La tirada solo decide qué obtienes**: jamás resuelve una acción.
3. **Firmar subidas.** El trámite de subida valida usos + PP + requisito de VOL (55/70/85/95), descuenta los PP, sube el nivel y postea en la hoja con motivo.

## 20.2 Qué comprobar al verificar el uso (anti-abuso)

- ¿El tipo usado está **despierto** en la ficha? (Armadura/Mantra: automáticas a nv10; Conquistador: solo por tirada.) ¿El **nivel** declarado es el que tiene?
- ¿La **activa** declarada existe en el catálogo y el jugador tiene el nivel que la habilita? ¿Pagó su coste de PE (se descuenta al cierre, como las técnicas)?
- ¿El uso fue **satisfactorio** de verdad — la acción cambió algo en la escena, no fue un «uso decorativo» para acumular? La filosofía es la del karma del capítulo 6: el uso que no arriesga ni aporta no cuenta.
- **Un uso por tipo y por tema.** Da igual que declare el Haki en diez posts del mismo tema: cuenta un uso. (Con el ritmo del foro, dominar un tipo son ~9 meses de uso real — así está calibrado.)
- **Regla de no-acumulación de +% de daño (5.10):** Koka y la Sobrecarga del capítulo 8 no se suman — se aplica el mayor. Igual con Furioso y las bandas de delta.
- **Adaptabilidad humana NO aplica al Haki:** su −10 % PP es solo para dominios y técnicas (5.1); la escalera del Haki (200/300/400/500) se paga entera.

## 20.3 La VOL como puerta de nivel (clave para validar)

Con el techo por atributo de 5.6 (`20 + 1,6×(L−1)`), los requisitos de Voluntad se alcanzan **solo a ciertos niveles de personaje**: VOL 55 → nv~23 · VOL 70 → nv~33 · VOL 85 → nv~42 · VOL 95 → nv~48. Al validar una subida:

- Si el jugador no llega a la VOL mínima **ni con bonus de raza/dote por encima del techo** (5.1/5.4), la subida se rechaza con motivo — la meseta del N1 (nv10→23) es intencionada.
- No confundir: los bonus de raza y dotes **sí cuentan** para la VOL efectiva (se suman por encima del techo); lo que no se puede es comprar puntos por encima del techo sin nivel.

## 20.4 El catálogo en una mirada (lo que verificas contra la ficha)

**Armadura (VOL+RES)** — pasivas: reducción creciente +2/+4/+6/+8/+10 (se suma a armaduras de 5.8, no acumula con otras reducciones de Haki) · activas: Toque sólido 5 % (toca Logias — cierra el contador de 5.18 §4.3) · Koka 10 %/turno (+5/10/15 % de daño como multiplicador sobre el golpe según banda FUE/RES de la Tabla 2) · Emisión 15 % (alcance medio) · Ryou 20 % (ignora la mitad de reducción plana) · Koka absoluto 30 % 1/tema-trama (niega una fuente de daño).

**Mantra (VOL+PER)** — pasivas: niega Emboscado, ventaja en la Tabla 1, −1 PA a evasiones de área · activas: Sentir 0 % (presencias al inicio) · Anticipar 10 % (defensa −1 PA) · Lectura completa 15 % (intención exacta) · Visión parcial 20 % **1/combate** (evita un ataque individual o iniciativa total) · Visión del futuro 30 % 1/tema-trama (evita una Épica/maestra declarada — la respuesta de 5.7 al Haki).

**Conquistador (VOL+CAR)** — activas: Pulso del Rey 10 % (barre terciarios y hordas por nivel: N1 Mara y nv<15 · N2 Masa y nv<30 · N3 Marea y secundarios débiles · N4 casi todos los secundarios · N5 nadie sin VOL comparable; los que tienen voluntad se resisten por la Tabla 3) · Presión 15 % (Miedo/Terror según banda — **el Terror es el estado del Conquistador en 5.10**) · Soberanía 20 % (Δ≤−20 → Terror; Δ−10 a −19 → Miedo; aliados coraje: ignora un estado mental o +5 % PE máx) · Infusión del Rey 30 % 1/tema-trama (+1 escalón de daño e ignora la mitad de reducción). Pasiva N3: rivales con VOL inferior pagan **+1 PA por acción** (espejo del Tambaleante).

**Costes de PE (escala, Sesión 11):** la banda de 5.7 (10–40 %) aplica a activas de daño/control pleno; las de **contador/utilidad** (Toque sólido 5 %, Sentir 0 %) son deliberadamente más baratas; las tres 1/tema-trama están en 30 % — justo bajo la Épica (40 % o condición) porque no llevan su multiplicador ×6.

## 20.5 Capa técnica

**Tablas:** `haki` (personaje, tipo, nivel, usos_acumulados, pp_invertidos) · `haki_conquistador` (personaje, intentos nv5/15/25/35/45, obtenido) · `haki_historico` (subidas con motivo: tema_cierre, usos, PP, firma).

**Hooks:** cierre de tema → cuenta usos de Haki declarados y verificados por tipo y actualiza `usos_acumulados`; al alcanzar el umbral, el trámite de subida habilita el pago de PP · tirada del Conquistador (trámite 5.21) → valida nivel e intentos, aplica la probabilidad, registra el intento y, si acierta, crea `haki` nivel 1 + suceso de Mundo Vivo · subida (trámite 5.21) → valida usos + PP + VOL, sube el nivel, descuenta PP, postea en la hoja.

**Paneles:** vista «Haki» en la ficha (niveles, usos, PP invertidos) · bandeja de trámites de tirada/subida (cap. 22) · histórico de subidas e intentos del Conquistador.

**Skill:** **sin skill nueva** — los trámites van por el motor del cap. 22 con la IA general; el conteo de usos lo hace el hook de cierre y tu verificación (no-crunch).

## 20.6 Checklist operativo del Haki

1. ¿El tipo está despierto y el nivel declarado es el de la ficha?
2. ¿La activa existe en el catálogo y el coste de PE se descontó al cierre?
3. ¿El uso fue satisfactorio y cuenta **1 por tipo y por tema** (nada de usos decorativos)?
4. ¿La subida cumple usos + PP + **VOL mínima** (55/70/85/95)? ¿Los bonus de raza/dote están justificados por encima del techo?
5. ¿La tirada del Conquistador respetó la ventana (nv5+ cada 10) y los `intentos`? ¿La probabilidad fue la de la tabla?
6. ¿El +% de daño (Koka) respetó la regla de no-acumulación con Sobrecarga/Furioso/bandas?
7. ¿El uso del Conquistador contra masa se resolvió como **factor de escenario** (sin veredictos por cabeza), con los personajes resistiendo por la Tabla 3?

---

# 21. Narradores y auto-narradas (operativo) ✅

> Sección operativa correspondiente al capítulo 21 del Manual del Jugador. **Confirmado (Sesión 11, 4 decisiones del usuario)**: ficha de misión de **6 bloques** (identidad · objetivo con condiciones de victoria/fracaso explícitas · escenas en 3 actos con NPCs · recompensas berries/PP/fama/objetos · requisitos · **secretos solo-staff**) · **auto-narrada por elección libre del jugador** (aunque haya narrador; requisito duro = ficha completa; flujo por rondas con el motor de oráculos de 5.16) · narradores **staff + habilitados** (rol de foro), **máx 2 simultáneas** · 5 categorías originales (Facción/Reino-Isla/Profesional/Bajo mundo/Especial) · **skill `skill-narracion-automatica` diseñada** (la única que faltaba — el Anexo B queda completo).

## 21.1 El papel del staff en las aventuras

Las aventuras son el terreno donde el mundo se juega. Tu papel tiene cuatro caras:

1. **Publicar el tablón.** Las misiones nacen del análisis de ronda (cap. 15): `skill-mundo-vivo` propone qué necesita cada isla y tú decides qué entra al tablón. El tablón es **solo-staff**: los jugadores no se inventan misiones, eligen las del mundo.
2. **Habilitar y supervisar narradores.** El narrador es staff o jugador con rol de foro. Verifica el **cupo de 2 aventuras simultáneas** por narrador (al superarlo, redirige a otro narrador o a auto-narrada) y que los NPCs se jueguen según su ficha y capa oculta (5.11) — no por capricho.
3. **Firmar los tramos de auto-narrada.** El flujo es el de navegación (5.16): solicitud → oráculos por isla → tú analizas → prompt → la IA narra el tramo → tú revisas, editas si hace falta y publicas (incluyendo fichas de NPC cuando corresponda). Nada se publica sin tu firma.
4. **Cerrar con veredicto.** En el acto final, verificas las **condiciones de victoria/fracaso** contra lo roleado (como un cierre de tema de 5.10/5.6), aplicas recompensas con motivo e histórico y alimentas la ronda siguiente (5.14).

## 21.2 La ficha de misión: qué comprobar al crearla

Al crear o validar una misión, verifica los **6 bloques** en orden:

1. **Identidad** — categoría del catálogo (Facción/Reino-Isla/Profesional/Bajo mundo/Especial), origen (quién la publica), isla de la matriz (5.14), dificultad (banda de nivel) y duración (rondas).
2. **Objetivo con condiciones explícitas** — la regla de oro: *condiciones de victoria y de fracaso* escritas, verificables sin dados. Una misión sin ellas **no puede ser auto-narrada** (rechaza la solicitud con motivo).
3. **Escenas en 3 actos** — comienzo/medio/final con los NPCs implicados (referencia a su ficha de 5.11) y los oráculos posibles (motor de 5.16). Los actos son terreno de juego, no guiones.
4. **Recompensas** — berries (5.9) + PP (cierre de tema, 5.6) + fama (5.12) + objetos (5.8) cuando aplique, con motivo.
5. **Requisitos** — nivel/oficios/facción/grupo. Verifica que el solicitante los cumple (validación dura).
6. **Secretos solo-staff** — el bloque invisible: los giros del narrador. **Solo el staff y los narradores lo leen** (permiso de campo restringido en BD); la IA lo usa solo en su acto, y tú verificas que el giro no rompa las condiciones.

## 21.3 El flujo de la auto-narrada (el mismo de navegación)

1. **Solicitud (trámite):** el jugador elige la misión y confirma requisitos. Valida: ficha completa + requisitos + tasa del tablón si la hay (5.9).
2. **Oráculos:** el sistema lanza los oráculos del acto según la isla (motor de 5.16 — peligrosidad de la matriz 5.14). Sin dados: son escenarios, no resultados.
3. **Zona staff:** revisas el trámite (¿la ficha? ¿el oráculo es coherente con la isla?) y generas el prompt con: ficha pública + secretos + oráculos + estado del Mundo Vivo.
4. **Skill `skill-narracion-automatica`:** la IA narra el tramo de la ronda (prosa rica, NPCs según ficha, secretos si tocan).
5. **Firma y publicación:** editas si hace falta, publicas el tramo (con fichas de NPC cuando corresponda).
6. **Ronda de posts:** los jugadores postean en el tema de la misión (tema presente, 5.6).
7. **Siguiente tramo:** vuelven al trámite; el sistema recoge los posts, lanza el oráculo siguiente si el acto lo pide, y el ciclo repite hasta el acto final.
8. **Cierre:** verificas las condiciones contra lo roleado, aplicas recompensas (berries a cartera, PP al histórico, fama a 5.12, objetos a 5.8), registras el resultado con motivo y alimentas la ronda (5.14).

**Anti-abuso:** la IA nunca adelanta un tramo sin los posts de la ronda · los secretos no se revelan antes de su acto · si el grupo abandona o el plazo se agota, cierra como **fracasada/abandonada** con motivo · la misión es tema presente e invadible (5.6) · sin ficha completa no hay auto-narrada (nunca "narro y ya veremos").

## 21.4 La skill `skill-narracion-automatica` (Anexo B)

**Función:** genera el tramo de narrativa de la ronda para misiones auto-narradas. **Entrada (el prompt del trámite):** ficha de misión (6 bloques) · oráculos del acto · resumen de los posts del grupo · NPCs presentes con sus fichas · contexto de isla (peligrosidad/sucesos/control) · ronda del calendario (5.6). **Salida:** un tramo en prosa rica que (a) continúa el acto con los NPCs actuando según su ficha, (b) aplica el oráculo si procede, (c) **no resuelve por los jugadores** (la IA reacciona a sus posts, no decide por ellos) y (d) deja la escena lista para el siguiente tramo — o verifica las condiciones si es el acto final. **Reglas:** sin dados · sin personajes ni eventos canon (principio 7) · los combates se remiten a 5.10 (la IA narra la escena, no dictamina el golpe) · el resultado es editable y firmado por ti antes de publicarse.

## 21.5 Capa técnica

**Tablas:** `misiones` (ficha completa: identidad, condiciones victoria/fracaso en JSON, escenas, recompensas, requisitos, **`secretos_json` con permiso restringido solo-staff**, estado, resultado con motivo, narrador_id o NULL si auto-narrada) · `mision_tramos` (histórico de la narración: tramo, acto, oráculo usado, texto, posts considerados, firma) · `mision_participantes` (quiénes juegan, entrada/salida — para reparto de fama y PP).

**Hooks:** ronda (5.14) → propone misiones al tablón y cierra caducadas como abandonadas · solicitud de auto-narrada (5.21) → valida ficha + requisitos, lanza oráculo del acto 1, genera prompt, crea tema presente y primer tramo · posteo de tramo (5.21) → recoge posts, lanza oráculo siguiente, genera prompt, marca tramo pendiente de firma · cierre (5.21) → verifica condiciones, aplica recompensas, registra resultado, alimenta 5.14.

**Paneles:** «Narradores» (rol de foro, cupo de 2 simultáneas, historial) · «Misiones» (tablón CRUD con la ficha de 6 bloques y el campo de secretos **visible solo para staff/narradores**) · bandeja de trámites de auto-narrada (cap. 22).

**Skill:** `skill-narracion-automatica` (§21.4) — con ella el catálogo de skills del Anexo B queda **completo** (8 skills, actualizado 2026-08-28 con la implementación real de todas).

## 21.6 Checklist operativo de las aventuras

1. ¿La misión tiene ficha completa con **condiciones de victoria/fracaso explícitas**?
2. ¿La categoría, la isla y la dificultad son coherentes con la matriz (5.14) y el nivel del solicitante?
3. ¿El narrador asignado cumple el **cupo de 2**? ¿Está habilitado (rol de foro) o es staff?
4. ¿La solicitud de auto-narrada validó requisitos y tasa? ¿Los oráculos son coherentes con la isla?
5. ¿El tramo publicado respetó los posts de la ronda y los secretos de su acto?
6. ¿El cierre verificó las condiciones contra lo roleado y aplicó **todas** las recompensas con motivo?
7. ¿El resultado (cumplida/fracasada/abandonada) alimentó la ronda de Mundo Vivo (5.14)?

---

# 22. Trámites: catálogo y prompts (operativo) ✅

> **Estado:** ✅ disponible (Sesión 11) — diseño cerrado (checklist ítem 195 ✅) y volcado completado (196 ✅). El resumen del flujo vive en el capítulo 1.2; **este capítulo es el catálogo completo y el manual operativo**. Fuente: `diseno/5.21_tramites.md`.

## 22.1 Tu papel en los trámites

Recibes, lanzas el análisis (o lo resuelves), editas, **firmas** y publicas. La regla de oro (1.2): *la IA propone, tú decides, y nada sale sin tu firma*. Tu firma es la garantía de coherencia de todo el foro — úsala con criterio, no con prisa. Los trámites **staff-only** (los que inicias tú) corren por el mismo motor: misma bandeja, mismo histórico, misma firma; solo cambia quién aprieta el botón de empezar.

## 22.2 Anatomía común y ciclo de vida

- **Formulario común:** tipo · solicitante · **motivo** (obligatorio en los narrativos — hitos, cambios, concesiones) · `ids_json` (contexto: tema/personaje/isla/rumor/cartel/barco/fruta) · campos específicos por tipo (los que cada sistema ya define, p. ej. el trámite de navegación: origen autocompletado, destino, barco, acompañantes, utensilio).
- **Estados:** `borrador → pendiente → prompt_listo → analizado → en_revision → publicado | rechazado | archivado`. Los hooks de creación validan requisitos duros (nivel, PP, ubicación, un-presente, cupos) y arman el prompt; los de resolución aplican efectos (PP, posteo, histórico, notificación, impacto 5.14).
- **Firma:** rangos con cupo y staff-only exigen **firma siempre**; rangos abiertos bastan con la validación del trámite. Toda firma queda con motivo.
- **Ciclo con usuario (confirmado):** solo en creación de técnica (8) y validación de ficha (2) — el resultado vuelve al usuario, que pide cambios hasta aceptar. En el resto, una iteración opcional a petición.

## 22.3 El catálogo: 67 trámites (listado CERRADO)

> **Listado cerrado del foro (Sesión 11; 67 desde la integración de 5.22 y las ampliaciones post-cierre):** 49 trámites + los **50–51 de 5.19** (tirada del Conquistador · subida de nivel de Haki) + los **52–55 de 5.20** (solicitud de auto-narrada · posteo de tramo · apertura de misión · cierre de misión) + los **56–61 de 5.22** (instalación de implante · retirada · mantenimiento/reparación · diseño de mejora a medida · concesión de linaje · revocación de linaje) + el **62 de 5.21-bis** (**muerte de personaje** — veredicto con motivo, efectos de mundo y herencia al siguiente personaje por bandas de nivel × calidad, sin dados ni grados de resurrección) + los **63–67 de 5.21-ter** (**tripulaciones** — la entidad que formaliza el cofre común de 5.9, el límite por plazas del barco de 5.17 y los presentes compartidos; sin bonos numéricos: valor solo operativo). Solo se amplía con motivo y visto bueno del staff. Naturaleza: **IA** = pasa por skill (prompt) · **ligero** = validación + hooks, sin IA · **staff** = solo el staff inicia · **hito** = hito narrativo con firma.

| # | Sistema | Trámite | Skill | Quién | Nat. | Efecto al publicar |
|---|---|---|---|---|---|---|
| 1 | Transv. | Apertura de tema (presente/pasado) | — | Jugador | ligero | Ancla temporal, instantánea, bloqueo un-presente (5.6) |
| 2 | Transv. | Cierre de temas | `cierre-temas` | Jugador/staff | IA | PP, karma, fama, peso 5.14 |
| 3 | Transv. | Validación de ficha (crear/editar) | `validacion-personajes` | Jugador | IA + ciclo | Ficha aprobada; balanza 0, híbridos, prerrequisitos |
| 4 | 5.2/5.3/5.6 | Compra de PP (atributos, dominios) | — | Jugador | **ligero automático** | Descuenta PP, techo por nivel, cronómetros, cupo INT |
| 5 | 5.3 | Maestría Suprema (hito nv5 de rama) | — | Jugador | hito | Oficio a Maestría Suprema con firma |
| 6 | 5.3/5.8/5.9 | Producción de oficio (forja, cocina…) | IA general | Jugador | IA | Ítem a inventario/almacén; stock de tienda; atributo rey como vara; recetas de los catálogos menores (cap. 9) |
| 7 | 5.4 | Dote/defecto por hito narrativo | — | Jugador | hito | Adquisición 0 PP, sin tocar balanza; requisitos verificados |
| 8 | 5.4 | Genética Alterada (híbrido) | — | Jugador | hito | UNA dote racial de la 2ª raza, sin combinar con la dominante |
| 9 | 5.5 | Evolución por hito (arraigo positivo → dote) | — | Jugador | hito | Rasgo arraigado → dote; origen `hito`; recalcula balanza |
| 10 | 5.5 | Superación de rasgo negativo | — | Jugador | hito | Abandona el rasgo; sustituye por equivalente o antagónico |
| 11 | 5.5 | Pérdida/cambio por contradicciones | — | Jugador | hito | Rebalanceo con motivo (5 contradicciones) |
| 12 | 5.5 | Justificación de contradicción | — | Jugador | hito | La contradicción no cuenta (hito validado) |
| 13 | 5.7 | Creación de técnica | `creacion-tecnicas` | Jugador | IA + ciclo | Ficha completa; PP 60–600, cupo INT/4, postea en la hoja |
| 14 | 5.8 | Equipar/cambiar equipo | — | Jugador | ligero | Ranuras, cupos Meito, duplicados bloqueados |
| 15 | 5.9 | Apertura de tienda | IA general | Jugador | IA + firma | Tienda activa (Comerciante + local/módulo + capital + bélicos) |
| 16 | 5.9 | Cierre/reapertura de tienda | — | Jugador | ligero/firma | Ítems al almacén; suspensión hasta reabrir |
| 17 | 5.9 | Reposición de stock | — | Jugador | ligero | Stock desde producción o almacén |
| 18 | 5.9 | Boletín de precios | — | Staff | staff | Precios con banda de margen |
| 19 | 5.11 | Reclutamiento de NPC | — | Jugador | ligero/firma | Marca «reclutado» en `npc_apariciones`; tripulante sin ficha de combate (efecto real F6.2) |
| 20 | 5.12 | Ascenso de facción | `cierre-temas` (anexo) | Jugador/staff | IA + firma | Propuesta fama/termómetro/umbral; staff firma; sueldo y rango |
| 21 | 5.12 | Concesión de subfacción élite | IA general | Staff | staff | Nombramiento con cupo y firma (ítem 139) |
| 22 | 5.12 | Cambio de facción | — | Jugador | hito + firma | Transición narrada, equivalencia, `cambios_faccion` |
| 23 | 5.12 | Deserción | — | Jugador | hito + firma | Baja hostil → criminal/Wanted; o baja legal |
| 24 | 5.12 | Infiltración | — | Staff | staff | Capa oculta, rango honorario, firma |
| 25 | 5.13 | Solicitar rumor a la red | IA general | Jugador | IA | Ficha del rumor según capacidad y tiempo |
| 26 | 5.13 | Comprar rumor | — | Jugador | ligero | Pago de cartera, ficha transferida |
| 27 | 5.13 | Contrastar rumor | IA general | Jugador | IA | Veredicto: afina fiabilidad/veracidad con coste |
| 28 | 5.13 | Vender rumor | — | Jugador | ligero | Transferencia + copia opcional |
| 29 | 5.13 | Montar/ampliar la red | — | Jugador | ligero | Espías, mantenimiento, límite 4 |
| 30 | 5.13 | Publicar cartel | IA general | Staff | staff | Cartel con caducidad de paradero (3 rondas) |
| 31 | 5.13 | Cobrar recompensa | IA general | Jugador | IA + firma | Entrega verificada + histórico |
| 32 | 5.13 | Crear rumor falso (propaganda) | IA general | Jugador | IA | Veracidad *falsa*; fiabilidad del staff |
| 33 | 5.13 | Ataque a una red | IA general | Jugador | IA | Veredicto sin dados |
| 34 | 5.15 | Anuncio de conquista | IA general + matriz | Jugador | staff + firma | Público; invita al defensor; rumores y periódico (lo inicia el jugador, F6.2) |
| 35 | 5.15 | Responder al asedio (defensor) | — | Jugador | firma | Defensa activa |
| 36 | 5.15 | Resolver/registrar conquista | `mundo-vivo` | Staff | staff | Veredicto; afiliación/fuerza defensiva con motivo |
| 37 | 5.15 | Declarar reconquista | IA general | Jugador | staff + firma | Nueva disputa con las mismas fases |
| 38 | 5.16 | Navegación (travesía) | `navegacion` | Jugador | IA | Ficha de travesía; abre el tema; cierre → víveres, daños, `ubicacion` |
| 39 | 5.17 | Compra/adquisición de barco | — | Jugador | ligero/firma | Barco en flota; verificación tipo/nivel/madera |
| 40 | 5.17 | Construcción de barco (Astillero) | IA general | Jugador | IA + firma | Barco construido con oficio y materiales |
| 41 | 5.17 | Mejora N1→N2→N3 | IA general | Jugador | IA + firma | Mejora por diferencia de precio + madera |
| 42 | 5.17 | Módulos (instalar/quitar) | IA general (Carpintero) | Jugador | IA + firma | Ranuras y efectos; personalizados calibrados |
| 43 | 5.17 | Reparación | IA general | Jugador | IA + firma | Grados de daño con oficio; `reparaciones` |
| 44 | 5.17 | Venta/desguace/baja | — | Jugador | ligero/firma | Barco fuera de flota; hundimiento con veredicto |
| 45 | 5.18 | Tirada de akuma aleatoria | — | Jugador | ligero (azar) | Pool por nivel (nv3+/15+/30+), afinidad −10 % PE; anti-abuso nv7 — **100 % automático** (F5.1) |
| 46 | 5.18 | Compra de fruta con PP | — | Jugador | ligero | Matriz 150/300/600/1.000/1.500 ×1/×2/×3; familia automática con descuento PP; concepto/concreta a bandeja (la propone el staff) |
| 47 | 5.18 | Comer la fruta | — | Jugador | ligero | Asignación, defectos exigidos, dotes exclusivas |
| 48 | 5.18 | Despertar | IA general (plantilla) | Jugador | IA + firma | Requisitos (nivel, antigüedad, temas, VOL); `despertares` |
| 49 | 5.18 | Adaptación de fruta bajo demanda | `adaptacion-akumas` + guía | Staff | staff + firma | Ficha de 8 bloques desde nombre+concepto canon (staff inicia; IA propone, staff firma) |
| 50 | 5.19 | Tirada del Conquistador (nv5+ cada 10 niveles) | IA general (validación + hook) | Jugador | ligero (hook) | Valida nivel e `intentos`, aplica probabilidad 3→40 %, registra; si acierta: nivel 1 + suceso 5.14 |
| 51 | 5.19 | Subida de nivel de Haki | IA general (verificación) | Jugador | IA + firma | Valida usos (1/tipo/tema) + PP (200/300/400/500) + VOL mínima (55/70/85/95); descuenta y postea en la hoja |
| 52 | 5.20 | Solicitud de auto-narrada | `narracion-automatica` | Jugador | IA + firma | Elige misión de la ficha pública, confirma requisitos, paga la tasa del tablón (5.9); hook: valida ficha completa, oráculo del acto 1, prompt, tema presente + primera `mision_tramos`; el staff publica el tramo inicial |
| 53 | 5.20 | Posteo de tramo (siguiente tramo de la ronda) | `narracion-automatica` | Jugador | IA + firma | Hook: recoge los posts de la ronda, lanza el siguiente oráculo si el acto lo pide, genera el prompt y marca el tramo pendiente de firma |
| 54 | 5.20 | Apertura de misión (tablón) | IA general (ficha de 6 bloques) | Staff | staff | Publica la misión en el tablón con ficha completa (condiciones de victoria/fracaso explícitas + secretos solo-staff) |
| 55 | 5.20 | Cierre de misión (veredicto + recompensas) | `narracion-automatica` (prompt suma `cierre-temas`) | Staff | staff | Verifica las condiciones del acto final, aplica recompensas (berries 5.9 · PP 5.6 · fama 5.12 · objetos 5.8) con motivo y alimenta el análisis de ronda (5.14) |
| 56 | 5.22 | Instalación de implante | `adaptacion-cibernetica` (si es a medida) | Jugador | IA + firma | Valida requisitos acumulativos + balanza a 0 + cupo por zona + pago; vara de Cirujano+Ingeniero; aplica defectos; revalida la ficha |
| 57 | 5.22 | Retirada de implante | — | Jugador | ligero/firma | Libera el cupo de la zona y la balanza; las mejoras se pierden |
| 58 | 5.22 | Mantenimiento / reparación | — | Jugador | ligero | Pago por ronda (hook de 5.14) o reparación con Ingeniero (grados de daño como 5.17) |
| 59 | 5.22 | Diseño de mejora a medida | `adaptacion-cibernetica` + guía | Staff | staff + firma | La ranura de habilidad especial calibrada (efecto del catálogo o no registrado con condiciones); la inicia el staff |
| 60 | 5.22 | Concesión de linaje | IA general (expediente) | Staff | staff | Cruza el expediente de fidelidad con el cupo (3–5); aplica dote/defecto «La sangre llama»; suceso de ronda (5.14) |
| 61 | 5.22 | Revocación de linaje | — | Staff | staff | Retira dote/defecto, libera cupo, suceso de ronda; motivo obligatorio |
| 62 | 5.21-bis | Muerte de personaje | `cierre-temas` (calidad del desenlace) | Jugador/staff | staff + firma | Veredicto con motivo; ficha a reliquia; fruta renace (5.18); cartel retirado (5.13); baja de facción (5.12); suceso de ronda (5.14); herencia al nuevo personaje por bandas de nivel × calidad (PP 60→1.000 · berries 5.000→1M · ×0,5/×1/×1,5) |
| 63 | 5.21-ter | Fundación de tripulación | IA general | Capitán (jugador) | IA + firma | Entidad creada; valida mínimo 2, ficha (nombre/bandera/propósito), plazas del barco (5.17), un PJ por usuario; abre el tema de fundación |
| 64 | 5.21-ter | Ingreso en tripulación | — | Capitán | ligero/firma | Verifica espacio del barco (solo PJs, 5.17) y un PJ por usuario; fecha de ingreso |
| 65 | 5.21-ter | Baja / expulsión | — | Capitán | ligero/firma | Libera plaza; reparto de la parte del cofre con registro |
| 66 | 5.21-ter | Cambio de capitán | IA general | Staff/jugador | staff + firma | Cesión o motín con veredicto (5.10/5.14); mueve el cofre; suceso de ronda si cambia el nombre |
| 67 | 5.21-ter | Disolución | — | Capitán/staff | staff + firma | Reparte el cofre, devuelve objetos, barco al último capitán; cierra la entidad (automática <2 activos) |

## 22.4 Naturaleza de los trámites (decisiones confirmadas)

- **Con IA (skill o IA general + prompt):** naturaleza `ia` pura — 2, 3, 6, 13, 15, 20, 25, 27, 31–33, 38, 40–43, 48, 51–53, 56, 63 — **y los staff-only que también usan IA** — 21, 30, 34, 36, 37, 49, 54, 55, 59, 60, 62, 66 (naturaleza `staff` en el catálogo, con su skill de la tabla 22.5). Cada uno con su **plantilla de prompt** (22.6) y su skill del Anexo B cuando existe. *(Ajustado F6: 26/28/29 son ligeros de pago/transferencia sin IA — ver lista siguiente; 34 y 37 son `staff` que inicia el jugador con IA general; 49 lo inicia el staff con `skill-adaptacion-akumas`.)*
- **Ligeros/automáticos (validación + hooks, sin IA):** 1, 4, 14, 16, 17, 19, 26, 28–29, 35, 39, 44–47, 50 (tirada aplicada por hook), 57, 58, 64, 65. **Confirmado: la compra de PP (4) es automática** — valida PP/techo/cronómetro y aplica al instante, sin IA ni firma. *(Ajustado F6: 19 reclutamiento de NPC es ligero con firma (marca «reclutado»); 57 retirada de implante y 58 mantenimiento son ligeros (58 sin firma); 35 responder al asedio es ligero con firma; el 18 boletín de precios es staff-only, no ligero.)*
- **Staff-only — mismo motor (confirmado):** 18, 21, 24, 30, 36, 49, 54 (apertura de misión), 55 (cierre de misión), **59 (diseño de mejora a medida)**, 60 (concesión de linaje), 61 (revocación de linaje), **62 (muerte de personaje)** — el veredicto puede nacer del cierre de un tema (combate 5.10, misión 5.20, suceso 5.14) o del sacrificio declarado por el jugador; siempre firma del staff —, **66 (cambio de capitán: cesión o motín con veredicto)** y **67 (disolución: voluntaria con firma o automática <2 activos por hook)**, y los paneles de recompensas/misiones (5.14).
- **Ciclo con usuario (confirmado):** solo 13 (técnica) y 3 (ficha). En el resto, una iteración opcional.

## 22.5 El registro de la zona staff: trámites y automatizaciones

> **Registro consolidado (Sesión 11):** qué trámites tocan la zona staff, qué automatiza el motor y qué queda para ti. Complementa la vista por evento del Anexo A.2 (allí: automatización por hook · aquí: por trámite).

**Staff-only — inicia el staff por el mismo motor (16 trámites + los procesos de ronda; ajustado F6.2):**

| # | Trámite | Automático (hook) | Zona staff (manual) |
|---|---|---|---|
| 18 | Boletín de precios | La ronda (5.14) calcula la fluctuación `base × F_oferta × F_demanda × F_suceso` (techo 0,5–2×) con su desglose | Publica con motivo; ajusta bandas si el suceso lo pide |
| 21 | Concesión de subfacción élite | Valida cupo y requisitos; arma el prompt | Firma el nombramiento con motivo |
| 24 | Infiltración | Valida requisitos; prepara la capa oculta | Firma; otorga el rango honorario |
| 30 | Publicar cartel | Programa la caducidad de paradero (3 rondas, hook de 5.13) | Fija la recompensa; firma con motivo |
| 36 | Resolver/registrar conquista | Recoge el veredicto del combate (5.10) y la matriz (5.14); propone el registro | Firma; asigna afiliación/fuerza defensiva con motivo |
| 49 | Fruta bajo demanda | Arma el prompt con `skill-adaptacion-akumas` + guía maestra | Revisa, edita y firma la ficha de 8 bloques |
| 54 | Apertura de misión | Valida la ficha de 6 bloques (condiciones explícitas, secretos solo-staff) | Publica en el tablón |
| 55 | Cierre de misión | Verifica las condiciones del acto final; propone recompensas (berries/PP/fama/objetos) con motivo | Firma; aplica y alimenta el análisis de ronda (5.14) |
| 59 | Diseño de mejora a medida | Arma el prompt con `skill-adaptacion-cibernetica` + guía maestra | Revisa, edita y firma la ficha calibrada |
| 60 | Concesión de linaje | Cruza el expediente de fidelidad con el cupo (3–5); propone dote/defecto | Firma; lanza el suceso de ronda (5.14) |
| 61 | Revocación de linaje | Verifica el motivo; libera el cupo | Firma; lanza el suceso de ronda |
| 62 | Muerte de personaje | Al firmar: congela la ficha (instantánea 5.6), libera el presente, marca la fruta renacida (5.18), retira el cartel (5.13), da de baja de facción (5.12), genera el suceso de ronda (5.14) y aplica la herencia (PP 60→1.000 · berries 5.000→1M × calidad) | Firma el veredicto con motivo; ajusta la banda de calidad (descuidada/digna/leyenda) y la herencia si procede |
| 66 | Cambio de capitán | Valida la cesión o el motín; propone el veredicto (5.10/5.14); mueve el cofre; dispara el suceso de ronda si cambia el nombre | Firma con motivo; decide el sucesor si el motín es ambiguo |
| 67 | Disolución | Automática si <2 PJs activos (hook de ronda 5.14, con aviso y plazo para reclutar); reparte el cofre y devuelve objetos | Firma la disolución voluntaria con motivo; valida el reparto |
| 34 | Anuncio de conquista | Valida control previo (16.2), rondas requeridas (16.3) y anti-abuso de activa duplicada; suceso público + invitación al defensor | Firma con motivo; el **jugador** lo inicia (guard del motor F6.2 — es el único staff+naturaleza que arranca el jugador) |
| 37 | Declarar reconquista | Mismas cinco fases del asedio; exige conquista previa registrada | Firma con motivo; el jugador lo inicia |
| Ronda | Procesos de 5.14 | Cron mensual: KPIs, precios, islas, rumores de ronda, hordas, periódico | Revisas el dashboard, editas y publicas el «News Coo» |

**IA + firma (llegan desde el jugador):** 2, 3, 6, 13, 15, 20, 25, 27, 31–33, 38, 40–43, 48, 51–53, 56, 63 (y los ligeros/hitos con firma: 5, 7–12, 16, 19, 22, 23, 35, 39, 44, 57, 58, 64, 65). En todos: al crear el trámite el hook valida requisitos y arma el prompt; la skill propone; **al firmar, el hook aplica los efectos** (PP, posteo, histórico, inventario, impacto en la matriz de 5.14) y deja el motivo auditable. Tu trabajo manual se limita a revisar, editar si procede y firmar.

**Regla de la zona staff:** la automatización nunca decide sola — *la IA propone, tú decides, nada sale sin firma*. Únicas excepciones **100 % automáticas** (validación dura sin margen interpretativo, no pasan por ti): compra de PP (4), tirada de fruta (45) y tirada del Conquistador (50).

## 22.6 Plantillas de prompt

**Genérica** (todos los trámites con IA): rol del staff de análisis · número y tipo de trámite · contexto con IDs y datos del sistema según tipo · skill exacta del Anexo B · salida esperada en el formato del tipo + motivo · formato editable, nada se publica sin firma.

**Específicas ya definidas por sistema** (se consolidan aquí; la lista completa vive en `inc/ope_rol/tramites/catalogo.php` → `ope7_prompt_especifica`):

- **Cierre de temas (2)** — `skill-cierre-temas`: IDs + participantes → PP = Base(T) × 7 factores (fidelidad, peso, calidad, extensión ≥350 palabras, presente/pasado F_tiempo, riesgo, perfil), informe de rasgos por participante, fama propuesta y peso en la matriz 5.14. Base 50/75/125/200/300, techo 2×, suelo 0,5×, redondeo a favor del jugador.
- **Validación de ficha (3)** — `skill-validacion-personajes`: raza(s)/híbrido/tribu, balanza dotes/defectos = 0, balanza de rasgos = 0, parejas antagónicas, cadenas, techos `20 + 1,6×(L−1)`, secundarios 5.2, físicos, cupo INT → informe checklist (ciclo con el usuario).
- **Creación de técnica (13)** — `skill-creacion-tecnicas`: nombre+descripción+tier → ficha completa (requisitos T1:25→T5:70/55/40, dominio ≥ tier, efectos dentro del presupuesto, tipo, PA 2+tier, PE %, reposo, puerta) con el criterio de originalidad (ciclo con el usuario).
- **Ascenso de facción (20)** — `skill-cierre-temas` (anexo): expediente de fama (4 capas) + termómetro de la facción + umbral del rango → procede / no procede todavía / espera de cupo.
- **Solicitar rumor a la red (25)** — IA general (14.2.3): espía y capacidad (Novato→Supremo limita categoría/alcance) y tiempo (1–4 rondas) → ficha del rumor: contenido, tipo, alcance, categoría, fiabilidad publicada, veracidad interna y precio sugerido. Sin tiradas: la capacidad decide.
- **Contrastar rumor (27)** — IA general (14.4): coste por alcance × sensibilidad (×1–×10), tiempo 1–2 rondas → afina la fiabilidad un grado; en Sólido revela la veracidad interna. Límites: sin pistas no llega a Sólido.
- **Publicar cartel (30, staff)** — IA general (14.6): cifra (escala 5.9), paradero publicado con fiabilidad y nivel aproximado; caducidad de paradero a las 3 rondas.
- **Cobrar recompensa (31)** — IA general (14.6): verifica cartel vigente, paradero no frío, entrega real con veredicto (5.10) y anti-abuso de autocaza.
- **Crear rumor falso (32)** — IA general (14.8): veracidad interna = falso (nunca se reescribe); el staff decide la fiabilidad publicada y el alcance.
- **Ataque a una red (33)** — IA general (14.5): método declarado → veredicto sin dados: espías descubiertos, si la red se desactiva y la trama resultante.
- **Anuncio de conquista (34)** — IA general + matriz (cap. 16): control previo, motivo y justificación de presencia (16.2), rondas requeridas (16.3: 0/1/2/3/4+ por fuerza defensiva, fortificaciones +1), invitación al defensor y suceso público.
- **Declarar reconquista (37)** — IA general (16.5): nueva disputa con las mismas cinco fases; ventaja del defensor = fuerza defensiva instalada.
- **Navegación (38)** — `skill-navegacion`: trámite + matriz 5.14 + oficios/barco/utensilio → IRT interno (no se publica), narrativa inicial, tiempo off-roll (72/48/36 h por tramo), oráculos (7 tipos × gravedad) y víveres.
- **Construcción (40) / Mejora (41) / Módulos (42) / Reparación (43) de barco** — IA general (Astillero): verifica oficio y costes (madera 5.8 por clase 18.5; mejora por diferencia + un paso a la vez; ranuras del tipo/nivel con requisito de oficio; reparación por grados con log).
- **Despertar de akuma (48)** — IA general (19.6): requisitos por banda de tier/familia (T1–T2 nv25 · T3–T4 nv32 · Logia/mitológica nv40) + antigüedad on-roll + temas usándola + VOL → propuesta del despertar DE ESTA fruta y su suceso de ronda (la Logia siempre lo es).
- **Fruta bajo demanda (49, staff)** — `skill-adaptacion-akumas` + guía maestra: concepto canon → ficha de 8 bloques en JSON (identidad, mecánica base, puertas, debilidades, requisitos, influencia con balanza a 0, despertar, precio/vías con cupo de fruto único).
- **Tirada del Conquistador (50)** — 100 % automático (hook): valida ventanas nv5/15/25/35/45 (3→40 %) e intentos; si acierta: nivel 1 + suceso de ronda en borrador que el staff publica.
- **Subida de nivel de Haki (51)** — IA general (20.2/20.3): valida tipo despierto, usos 1/tipo/tema (satisfactorios), PP (200/300/400/500) y VOL efectiva (55/70/85/95); escalera N1→N5 pagada entera, sin adaptabilidad humana.
- **Solicitud de auto-narrada (52) y posteo de tramo (53)** — `skill-narracion-automatica`: ficha de 6 bloques + oráculos del acto (motor 5.16) + posts de la ronda + contexto de isla/ronda → tramo narrado en prosa rica; los NPCs actúan según su ficha, no se resuelve por los jugadores; verificación de condiciones en el acto final. **54 (apertura de misión, staff)** — IA general: ficha de 6 bloques en JSON (identidad, condiciones de victoria/fracaso explícitas, escenas en 3 actos con NPCs, recompensas, requisitos, secretos solo-staff) para el tablón. **55 (cierre de misión, staff)** — `skill-narracion-automatica` + `skill-cierre-temas`: verifica condiciones contra lo roleado, aplica recompensas con motivo y alimenta la ronda (5.14).
- **Instalación de implante (56) y diseño a medida (59, staff)** — `skill-adaptacion-cibernetica` + guía maestra: concepto → ficha calibrada con requisitos acumulativos (suma de todos los implantes), ranuras + efectos 5.7, defectos exigidos con balanza a 0, precios (100k/500k/2,5M ฿ · PP 200/400/600 · mantenimiento 2.500/10.000/40.000 ฿/ronda) e incompatibilidades (frutas, kairoseki máx O). **57 (retirada)** — motivo narrativo, libera cupo y balanza. **58 (mantenimiento/reparación)** — pago por ronda (×2 con «Mantenimiento oneroso») o reparación con Ingeniero.
- **Concesión (60) / revocación (61) de linaje (staff)** — IA general (expediente de fidelidad ponderado por `skill-cierre-temas`): cruza con el cupo mundial (3–5) → dote/defecto «La sangre llama» (−1) y suceso de ronda; la revocación exige motivo (traición al nombre o contradicciones 5.5).
- **Muerte de personaje (62)** — `skill-cierre-temas` (calidad del desenlace): tema/combate/misión/suceso que causó la muerte + confirmación mecánica del umbral de 5.10 (PV ≤ −(VOL×2) o PE ≤ −RES) + banda de calidad (descuidada/digna/leyenda) + efectos de mundo propuestos (fruta renacida, cartel retirado, baja de facción, suceso) + herencia calculada (PP 60→1.000 · berries 5.000→1M × 0,5/×1/×1,5).
- **Fundación de tripulación (63)** — IA general: ficha de nombre/bandera/propósito + capitán + fundadores + barco con plazas de 5.17 → validación (mínimo 2, un PJ por usuario) y tema de fundación. **Cambio de capitán (66)** — IA general: cesión o motín con el veredicto de 5.10/5.14 → sucesor, traslado del cofre y suceso de ronda si cambia el nombre. **Ingreso (64), baja/expulsión (65) y disolución (67)** — sin prompt: ligeros con firma (validación + reparto del cofre).

El sistema genera el prompt al crear el trámite; tú lo pegas en la sesión de IA con la skill; el resultado vuelve **editable** a la bandeja.

## 22.7 Capa técnica (ver Anexo A)

- **Tablas:** `tramites` (id · tipo · estado · solicitante · motivo · `ids_json` · prompt · `resultado_json` · firma_staff · fechas · `ciclo_version`) + `tramites_historico` (estado por estado con motivo — auditable).
- **Hooks:** creación → valida requisitos por tipo y arma el prompt · resolución → aplica efectos (PP, posteo, histórico, notificación) e **impacto en la matriz de 5.14** (ascenso, concesión, tienda, conquista, rumor) · anti-abuso (duplicados, spam, autocompra, ventas circulares, tiradas de fruta sin sanción).
- **Paneles:** bandeja por tipo/estado · vista «prompt listo» (copiar) · resultado editable · historial con motivo · búsqueda por jugador/tipo/fecha. Los paneles por sistema (Mundo Vivo, Técnicas, Tiendas, Barcos, Akumas, Facciones, NPCs, Bajo Mundo, Conquista, Navegación) son **vistas filtradas del mismo motor**.

## 22.8 Anti-abuso y reglas transversales

- Nada se publica sin tu firma · motivo e histórico obligatorios en toda resolución · la IA propone y tú decides · **sin azar** (las únicas excepciones: Conquistador 5.19 y tirada de fruta 5.18 — ambas deciden *qué obtienes*, no una acción) · **un presente a la vez** (5.6): los trámites que abren temas lo validan.

## 22.9 Checklist operativo del staff

1. ¿Formulario completo y **motivo** presente (si es narrativo)?
2. ¿IDs de contexto correctos (personaje/tema/isla/…)? ¿La skill del Anexo B es la correcta para el tipo?
3. ¿El **prompt generado** incluye todo el contexto que la skill necesita? (Pégalo tal cual; no lo edites a mano.)
4. ¿El resultado es coherente con los manuales y el lore? ¿Con los cupos y requisitos duros?
5. ¿Firma con motivo? ¿El hook aplicó los efectos (PP, posteo, histórico, impacto 5.14)?
6. ¿Rechazo/archivo con motivo y con la puerta abierta a reintentarlo?

---

# 23. Sistemas opcionales: cibernética y Familias Legendarias (operativo) ✅

> Sección operativa correspondiente al capítulo 23 del Manual del Jugador. **Confirmado (Sesión 11, dos tandas de ❓ + segunda tanda numérica, checklist 206–207 + 209 ✅)**: se incluyen **Cibernética** (sistema completo: 3 zonas anatómicas × niveles N1–N3 con requisitos acumulativos recalibrados a los techos de 5.6 · ranuras de mejora · instalación por oficio Médico-Cirujano + Ingeniero sin dados · mixto berries+PP con mantenimiento por ronda · todos los implantes exigen defectos con balanza a 0 · compatibles con frutas con condiciones · disponibilidad por isla/eventos) **+ Familias Legendarias** (cierra la Línea D. reservada de 5.1-bis: 3 linajes — Línea D. · Los Vientomar · La Casa Cindral — con dotes de linaje, sangre narrativa, cupos 3–5 y concesión/revocación staff-only) · **skill `skill-adaptacion-cibernetica`** (la 8.ª del Anexo B) con su guía maestra `diseno/5.22_guia_adaptacion_implantes.md` · **guía maestra** · trámites 56–61 del catálogo (cap. 22).

## 23.1 Tu papel en los opcionales

Los opcionales son, ante todo, **contenido moderado como el resto**: nada se instala, se concede ni se revoca sin firma, y todo lo que un implante o un linaje hace se declara y se verifica al cierre de tema — nunca por tirada (principio 1). Tu papel concreto: **validar las fichas de implante** (balanza a 0, requisitos, ranuras dentro del presupuesto), **vigilar la disponibilidad** (isla/eventos), **moderar la instalación** (vara de oficio) y **gestionar los linajes** (expediente de fidelidad, cupos, concesión/revocación con suceso de ronda).

## 23.2 La cibernética: qué comprobar en la ficha del implante

Cada implante de un personaje vive en `modificaciones_personaje` y debe cumplir, en orden:

1. **Zona y nivel:** una por zona (máx. 3 implantes: extremidades, torso, cabeza) · nivel N1/N2/N3 con su **puerta de personaje** (nv10/20/35).
2. **Requisitos acumulativos** (tabla de 5.22 §A.2, confirmada): el cuerpo soporta la **suma** de los requisitos de todos sus implantes, no cada uno por separado. N1: RES 30–35/VOL 25–30 · N2: 45–55/35–40 · N3: 65–75/50–60 (+INT en cabeza 30/45/65).
3. **Ranuras:** material (obligatoria, slot 1 — calidad de 5.8; kairoseki máx. O Wazomono y contenido, 5.18; maderas de 5.17) · armamento/dial (del dominio o los diales, con su PA/PE) · bonificador (**tope +5 por atributo** y nunca sobre el techo de 5.6) · habilidad especial (catálogo de 5.7 o efecto no registrado con condiciones, calibrado).
4. **Defectos exigidos** (balanza a 0): el paquete «implante» (ranuras + defectos) suma **exactamente 0** — `skill-validacion-personajes` lo comprueba como con las frutas. Catálogo: Cuerpo pesado −1 · Mantenimiento oneroso −2 · Vulnerabilidad al magnetismo −2 · Ancla al agua −1 · Rechazo social −1 · El cuerpo manda −3. Las prótesis de **reemplazo** estéticamente equivalentes no exigen defectos (decisión narrativa del staff con el trámite).

## 23.3 Instalación, costes y mantenimiento

- **Vara de instalación (sin dados):** Médico con rama **Cirujano** + **Ingeniero** al nivel de rama del implante (N1 → nv3 · N2 → nv4 · N3 → nv5). Autocirugía: +1. El **INT** es matiz de recuperación (1 ronda con INT suficiente, 2 si no).
- **Costes (confirmados):** instalación **N1 100.000 · N2 500.000 · N3 2.500.000 ฿** (tasación de 5.9) · **PP 200/400/600 por implante completo** (no por ranura) · **mantenimiento 2.500/10.000/40.000 ฿/ronda** (espejo de los espías de 5.13).
- **Mantenimiento:** el hook de la ronda de 5.14 descuenta y degrada (estado «averiado») si no se paga; el defecto «Mantenimiento oneroso» duplica el coste. **Reparaciones:** Ingeniero, grados de daño como el barco (5.17).

## 23.4 Compatibilidad con frutas (condiciones a verificar)

- **Básicos** (prótesis de reemplazo, materiales): para todos, incluidos portadores de fruta.
- **Avanzados** (armamento, bonificadores, habilidades): con **declaración** — Paramecia se integra (el elastómero recubre el metal); Zoan en **forma total** no aporta pasivas del implante; Logia en forma elemental **no se toca** salvo el **brazo de kairoseki** (el contador cerrado de 5.18).
- **Agua y kairoseki universales:** el mar apaga la electrónica («Ancla al agua»); el kairoseki del implante afecta como cualquier kairoseki.

## 23.5 Disponibilidad (tres condiciones)

Requisitos del portador (zona × nivel + puerta + balanza) · **isla** de desarrollo Ciudad/Reino o peligrosidad 30+ (5.14) · **sucesos** del Mundo Vivo (el científico, el cargamento del mercado negro, la subasta — 5.13/5.14/5.20). Si una de las tres falla, el trámite se rechaza con motivo.

## 23.6 La skill `skill-adaptacion-cibernetica`

Ficha de implante **bajo demanda** (trámites 56/59): entrada = concepto (zona, nivel, ranuras, justificación) → salida = ficha completa calibrada (requisitos exactos, ranuras del catálogo + efectos de 5.7, defectos que exige con balanza a 0, precios/mantenimiento, incompatibilidades). Se rige por la **guía maestra** `diseno/5.22_guia_adaptacion_implantes.md` (mapeo mejora → catálogo, tabla de calibración N1–N3, anti-abuso de 8 reglas, ejemplos resueltos). Igual que la skill de frutas: el resultado es **editable en la bandeja** y nada se publica sin tu firma.

## 23.7 Las Familias Legendarias (el linaje, no la genética)

- **3 linajes con cupo mundial (3–5):** Línea D. (VOL +5 efectiva para estados y puertas de Haki) · Los Vientomar (IRT −1 en travesías + anticipar un oráculo) · La Casa Cindral (técnicas de fuego con +1 tier efectivo de requisito, sin subir daño). Cada uno con su **dote de linaje** (+1/+2, 5.4) y el defecto **«La sangre llama»** (−1).
- **Requisito de sangre = narrativo:** la herencia se **juega**, no se demuestra — el **expediente de fidelidad** del personaje en sus temas (ponderado por `skill-cierre-temas` al cierre), su historia y el visto bueno del staff.
- **Concesión/revocación (trámites 60–61, staff-only):** cruza el expediente con el cupo — la IA propone, **tú decides y firmas** con motivo. Concedido → dote/defecto en la ficha + **suceso de ronda** (5.14). Revocado (traición al nombre, contradicciones de 5.5) → se retira, se libera el cupo y hay suceso.

## 23.8 Capa técnica (ver Anexo A)

**Tablas:** `implantes` (catálogo: zona · nivel · requisitos · ranuras · precios · mantenimiento · defectos) · `modificaciones_personaje` (implante_id · personaje_id · ranuras JSON · nivel · estado/daño) · `implante_historico` · `familias_legendarias` (catálogo: nombre · dote · defecto · cupo · lore) · `linaje_personaje` (familia · persona · estado activo/revocado · motivo).

**Hooks:** instalación → valida requisitos + balanza + cupo por zona, descuenta berries/PP, aplica defectos · **ronda (5.14)** → mantenimiento por ronda y degradación · daño (5.10) → grados de daño del implante · retirada → libera cupo y balanza · concesión/revocación de linaje → valida cupo/expediente, aplica dote/defecto, lanza suceso de ronda.

**Paneles:** «Cibernética» (fichas por personaje, mantenimientos pendientes, estado/daño, histórico con firma) · «Familias Legendarias» (catálogo con cupos, portadores, expediente de fidelidad, bandeja de concesión/revocación).

## 23.9 Anti-abuso y checklist operativo

**Anti-abuso:** implantes sin defectos · bonos fuera del tope +5 · efectos de tier superior al nivel del implante (N1 ≈ T1–T2 · N2 ≈ T3 · N3 ≈ T4/T5 con su condición) · transformación radical solo por trama · combinaciones rotas con Logia/Zoan · el «Mecatrón» no da Haki gratis (exige el requisito de nivel del Haki) · cupo de 1 por zona y disponibilidad por isla/eventos · sin personajes canon (principio 7).

**Checklist del staff:**
1. ¿Zona única y nivel con su puerta de personaje? ¿Requisitos acumulativos cumplidos (suma de todos los implantes)?
2. ¿Ranuras dentro del presupuesto del nivel? ¿Bonificador ≤ +5 por atributo y dentro del techo de 5.6?
3. ¿**Balanza a 0** — ranuras + defectos exactamente 0? ¿La `skill-validacion-personajes` la pasó?
4. ¿Costes correctos (instalación, PP, mantenimiento por ronda)? ¿La vara de instalación (Cirujano + Ingeniero) se cumple?
5. ¿Compatibilidad con frutas declarada y coherente (Logia/Zoan)? ¿Disponibilidad por isla/eventos justificada?
6. ¿Linaje: cupo libre, expediente de fidelidad suficiente, dote/defecto aplicados, suceso de ronda lanzado?
7. ¿Firma con motivo y registro en el histórico (`implante_historico` / `linaje_personaje`)?

---

# Anexo A: capa de implementación técnica (MyBB)

> **Entregable consolidado de la sección 7 del maestro** (Sesión 11). La capa técnica se diseñó **en paralelo a cada sistema** (5.1–5.21, todos cerrados y volcados) y aquí se unifica en tres vistas: (1) estructura de datos, (2) automatizaciones/hooks con su parte manual vs. automática, y (3) paneles de staff. Todo lo que sigue es la **propuesta consolidada** del proyecto; el detalle de cada pieza vive en el capítulo de su sistema y en su `diseno/5.X`.

## A.1 Estructura de datos (tablas)

| Tabla(s) | Sistema | Dónde se detalla |
|---|---|---|
| `razas` · `raciales` | 5.1 | `diseno/5.1_razas_y_raciales.md` |
| `tribus` + `personajes.tribu_id` | 5.1-bis | `diseno/5.1_tribus.md` |
| `personajes` (atributos, nivel, pp_saldo, es_NPC, **`estado` = validación** borrador/revision/aprobado/rechazado + **`estado_vida`** activa/muerta) · `cuentas` (puntero activo + staff/narrador + slots, canon F6.3) | 5.1/5.2/5.6 | caps. 2, 3 y 7 |
| `atributos_secundarios` | 5.2 | cap. 3 |
| `dominios` · `dominios_personaje` · `especializaciones` | 5.3 | cap. 4 |
| `dotes` · `defectos` · `personaje_dotes` | 5.4 | cap. 5 |
| `rasgos` · `personaje_rasgos` | 5.5 | cap. 6 |
| `temas` (ampliación) · `temas_participantes` · `calendario_foro` · `historico_pp` | 5.6 | cap. 7 |
| `tecnicas` · `catalogo_efectos` | 5.7 | cap. 8 |
| `objetos` · `inventario_personaje` · `almacen` · `arma_meito` | 5.8 | cap. 9 |
| `economia_config` · `precios_mercado` · `carteras` · `tiendas` · `tienda_items` · `transacciones` | 5.9 | cap. 10 |
| `acciones_pa` · `estados` · `estados_activos` · `resoluciones_combate` · `matices_combate` · `turnos_combate` · `sala_combate` | 5.10 | cap. 11 (grupo/naval y formato de posteo) |
| `es_NPC`/`tipo_npc` (en `personajes`) · `npc_primario` (capa oculta) · `bestiario` · `npc_apariciones` · `npcs_secundarios` | 5.11 | cap. 12 |
| `facciones` · `rangos_faccion` · `faccion_personaje` (fama) · `subfaccion_elite` · `cambios_faccion` · `sueldos` · `npc_faccion` | 5.12 | cap. 13 |
| `rumores` (veracidad solo-staff) · `fuentes_informacion` · `red_espionaje` + `espias` · `rumor_operaciones` · `carteles_recompensa` · `rumor_isla_ronda` | 5.13 | cap. 14 |
| `islas` · `isa_estado` (con **`fuerza_defensiva_nivel`**, no `fuerza_defensiva`) · `isa_estado_historico` · `rondas` · `matriz_peso` · `dashboard_acciones` · `recompensas_historico` · `sucesos` · `historico_periodicos` · `mv_noticias` · `cronologia` (respaldo legado F6.4) · `mv_mision_asignaciones` | 5.14 | cap. 15 |
| `conquistas` · `asedios` · `unidades` · `hordas` · `zonas` · `fuerza_defensiva_nivel` (en `isla_estado`) | 5.15 | cap. 16 |
| `travesias` · `oraculos_catalogo` · `incidentes_travesia` · `transportes` | 5.16 | cap. 17 |
| `barcos` · `tipos_barcos` · `maderas_casco` · `modulos_barcos` · `reparaciones` | 5.17 | cap. 18 |
| `akumas` · `akuma_pool_tirada` · `personajes.akuma_id`/`akuma_afinidad` · `despertares` · `akuma_historico` | 5.18 | cap. 19 |
| `haki` · `haki_conquistador` · `haki_historico` | 5.19 | cap. 20 (niveles/usos/PP, intentos de tirada, subidas con firma) |
| `misiones` · `mision_tramos` · `mision_participantes` | 5.20 | cap. 21 (ficha de 6 bloques con `secretos_json` de permiso restringido solo-staff, histórico de tramos con oráculos y firma, participantes para reparto de fama/PP) |
| `implantes` · `modificaciones_personaje` · `implante_historico` · `familias_legendarias` · `linaje_personaje` | 5.22 | cap. 23 (catálogo de implantes con requisitos/ranuras/precios/defectos; fichas por personaje con ranuras JSON y estado/daño; histórico; linajes con dote/defecto/cupo y portadores activos/revocados) |
| `tramites` · `tramites_historico` | 5.21 | cap. 22 (tipos, estados, prompt/resultado, firma, histórico auditable) |
| `personajes.estado_vida` (activa/muerta) · `muertes` | 5.21-bis | cap. 22 (trámite 62: veredicto con causa y calidad, herencia PP+berries, reliquia visible, suceso de ronda) |
| `tripulaciones` · `tripulantes` · `cofre_tripulacion` (berries/log, no el mecanismo de carteras de 5.9) · `tripulacion_historico` | 5.21-ter | cap. 22 (trámites 63–67: ficha con `cofre_id` → `cofre_tripulacion`, miembros con `espacio_ocupado` por raza de 5.17, histórico auditable) |
| Venenos/diales/materiales (catálogos menores) | 5.8-bis | cap. 9 (reutilizan `objetos` con `categoria` consumible/dial y el trámite 6 — **sin tablas nuevas**) |
| Espejos del front legado migrados (F6.4) · `cron_log` (F6.5) | — | **sin sistema propio**: `alertas` · `mensajes` · `relaciones` · `post_templates` · `thread_meta` · `estilos` · `lore` · `pp_saldo` · `pp_log` · `pj_vocaciones` · `acompanantes` · `acompanante_solicitudes` — respaldados read-only del esquema anterior conservados para el front · `cron_log` (última ejecución de cada cron, panel Progresión F6.5) |

## A.2 Automatizaciones / hooks

> **Principio (5.21):** la IA **propone**, el staff **decide**, y nada se publica sin **firma**. Por eso cada hook tiene dos caras: lo que el sistema hace **solo** (validación dura, descuentos, cronómetros, posteo, histórico) y lo que **espera al staff** (pegar el prompt en la IA, revisar/editar el resultado, firmar). Los hooks que usan skill **nunca aplican el resultado de la IA sin firma**; los ligeros (validación + descuento) son 100 % automáticos.

| Evento / hook | Sistema | Automático (validación, descuento, registro) | Requiere staff (prompt → IA → firma) |
|---|---|---|---|
| **Crear personaje / validar ficha** | 5.1–5.6 | Balanza dotes/defectos = 0, atributos de híbridos, techos por nivel, cupos | `skill-validacion-personajes` (ciclo con el usuario: técnica y ficha) |
| **Abrir tema** | 5.6 | Valida tipo (presente = fecha actual; pasado = biográfico), **un presente a la vez** por personaje, instantánea de ficha | — |
| **Cerrar tema** | 5.6 | F_tiempo según tipo, libera congelación, registra histórico | `skill-cierre-temas` (PP, karma, fama, peso 5.14) |
| **Compra de PP / subir atributo / dominio** | 5.2/5.3/5.6 | **Ligero automático**: descuenta PP por tramo, techo `20+1,6(L−1)`, cronómetros, cupo INT; sube nivel y recalcula secundarios al completar bloque | — |
| **Crear / subir técnica** | 5.7 | Valida requisitos (atributos, dominio ≥ tier, INT/4), descuenta PP por tier, aplica reposos/puertas en la ficha | `skill-creacion-tecnicas` (ficha completa desde la idea; ciclo con el usuario) |
| **Derrota / cierre de combate** | 5.10 | Veredicto de 5.10 (tablas de delta, umbral del dolor), KO/muerte, robo de equipado, integridad de armaduras, descuenta consumibles | Resolución del intercambio en la moderación del tema |
| **Saqueo / botín** | 5.8/5.9 | Al derrotar: robo del **equipado** (nunca almacén), descuento de integridad, registro de botín | — |
| **Compra/venta y tiendas** | 5.9 | Valida cartera, aplica fluctuación `F_oferta×F_demanda×F_suceso`, descuenta stock, compra NPC al 50 % | Apertura/cierre de tienda de jugador (Comerciante + local/módulo) |
| **Ronda de Mundo Vivo** | 5.14 | Cron mensual: acumula temas presentes, cierra la ronda, aplica matriz de islas, `precios_mercado`, histórico de periódicos | `skill-mundo-vivo` (dashboard, misiones, recompensas, periódico «News Coo», rumores y carteles) — **todo editable antes de publicar** |
| **Conquista / asedio** | 5.15 | Hooks de anuncio/ronda/registro/abandono: actualiza `isla_estado` + histórico, duración por fuerza defensiva, pérdida por abandono (2 rondas) | Anuncio, veredicto (5.10/matriz 5.14), registro con motivo |
| **Navegación** | 5.16 | Hook de ronda: valida ubicación y un-presente, tiempo off-roll, víveres, daños al barco | `skill-navegacion` (inicio/tramo/oráculos) + firma del veredicto |
| **Barcos** | 5.17 | Hooks de daño (5.10/5.16) → `pv_actual`, módulos, reparaciones, mantenimiento | Compra/construcción/mejora/venta (Astillero, trámites 5.21) |
| **Akuma no Mi** | 5.18 | Tirada aleatoria (pool por nivel), compra PP (matriz de especificidad), consumo (cupo mundial, balanza a 0), muerte del portador (libera cupo, suceso 5.14) | `skill-adaptacion-akumas` (ficha bajo demanda desde nombre+concepto) + despertar |
| **Haki** | 5.19 | Cierre de tema → cuenta usos por tipo (1/tipo/tema); tirada del Conquistador (valida nv5+ e intentos, aplica 3→40 %) | Subida de nivel (usos + PP + VOL) con firma |
| **Aventuras / auto-narradas** | 5.20 | Ronda → propone misiones y cierra caducadas; solicitud → valida ficha + requisitos, lanza oráculos, crea tema presente | `skill-narracion-automatica` (tramo por rondas) + firma del cierre (condiciones + recompensas) |
| **Cibernética / linajes** | 5.22 | Instalación → valida requisitos + balanza + cupo por zona, descuenta berries/PP, aplica defectos; **ronda (5.14)** → mantenimiento por ronda y degradación; daño (5.10) → grados de daño del implante; retirada → libera cupo y balanza; concesión/revocación de linaje → valida cupo/expediente, aplica dote/defecto, lanza suceso de ronda | `skill-adaptacion-cibernetica` (ficha bajo demanda desde concepto, con guía maestra) + firma del staff |
| **Trámites (transversal)** | 5.21 | Creación → valida requisitos duros y **arma el prompt**; resolución → aplica efectos (PP, posteo, histórico, notificación, impacto 5.14) | El staff pega el prompt en la IA con la skill, edita el resultado y **firma con motivo** |
| **Muerte de personaje** | 5.21-bis | Al firmar el trámite 62: congela la ficha (instantánea 5.6), libera el presente, marca la fruta renacida (5.18), retira el cartel (5.13), baja de facción (5.12), aplica la herencia (PP+berries), verifica la sanción de tirada de fruta (5.18) y genera suceso+rumor (5.14/5.13) | Veredicto con motivo (umbral 5.10 / misión 5.20 / suceso / sacrificio) + banda de calidad (`skill-cierre-temas`) + firma |
| **Tripulaciones** | 5.21-ter | Fundación/ingreso → valida mínimo 2, ficha, plazas del barco (solo PJs) y 1 PJ/usuario; cambio de capitán → mueve el cofre y dispara suceso de ronda si cambia el nombre; disolución → reparte el cofre y devuelve objetos; ronda (5.14) → avisa si <2 activos con plazo | Motín con veredicto (5.10/5.14) + firma; disolución voluntaria con firma y reparto validado |

### A.2-bis Verificación de implementación (hooks y crones reales, 2026-08-28)

> Tabla de control que cierra la brecha diseño → código: **cada hook/cron del motor 7 Seas**
> con su archivo y cuándo se dispara. Auditoría hecha contra el código real (plugin + `inc/ope_rol/`)
> en F6. Los hooks del motor viejo desactivados (snapshot, CU, PP por post, parses legacy) están
> **comentados** en el plugin y no aparecen aquí: son código muerto retirado en D6.3/F6.4.

#### Hooks del plugin (`inc/plugins/ope_rol.php`)

| Hook MyBB | Función | Cuándo se dispara | Qué hace |
|---|---|---|---|
| `global_start` | `ope_rol_global` | Cada petición | Contexto del motor: staff/permisos (`ope_cuentas`), PJ activo, navbar, contadores alertas/mensajes |
| `global_start` | `ope7_progresion_cron` | Cada petición (cron perezoso, idempotente) | Avanza calendario on-roll, finaliza entrenamientos/dominios y encadena los subcrones de ronda de la tabla siguiente |
| `datahandler_post_insert_thread` | `ope_rol_stamp_thread` | Al insertar un hilo nuevo (validación) | Ancla `ope_lastpid` del hilo al PJ activo |
| `datahandler_post_insert_thread_post` | `ope_rol_stamp_thread_post` | Tras validar el hilo | Registro del hilo con el PJ autor |
| `datahandler_post_insert_post` | `ope_rol_stamp_post` | Al insertar una respuesta | Ancla `ope_lastpid` del post al PJ activo |
| `datahandler_post_insert_thread_end` | `ope_rol_after_thread` | Al insertarse el hilo en BD (D1.8) | Vincula `ope_temas.tid ↔ mybb_tid` del hilo real; `ope_lastpid` en hilo/foro |
| `datahandler_post_insert_post_end` | `ope_rol_after_post` | Al insertarse la respuesta en BD | Cierra el vínculo del presente y refresca `ope_lastpid` |
| `datahandler_post_insert_thread_end` / `post_end` | `ope7_zonab_on_post` | Tras cada posteo | Persiste el turno de Zona B (`turnos_combate`/`sala_combate`) si el post lo lleva |
| `newthread_do_newthread_start` | `ope_rol_guard_newthread` | Antes de crear un hilo | Guard de apertura: zona permitida, un-presente, permisos |
| `newreply_do_newreply_start` | `ope_rol_guard_newreply` | Antes de responder | Guard de respuesta: presencia en el tema, bloqueos |
| `parse_message` | `ope_rol_parse_spoilers` | Render de cada mensaje | Parsea los spoilers propios del foro |
| `parse_message` | `ope7_zonab_parse` | Render de cada mensaje | Parsea el bloque de Zona B en posts |
| `postbit` | `ope_rol_postbit` | Render de cada post | Postbit 7 Seas: nombre/avatar del PJ activo, identidad |
| `newreply_threadreview_post` | `ope_rol_threadreview_post` | Revisión de hilo al responder | Vista de posts del hilo en la respuesta |
| `forumdisplay_thread_end` | `ope_rol_forumdisplay_thread` | Listado de un foro | Marca/personaliza el listado (último PJ, estilos) |
| `showthread_end` | `ope_rol_hide_modtools_showthread` | Vista de un hilo | Oculta herramientas de moderación no aplicables |
| `showthread_end` | `ope_rol_showthread_tags` | Vista de un hilo | Etiquetas/estados visibles del tema |
| `pre_output_page` | `ope_rol_inject_navbar` | Antes de servir la página | Inyecta la navbar 7 Seas (fallback JS para páginas propias) |
| `pre_output_page` | `ope_rol_inject_zonab_editor` | Antes de servir la página (solo newthread/newreply/editpost) | **Zona B (F2.2)**: incrusta `ope7_zonab_editor_html()` justo bajo el editor si hay PJ 7 Seas activo con vida |

#### Subcrones encadenados desde `ope7_progresion_cron` (`inc/ope_rol/sistemas/progresion.php`)

| Cron | Archivo | Cuándo se dispara | Qué hace |
|---|---|---|---|
| `ope7_calendario_avanzar` | `sistemas/progresion.php` | Cada petición (perezoso, 1 real = 2 on-roll) | Avanza la fecha on-roll e histórico |
| `ope7_pj_finalizar_entrenamientos` | `sistemas/progresion.php` | Cada petición | Cronómetros de atributos vencidos → reserva + subida de nivel |
| `ope7_pj_finalizar_dominios` | `sistemas/progresion.php` | Cada petición | Cronómetro de 15 días de dominios vencidos → nivel objetivo (F4.3) |
| `ope7_travesias_vencidas` | `sistemas/navegacion.php` | Cada petición | Travesías con plazo off-roll agotado sin cierre → vencidas (17.5) |
| `ope7_conquista_mantenimientos` | `sistemas/conquista.php` | Cada petición | Mantenimiento por ronda de unidades/hordas (16.7) |
| `ope7_conquista_abandonos` | `sistemas/conquista.php` | Cada petición | Asedios sin actividad: 2.ª ronda → propuesta, 3.ª → revuelta (16.5) |
| `ope7_haki_auto_despertar_cron` | `sistemas/akumas.php` | Cada petición | Despertar automático de Armadura/Mantra al nv10 (20.1) |
| `ope7_misiones_ronda_cerrar` | `sistemas/misiones.php` | Cada petición | Misiones con tema cerrado sin cierre → abandonadas (21.2) |
| `ope7_tripulaciones_ronda_cerrar` | `sistemas/tripulaciones.php` | Cada petición | <2 activos: 1.ª detección → aviso con plazo; 2.ª → disolución (22.9) |
| `ope7_implantes_ronda_mantenimiento` | `sistemas/cibernetica.php` | Cada petición | Mantenimiento por ronda de implantes; sin saldo → averiado (23.3) |
| `ope7_bajomundo_cron` | `sistemas/bajomundo.php` | Cada petición | Paraderos de carteles fríos a las 3 rondas sin avistamiento (14.6) |

#### Disparos manuales (staff / motor de trámites)

| Disparador | Función | Archivo | Cuándo |
|---|---|---|---|
| Firma de trámite (bandeja staff) | `ope7_tramite_firmar` → `ope7_tramite_aplicar_efectos` | `tramites/motor.php` | Al publicar/rechazar/archivar un trámite: `switch` de los 67 efectos (apertura/cierre de tema, PP, técnicas, akumas, Haki, misiones, cibernética, tripulaciones, muerte…) |
| Panel «Mundo Vivo» | `ope7_ronda_abrir_siguiente` | `sistemas/mundo.php` | Al renderizar el panel: asegura la ronda actual abierta (crea la siguiente si la anterior está cerrada) |
| Panel «Mundo Vivo» | `ope7_ronda_aplicar_cierre` | `sistemas/mundo.php` | Cierre de ronda por el staff: matriz de islas, precios, recompensas, periódico en borrador |
| Bot «OPE Eternal» | `ope7_bot_post_thread` / `ope7_bot_post_reply` | `core/bot.php` | Posteo automático del bot (News Coo, sucesos, trámites) desde los efectos al publicar |

## A.3 Paneles de staff (la zona staff)

> Todas las vistas comparten el mismo motor de trámites (5.21): bandeja, histórico, firma y auditoría unificados — un panel es una **vista filtrada** de ese motor.

| Panel | Qué muestra / qué permite | Sistema |
|---|---|---|
| **«Calendario»** | Fecha on-roll, temas presentes activos con su ancla y jugadores congelados, histórico de aperturas/cierres, avisos de pasados incoherentes | 5.6 |
| **«Progresión»** | Cronómetros de entrenamiento, subidas de nivel recientes, gastos de PP por concepto, saldos y reservas | 5.6 |
| **«Mercado» / «Tiendas» / «Economía»** | Fluctuación por zona y ronda con motivo, tiendas de jugador (apertura, stock, margen), carteras y transacciones | 5.9 |
| **«NPCs»** | Primarios (capa visible + oculta), editor de bestiario, apariciones por tema (incluido «reclutado»), integración con 5.14 (agenda) y 5.20 (quién narra qué) | 5.11 |
| **«Facciones»** | Catálogo y cupos, rangos, subfacciones élite (solo-staff), propuestas de ascenso (anexo de `skill-cierre-temas`), sueldos y nóminas, cambios/deserciones/infiltraciones | 5.12 |
| **«Bajo Mundo» / «Redes» / «Carteles»** | Veracidad interna de rumores (solo-staff), contraste pagado, espías y capacidad, carteles Wanted con caducidad de paradero | 5.13 |
| **«Mundo Vivo»** | Dashboard de la ronda (KPIs + acciones detectadas, separado del periódico), matriz de islas, recompensas con motivo, periódico «News Coo», tablón de misiones (solo-staff) | 5.14 |
| **«Conquista» / «Guerras» / «Ejércitos»** | Asedios en curso (duración por fuerza defensiva), unidades y hordas con coste/mantenimiento, zonas, registro de ocupación | 5.15 |
| **«Navegación»** | Trámites de travesía (origen/destino/barco/utensilio), IRT con desglose solo-staff, oráculos, vencimientos del plazo off-roll | 5.16 |
| **«Barcos»** | Fichas (casco/maniobra/cañones/espacio/módulos), maderas, reparaciones, mejoras N | 5.17 |
| **«Akumas»** | Catálogo CRUD con la plantilla de 8 bloques, cupos mundiales, renacimientos, adaptación bajo demanda | 5.18 |
| **«Haki»** | Vista en la ficha (niveles, usos, PP invertidos), bandeja de tiradas del Conquistador y subidas, histórico con firma | 5.19 |
| **«Narradores» / «Misiones»** | Rol de foro y cupo de 2 simultáneas; tablón CRUD con la ficha de 6 bloques (secretos visibles solo staff/narradores); bandeja de auto-narradas | 5.20 |
| **«Cibernética»** | Fichas de implantes por personaje (ranuras, estado/daño), mantenimientos pendientes por ronda, histórico con firma | 5.22 |
| **«Familias Legendarias»** | Catálogo de linajes con cupos, portadores activos, expediente de fidelidad, bandeja de concesión/revocación con firma | 5.22 |
| **«Tripulaciones»** | Fichas, miembros con su espacio (5.17), cofre común (5.9), histórico auditable, bandeja de fundación / cambio de capitán / disolución | 5.21-ter |
| **«Reliquias»** | Fichas muertas con su leyenda (visibles para el mundo), histórico de muertes con calidad y herencia | 5.21-bis |
| **«Trámites»** | La bandeja transversal: pendientes, prompt generado, resultado de la IA editable, firma con motivo, histórico auditable | 5.21 |

# Anexo B: catálogo de skills de IA (8 completas, 2026-08-28)

> Las 8 skills están **implementadas** como plantillas de prompt en el catálogo de trámites
> (`inc/ope_rol/tramites/catalogo.php`, `ope7_prompt_especifica`) y siguen el flujo estándar
> 5.21: prompt → la IA propone → resultado editable → firma del staff con motivo → se publica.
> Regla de oro: **la automatización nunca decide sola** — la IA propone, el staff firma
> (3 excepciones 100 % automáticas sin skill: compra de PP · tirada de fruta · Conquistador).

| Skill | Trámites que la usan | Función (implementación real) |
|---|---|---|
| `skill-validacion-personajes` | **3** (validación de ficha) | Valida fichas con **ciclo con el usuario**: raza(s) en catálogo, híbridos con media bien calculada y sin secundarias, tribu (pureza/unicidad/sustitución), **balanza dotes/defectos = 0 exacto**, **balanza de rasgos = 0**, sin parejas antagónicas, requisitos y cadenas, sin redundancias, **techos por nivel** (20 + 1,6×(L−1)), secundarios con las fórmulas de 5.2, físicos coherentes, cupo INT. Devuelve **informe checklist con rojos/verdes** que el jugador ve para corregir (estados `revision_usuario`/`en_revision`/`aceptado_usuario` en el motor). |
| `skill-cierre-temas` | **2** (cierre de tema) · **20** (ascenso de facción, anexo) · **60** (concesión de linaje, ponderación) · **62** (muerte, calidad del desenlace) · **55** (cierre de misión, con narración) | Calcula PP al cerrar temas: **PP = Base(T) × 7 factores** (fidelidad, peso, calidad, extensión ≥350 palabras, presente/pasado F_tiempo, riesgo, perfil). **Base(T) por tramo: 50/75/125/200/300 PP** · techo 2×, suelo 0,5×, redondeo al entero más cercano (mitades a favor del jugador). Produce además: informe de rasgos por participante (jugado/no jugado/contradicho), **fama propuesta** (4 capas de 5.12, bandeja de fama) y peso en la matriz de 5.14. **Anexos:** propuesta de ascenso de facción (cruza expediente de fama + termómetro de la facción + umbral del rango → procede / no procede todavía / espera de cupo) · ponderación del expediente de fidelidad para linajes · banda de calidad del desenlace en muertes (descuidada/digna/leyenda). |
| `skill-creacion-tecnicas` | **13** (creación de técnica) | Genera la ficha completa de una técnica desde nombre+descripción+tier con **ciclo con el usuario**: requisitos escalados (T1:25 → T5:70/55/40), dominio ≥ tier, **efectos dentro del presupuesto del tier** (T1:1 · T2:1 · T3:2 · T4:2 · T5:3 efectos, +25 % coste, máx 1 extra), tipo, **PA 2+tier**, PE %, reposo, puerta de turno, aplicando el **criterio de originalidad** (la justificación narrativa se integra en la ficha — p. ej. un humano que gira para incendiar su pierna lleva Carga + Quemadura). Sin duplicados con la librería del personaje (cupo INT/4 + PP por tier). |
| `skill-mundo-vivo` | Análisis de ronda (cap. 15) · rumores de ronda (5.13) · fluctuación del mercado (5.9) · hordas de conquista (5.15) | Análisis de ronda: procesa los temas presentes y produce **dashboard** (KPIs + «Acciones detectadas», interno y separado del periódico) · **matriz de islas** (cambios con peligrosidad 1–50 y motivo) · **ajustes de recompensas** (criterio libre del staff + `recompensas_historico`) · **periódico «News Coo»** en HTML autocontenido (widget + modal + catálogo, edición en borrador publicable desde el panel) · **matriz de peso** (rangos 0,5×–3× interno) y **veredicto de destructividad** (daño de ruptura por nivel). **Y la fluctuación del mercado (5.9):** computa los tres factores por zona y ronda con su motivo y publica el boletín de precios + histórico (banda 0,5×–2×). **Y los rumores de ronda (5.13):** escribe el «qué se sabe/rumorea en la isla», propone los carteles (Wanted) y pondera la propagación de los rumores usados. **Y las hordas de conquista (5.15):** cuando la guerra lo pide, propone una horda (Mara/Masa/Marea) como factor del escenario del asedio. |
| `skill-navegacion` | **38** (travesía) | Genera la **ficha de travesía** editable: calcula el **IRT interno** (base del mar 1–4 + peligrosidad del destino 1–50 + estado del Mundo Vivo − mitigadores Navegante/Timón/Cartógrafo/barco/utensilio — el desglose **no se publica**), produce narrativa inicial, **tiempo off-roll** (72/48/36 h por tramo, −12 h utensilio, −25 % Maestre, +24 h incidentes, +tiempo transporte), **oráculos** deterministas por banda (catálogo de 7 tipos × gravedad, ruta segura en transporte civil) y **gasto de víveres** (1 ración/persona/día on-roll). La ficha es editable y el staff firma el veredicto al cierre (17.6). Sin dados: el IRT es cálculo interno de la skill. |
| `skill-narracion-automatica` | **52** (solicitud de auto-narrada) · **53** (posteo de tramo) · **54** (apertura de misión en el tablón) · **55** (cierre de misión) | Narrativa por rondas para misiones/aventuras auto-narradas sobre la **ficha de 6 bloques** (identidad · objetivo con condiciones de victoria/fracaso explícitas · escenas en 3 actos con NPCs · recompensas · requisitos · secretos solo-staff). **52** abre el tema presente invadible y crea el tramo 1 firmado; **53** narra cada tramo en prosa rica aplicando el **oráculo del acto** (motor de 5.16) y los NPCs según su ficha, sin resolver por los jugadores, y verifica las condiciones si es el acto final; **54** publica la ficha JSON en el tablón (validación dura del hook: condiciones explícitas, tasa 5.9, un-presente); **55** verifica las condiciones del acto final contra lo roleado (sin dados), aplica recompensas (berries/PP/fama/objetos con motivo) y alimenta un suceso de Mundo Vivo en borrador. Resultado siempre editable y firmado antes de publicarse. |
| `skill-adaptacion-akumas` | **45** (tirada) · **46** (compra) · **49** (fruta bajo demanda, staff) · **48** (despertar) | Ficha completa de fruta **bajo demanda** desde nombre+concepto canon (trámite 49): **ficha de 8 bloques** (identidad con familia/tier/rareza · mecánica base con límites · puertas del catálogo 5.7 + efectos no registrados con calibración y condición · debilidades con enemigo natural · requisitos del portador · influencia en la ficha con balanza a 0 y dote exclusiva inventada · despertar · precio y vías con cupo de fruto único). Usa la **guía maestra** `diseno/5.18_guia_adaptacion_frutas.md` (árbol de decisión del poder canon a la ficha, tabla de tier por tipo y rareza, anti-abuso). **48** (despertar): valida banda de nivel por tier/familia (T1–T2 nv25 · T3–T4 nv32 · Logia/mitológica nv40) + no repetido + antigüedad on-roll y propone el despertar de la fruta con su suceso (la Logia siempre es periódico). |
| `skill-adaptacion-cibernetica` | **56** (instalación) · **59** (diseño a medida, staff) · 57/58 (retirada/mantenimiento, apoyo) | Genera la ficha calibrada del implante desde concepto+zona+nivel (trámites 56/59): **requisitos acumulativos** (suma de todos los implantes), **ranuras del catálogo + efectos de 5.7**, **defectos exigidos con balanza a 0 exacta**, **precios cerrados** (instalación 100.000/500.000/2.500.000 ฿ · PP 200/400/600 · mantenimiento 2.500/10.000/40.000 ฿/ronda, ×2 con «Mantenimiento oneroso»), incompatibilidades (frutas, kairoseki máx 0). Usa la **guía maestra** `diseno/5.22_guia_adaptacion_implantes.md` (árbol de decisión concepto→ranura, tabla de mapeo mejora→efectos, calibración por nivel, anti-abuso, ejemplos resueltos). Aplica defectos con balanza exacta y registra el histórico de instalaciones. |

*El detalle completo del flujo de cada skill se desarrolla con su sistema correspondiente (capítulos 7–23) y en el capítulo 22 (Trámites). Las plantillas de prompt exactas están en `inc/ope_rol/tramites/catalogo.php` (`ope7_prompt_especifica`) y los paneles staff por sistema en la Zona Staff.*

---

*Documento vivo — este manual se actualiza a medida que el proyecto avanza.*
