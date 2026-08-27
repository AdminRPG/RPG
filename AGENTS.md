# SUPER AGENTS.md — One Piece: 7 Seas (motor Eternal)

> **Documento maestro operativo del proyecto.** Cualquier agente lo lee **al empezar cada sesión** y lo mantiene **al día al cierre de cada fase y de cada sesión**. La deuda documental no se acumula.
> Repo hermano de sistemas (reglas de diseño): `c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Sistema/docs/` (solo si se abre/referencia manualmente — no accesible por defecto desde este workspace).

---

## 1. Identidad

- **Producto / marca visible:** One Piece: **7 Seas** (foro de rol por escrito en el universo One Piece).
- **Motor (nombre técnico):** Eternal · prefijo de código PHP `ope_` (funciones, tablas `mybb_ope_*`) · prefijo CSS/plantillas `ope-`.
- **Plugin:** `inc/plugins/ope_rol.php` (codename `ope_rol`) · **Backend:** `inc/ope_rol/` (capas `core/`, `catalogos/`, `config/`, `dominio/`, `mundo/`, `sistemas/`, `tramites/`; entrada `bootstrap.php`).
- **Bot del sistema:** «OPE Eternal» (uid reservado 2) — News Coo, sucesos, rumores, posteos automáticos.
- **Prohibido reintroducir** `gbe_`, `gbe-`, Granblue, GBF, I-Forge o iforge en código nuevo (regla vigente).

## 2. Misión y objetivo final

> Que un jugador nuevo cree su personaje hoy y dentro de un mes esté navegando por el New World, peleando con cartas de técnica, ganando PP, y que el staff modere todo desde la zona staff con la IA como copiloto — fiel a los manuales, **sin dados** y sin crunch innecesario.

**Definición de done (checklist de lanzamiento, §12):** foro jugable de punta a punta (F6) · 67 trámites operativos · tablas del Anexo A.1 migradas · hooks de A.2 · paneles de A.3 · 8 skills integradas al flujo de trámites · formato de posteo en dos zonas con validación de PA · sin dados (2 excepciones solo deciden *qué obtienes*) · sin personajes/eventos canon · `check-inline-styles` limpio · `sync-theme verify` OK · migraciones idempotentes · `docs/OPERACION-STAFF.md` escrito · SUPER AGENTS.md y REGISTRO_DECISIONES.md al día.

## 3. Fuentes de verdad y orden de lectura

**`docs/sistema/` (los manuales) = ley de reglas.** Si un doc del repo (README, docs antiguos) contradice a los manuales, **ganan los manuales**. La identidad visual y las convenciones de código siguen el `DESIGN` y el `GUIA-ESTILOS-PHP.md`.

1. `docs/sistema/Manual_del_Staff.md` — **la fuente de implementación**: operativa + cap. 22 (67 trámites + prompts) + **Anexo A** (A.1 datos · A.2 hooks · A.3 paneles) + **Anexo B** (skills).
2. `docs/sistema/Manual_del_Jugador.md` — 23 capítulos; define la UX del front.
3. `docs/sistema/diseno/` — `5.14_catalogo_islas.md` (17 islas a sembrar) · `5.18_guia_adaptacion_frutas.md` · `5.22_guia_adaptacion_implantes.md` (guías maestras de skills).
4. Docs del repo: `README.md` · `docs/GUIA-ESTILOS-PHP.md` · `docs/DESIGN-ONE-PIECE-ETERNAL.md` · `docs/RESTAURAR-BACKUP.md` · `inc/ope_rol/README.md`.

**Regla de consulta por tarea:** antes de implementar un sistema, lee (a) su capítulo en el Manual del Staff (operativa + capa técnica + fila del Anexo A), (b) su capítulo en el Manual del Jugador, (c) la guía maestra si es fruta o implante. No implementes de memoria.

## 4. Principios innegociables (los 8)

