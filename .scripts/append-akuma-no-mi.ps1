$file = "C:\Users\Fgonz\Documents\Proyectos\I-Forge-Sistema\docs\CATALOGO-AKUMA-NO-MI.md"

Add-Content -Path $file -Value @"

---

# Aro Aro no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.aro_aro`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta de las flechas vectoriales. Permite al usuario generar vectores luminosos en cualquier superficie o proyectarlos al aire, marcando direcciones forzosas de desplazamiento. Cualquier entidad u objeto que atraviese un vector es impulsado inexorablemente en esa dirección. El campo de batalla se convierte en un tablero de pinball donde el usuario decide las trayectorias. Los vectores permanecen activos hasta que el usuario los disipa o re-diseña.

## Efecto general
Generación de vectores direccionales que fuerzan el desplazamiento de enemigos, proyectiles y objetos dentro de su área de influencia.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Los vectores requieren una superficie sólida donde anclarse; no pueden crearse en el vacío o el agua.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Carta de ataque a distancia ≤ 8 + 2×Pot m. Daño físico = (INT × 2) + (Pot × 3). Desplaza al objetivo 3 m en la dirección del vector aplicado.
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Desvía un proyectil anunciado, reduciendo su daño en (INT × 2) + (Pot × 2) y redirigiéndolo hasta 5 m en otra dirección.
- **Pasiva:** + (2+⌊Pot/2⌋) Gaps AGI para resistir empujones y desplazamientos no deseados.

### Nv.1 — Control
- **CAP-03:** Trampa de vectores. Coloca un vector oculto en terreno (alcance 12 + 2×Pot m, radio 3 m). Quien lo active sufre estado Desorientado y es lanzado 5 m en la dirección del vector.
- **CAP-04:** Mantenimiento (1 PA, 10+Pot EN). + (4+Pot) AGI efectiva durante el movimiento mientras el usuario se desplaza por sus propios vectores de aceleración.
- **Pasiva:** El primer ataque tras desplazarse por vector propio gana + (4+Pot) daño bruto.

### Nv.2 — Maestría
- **CAP-05:** AoE radio 6 + Pot m. Daño = (INT × 3) + (Pot × 5). Todos los enemigos dentro reciben estado Ralentizado durante 1 turno.
- **CAP-06:** Reacción (1 PA, 16+2×Pot EN). Refleja un ataque de distancia o proyectil hacia su origen, infligiendo el 50% del daño base al atacante original.
- **Pasiva:** Inmune a estados de derribo y empujón involuntario.

### Nv.3 — Despertar
- **CAP-07:** 1/escena (2 PA, 30+3×Pot EN), 3 turnos, radio 15 + 2×Pot m. El terreno se llena de vectores visibles. Toda carta de movimiento enemiga cuesta +1 PA adicional.
- **CAP-08:** ≤2 usos/combate. Cañón vectorial convergente. Daño = (INT × 4) + (Pot × 8). Desplaza al objetivo 15 m y destruye coberturas ligeras en la trayectoria.
- **Pasiva:** 1/combate: Al recibir daño letal, un vector automático desplaza al usuario 10 m, cancelando el exceso de daño.

## Notas de diseño de cartas
- Tags: distancia, zona, desplazamiento, reaccion
- Prohibiciones: No puede alterar la masa de los objetos impulsados ni crear vectores sobre seres vivos sin impacto directo.

## Notas staff
- Justificación de Tier: Control espacial de combate con escalado ofensivo y defensivo.

---

# Ato Ato no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.ato_ato`
- **Estado:** libre
- **Secundario Potencia:** CAR
- **Origen:** Canon

## Descripción breve
La fruta del arte abstracto. Permite al usuario transformar seres vivos, objetos y ataques entrantes en obras de arte estilizadas (cubistas, surrealistas). Una entidad transformada pierde su funcionalidad física original: un arma abstracta no corta, un brazo estilizado no golpea con fuerza. El campo de batalla se distorsiona en una galería surrealista donde la lógica física deja de aplicarse.

## Efecto general
Deformación abstracta de objetivos y proyectiles, imponiendo penalizaciones de stats e inhabilitando equipo.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El efecto termina si el usuario pierde la concentración o recibe daño directo significativo en un mismo turno.

## Potencia
TEM + CAR → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Carta de ataque a distancia ≤ 10 + 2×Pot m. Daño especial = (CAR × 2) + (Pot × 3). Aplica estado Desarmado 1 turno (el arma del objetivo se transforma en cuadro inofensivo).
- **CAP-02:** Reacción (1 PA, 10+Pot EN). Transforma un ataque físico entrante en escultura abstracta, reduciendo su daño en (CAR × 2) + (Pot × 4).
- **Pasiva:** + (3+Pot) mitigación contra ataques cortantes y punzantes.

### Nv.1 — Control
- **CAP-03:** Carta a distancia ≤ 15 + 2×Pot m. Impone - (3+Pot) a FUE y AGI del objetivo durante 2 turnos (Gap CAR vs VOL del defensor).
- **CAP-04:** Zona activa, radio 5 m. Transforma el terreno en una superficie pictórica. Los enemigos dentro sufren estado Ralentizado mientras permanezcan en el área.
- **Pasiva:** Las cartas del usuario con tag arte cuestan -3 EN.

### Nv.2 — Maestría
- **CAP-05:** AoE cono 10 m. Daño especial = (CAR × 3) + (Pot × 5). Los proyectiles dentro del cono se transforman en pintura inofensiva al instante.
- **CAP-06:** Cc/distancia ≤ 8 m. Silencia los activos de arma del enemigo durante 2 turnos si Gap CAR ≥ +1 a favor del usuario.
- **Pasiva:** Enemigos bajo efecto de la Ato Ato reciben + (4+Pot) daño extra de cualquier fuente.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), radio 15 m, 3 turnos. La zona se convierte en una pintura tridimensional. Ningún enemigo puede usar cartas con tag arma ni fisico de Tier II+.
- **CAP-08:** ≤2 usos/combate. Daño especial = (CAR × 5) + (Pot × 7). Si el objetivo cae por debajo del 15% HP, queda petrificado como estatua de arte el resto del combate.
- **Pasiva:** 1/combate: Ignora un ataque que cause estados alterados, convirtiéndolo en un mural decorativo.

## Notas de diseño de cartas
- Tags: debilitacion, distancia, zona, control
- Prohibiciones: No destruye materia, solo altera su forma funcional temporalmente.

## Notas staff
- Justificación de Tier: Control conceptual que desarma y debilita, pero sin daño sostenido propio.

---

# Awa Awa no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.awa_awa`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta del jabón. El usuario genera y manipula espuma jabonosa capaz de limpiar cualquier mancha, pero cuya propiedad principal es lavar la fuerza y el agarre de las cosas. Quien es alcanzado por la espuma pierde adherencia, vuelve las superficies resbaladizas y ve pulidos sus atributos físicos. La espuma puede moldearse en burbujas, escudos y olas que arrastran a los enemigos como un tsunami de detergente.

## Efecto general
Generación de espuma jabonosa que aplica estados Resbaladizo y Lavado, reduciendo la capacidad de agarre y sostenimiento de armas.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El agua dulce corriente disuelve la espuma y cancela el estado Lavado de inmediato.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Carta de ataque a distancia ≤ 10 + 2×Pot m. Daño especial = (INT × 2) + (Pot × 3). Aplica estado Resbaladizo (Gap INT vs AGI del defensor para mantener equilibrio).
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Crea una burbuja de espuma que absorbe (INT × 2) + (Pot × 4) de daño antes de reventar.
- **Pasiva:** El usuario se desliza sobre su propia espuma ganando + (2+Pot) m de movimiento base.

