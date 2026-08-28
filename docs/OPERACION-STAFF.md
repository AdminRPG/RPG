# OPERACIÓN STAFF — One Piece: 7 Seas (motor Eternal)

> Guía operativa diaria. Los manuales (`docs/sistema/Manual_del_Staff.md`,
> `Manual_del_Jugador.md`) son la ley; esto es el **cómo se opera** en el foro:
> dónde está cada cosa, qué haces en cada panel y qué se automatiza solo.
> Principio rector (5.21): **la IA propone, el staff decide, nada se publica
> sin firma** — salvo las 3 excepciones 100 % automáticas (compra de PP (4),
> tirada de fruta (45) y tirada del Conquistador (50)).

---

## 1. Tu día a día en 5 minutos

1. **Bandeja** (`bandeja.php`): firma o rechaza los trámites pendientes. Es tu
   tarea principal: revisar el prompt generado, pegar el resultado de la IA
   (o editar), firmar con motivo.
2. **Mundo Vivo** (`mundo-vivo.php`): abre la ronda mensual en análisis, pega
   el prompt de la skill, revisa el dashboard, recompensas, precios y el
   periódico, y firma el cierre.
3. **Paneles de estado** (facciones, conquista, navegación, misiones,
   tripulaciones, cibernética, familias, bajo mundo, akumas…): revisa los
   avisos y resuelve lo que requiere veredicto.
4. **Foro**: modera el combate en Zona B (validación de PA) y los cierres de
   tema.

Acceso: todo está en **Zona Staff** (`zona-staff.php`) → cards por panel.
Solo staff (los narradores habilitados ven su panel de misiones).

---

## 2. El flujo de un trámite (staff ↔ IA)

1. El usuario (o el staff) crea la solicitud desde una ventanilla
   `tramite-NN.php`.
2. El sistema genera el **prompt** con los IDs (tema, personaje, objetos…).
3. Cópialo, pégalo en tu sesión de IA con la **skill** correspondiente
   (Anexo B del Manual del Staff) y los manuales como contexto.
4. La IA devuelve el resultado: **revísalo y edítalo** en la bandeja.
5. **Firma con motivo** → el sistema aplica los efectos (PP, posteo,
   histórico, notificación, impacto de Mundo Vivo).

**Naturaleza de los trámites:**

| Naturaleza | Qué espera el staff | Ejemplos |
|---|---|---|
| `ia` | Prompt → IA → editar → firma | 3 (ficha), 13 (técnica), 20 (ascenso), 25 (rumor), 38 (travesía)… |
| `ligero` | Solo validación + efectos (100 % auto) | 4 (PP), 14 (equipar), 17 (reponer), 26/28/29, 45/47/50 |
| `staff` | Solo el staff inicia | 18, 21, 24, 30, 36, 49, 54/55, 59/60/61 |
| `hito` | Narrativo con firma | 62 (muerte) y otros cierres |

**Ciclo con usuario** (3 y 13): el resultado vuelve al jugador para
confirmarlo antes de publicar — no lo saltes.

---

## 3. La bandeja (el corazón)

`bandeja.php` — pendientes, prompt generado, resultado editable, firma con
motivo e histórico auditable (A.3 «Trámites»).

- **Pendientes**: cada fila muestra trámite, número, solicitante y estado.
- **Prompt**: botón/campo con la orden para la IA (ids incluidos).
- **Resultado**: caja editable — pega la salida de la IA y corrige lo que
  haga falta (coherencia > velocidad, 1.3).
- **Firmar**: `publicar` (aplica efectos) / `rechazar` (motivo obligatorio)
  / `archivar`.
- El histórico (`tramites_historico`) queda auditable con actor y motivo.

Regla de oro: si la IA propone algo incoherente (bandas de precios, números
sagrados, cupos), **corrígelo** — el motor aplica lo que firmas, nunca
recalcula por su cuenta.

---

## 4. La ronda de Mundo Vivo (mensual)

`mundo-vivo.php` — el pilar (5.14). Flujo:

1. **Abrir análisis**: la ronda activa pasa a «En análisis»; la cola lista
   los temas presentes abiertos con sus participantes.
2. **Pegar el prompt** (skill-mundo-vivo) con los IDs de la cola: la IA
   propone dashboard, matriz de islas, recompensas con motivo, fluctuación
   de precios y el periódico.
3. **Revisar y editar** cada salida antes de publicar (visibilidad manual):
   - Recompensas con motivo: `recompensas_historico` (tipo/cantidad/ronda).
   - Periódico «News Coo»: el borrador se archiva y **tú lo publicas**
     (botón «Publicar edición»); nada se publica solo.
4. **Firmar el cierre**: el motor aplica los cambios de isla (con motivo e
   histórico), recompensas, precios (banda 0,5×–2×) y archiva la edición.

Cron (A.2): el calendario on-roll avanza solo (1 real = 2 on-roll), los
mantenimientos (implantes, espías, sueldos, conquista) y las caducidades
(carteles a 3 rondas, misiones abandonadas, tripulaciones < 2 activas) se
disparan en el hook diario — revísalos en sus paneles.