1. **Sin azar.** Ninguna acción se resuelve con tiradas/dados. Únicas excepciones (deciden *qué obtienes*, jamás *qué pasa*): tirada del Haki del Conquistador (5.19) y tirada de akuma aleatoria (5.18, nv3+). No implementes ningún sistema de dados.
2. **Mundo Vivo (5.14) = pilar central.** Toda acción en un tema presente tiene consecuencias reales y medibles.
3. **Narrativa + comparación de mecánicas.** Los jugadores no dictan resultados; el veredicto lo cierra el staff con las tablas de delta al cierre del tema.
4. **Alta personalización.** Dos personajes con la misma raza y atributos se juegan distinto.
5. **Redacción explicativa.** Todo en el front bien explicado en prosa; las tablas apoyan, no sustituyen.
6. **Automatismos + no-crunch.** Los triggers condicionales los declara el jugador en su post y el staff los verifica en el momento; el sistema sugiere, nunca bloquea por falta de cálculo.
7. **Originalidad.** Sin personajes ni eventos canon como contenido. Los nombres canon (frutas, Haki, islas, facciones, «News Coo») sí se usan (News Coo: derogación puntual confirmada por el usuario).
8. **Números sagrados.** Fórmulas, precios, cupos y bandas cerrados. Ante incoherencia: anótala en `REGISTRO_DECISIONES.md` y consulta con pregunta guiada — no la cambies por tu cuenta.

## 5. Protocolo de trabajo guiado (IMPRESCINDIBLE)

- El agente **pregunta con opciones** (2–4, recomendada primero) en cada decisión consecuente y en cada fase; el usuario decide. **Máximo 4–5 preguntas por tanda**, priorizadas.
- **Reversible/menor** (variable, CSS, texto de botón) → elige la opción sensata, aplícala y avisa en una línea. **Consecuente** (arquitectura, números, flujo, alcance) → pregunta sí o sí.
- Si el usuario no responde, aplica la **recomendada**, señálalo al retomar y sigue — nunca te quedes bloqueado.
- Toda respuesta se anota en `docs/sistema/REGISTRO_DECISIONES.md` (fecha, decisión, opción, motivo, sistemas afectados). **Léelo antes de trabajar.**
- Al terminar cada fase: resumen + confirmación para avanzar (avanzar / corregir X / repasar Y).

## 6. Inventario del repo (actualizado a F0 · 2026-08-27)

**Ya existe (se conserva y se completa):**
- Portada `index.php` (scope `body.ope-index`): hero + bento + Los Mares (8 tarjetas vacías → se rellenan con el catálogo de 5.14) + Off Topic.
- Páginas: `ficha.php`, `personajes.php`, `crear-personaje.php`, `tramites.php` (skeleton), `zona-staff.php`, `progresion.php`, `mapa.php`, `tiendas*.php`, `astillero.php`, `barco.php`, `tripulacion.php`, bibliotecas, `gestion.php`, `mensajes.php`, `alertas.php`, `resumen.php`, `guias.php`.
- Motor `ope_rol` (era anterior, **se conserva intacto**): `inc/plugins/ope_rol.php` (~4.500 líneas, hooks + navbar + staff por personaje) + `inc/ope_rol/` (core, catalogos, dominio, sistemas, mundo, tramites) + **17 stubs** `inc/ope_rol_*.php` (decisión D0.4: conservar como redirección).
- **Nuevo en F0:** `docs/sistema/` (manuales + diseno + `REGISTRO_DECISIONES.md`) · `docs/sistema/REGISTRO_DECISIONES.md` · esquema `mybb_ope_*` (Anexo A.1, decisión D0.3-bis) · motor de trámites 5.21 (`inc/ope_rol/tramites/{catalogo,motor,bandeja}.php`) · página `bandeja.php` · permisos sobre `mybb_rol_cuentas`.
- BD `rpg_forum` (PHP 8.3, MyBB 1.8.39): 129 tablas — ~55 `mybb_rol_*` viejas conservadas + las nuevas `mybb_ope_*`. Entorno local `http://rpg.test/`, `MYBB_DB_NAME=rpg_forum`.
- Tema: `docs/themes/ope.css` (fuente de verdad) → sync con `php scripts/sync-theme.php import` / `verify`; `php scripts/check-inline-styles.php`.
- Scripts: migraciones/seeds en `scripts/` (convención `migrate-*.php`, `seed-*.php`, idempotentes, `_db-config.php`, `_migrate-lib.php`) · `sync-theme.php` · `check-inline-styles.php` · `restore-backup.php`.
- CI/CD: `.github/workflows/deploy.yml` → FTP a InfinityFree en cada push a `main`. **No desplegar manualmente.**
- Usuarios: `admin` + «OPE Eternal» (bot, uid 2).