### Nv.1 — Control
- **CAP-03:** Carta a distancia ≤ 12 m. Daño = (INT × 1) + (Pot × 2). Aplica estado Lavado 2 turnos: el objetivo no puede sostener armas y su mitigación física se reduce en (4+Pot).
- **CAP-04:** Zona de terreno, radio 8 m. Todo enemigo que se mueva dentro debe superar Gap INT o caer Derribado.
- **Pasiva:** Inmune a los estados Resbaladizo y Derribo mientras esté sobre espuma propia.

### Nv.2 — Maestría
- **CAP-05:** AoE cono 12 m. Daño = (INT × 3) + (Pot × 5). Aplica Lavado a todos los afectados que no tengan Buso Nv.2+ activo.
- **CAP-06:** Mantenimiento defensivo. Otorga al usuario y aliados en radio 5 m un escudo de (INT × 2) + (Pot × 5) HP temporales.
- **Pasiva:** Quien golpee al usuario en Cc recibe estado Resbaladizo automático (1 vez por ronda).

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), radio 20 m, 3 turnos. Toda la superficie se cubre de jabón pulido. Los enemigos sufren -50% velocidad de movimiento y - (5+2×Pot) a FUE.
- **CAP-08:** ≤2 usos/combate. Daño especial = (INT × 4) + (Pot × 8). Si el objetivo está bajo Lavado, el daño ignora la mitigación normal.
- **Pasiva:** 1/combate: El usuario se lava a sí mismo, eliminando todos los debuffs y estados alterados negativos activos (0 PA, 10 EN).

## Notas de diseño de cartas
- Tags: zona, debilitacion, escudo, distancia
- Prohibiciones: No genera daño cortante ni perforante.

## Notas staff
- Justificación de Tier: Control de movimiento y desarme consistentes, pero daño directo limitado.

---

# Baku Baku no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.baku_baku`
- **Estado:** libre
- **Secundario Potencia:** RES
- **Origen:** Canon

## Descripción breve
La fruta de la masticación y fusión. Convierte las mandíbulas y el estómago del usuario en un horno de fusión capaz de devorar cualquier sustancia inanimada o ser vivo sin sufrir daño. Tras ingerir elementos, el usuario puede fusionarlos con su cuerpo, transformando sus brazos en cañones o creando materiales nuevos combinando objetos devorados en su estómago-fábrica. El límite es su capacidad de almacenamiento interno.

## Efecto general
Devoración de objetos y ataques, auto-modificación corporal mediante integración de materiales consumidos y síntesis de nuevos compuestos.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Si no ha ingerido materiales adecuados antes o durante el combate, su versatilidad se reduce drásticamente.

## Potencia
TEM + RES → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc. Mordisco devorador. Daño físico = (RES × 3) + (Pot × 3). Puede devorar proyectiles o armas Cc no imbuidas en Buso del tamaño de un brazo.
- **CAP-02:** Al devorar metal o material sólido, gana + (3+Pot) mitigación física durante 2 turnos.
- **Pasiva:** Inmune a venenos y toxinas ingeridas por vía oral. Digestión perfecta.

### Nv.1 — Control
- **CAP-03:** Carta de ataque a distancia ≤ 15 m. Transforma un brazo en cañón usando material devorado. Daño = (RES × 3) + (Pot × 5).
- **CAP-04:** Reacción (1 PA, 12+Pot EN). Devora un ataque de Cc o proyectil (hasta daño máx. RES × 4), cancelándolo y recuperando 10 EN.
- **Pasiva:** + (2+Pot) RES mientras tenga materiales ingeridos en su organismo.

### Nv.2 — Maestría
- **CAP-05:** Fusiona dos objetos devorados para crear un arma compuesta. Otorga carta tag fusion con +2 efectos adicionales (ej. fuego + perforación) y daño (RES × 4) + (Pot × 6).
- **CAP-06:** Auto-modificación. Se expande usando materiales ingeridos. Gana + (6+2×Pot) RES y -2 AGI durante 3 turnos.
- **Pasiva:** La mitigación por materiales devorados escala a (6+2×Pot).

### Nv.3 — Despertar
- **CAP-07:** 1/escena (2 PA, 30+3×Pot EN). Devora radio 10 m de terreno circundante. Metal y piedra del área se asimilan a su cuerpo 3 turnos, concediendo +50% HP temporales.
- **CAP-08:** ≤2 usos/combate. Daño masivo = (RES × 5) + (Pot × 8). Ataque combinado de todas las armas integradas en el organismo.
- **Pasiva:** 1/combate: Devora a un aliado voluntario para alojarlo en su estómago a salvo de todo daño durante 2 turnos.

## Notas de diseño de cartas
- Tags: fisico, distancia, fusion, modificacion
- Prohibiciones: No puede devorar Kairoseki sin sufrir su debilidad. No puede devorar seres vivos contra su voluntad.

## Notas staff
- Justificación de Tier: Versatilidad táctica alta con auto-modificación y tanqueo condicional.

---

# Bane Bane no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.bane_bane`
- **Estado:** libre
- **Secundario Potencia:** AGI
- **Origen:** Canon

## Descripción breve
La fruta del resorte. Permite al usuario transformar cualquier parte de su cuerpo en muelles de acero de alta tensión. Brazos y piernas se convierten en resortes comprimibles que almacenan energía cinética y la liberan en impactos devastadores. La movilidad se multiplica: el usuario rebota entre paredes, suelos y techos como una bala de goma, acumulando velocidad y fuerza con cada rebote.

## Efecto general
Movilidad por rebote, acumulación de energía cinética mediante compresión de resortes y ataques de impacto potenciados por velocidad acumulada.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Terrenos blandos (arena, barro, nieve) absorben el rebote y reducen la aceleración a la mitad.

## Potencia
TEM + AGI → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc/embestida en línea recta ≤ 10 + 2×Pot m. Daño físico = (AGI × 3) + (Pot × 3). El usuario se desplaza hasta el objetivo en un salto-resorte.
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Amortigua un impacto contundente usando piernas-resorte, reduciendo daño en (AGI × 2) + (Pot × 3).
- **Pasiva:** + (2+Pot) m de alcance en saltos y desplazamientos verticales.

### Nv.1 — Control
- **CAP-03:** Mantenimiento (1 PA, 10+Pot EN), 2 turnos. Rebota continuamente entre superficies, ganando + (3+Pot) AGI y acumulando 1 carga de inercia por rebote (máx. 3).
- **CAP-04:** Cc. Consume hasta 3 cargas de inercia. Daño = (AGI × 3) + (Pot × 6). Empuja al objetivo 5 m por carga consumida.
- **Pasiva:** Tras 2+ rebotes en un turno, el próximo ataque de impacto gana + (4+Pot) daño bruto.