---

## 5. Panel por panel (A.3) — qué haces en cada uno

| Panel | Qué revisas / qué haces |
|---|---|
| **Calendario** | Fecha on-roll, temas presentes con congelados, avisos de pasados incoherentes. |
| **Progresión** | Cronómetros de entrenamiento, subidas de nivel, gastos de PP por concepto, saldos y reservas. |
| **Mercado / Economía** | Fluctuación por zona y ronda con motivo, carteras (robable/bóveda), transacciones. |
| **NPCs** | Primarios con capa oculta solo-staff, bestiario (fichas de combate), apariciones por tema (incluido «reclutado», trámite 19). |
| **Facciones** | Rangos con cupos, propuestas de ascenso (20), élite (21), deserciones (23), infiltraciones (24), sueldos y nóminas. |
| **Bajo Mundo** | Veracidad interna de rumores (25/27/32), redes y espías (29/33), carteles Wanted con caducidad (30/31), histórico de operaciones. |
| **Mundo Vivo** | Dashboard de ronda, matriz de islas, recompensas con motivo, periódico, tablón de misiones. |
| **Conquista** | Asedios por fases/rondas, unidades y hordas con mantenimiento, registro de ocupación (34–37). |
| **Navegación** | Travesías activas (ruta/plazo/oráculos/víveres), vencimientos < 48 h, histórico (38). |
| **Barcos** | Flota por jugador, daños, reparaciones, mejoras N, módulos (39–44). |
| **Akumas y Haki** | Cupos mundiales, pool de tirada, ficha de 8 bloques, despertar (48), fruta bajo demanda (49), Conquistador (50), subidas de Haki (51), sucesos en borrador. |
| **Narradores / Misiones** | Tablón CRUD con la ficha de 6 bloques (secretos solo staff/narradores), cupo de 2 narradores, auto-narradas por rondas (52–55). |
| **Cibernética** | Implantes por zona/nivel con requisitos acumulativos, mantenimientos por ronda (56–58), diseño a medida (59). |
| **Familias** | Cupos de linaje, portadores, concesión/revocación con expediente (60–61). |
| **Tripulaciones** | Fichas de banda, cofre común, avisos de disolución, cambio de capitán (63–67). |
| **Reliquias** | Fichas muertas con su leyenda, histórico de muertes con calidad y herencia (62). |

---

## 6. Las 8 skills (Anexo B) — cuándo usarlas

| Skill | Cuándo | Naturaleza |
|---|---|---|
| `validacion-personajes` | Crear/validar ficha (3) | ciclo con usuario |
| `cierre-temas` | Cerrar tema / veredicto de combate (2, 62) | firma |
| `creacion-tecnicas` | Crear/subir técnica (13) | ciclo con usuario |
| `mundo-vivo` | Ronda mensual (dashboard, islas, recompensas, periódico) | firma + publicación manual |
| `navegacion` | Iniciar tramo/oráculos de travesía (38) | firma del veredicto |
| `narracion-automatica` | Auto-narradas (52–55) | firma del cierre |
| `adaptacion-akumas` | Ficha bajo demanda (46 concepto/concreta, 49) + despertar (48) | firma |
| `adaptacion-cibernetica` | Implante bajo demanda (56, 59) | firma |

La skill **propone la ficha y los números según el manual; tú firmas**.
Nunca apliques el resultado de una skill sin revisarlo (editable en la
bandeja).

---

## 7. Números que no tocas (sagrados)

Precios y cupos cerrados del manual — ante incoherencia, **anótala en
`docs/sistema/REGISTRO_DECISIONES.md`** y consulta; no la cambies por tu
cuenta:

- Banda de precios de mercado: 0,5×–2× del base (5.9); venta a NPC al 50 %.
- PP por tramo de atributo y techos `20+1,6(L−1)` (5.6); dominios ×1,5/×2.
- Cupos: fruta = fruto único mundial; implantes por zona; linajes 3–5;
  espías 4 por red; narradores 2 simultáneas.
- Escala de carteles: desde cientos de miles (5.9/14.6).
- Raciones 50 ฿; mantenimientos de implantes 2.500/10.000/40.000 ฿.

---

## 8. Autocuidado del sistema

- **Sin dados**: ninguna acción se resuelve por azar. Únicas excepciones:
  tirada del Conquistador (50) y tirada de fruta aleatoria (45, nv3+).
- **Sin canon**: no introduzcas personajes ni eventos canon como contenido.
- **Originalidad**: los nombres canon (frutas, Haki, islas, facciones,
  «News Coo») sí se usan.
- **No-crunch**: el sistema sugiere, nunca bloquea por falta de cálculo; los
  triggers condicionales los declara el jugador en su post y tú los verificas.
- Si algo del motor viejo (`gbe_*`, dados) reaparece en la zona jugable,
  **repórtalo** — está prohibido reintroducirlo.

---

*Documento vivo — se actualiza con cada fase (F0 → F6).*