**No tocar / no reescribir:** identidad visual del DESIGN · scaffolding de páginas (clases `.shead/.plate/.plate-h/.plate-b/.reveal/.flash/.pj-empty` **solo por scope** `body.ope-pg-<pagina>`; cero `<style>`/`style=""` estáticos; variables CSS solo las del sistema) · flujo de despliegue y backups · tablas `mybb_rol_*` (hasta decisión de retirada) · `.freebuff/` y prototipos `docs/Prototypes/` (referencia visual).

## 7. Arquitectura

- **Capas del motor (`inc/ope_rol/`):** `core/` (bootstrap, data, system, tablas, permisos) · `catalogos/` (datos puros en arrays) · `dominio/` (reglas puras) · `sistemas/` (combate, progresión, economía, haki, akumas, implantes, navegación, conquista) · `mundo/` (ronda, islas, sucesos, periódico, misiones, rumores, facciones, viajes) · `tramites/` (**motor transversal**: catálogo 67, motor, bandeja).
- **Nuevas funciones del motor 7 Seas usan prefijo `ope7_`** para no colisionar con el código viejo (`ope_`, `ope_rol_*`).
- **Patrón de página PHP (obligatorio):** `define('IN_MYBB',1)` + `require './global.php'` → `ope_rol_head_base()` → `ope_rol_navbar_html()` → breadcrumb → `<div class="wrap">` → `include inc/footer_custom.php` → IntersectionObserver. Scope CSS `body.ope-pg-<pagina>`.
- **BD:** tablas canónicas `mybb_ope_*` creadas por migraciones en `scripts/` (idempotentes). Nunca SQL a mano en producción. Respeta `docs/RESTAURAR-BACKUP.md`.
- **Cron/hooks (A.2):** calendario on-roll diario (1 real = 2 on-roll) · ronda del Mundo Vivo mensual · mantenimientos por ronda (implantes, espías, sueldos) · tripulaciones (<2 activos → disolución) · carteles (paradero 3 rondas). Doble cara: automático vs. lo que espera al staff (prompt → IA → firma).

## 8. Roadmap de fases (copia viva del maestro)

| Fase | Contenido | Estado |
|---|---|---|
| **F0** | Fundaciones: manuales en `docs/sistema/`, SUPER AGENTS.md, REGISTRO_DECISIONES.md, esquema Anexo A.1 (`mybb_ope_*`), motor de trámites + 67, permisos, hitos de bandeja/bot | ✅ completada (2026-08-27) |
| **F1** | Personaje: wizard de creación, skill-validacion-personajes, ficha completa, trámites 1–12 | ✅ completada (2026-08-27) |
| **F2** | Combate: PA/defensas/técnicas/estados, posteo en dos zonas, tablas de delta, muerte (trámite 62) | ✅ completada (2026-08-27) |
| **F3** | Progresión y economía: calendario, cierre de temas (skill-cierre-temas), carteras/tiendas, inventario | ✅ completada (2026-08-27) |
| **F4** | Mundo Vivo: ronda mensual, 17 islas, facciones, bajo mundo, conquista, navegación, barcos | ⏳ (F4.1 ✅) |
| **F5** | Akumas, Haki, narradores/auto-narradas, cibernética, tripulaciones | ⏳ |
| **F6** | Cierre y QA: auditoría transversal, seeds finales, OPERACION-STAFF.md, checklist de lanzamiento | ⏳ |

## 9. Los 67 trámites y las 8 skills