### Nv.2 — Maestría
- **CAP-05:** Cc/distancia ≤ 12 m. Daño penetrante = (AGI × 4) + (Pot × 6). Ignora 15% de mitigación física del objetivo.
- **CAP-06:** Zona radio 6 m. Transforma el suelo en muelles. Aliados ganan +4 m de movimiento; enemigos sufren Desorientado.
- **Pasiva:** Inmune a daño por caída de cualquier altura.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 15 m, 3 turnos. Todo el escenario se vuelve elástico. El usuario puede realizar hasta 3 ataques de impacto en el mismo turno gastando solo 1 PA adicional por cada uno.
- **CAP-08:** ≤2 usos/combate. Compresión extrema de todo el cuerpo. Daño = (AGI × 5) + (Pot × 8). Derriba e inmoviliza al objetivo 1 turno.
- **Pasiva:** 1/combate: Devuelve 50% del daño contundente recibido en una ronda como contraataque de área inmediato.

## Notas de diseño de cartas
- Tags: fisico, desplazamiento, impacto, inercia
- Prohibiciones: No genera cortes ni filos. Máximo 3 cargas de inercia simultáneas.

## Notas staff
- Justificación de Tier: Movilidad agresiva con escalado por acumulación, pero dependiente de superficies sólidas.

---

# Bara Bara no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.bara_bara`
- **Estado:** libre
- **Secundario Potencia:** AGI
- **Origen:** Canon

## Descripción breve
La fruta de la separación. Concede al usuario la capacidad de dividir su cuerpo en segmentos flotantes que puede controlar de forma independiente a distancia. Puños, piernas, torso y cabeza pueden separarse y flotar en el aire, permitiendo ataques desde ángulos imposibles y esquivando golpes al dispersar las partes del cuerpo. El control de cada fragmento es tan preciso como si aún estuviera conectado.

## Efecto general
Separación y control independiente de partes del cuerpo, permitiendo ataques desde múltiples direcciones y evasión por dispersión.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Los pies deben permanecer en el suelo para mantener el control; sin apoyo firme las partes separadas caen.

## Potencia
TEM + AGI → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc a distancia. Separa puño o pie y lo lanza hasta 8 + 2×Pot m. Daño físico = (AGI × 2) + (Pot × 3). El miembro regresa tras impactar.
- **CAP-02:** Reacción (0 PA, 6+Pot EN). Separa el torso o cabeza del alcance de un ataque, esquivando automáticamente un golpe Cc.
- **Pasiva:** + (2+Pot) Gaps AGI para esquivar ataques dirigidos a zonas concretas del cuerpo.

### Nv.1 — Control
- **CAP-03:** Mantenimiento (1 PA, 10+Pot EN). Separa ambos brazos para atacar desde dos direcciones. Daño combinado = (AGI × 3) + (Pot × 4). El objetivo no puede bloquear ambos ángulos.
- **CAP-04:** Utilidad. Separa las piernas para que el torso flote a 3 m del suelo. + (4+Pot) AGI para esquivar ataques rasos durante 2 turnos.
- **Pasiva:** Mientras esté separado, el daño recibido se distribuye entre los segmentos activos (25% HP del total por segmento).

### Nv.2 — Maestría
- **CAP-05:** Separa múltiples partes y las lanza en ráfaga. Daño = (AGI × 4) + (Pot × 5). Hasta 5 impactos en cono de 8 m.
- **CAP-06:** Reacción (1 PA, 12+Pot EN). Separa el cuerpo en todos sus segmentos para evitar un AoE, reensamblándose 5 m fuera del área.
- **Pasiva:** Inmune a ataques de agarre y presas (el cuerpo se separa para liberarse automáticamente).

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), 3 turnos. Separa el cuerpo en 20+ fragmentos diminutos. 90% de probabilidad de esquivar cualquier golpe Cc.
- **CAP-08:** ≤2 usos/combate. Ensambla todos los fragmentos en un impacto masivo. Daño = (AGI × 5) + (Pot × 8). El usuario se reensambla en la posición del impacto.
- **Pasiva:** 1/combate: Al recibir daño letal, el cuerpo se separa automáticamente y el torso flota 10 m hacia atrás, evitando la muerte.

## Notas de diseño de cartas
- Tags: fisico, distancia, evasion, separacion
- Prohibiciones: No puede separar partes mientras esté sumergido en agua de mar.

## Notas staff
- Justificación de Tier: Evasión y versatilidad ofensiva alta, pero daño individual de cada segmento limitado.

---

# Bari Bari no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.bari_bari`
- **Estado:** libre
- **Secundario Potencia:** RES
- **Origen:** Canon

## Descripción breve
La fruta de la barrera. Concede la capacidad de generar barreras translúcidas e indestructibles cruzando los dedos. Ningún ataque físico, explosivo o elemental convencional puede atravesarlas. El usuario puede moldear las barreras como escudos, paredes, escaleras flotantes o proyectiles de aplastamiento. La única limitación real es la superficie total de barrera activa simultánea y la necesidad de cruzar los dedos para crearlas.

## Efecto general
Defensa absoluta mediante barreras indestructibles, creación de estructuras ofensivas y de control de campo táctico.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El usuario debe cruzar los dedos para crear nuevas barreras. Ataques sónicos o psíquicos pueden evitar la barrera.

## Potencia
TEM + RES → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Reacción (1 PA, 10+Pot EN). Bloquea un ataque directo. Absorbe hasta (RES × 5) + (Pot × 10) de daño antes de quebrarse.
- **CAP-02:** Cc/embestida frontal ≤ 8 m. Daño contundente = (RES × 3) + (Pot × 4). Empuja al objetivo 4 m.
- **Pasiva:** + (4+Pot) mitigación base a todo daño frontal mientras haya una barrera activa.

### Nv.1 — Control
- **CAP-03:** Crea muralla de barrera (ancho 6 m, alto 4 m). Bloquea el paso de enemigos y proyectiles durante 2 turnos.
- **CAP-04:** Utilidad. Plataforma flotante de barrera (alcance 15 m) que soporta hasta 200 kg por punto de Potencia.
- **Pasiva:** Las barreras reflejan 15% del daño recibido contra atacantes en Cc.

### Nv.2 — Maestría
- **CAP-05:** Ataque a distancia ≤ 15 m. Daño de aplastamiento = (RES × 4) + (Pot × 6). Aplica Enraizado si el objetivo es empujado contra superficie sólida.
- **CAP-06:** Reacción/mantenimiento (2 PA, 20+2×Pot EN). Domo de barrera indestructible sobre usuario o aliado durante 1 ronda completa.
- **Pasiva:** Inmune a ataques que atraviesan defensas ordinarias (excepto Haki avanzado directo al cuerpo).

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 40+3×Pot EN), 3 turnos, radio 15 m. El área se rodea de barreras geométricas. Enemigos no pueden salir ni atacar hacia fuera.
- **CAP-08:** ≤2 usos/combate. Puño recubierto en microbarrera. Daño = (RES × 5) + (Pot × 9). Ignora 30% de mitigación del objetivo.
- **Pasiva:** 1/combate: Anula un ataque de escala destructiva masiva (AoE Tier IV+) sin que usuario ni aliados tras la barrera reciban daño.

## Notas de diseño de cartas
- Tags: defensa, escudo, zona, impacto
- Prohibiciones: No puede crear más de una estructura de barrera principal a la vez antes de Nv.3.

## Notas staff
- Justificación de Tier: Defensa casi absoluta con utilidad de control de campo y daño contundente.

---

# Bata Bata no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.bata_bata`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta de la mantequilla. Permite al usuario generar, moldear y proyectar mantequilla en cualquier estado de consistencia, desde líquida cremosa hasta sólida como un ladrillo amarillo. La mantequilla cubre superficies volviéndolas resbaladizas, amortigua impactos al recubrir al usuario y puede endurecerse en barreras o proyectiles contundentes. La versatilidad radica en el control preciso de la viscosidad del material.

## Efecto general
Generación de mantequilla para control de terreno, defensa por absorción de impacto y ataques contundentes.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El calor extremo (fuego, magma) derrite la mantequilla instantáneamente, reduciendo su efectividad defensiva a cero.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Carta ataque a distancia ≤ 10 m. Daño especial = (INT × 2) + (Pot × 3). Aplica estado Resbaladizo 1 turno.
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Recubre zona del cuerpo con mantequilla espesa, absorbiendo (INT × 2) + (Pot × 2) daño físico.
- **Pasiva:** Inmune a agarres físicos (las manos enemigas resbalan sobre la capa de mantequilla).

### Nv.1 — Control
- **CAP-03:** Zona radio 6 m. Cubre el suelo de mantequilla. Enemigos dentro sufren -3 m movimiento y deben superar Gap INT o caer Derribados.
- **CAP-04:** Muro de mantequilla endurecida (ancho 4 m, alto 3 m). Absorbe (INT × 3) + (Pot × 4) daño.
- **Pasiva:** + (2+Pot) m velocidad al deslizarse sobre mantequilla propia.

### Nv.2 — Maestría
- **CAP-05:** AoE radio 8 m. Mantequilla hirviente. Daño = (INT × 3) + (Pot × 5). Causa Quemado 1 y Resbaladizo.
- **CAP-06:** Mantenimiento (1 PA, 12+Pot EN). Capa continua de mantequilla espesa. + (4+Pot) mitigación física.
- **Pasiva:** Los ataques de impacto contra el usuario sufren -15% daño por absorción mantecosa.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 15 m, 3 turnos. Diluvio de mantequilla anega el terreno. Enemigos pierden capacidad de correr y sufren - (4+Pot) AGI.
- **CAP-08:** ≤2 usos/combate. Bloque de mantequilla hipercomprimida. Daño = (INT × 4) + (Pot × 8). Enraiza al objetivo 1 turno.
- **Pasiva:** 1/combate: El usuario licúa su cuerpo en mantequilla para esquivar automáticamente un ataque crítico.

## Notas de diseño de cartas
- Tags: zona, debilitacion, escudo, resbaladizo
- Prohibiciones: No genera temperaturas frías ni puede congelar la mantequilla.

## Notas staff
- Justificación de Tier: Control de masas y mitigación sólidos, pero contrarrestable por fuentes de calor.

---

# Beri Beri no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.beri_beri`
- **Estado:** libre
- **Secundario Potencia:** AGI
- **Origen:** Canon

## Descripción breve
La fruta del cuerpo esférico. Convierte el cuerpo del usuario en una serie de esferas metálicas conectadas entre sí, como un collar de bolas de acero. Cada esfera puede separarse, rodar independientemente y reconfigurar la forma del usuario. El cuerpo esférico permite rodar a alta velocidad, absorber impactos distribuyendo la fuerza entre las esferas y atrapar enemigos envolviéndolos con la cadena de bolas.

## Efecto general
Transformación del cuerpo en esferas metálicas conectadas, con movilidad por rodadura, defensa por distribución de impacto y aprisionamiento.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Las esferas son metálicas y conductoras; ataques eléctricos (Goro Goro) causan daño adicional y paralizan la reconfiguración.

## Potencia
TEM + AGI → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc/embestida rodante ≤ 10 + 2×Pot m. Daño físico = (AGI × 2) + (Pot × 3). Derriba al objetivo si no supera Gap AGI.
- **CAP-02:** Reacción (0 PA, 6+Pot EN). Distribuye el daño de un impacto entre las esferas, reduciéndolo en (AGI × 1) + (Pot × 2).
- **Pasiva:** + (3+Pot) m de movimiento en línea recta al rodar.

### Nv.1 — Control
- **CAP-03:** Distancia ≤ 12 m. Separa una esfera como proyectil. Daño = (AGI × 3) + (Pot × 4). La esfera regresa tras impactar.
- **CAP-04:** Separa el cuerpo en cadena de esferas para envolver a un objetivo. Aplica Enraizado 2 turnos si no libera con Gap FUE.
- **Pasiva:** Esferas separadas pueden rodar hasta 5 m del cuerpo principal sin perder control.

### Nv.2 — Maestría
- **CAP-05:** AoE radio 5 m. Gira y lanza esferas en todas direcciones. Daño = (AGI × 4) + (Pot × 5). Todos los enemigos reciben Derribado.
- **CAP-06:** Mantenimiento (1 PA, 12+Pot EN). Cuerpo en rueda de esferas gigante (6 m). + (4+Pot) AGI movimiento y daño continuo a quien toque.
- **Pasiva:** Reduce en 1 el daño por golpe individual en forma esférica.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 12 m, 3 turnos. Esferas rodantes persiguen enemigos. Cada turno causan (AGI × 2) + (Pot × 3) daño y aplican Derribado.
- **CAP-08:** ≤2 usos/combate. Ensambla todas las esferas en megaesfera. Daño = (AGI × 5) + (Pot × 8). Impacto en área de 10 m.
- **Pasiva:** 1/combate: Al recibir golpe que atraviese defensa, el cuerpo se desarma en esferas sueltas que ruedan 6 m atrás, cancelando exceso de daño.

## Notas de diseño de cartas
- Tags: fisico, desplazamiento, zona, aprisionamiento
- Prohibiciones: No genera ataques cortantes ni perforantes.

## Notas staff
- Justificación de Tier: Movilidad y control por aprisionamiento sólidos, pero predecible en línea recta.

---

# Beta Beta no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.beta_beta`
- **Estado:** libre
- **Secundario Potencia:** AGI
- **Origen:** Canon

## Descripción breve
La fruta de la sustancia pegajosa. Permite al usuario generar y controlar un moco denso y altamente adhesivo desde cualquier parte de su cuerpo. El pegamento puede lanzarse a distancia para inmovilizar enemigos, cubrir superficies enteras para atrapar pisadas, o moldearse en formas semisólidas como látigos y redes. Cuanto más pegamento se acumula sobre un objetivo, más difícil le resulta moverse.

## Efecto general
Generación de adhesivo que inmoviliza progresivamente, ralentiza y atrapa a los enemigos, con capacidad de control de zona.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El disolvente alcalino (jabón, Awa Awa) deshace el pegamento instantáneamente.

## Potencia
TEM + AGI → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 10 + 2×Pot m. Daño especial = (AGI × 2) + (Pot × 3). Aplica 1 carga de Pegajoso (-1 m movimiento por carga).
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Escudo de pegamento que atrapa ataque Cc, reduciendo daño en (AGI × 2) + (Pot × 2).
- **Pasiva:** + (2+Pot) AGI para liberarse de agarres y trampas propias.

### Nv.1 — Control
- **CAP-03:** Distancia ≤ 12 m. Látigo de pegamento. Aplica 3 cargas de Pegajoso. Al llegar a 5 cargas, el objetivo queda Enraizado.
- **CAP-04:** Zona radio 6 m. Suelo adhesivo. Enemigos que entren ganan 2 cargas de Pegajoso.
- **Pasiva:** El usuario se mueve con normalidad sobre su propio pegamento.