- **Catálogo de 67 trámites** como datos: `inc/ope_rol/tramites/catalogo.php` (número, sistema, nombre, skill, quién, naturaleza, efecto al publicar, plantilla de prompt). Listado cerrado en el **cap. 22.3 del Manual del Staff**.
- **Naturaleza:** `ia` (prompt → IA → editable → firma) · `ligero` (validación + hooks, sin IA) · `staff` (solo el staff inicia) · `hito` (narrativo con firma).
- **Regla de oro:** la automatización nunca decide sola — la IA propone, el staff firma. Únicas **3 excepciones 100 % automáticas**: compra de PP (4) · tirada de fruta (45) · tirada del Conquistador (50).
- **Las 8 skills (Anexo B):** `validacion-personajes` · `cierre-temas` · `creacion-tecnicas` · `mundo-vivo` · `navegacion` · `narracion-automatica` · `adaptacion-akumas` · `adaptacion-cibernetica`. Implementación = plantillas de prompt + flujo editable + firma + documentación staff (no son código de juego).

## 10. Reglas de trabajo

1. **Idioma:** todo en español (código, comentarios, commits, docs, mensajes).
2. **Lee antes de tocar:** este SUPER AGENTS.md → `docs/sistema/REGISTRO_DECISIONES.md` → los manuales. UI: GUIA-ESTILOS-PHP + DESIGN.
3. **Nunca reescribas el skin:** extiende `docs/themes/ope.css` con scopes `body.ope-pg-<pagina>`; cero `<style>`/`style=""` estáticos; botones rectangulares `border-radius:8px` (prohibido pill); la portada usa `body.ope-index`.
4. **Verificación obligatoria al terminar cada tarea:** `php scripts/check-inline-styles.php` limpio · `php scripts/sync-theme.php import` · `php scripts/sync-theme.php verify` → `OK CSS: in sync` · comparación visual en navegador.
5. **BD:** migraciones y seeds en `scripts/` (idempotentes, `MYBB_DB_NAME=rpg_forum`); nunca SQL a mano; respeta backups.
6. **Sin azar** (ver principio 1): si algo del repo viejo tira dados, elimínalo o sustitúyelo por veredicto.
7. **No-crunch** (ver principio 6).
8. **Números sagrados** (ver principio 8).
9. **La automatización nunca decide sola** (ver §9).
10. **Commits por fase/hito** en español, sin tocar archivos ajenos; no despliegues manualmente; sin secretos en commits.
11. **Originalidad** (ver principio 7).
12. **No toques** `.freebuff/`, `docs/Prototypes/` ni el flujo de despliegue.
13. **Protocolo guiado (§5):** pregunta con opciones, anota todo en el REGISTRO, confirma cada fase antes de avanzar.
14. **graphify:** para preguntas sobre código, `py -m graphify query "<pregunta>"` (existe `graphify-out/graph.json`); tras modificar código, `py -m graphify update .`.
15. **Herramientas (Windows/PowerShell):** separar comandos con `;`, no `&&`.

## 11. Registro de decisiones y pendientes

- Acta completa: **`docs/sistema/REGISTRO_DECISIONES.md`** (decisiones F0: D0.1–D0.6, mapeo BD→Anexo A, incoherencias detectadas).
- **Cierre F0 (2026-08-27):** 99 tablas `mybb_ope_*` migradas (idempotente) · catálogo 67 trámites · motor (ligero/IA/staff/hito) + firma con histórico probados · bot «OPE Eternal» postea (`scripts/test-7seas-motor.php`) · `bandeja.php` y `tramites.php` renderizan · `check-inline-styles` limpio · `sync-theme verify` OK.
- **Cierre F1 (2026-08-27):** puntero `personaje_tabla` (rol/ope) + `ope7_pj_activo()` · seeds de catálogos cerrados (11 razas, 20 raciales, 7 tribus, 18 dominios, 24 ramas, 28 dotes, 13 defectos, 24 rasgos, 15 efectos de técnicas) · módulo `dominio/personajes.php` (secundarios con raciales, techos, coste PP por tramo, validación de ficha) · wizard 8 pasos con balanzas a 0 y carta en vivo · ficha 7 Seas con desglose auditable + sección solo-staff · trámites 1–12 con efectos al publicar + **ciclo con usuario (3 y 13)** · tests `test-7seas-f1*.php` verdes (wizard, dominio, ciclo+efectos) · `check-inline-styles` limpio · `sync-theme verify` OK.
- **Cierre F2 (2026-08-27):** motor de combate puro `sistemas/combate.php` (PA `6+AGI/10+Nv/5`, daño CC/distancia/desarmado, bandas ±5/±10/±20, Tablas 1–3 + choque + umbral del dolor, matices, estados I–III, 1vN, tope de sala 5, resolución de cierre) · seeds (24 acciones · 34 estados · 10 matices) · **trámites 13 y 62** (técnica → `ope_tecnicas` + cupo INT/4 + PP por tier; muerte → reliquia + herencia reclamable desde el wizard + fruta renace) · **Zona B** (`sistemas/combate_ui.php`: panel bajo el editor, avisos que nunca bloquean, parse + persistencia en `turnos_combate`/`sala_combate`) · panel staff `resolucion-combate.php` (turnos pa_total vs gastado, excesos, veredictos con matices, firma con motivo, histórico de bandas) + enlace en Zona Staff · tests `test-7seas-f2.php` 66/66 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK.
- **Cierre F3 (2026-08-27):** calendario on-roll (`sistemas/progresion.php`: semilla = hoy reiniciable, avance perezoso ×2 idempotente en hook, histórico) + finalización de entrenamientos y colocación de reserva (7.3) · **skill-cierre-temas** (Base×7 factores, bandas cerradas, techo/suelo, redondeo a favor del jugador; trámite 2 referencia el veredicto de combate D2.3 y libera congelación) · catálogo `objetos` (34) + `economia_config` · ranuras/carga + equipar con cupos Meitou (trámites 6 y 14) · cartera al crear PJ + movimientos · **tiendas** (15–18: apertura Comerciante/local/capital/bélicos, cierre, reposición, boletín; compra/venta con transacciones y anti-abuso) · `tienda-general.php` y `tiendas-personales.php` funcionales · bloque «Equipo y cartera» en la ficha · **panel staff `calendario-staff.php`** (Anexo A.3: fecha on-roll + avances, presentes activos con congelados, histórico aperturas/cierres, avisos de coherencia; apertura de pasados con fecha declarada, bug F0 del resultado inicial corregido) · tests `test-7seas-f3.php` 41/41 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK.
- **Cierre F4.1 (2026-08-27):** **semilla del mundo** (`scripts/seed-7seas-mundo.php`: 7 mares · 17 islas 5.14 con ficha viva de 13 parámetros + histórico de arranque · 34 zonas · 8 tipos de barco ×N1–N3 · 5 maderas · 10 módulos · 7 oráculos · 3 transportes · 8 facciones con 44 rangos) · **motor de ronda** (`sistemas/mundo.php`: abrir/analizar/cerrar, cola de presentes, `ope7_ronda_aplicar_cierre()` — islas con motivo e histórico, recompensas, precios en banda 0,5×–2×, periódico en borrador, visibilidad manual) + helpers (`ope7_isla_por_slug/id`, `ope7_isla_ficha`, `ope7_isla_zonas`, `ope7_isla_actualizar`) · **panel staff `mundo-vivo.php`** (Anexo A.3, scope `ope-pg-mundo-vivo`, card en Zona Staff) · **tienda ↔ isla (D4.4)**: apertura exige isla + territorio + Comerciante, `zona_id` = isla, precio del mercado de su zona, `ope7_tiendas_suspender_en_isla()` para la conquista · `tiendas-personales.php` con selector de isla · tests `test-7seas-f4.php` 36/36 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK.
- **Cierre F4.2 (2026-08-27):** **panel staff `progresion-staff.php`** (Anexo A.3, scope `ope-pg-progresion-staff`, card en Zona Staff): cronómetros de entrenamiento por jugador (bloque 5/10, fin + días restantes, al vencer → reserva) · saldos y reservas con progreso de nivel (comprados/10, falta para subir) · **gastos de PP por concepto** (libro `historico_pp`: compras, técnicas, cierres) · histórico reciente (últimos 25 movimientos con fecha/motivo). Libro de PP completado: el efecto 4 (compra) ahora registra el gasto con concepto y tramite_id, y el cierre (2) completa su fila con cantidad/concepto. **Bloque «Reserva de puntos» en la ficha** (F4.2-bis, 7.3): stepper por atributo con techo del nivel, suma live en JS, `gaccion=reserva` → `ope7_pj_colocar_reserva()` con flash. Tests `test-7seas-f4.php` 47/47 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. **Cronómetro de dominios** (F4.3, 5.3/4.4 + D4.5): trámite 4 con rama dominios (adquisición con nivel mínimo de PJ + cupo INT 1/50 + multiplicador anclado ×1,5 el 1.º adicional / ×2 el 2.º+ / ×1,0 creación, D4.5; subida nivel a nivel) · cronómetro de 15 días independiente del de atributos con `ope7_pj_finalizar_dominios()` · gasto en `historico_pp` con concepto · bloque de Dominios de la ficha con cronómetro visible y formulario de compra (`gaccion=dominio`) · cronómetros de dominio en el panel «Progresión». Tests `test-7seas-f4.php` 58/58 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. **Trámite 38 — Navegación/travesía** (F4.3, 5.16/17): módulo `sistemas/navegacion.php` — validación de ubicación (origen no editable) y un-presente · **límite de mar por barco + madera** (18.5: madera habilita mares, tipo exige madera mínima) · **IRT interno** (base del mar 1–4 + peligrosidad 1–50 + mundo 0–2 − mitigadores Navegante/Timón/Cartógrafo/barco/utensilio; solo-staff) · **oráculos** deterministas por banda (7 tipos, ruta segura en transporte) · **tiempo off-roll** 72/48/36 h por tramo −12 h utensilio −25 % Maestre +incidentes +24 h transporte · **víveres** 1 ración/persona/día on-roll +1/+2/+3, consumidos al cierre con veredicto empeorado si falta stock · cierre integrado en el efecto 2 (raciones + daño al barco + `ubicacion = destino`) · `ope7_travesias_vencidas()` en el cron (plazo del tema). Seed: 4 utensilios (Brújula/Log Pose/Eternal/Log Pose NM). Tests `test-7seas-f4.php` 83/83 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. **Panel staff «Navegación»** (A.3, 17.8): página `navegacion-staff.php` (scope `ope-pg-navegacion-staff`, card en Zona Staff) con `ope7_navegacion_panel_html()` — travesías activas por jugador (ruta, medio, plazo, vencimiento con aviso <48 h, oráculos, víveres al cierre) + histórico de resueltas/vencidas. **D4.6:** precio de la ración = 50 ฿ (manual 17.6; seed corregido). Tests `test-7seas-f4.php` 87/87 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. **Facciones — trámites 19–24** (F4.3, 5.12/13 + D4.7): módulo `sistemas/facciones.php` — **20 ascenso** sin saltos con requisito duro `rep_min` + cupo del rango (espera de cupo si lleno/sobrecupo) + termómetro · **21 subfacción élite** Shichibukai (cupo 7, revocación → Wanted ×1,5; Gorosei NPC) · **22 cambio** de facción (baja + alta por rango inicial, anti-abuso 1 PJ/facción) · **23 deserción** hostil → criminal (Wanted 5M + infamia) / pacífica → Aventurero libre · **24 infiltración** con capa oculta (`infiltracion_*` en `faccion_personaje`, staff) · histórico inmutable `cambios_faccion`. **Panel staff «Facciones»** (A.3): página `facciones-staff.php` (scope `ope-pg-facciones-staff`, card en Zona Staff) con `ope7_facciones_panel_html()` — tablero de rangos con cupos/ocupación, cúspide, Shichibukai activos, histórico, Wanted. Seed: cupos de cúspide (D4.7) + `rep_min` en requisitos. Bug real corregido en `ope7_faccion_cupo_libre` (sobrecupo = 0 plazas, antes −1 colisionaba con «ilimitado»). Tests `test-7seas-f4.php` 109/109 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. **Conquista — trámites 34–37** (F4.3, 5.15/cap. 16): módulo `sistemas/conquista.php` — **34 anuncio** (control previo salvaje/guarnición/jugador, presencia justificada 16.2, anti-abuso de activa duplicada, rondas requeridas 16.3: 0/1/2/3/4+ por fuerza defensiva con fortificaciones +1, suceso público + invitación al defensor) · **35 responder al asedio** (defensa activa → fase asedio + log por ronda; sin defensor no hay veredicto) · **36 resolver/registrar** (duración mínima 16.3, veredicto con motivo, afiliación mixta + quien manda con histórico fuente `conquista`, **suspensión de tiendas del anterior dueño** 16.6 vía `ope7_tiendas_suspender_en_isla`, estado ganada/perdida) · **37 reconquista** (mismas cinco fases, exige conquista previa). **Unidades/hordas (16.7)**: contrato + mantenimiento por ronda, máx 4 por bando, más de 2 exige rango alto (D4.8: cúspide o nv≥30), horda una vez por asedio; crones `ope7_conquista_mantenimientos()` y `ope7_conquista_abandonos()` (16.5: 2 rondas → propuesta, 3.ª → revuelta con motivo). **Panel staff «Conquista»** (A.3): página `conquista-staff.php` (scope `ope-pg-conquista-staff`, card en Zona Staff) con `ope7_conquista_panel_html()` — activas por isla con fases/rondas/ejércitos + histórico con motivo. Migración idempotente (columnas de conquista + `conquista_id`/`bando` en unidades + `conquista_id`/`contratada_por` en hordas). Bug MyBB null→'' en `conquistas.zona_id`/`defensor_id` → SQL crudo. Tests `test-7seas-f4.php` 146/146 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. **Barcos — trámites 39–44** (F4.3, 5.17/cap. 18): módulo `sistemas/barcos.php` — **39 compra** (primer barco gratis, precio N1 + madera por clase 18.5 ×0,5/×2/×3/×5 +25 % mano de obra, madera mínima del tipo, acorazado solo con imperio D4.10) · **40 construcción** (Carpintero/Astillero + materiales) · **41 mejora** N1→N2→N3 (diferencia + madera, un paso a la vez) · **42 módulos** (ranuras del tipo/nivel + requisitos de oficio, instalar/quitar) · **43 reparación** (grados leve/moderado/grave con materiales + log `reparaciones`) · **44 venta/desguace** (50 % D4.9, fuera de flota) + `ope7_barco_hundir()` con suceso «naufragio`. Ficha 18.2 (`ope7_barco_crear`) y espacio por raza 18.3 (Tontatta 0/Gigante 5). **Panel staff «Barcos»** (A.3): página `barcos-staff.php` (scope `ope-pg-barcos-staff`, card en Zona Staff) con `ope7_barcos_panel_html()` — flota, catálogo de módulos, reparaciones. **Bug real heredado corregido (navegación y barcos):** `madera_minima` es el nombre de la madera, no un mar — el límite de madera mínima nunca se disparaba. ENUM `barcos.estado` ampliado con `vendido` (migración idempotente). Tests `test-7seas-f4.php` 175/175 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK.
- **Pendientes abiertos:** retirada de `mybb_rol_*` (post-F1, decisión del usuario) · efectos «al publicar» por trámite fuera de 1–12, 62 y 15–18 (F4.3–F6: 19–44, 46–61, 63–67) · hooks de A.2 progresivos (F1–F6) · Anexo B del Staff desactualizado con 7 de 8 skills (anotado, no corregido) · vincular `ope_temas.tid` al thread MyBB real (F4.3, decisión D1.8) · efectos de mundo del trámite 62 (cartel/facción/suceso) cuando exista F4 (D2.2) · el editor real MyBB aún no inyecta el panel Zona B en producción (verificado por hooks; falta QA visual con XAMPP) · F4.3+: trámites 25–33 (bajo mundo) con su panel de A.3. (19–24 facciones ✅ · 38 navegación ✅ · 34–37 conquista ✅ · 39–44 barcos ✅ hechos.)

## 12. Definición de done

Checklist completo en el §2 (arriba). Hitos verificables por fase en el maestro (`§11`) y en la tabla del §8.

---
*Documento vivo — se actualiza al cierre de cada fase y de cada sesión.*