### Nv.2 — Maestría
- **CAP-05:** AoE cono 8 m. Ráfaga de pegamento. Aplica 3 cargas de Pegajoso a todos. Daño = (AGI × 3) + (Pot × 5).
- **CAP-06:** Golem de moco semisólido con (AGI × 3) + (Pot × 4) HP. El golem ataca aplicando cargas de Pegajoso.
- **Pasiva:** Enemigos con 5+ cargas de Pegajoso también sufren - (2+Pot) a FUE y AGI.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 15 m, 3 turnos. Terreno entero pegajoso. Enemigos ganan 2 cargas de Pegajoso al inicio de cada turno.
- **CAP-08:** ≤2 usos/combate. Prisión de pegamento. Daño = (AGI × 4) + (Pot × 8). Inmoviliza totalmente 1 turno.
- **Pasiva:** 1/combate: Al recibir daño, expulsa pegamento en radio 5 m aplicando 2 cargas de Pegajoso a todos los enemigos.

## Notas de diseño de cartas
- Tags: zona, debilitacion, control, aprisionamiento
- Prohibiciones: El pegamento no causa daño directo; solo aplica cargas de ralentización.

## Notas staff
- Justificación de Tier: Control de masas progresivo con escalado de inmovilización, pero daño directo bajo.

---

# Bisu Bisu no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.bisu_bisu`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta de las galletas. Permite al usuario generar galletas hiperresistentes al aplaudir, tan duras como el acero laminado. Las galletas pueden moldearse en armaduras, escudos y guerreros autónomos que el usuario pilota desde el interior. La producción es prácticamente ilimitada mientras tenga energía, y la dureza del material permite crear construcciones defensivas y ofensivas a escala de batalla.

## Efecto general
Generación de galletas de dureza metálica, creación de golems autónomos y construcción de defensas modulares.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El agua y los líquidos ablandan las galletas, reduciendo su dureza y mitigación a la mitad en contacto directo.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 10 m. Proyectil de galleta endurecida. Daño físico = (INT × 2) + (Pot × 3).
- **CAP-02:** Reacción (1 PA, 10+Pot EN). Escudo de galleta que absorbe (INT × 2) + (Pot × 4) daño.
- **Pasiva:** + (3+Pot) mitigación contundente mientras tenga galletas activas en campo.

### Nv.1 — Control
- **CAP-03:** Mantenimiento (1 PA, 12+Pot EN). Guerrero de Galleta. + (4+Pot) INT y + (4+Pot) RES. El golem recibe daño en lugar del usuario.
- **CAP-04:** El Guerrero genera 4 brazos con espadas. Cc daño = (INT × 3) + (Pot × 5).
- **Pasiva:** Estructuras de galleta tienen + (4+Pot) dureza contra cortes y perforación.

### Nv.2 — Maestría
- **CAP-05:** Convoca hasta 3 Guerreros independientes (radio 12 m). Daño combinado = (INT × 4) + (Pot × 6).
- **CAP-06:** Perforante ≤ 15 m. Daño = (INT × 4) + (Pot × 6). Ignora 20% mitigación.
- **Pasiva:** Mientras esté dentro de un Guerrero, ataques enemigos golpean primero al golem.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), radio 20 m, 3 turnos. Docenas de golems. Cartas tag galleta cuestan -1 PA.
- **CAP-08:** ≤2 usos/combate. Montaña de galletas aplasta al enemigo. Daño = (INT × 5) + (Pot × 8).
- **Pasiva:** 1/combate: Golem destruido explota en fragmentos causando (INT × 3) daño a enemigos adyacentes (radio 4 m).

## Notas de diseño de cartas
- Tags: golem, escudo, fisico, ejercito
- Prohibiciones: No puede crear galletas comestibles con propiedades curativas.

## Notas staff
- Justificación de Tier: Capacidad de generar ejército propio con alta resistencia y daño sostenido.

---

# Bomu Bomu no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.bomu_bomu`
- **Estado:** libre
- **Secundario Potencia:** RES
- **Origen:** Canon

## Descripción breve
La fruta de la explosión. Convierte todo el cuerpo del usuario en un explosivo viviente. Cualquier parte de su cuerpo (moco, aliento, sudor, pelo, extremidades) puede detonar a voluntad sin causarle daño a él mismo. El usuario es inmune a explosiones externas y al fuego convencional. Desde estornudos explosivos hasta autodetonaciones masivas, su poder es pura pirotecnia controlada.

## Efecto general
Detonación de cualquier parte del cuerpo para causar daño de área, inmunidad a explosiones y ataques de fuego convencionales.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Las explosiones requieren oxígeno; sin aire o bajo el agua, la potencia y el alcance se reducen a la mitad.

## Potencia
TEM + RES → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 10 m. Proyectil de moco explosivo. Daño fuego/explosión = (RES × 2) + (Pot × 3).
- **CAP-02:** Cc. Puño o patada detonante al impactar. Daño = (RES × 3) + (Pot × 3). Empuje adicional 3 m.
- **Pasiva:** Inmunidad absoluta a daño por explosiones y fuego no mágico.

### Nv.1 — Control
- **CAP-03:** AoE cono 6 m. Aliento explosivo. Daño = (RES × 3) + (Pot × 4). Causa Quemado 1.
- **CAP-04:** Movilidad/ataque. Detona pies para impulsarse (8 m). Daño = (RES × 3) + (Pot × 5).
- **Pasiva:** + (3+Pot) mitigación contundente (microdetonaciones internas amortiguan golpes).

### Nv.2 — Maestría
- **CAP-05:** Cc/zona. Detona el suelo al pisar (radio 5 m). Daño = (RES × 4) + (Pot × 5). Derriba a todos en el área.
- **CAP-06:** Reacción (1 PA, 12+Pot EN). Al ser golpeado en Cc, detona sudor en el impacto infligiendo (RES × 3) + (Pot × 4) al atacante.
- **Pasiva:** Ataques de explosión ignoran 15% de mitigación por armadura.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN). Autodetonación masiva (radio 20 m). Daño = (RES × 5) + (Pot × 8). Usuario no sufre daño pero queda con -20 EN.
- **CAP-08:** ≤2 usos/combate. Esquirlas explosivas en abanico. Daño = (RES × 4) + (Pot × 8).
- **Pasiva:** 1/combate: Absorbe energía de explosión enemiga externa para recuperar 20 EN.

## Notas de diseño de cartas
- Tags: explosion, fuego, zona, reaccion
- Prohibiciones: No puede detonar objetos inanimados lejanos sin contacto previo.

## Notas staff
- Justificación de Tier: Daño de área constante con inmunidad a su propio elemento y versatilidad defensiva.

---

# Buki Buki no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.buki_buki`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta del arsenal corporal. Permite al usuario transformar cualquier parte de su cuerpo en armas de filo o de fuego. Los brazos se convierten en espadas, las piernas en lanzas, los dedos en pistolas o cañones. El usuario conoce instintivamente el manejo de cada arma en la que transforma su cuerpo y puede cambiar entre tipos en pleno combate. No necesita munición: su cuerpo genera las balas y cuchillas.

## Efecto general
Transformación de extremidades en armas Cc y a distancia, sin límite de munición, con cambio instantáneo entre tipos.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Las armas generadas son tan resistentes como el cuerpo del usuario; si está débil, las armas se vuelven frágiles.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc. Brazo transformado en espada o garra. Daño cortante = (INT × 2) + (Pot × 3).
- **CAP-02:** Distancia ≤ 10 m. Dedos transformados en pistolas. Daño perforante = (INT × 2) + (Pot × 3).
- **Pasiva:** + (2+Pot) Gaps de ataque al cambiar de tipo de arma entre turnos.

### Nv.1 — Control
- **CAP-03:** Cc/distancia. Brazo entero en cañón. Daño explosivo = (INT × 3) + (Pot × 4). AoE radio 3 m en impacto.
- **CAP-04:** Utilidad. Piernas en lanzas extensibles. Alcance Cc a 4 m y + (4+Pot) AGI ofensiva.
- **Pasiva:** Cambio entre tipos de arma una vez por turno sin coste de PA.

### Nv.2 — Maestría
- **CAP-05:** Cc. Cuchillas múltiples en brazos y piernas. Daño = (INT × 4) + (Pot × 5). Hasta 3 impactos al mismo objetivo.
- **CAP-06:** Distancia ≤ 15 m. Cañón pesado de hombro. Daño = (INT × 4) + (Pot × 6). Ignora coberturas ligeras.
- **Pasiva:** Armas generadas son equivalentes a armas de calidad Buena.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), 3 turnos. Arsenal andante: cada extremidad es un arma diferente. Hasta 3 ataques de distinto tipo en el mismo turno.
- **CAP-08:** ≤2 usos/combate. Cañón corporal total. Daño = (INT × 5) + (Pot × 8). Línea recta 20 m (atraviesa enemigos).
- **Pasiva:** 1/combate: Al quedarse sin EN, transforma una extremidad en arma básica sin coste de EN por 1 turno.

## Notas de diseño de cartas
- Tags: fisico, distancia, cortante, perforante
- Prohibiciones: No genera armas de cualidad legendaria; calidad máxima Buena.

## Notas staff
- Justificación de Tier: Versatilidad ofensiva total sin dependencia de equipo externo.

---

# Buku Buku no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.buku_buku`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta de los libros. Permite al usuario generar libros en blanco desde su cuerpo y manipularlos a voluntad. Los libros pueden contener información, almacenar objetos pequeños entre sus páginas o desplegarse en estructuras de papel encolado. El usuario puede leer cualquier libro existente instantáneamente y copiar su contenido. Un libro cerrado puede aprisionar a un enemigo atrapándolo entre sus páginas.

## Efecto general
Generación de libros con propiedades de almacenamiento, aprisionamiento y consulta instantánea de información.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Los libros son de papel y extremadamente vulnerables al fuego. Un ataque de fuego destruye todos los libros activos.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 8 m. Libro abierto golpea al objetivo. Daño especial = (INT × 2) + (Pot × 3). Aplica 1 carga de Atrapado.
- **CAP-02:** Reacción (0 PA, 6+Pot EN). Libro desplegado como escudo de papel absorbe (INT × 2) + (Pot × 2) daño.
- **Pasiva:** + (3+Pot) INT para pruebas de conocimiento e investigación fuera de combate.

### Nv.1 — Control
- **CAP-03:** Utilidad. Almacena hasta (Pot × 2) objetos pequeños en un libro. Recuperables como acción gratuita.
- **CAP-04:** Cc. Atrapa objetivo en libro cerrado. Aplica Enraizado. Escapa con Gap FUE vs INT al inicio de su turno.
- **Pasiva:** Libros activos + (2+Pot) a INT para identificar frutas y habilidades.

### Nv.2 — Maestría
- **CAP-05:** AoE radio 6 m. Torre de libros atrapa varios enemigos. Daño = (INT × 3) + (Pot × 5). Todos Enraizados 1 turno.
- **CAP-06:** Utilidad. Copia contenido de documentos instantáneamente. Registra habilidades enemigas vistas durante el combate.
- **Pasiva:** + (2+Pot) Gaps INT para resistir ilusiones y ataques mentales.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 15 m, 3 turnos. Biblioteca de batalla. Cada turno, un libro aplica Desorientado a un enemigo aleatorio.
- **CAP-08:** ≤2 usos/combate. Prisión de hojas. Daño = (INT × 4) + (Pot × 7). Inmoviliza al objetivo hasta que gaste 2 turnos liberándose.
- **Pasiva:** 1/combate: Almacena un ataque entrante en libro en blanco. Luego puede liberarlo como carta propia.

## Notas de diseño de cartas
- Tags: zona, control, utilidad, conocimiento
- Prohibiciones: No puede almacenar seres vivos que no estén incapacitados.

## Notas staff
- Justificación de Tier: Utilidad versátil fuera de combate y control moderado en combate, pero frágil al fuego.

---

# Chiyu Chiyu no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.chiyu_chiyu`
- **Estado:** libre
- **Secundario Potencia:** VOL
- **Origen:** Canon

## Descripción breve
La fruta de la curación. Concede al usuario la capacidad de generar lágrimas curativas con propiedades regenerativas extraordinarias. Las lágrimas pueden sanar heridas externas e internas, acelerar la cicatrización y contrarrestar venenos no letales. El usuario puede canalizar sus lágrimas a través de las manos para concentrar la curación en puntos específicos o dispersarlas en área para efectos de grupo.

## Efecto general
Curación de heridas mediante lágrimas regenerativas, eliminación de estados alterados físicos y recuperación de HP a aliados.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Las lágrimas curativas son finitas; si el usuario se deshidrata, la producción cesa.

## Potencia
TEM + VOL → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 5 m. Lágrima curativa restaura (VOL × 3) + (Pot × 4) HP a un objetivo.
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Lágrimas en propia herida al recibir daño, recuperando (VOL × 2) + (Pot × 3) HP inmediato.
- **Pasiva:** + (3+Pot) a recuperación natural de HP por turno fuera de combate.

### Nv.1 — Control
- **CAP-03:** Distancia ≤ 10 m. Lágrima concentrada elimina un estado alterado físico (Quemado, Sangrado, Veneno no mágico) y restaura (VOL × 2) + (Pot × 2) HP.
- **CAP-04:** Grupo. Dispersión en radio 6 m restaura (VOL × 2) + (Pot × 3) HP a todos los aliados.
- **Pasiva:** + (2+Pot) VOL para resistir efectos que reduzcan curación recibida.

### Nv.2 — Maestría
- **CAP-05:** Curación canalizada (1 PA, 15+Pot EN por turno). Restaura (VOL × 4) + (Pot × 5) HP por turno a un objetivo durante 3 turnos.
- **CAP-06:** Reacción (1 PA, 12+Pot EN). Niega daño de ataque que aplicaría estado alterado físico, curando antes de que se aplique.
- **Pasiva:** Curaciones +20% efectivas sobre aliados por debajo del 30% HP.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), radio 15 m. Lluvia de lágrimas restaura (VOL × 5) + (Pot × 6) HP a todos los aliados y elimina todos los estados alterados físicos.
- **CAP-08:** ≤1 uso/combate. Lágrima de resurrección. Si un aliado cayó a 0 HP en la misma ronda, lo restaura al 25% HP máximo.
- **Pasiva:** 1/día: Regenera una extremidad perdida u órgano dañado irreversiblemente fuera de combate.

## Notas de diseño de cartas
- Tags: curacion, soporte, zona, reaccion
- Prohibiciones: No cura enfermedades congénitas ni estados alterados mágicos de nivel Despertar sin gasto adicional.

## Notas staff
- Justificación de Tier: Curación masiva con capacidad de resurrección en combate, pero sin daño ofensivo propio.

---

# Choki Choki no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.choki_choki`
- **Estado:** libre
- **Secundario Potencia:** AGI
- **Origen:** Canon

## Descripción breve
La fruta de las tijeras. Convierte las manos del usuario en tijeras gigantes capaces de cortar prácticamente cualquier material con precisión quirúrgica. El usuario puede cortar papel, tela, madera, piedra y metales con la misma facilidad. Además, puede recortar formas de cualquier material y darles movilidad temporal, creando siluetas recortadas que actúan como extensiones de su voluntad.

## Efecto general
Corte preciso de cualquier material mediante tijeras corporales y creación de siluetas recortadas animadas.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: No puede cortar materiales imbuidos en Busoshoku Nv.2+ ni sustancias sin densidad (fuego, luz, gas).

## Potencia
TEM + AGI → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc. Corte con tijeras de mano. Daño cortante = (AGI × 2) + (Pot × 3). Corta objetos inanimados hasta dureza piedra.
- **CAP-02:** Distancia ≤ 8 m. Proyectil de tijera lanzado. Daño perforante = (AGI × 2) + (Pot × 3).
- **Pasiva:** + (3+Pot) AGI en acciones que requieran precisión manual.

### Nv.1 — Control
- **CAP-03:** Cc. Corte doble cruzado. Daño = (AGI × 3) + (Pot × 4). Aplica Sangrado (daño continuo 3 turnos).
- **CAP-04:** Utilidad. Recorta silueta de papel/tela tamaño humano. Se mueve y sigue órdenes simples 3 turnos.
- **Pasiva:** Tijeras cortan metal de dureza media sin perder filo.

### Nv.2 — Maestría
- **CAP-05:** Cc/distancia ≤ 10 m. Tijeras extensibles. Daño = (AGI × 4) + (Pot × 5). Atraviesa hasta 2 enemigos en línea.
- **CAP-06:** AoE cónico. Ráfaga de múltiples tijeras pequeñas. Daño = (AGI × 3) + (Pot × 5). Sangrado a todos.
- **Pasiva:** Corta acero de baja calidad y blindajes ligeros.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 12 m. Entorno recortable. Usuario deforma el escenario recortando y moviendo secciones del terreno.
- **CAP-08:** ≤2 usos/combate. Tijera titánica. Corte = (AGI × 5) + (Pot × 7). Si objetivo <50% HP, aplica Sangrado severo (daño doble por turno).
- **Pasiva:** 1/combate: Recorta silueta de ataque entrante, reduciendo su daño 40%.

## Notas de diseño de cartas
- Tags: cortante, fisico, distancia, utilidad
- Prohibiciones: No corta Kairoseki, Buso Nv.2+ ni Logias sin imbuir.

## Notas staff
- Justificación de Tier: Corte versátil con utilidad creativa, pero limitado por materiales que puede cortar.

---

# Dero Dero no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.dero_dero`
- **Estado:** libre
- **Secundario Potencia:** AGI
- **Origen:** Canon

## Descripción breve
La fruta de la disolución. Permite al usuario derretir su cuerpo y convertirlo en un líquido viscoso de color púrpura. En estado líquido puede filtrarse por rendijas, esquivar ataques físicos y fluir a través de obstáculos. El cuerpo líquido puede endurecerse parcialmente para golpear o moldearse en formas simples. Es inmune a ataques contundentes mientras esté licuado.

## Efecto general
Transformación del cuerpo en líquido viscoso, evasión total de ataques contundentes, infiltración y movilidad fluida.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Las bajas temperaturas extremas (Hie Hie) solidifican el cuerpo líquido y lo vuelven quebradizo.

## Potencia
TEM + AGI → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Cc. Golpe con extremidad endurecida. Daño contundente = (AGI × 2) + (Pot × 3).
- **CAP-02:** Reacción (0 PA, 6+Pot EN). Licúa zona impactada al recibir ataque, reduciendo daño contundente en (AGI × 2) + (Pot × 3).
- **Pasiva:** Caídas desde altura no causan daño (cuerpo se licúa al impactar).

### Nv.1 — Control
- **CAP-03:** Mantenimiento (1 PA, 8+Pot EN). Licúa todo el cuerpo. Inmunidad a golpes contundentes y agarres. Fluye por espacios de hasta 2 cm de ancho.
- **CAP-04:** Distancia ≤ 8 m. Proyectil líquido endurecido. Daño = (AGI × 3) + (Pot × 4). Aplica Resbaladizo.
- **Pasiva:** + (4+Pot) AGI en estado líquido al moverse por superficies horizontales.

### Nv.2 — Maestría
- **CAP-05:** Cc. Ráfaga de golpes líquidos semisólidos. Daño = (AGI × 4) + (Pot × 5). Hasta 3 impactos.
- **CAP-06:** Zona radio 4 m. Cubre suelo de líquido resbaladizo. Enemigos sufren Derribado si no superan Gap AGI.
- **Pasiva:** Estado líquido permite esquivar ataques cortantes con 40% probabilidad.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), 3 turnos. Masa líquida gigante (radio 10 m). Enemigos dentro sufren Ralentizado y reciben (AGI × 2) + (Pot × 3) daño por turno.
- **CAP-08:** ≤2 usos/combate. Envuelve objetivo en líquido y solidifica. Daño = (AGI × 4) + (Pot × 8). Enraiza 1 turno.
- **Pasiva:** 1/combate: Al recibir daño que destruiría extremidad, la licúa y reforma, negando daño permanente.

## Notas de diseño de cartas
- Tags: evasion, movilidad, fisico, control
- Prohibiciones: No puede licuar objetos externos ni a otros seres vivos.

## Notas staff
- Justificación de Tier: Evasión física excelente con movilidad fluida, pero daño ofensivo bajo.

---

# Doa Doa no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.doa_doa`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta de las puertas. Permite al usuario crear puertas en cualquier superficie sólida, incluyendo paredes, suelos, techos e incluso el aire. Las puertas conducen a un espacio de bolsillo interdimensional donde el usuario puede almacenar objetos o esconderse. También puede atravesar cualquier obstáculo sólido abriendo una puerta que lo conecte al otro lado. El espacio interior es seguro y privado.

## Efecto general
Creación de puertas en cualquier superficie para atravesar obstáculos, teletransporte táctico y almacenamiento dimensional.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Solo crea puertas en superficies que pueda tocar. No abre puertas en cuerpos de seres vivos.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Utilidad. Puerta en pared/superficie de hasta 3×2 m. Atraviesa obstáculo hasta (INT × 1) + (Pot × 2) m de profundidad.
- **CAP-02:** Reacción (0 PA, 6+Pot EN). Puerta en el aire frente a ataque, desviándolo al espacio de bolsillo si daño < (INT × 3) + (Pot × 4).
- **Pasiva:** + (4+Pot) Gaps INT para detectar amenazas y abrir puertas de emergencia.

### Nv.1 — Control
- **CAP-03:** Utilidad. Almacena objetos hasta 1 m³ en espacio de bolsillo. Capacidad 10 objetos. Recuperación como acción gratuita.
- **CAP-04:** Cc. Puerta bajo los pies del objetivo. Cae al espacio de bolsillo y queda fuera de combate 1 turno.
- **Pasiva:** Abre puertas en techos y suelos además de paredes.

### Nv.2 — Maestría
- **CAP-05:** Movilidad. Puerta que conecta dos puntos del campo (alcance 20 + 2×Pot m). Usuario y aliados la atraviesan instantáneamente.
- **CAP-06:** Cc/distancia ≤ 5 m. Puerta junto al objetivo y otra junto a pared, empujándolo a través. Daño impacto = (INT × 3) + (Pot × 6). Derribado.
- **Pasiva:** Espacio de bolsillo para 20 objetos y 1 persona.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), 3 turnos. Múltiples puertas (hasta 6) por todo el campo. Usuario se mueve entre ellas instantáneamente y ataca desde cualquier ángulo.
- **CAP-08:** ≤2 usos/combate. Puerta dimensional al vacío. Succiona todo en radio 8 m. Daño = (INT × 4) + (Pot × 8). Expulsa objetivos 10 m en direcciones aleatorias.
- **Pasiva:** 1/combate: Se teletransporta al espacio de bolsillo para evitar ataque letal, reapareciendo en cualquier puerta del campo.

## Notas de diseño de cartas
- Tags: movilidad, control, utilidad, teletransporte
- Prohibiciones: No abre puertas en cuerpos de seres vivos ni en Kairoseki.

## Notas staff
- Justificación de Tier: Teletransporte táctico y control de campo con gran versatilidad estratégica.

---

# Doku Doku no Mi
- **Tipo:** Paramecia
- **Tier:** III
- **ID:** `fruta.doku_doku`
- **Estado:** libre
- **Secundario Potencia:** RES
- **Origen:** Canon

## Descripción breve
La fruta del veneno. Convierte al usuario en un pozo de toxinas viviente: su sangre, sudor, aliento y mucosidad se vuelven letalmente venenosos. El usuario es inmune a todos los venenos conocidos. Puede generar distintos tipos de toxinas: paralizantes, corrosivas, debilitantes o letales. El veneno se libera por contacto, proyectiles de fluido corporal o como gas tóxico que contamina el campo de batalla.

## Efecto general
Generación y control de venenos de distinto tipo, inmunidad total a toxinas, contaminación del campo de batalla.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: El fuego intenso quema y purifica el veneno en el aire. El agua diluye rápidamente las toxinas líquidas.

## Potencia
TEM + RES → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 10 m. Proyectil de veneno líquido. Daño especial = (RES × 2) + (Pot × 3). Aplica 1 carga de Envenenado (daño continuo = (RES × 1) + Pot por turno).
- **CAP-02:** Cc. Puño envenenado. Daño = (RES × 2) + (Pot × 3). Aplica 1 carga de Envenenado.
- **Pasiva:** Inmune a todos los venenos y toxinas convencionales.

### Nv.1 — Control
- **CAP-03:** Distancia ≤ 12 m. Aliento venenoso crea nube de gas (radio 4 m, 2 turnos). Enemigos dentro reciben 1 carga de Envenenado por turno.
- **CAP-04:** Recubre cuerpo de veneno corrosivo 2 turnos. Cualquier ataque Cc recibido inflige 1 carga de Envenenado al atacante.
- **Pasiva:** + (3+Pot) RES para resistir purificación y desintoxicación forzada.

### Nv.2 — Maestría
- **CAP-05:** AoE cono 10 m. Ráfaga de veneno mixto. Daño = (RES × 3) + (Pot × 5). Aplica 2 cargas Envenenado y Ralentizado.
- **CAP-06:** Zona radio 8 m. Terreno contaminado (3 turnos). Enemigos dentro: 1 carga Envenenado por turno y - (2+Pot) AGI.
- **Pasiva:** Cargas de Envenenado infligen + (2+Pot) daño extra por cada carga más allá de la primera.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 35+3×Pot EN), radio 20 m, 3 turnos. Niebla púrpura masiva. Todos los enemigos reciben 3 cargas de Envenenado inmediatas.
- **CAP-08:** ≤2 usos/combate. Cañón de veneno hiperconcentrado. Daño = (RES × 5) + (Pot × 8). Si sobrevive, recibe 5 cargas de Envenenado.
- **Pasiva:** 1/combate: Al caer a 0 HP, explota en nube de veneno (radio 10 m) aplicando 3 cargas de Envenenado a todos los enemigos.

## Notas de diseño de cartas
- Tags: veneno, zona, distancia, debilitacion
- Prohibiciones: No envenena a través de Busoshoku Nv.2+ que cubra completamente al objetivo.

## Notas staff
- Justificación de Tier: Daño progresivo por acumulación de veneno con control de zona y contaminación persistente.

---

# Doru Doru no Mi
- **Tipo:** Paramecia
- **Tier:** II
- **ID:** `fruta.doru_doru`
- **Estado:** libre
- **Secundario Potencia:** INT
- **Origen:** Canon

## Descripción breve
La fruta de la cera. Permite al usuario generar y moldear cera derretida desde cualquier parte de su cuerpo. La cera se endurece al contacto con el aire volviéndose resistente como el cemento. El usuario puede crear barreras, escaleras, proyectiles sólidos, llaves moldeadas y trampas de cera. Al controlar la temperatura de la cera, también puede usarla líquida para atrapar enemigos o semisólida para vendajes protectores.

## Efecto general
Generación y control de cera moldeable que se endurece al aire, permitiendo construcción de estructuras, trampas y defensas.

## Debilidad
- Universal: Agua de mar / Kairoseki
- Específica: Las altas temperaturas derriten la cera endurecida, reduciendo su resistencia a la mitad.

## Potencia
TEM + INT → ⌊suma/8⌋ (suelo 1)

## Capacidades por nivel

### Nv.0 — Manifestación
- **CAP-01:** Distancia ≤ 10 m. Proyectil de cera endurecida. Daño contundente = (INT × 2) + (Pot × 3).
- **CAP-02:** Reacción (0 PA, 8+Pot EN). Capa de cera en zona impactada absorbe (INT × 2) + (Pot × 3) daño.
- **Pasiva:** + (2+Pot) mitigación física por capa de cera natural en su piel.

### Nv.1 — Control
- **CAP-03:** Muro de cera (ancho 5 m, alto 3 m). Absorbe (INT × 3) + (Pot × 4) daño.
- **CAP-04:** Utilidad. Crea llaves y herramientas simples de cera endurecida copiando cerraduras vistas.
- **Pasiva:** Cera ignífuga a temperaturas normales (no se derrite con fuego doméstico).

### Nv.2 — Maestría
- **CAP-05:** AoE radio 6 m. Estalagmitas de cera del suelo. Daño = (INT × 3) + (Pot × 5). Afectados quedan Enraizados 1 turno.
- **CAP-06:** Mantenimiento (1 PA, 12+Pot EN). Armadura de cera total. + (6+Pot) mitigación física y + (3+Pot) elemental 3 turnos.
- **Pasiva:** Cera adopta colores y texturas arbitrarios, permitiendo camuflaje básico.

### Nv.3 — Despertar
- **CAP-07:** 1/combate (2 PA, 30+3×Pot EN), radio 15 m, 2 turnos. Área cubierta de cera que se endurece. Enemigos no voladores Enraizados hasta romper con Gap FUE.
- **CAP-08:** ≤2 usos/combate. Prisión de cera maciza. Daño = (INT × 4) + (Pot × 7). Objetivo inmóvil 1 turno completo.
- **Pasiva:** 1/combate: Capullo de cera absorbe todo el daño de una ronda; emerge sin daño al siguiente turno.

## Notas de diseño de cartas
- Tags: zona, escudo, control, utilidad
- Prohibiciones: No crea cera con propiedades conductoras o aislantes especiales.

## Notas staff
- Justificación de Tier: Construcción versátil con aplicaciones defensivas y de control, pero vulnerable al fuego.
"@
