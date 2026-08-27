# Registro de decisiones — One Piece: 7 Seas (motor Eternal)

> Acta del **modo guiado**: toda decisión consecuente tomada por el usuario se anota aquí (fecha, decisión, opción elegida, motivo, sistemas afectados). También se registran incoherencias detectadas (con propuesta, **sin** aplicar cambios a reglas) y el mapeo de la BD existente al Anexo A.
>
> Regla: **léeme antes de trabajar** (junto al SUPER AGENTS.md). Si una decisión del usuario rompe la coherencia con un manual, se señala aquí y se documenta la resolución.

---

## F0 — Fundaciones (2026-08-27)

### D0.1 · PLAN-MAESTRO-ONE-PIECE-ETERNAL.md obsoleto
- **Decisión:** **Borrar el archivo** `docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md`.
- **Opción elegida:** Borrar (el documento de la era Granblue — Cielos flotantes, Pactos Primordial, Monedas, codename `gbe` — no corresponde a 7 Seas).
- **Motivo:** El usuario prefiere no arrastrar documentación obsoleta; queda en el historial de git.
- **Sistemas afectados:** Documentación del repo. **Nota:** queda prohibido reintroducir `gbe_`, `gbe-`, Granblue, GBF, I-Forge o iforge (regla del repo, se mantiene).

### D0.2 · Ubicación del SUPER AGENTS.md
- **Decisión:** **Raíz del repo — `AGENTS.md` fusionado**.
- **Opción elegida:** Un solo maestro en la raíz que fusiona las instrucciones de código existentes con el sistema de reglas (índice del §3.3 del maestro).
- **Motivo:** Cualquier agente que abra el repo lee primero el maestro completo.
- **Sistemas afectados:** Documentación operativa del proyecto.

### D0.3 · Tablas existentes vs. Anexo A
- **Decisión:** **Recrear las tablas del Anexo A desde cero** como esquema canónico nuevo.
- **Opción elegida:** Se deja de lado el esquema anterior (`mybb_rol_*`, ~55 tablas, JSON pesado, incl. `mybb_rol_tiradas` con dados) y se crea el esquema del Anexo A como migración única **con prefijo `mybb_ope_*`** (decisión D0.3-bis).
- **Matiz aplicado (no destructivo):** las tablas viejas **no se borran ni se renombran** en F0 — el código anterior (stubs conservados, D0.4) las sigue usando. El esquema nuevo convive en paralelo; la retirada del viejo se decidirá en una fase posterior con el usuario.
- **Motivo:** El manual es la ley (Anexo A.1) y el esquema viejo no se le parece; recrear desde cero evita arrastrar decisiones de la era anterior (incluido el sistema de dados, que el principio 1 prohíbe).

### D0.3-bis · Prefijo de las tablas nuevas
- **Decisión:** **`mybb_ope_*`** (p. ej. `mybb_ope_tramites`, `mybb_ope_personajes`).
- **Motivo:** Sigue la convención MyBB (`mybb_`), evita colisión con las `mybb_rol_*` conservadas y es coherente con la marca `ope_`. El código usará un prefijo configurable.
- **Sistemas afectados:** Todo el esquema nuevo (Anexo A.1).

### D0.4 · Stubs `inc/ope_rol_*.php` (17 archivos)
- **Decisión:** **Conservar como redirección** de compatibilidad.
- **Motivo:** Las páginas y scripts de la era anterior los referencian; se mantienen intactos y se retiran solo cuando su uso desaparezca.
- **Sistemas afectados:** Motor `ope_rol` (compatibilidad).

### D0.5 · Modelo de permisos de la zona staff
- **Decisión:** **Reutilizar `mybb_rol_cuentas`** (staff_level 0–3 + flag `staff_narrador` + `staff_rol` por personaje) como base de la capa de permisos, envuelta en helpers nuevos del motor 7 Seas (`ope7_*`).
- **Motivo:** Ya existe, el staff la conoce y evita tablas nuevas duplicadas. Roles: **staff** (rank ≥ 1 o bypass admin), **narrador habilitado** (`staff_narrador=1`), **jugador** (resto).
- **Sistemas afectados:** Seguridad de paneles, `secretos_json` y campos solo-staff (veracidad de rumores, `npc_primario`).

### D0.6 · Alcance de los «seeds de catálogos base» en F0
- **Decisión (aplicada, reversible):** En F0 solo se siembra lo que el motor de trámites necesita: el **catálogo de 67 trámites como datos** (PHP, no BD), la configuración de economía (1 fila) y el registro de skills en el catálogo. Los catálogos de contenido (razas, dotes, islas, frutas, Haki…) se siembran **en su fase** (F1–F5), como indica el mapa de fases del maestro.
- **Motivo:** F1–F5 ya definen sus seeds; sembrar todo en F0 duplicaría trabajo y no aporta al hito de la bandeja.

---

## F1 — Personaje (2026-08-27)

### D1.1 · Puntero de personaje activo (rol/ope)
- **Decisión:** **`personaje_tabla` en `mybb_rol_cuentas`** (`ENUM('rol','ope')` junto a `personaje_activo`) + resolver `ope7_pj_activo()`/`ope7_pj_set_activo()`.
- **Opción elegida:** Columna nueva en el puntero existente (migración mínima, datos viejos intactos).
- **Motivo:** `personaje_activo` (int) es ambiguo entre `mybb_rol_personajes` (era anterior) y `mybb_ope_personajes` (esquema Anexo A); los personajes nuevos conviven con el ecosistema actual sin romper nada.
- **Sistemas afectados:** Personajes, sesión (`ope_active_pid`), ficha, plugin.

### D1.2 · Orden del wizard de creación
- **Decisión:** **El del maestro**: razas/tribus → atributos (120) → dotes/defectos (balanza 0) → dominios (2 puntos) → rasgos (balanza 0) → técnica inicial → trámite 3.
- **Motivo:** La raza primero porque sus modificadores suman por encima del reparto; cada paso valida sobre lo anterior.

### D1.3 · Siembra de catálogos en F1.0
- **Decisión:** **Cerrados completos + técnicas mínimas**: razas (11), raciales (20), tribus (7), dominios (6+12), especializaciones (24 ramas), dotes (28), defectos (13), rasgos (24) y el catálogo de efectos de técnicas (15) sembrados del manual; las técnicas de personaje entran por trámite 13 (ver D1.4).
- **Motivo:** Los catálogos cerrados son la base del wizard y de la validación; el seed es idempotente por nombre (ids conservados).

### D1.4 · Técnicas iniciales: NO son gratuitas
- **Decisión:** **No existe técnica gratuita al crear** (respuesta libre del usuario: «no, no hay técnica gratuita»).
- **Opción elegida:** La idea de técnica inicial viaja como **nota del trámite 3**; la técnica se construye después por el **trámite 13** pagando PP (T1 = 60 PP).
- **⚠️ Incoherencia señalada (no corregida):** el Manual del Jugador §4.5 dice «empiezas con técnicas tier 2» (Opción B del especialista), lo que sugiere técnicas iniciales al crear. Con esta decisión un personaje nuevo (0 PP) no puede tener técnica hasta tramitar la 13. Documentado; el usuario decide con conocimiento.
- **Sistemas afectados:** Wizard (paso 7), trámites 3 y 13, economía de PP.

### D1.5 · Filtro `get_input` corregido (INPUT_INT = 1)
- **Decisión (aplicada, técnica):** En MyBB, `MyBB::INPUT_INT = 1` (no 2, que es `INPUT_ARRAY`). Se corrigieron los usos de enteros en `crear-personaje.php` y `bandeja.php`.
- **Motivo:** Bug latente: `get_input(x, 2)` sobre un escalar devuelve `array()` → `(int) 0`; la bandeja no habría procesado firmas ni el wizard sus atributos.

### D1.6 · Ligeros automáticos bloqueados → trámite RECHAZADO (no publicado en falso)
- **Decisión (aplicada):** Si la validación de un ligero 100 % automático (4, 45, 50) falla (cronómetro, techo, PP insuficientes, datos incompletos), el trámite **no se publica**: queda `rechazado` con el motivo de la validación y el solicitante lo ve en su hub.
- **Motivo:** Antes el ligero se publicaba igualmente sin aplicar efectos (estado engañoso). La regla de oro se mantiene: la validación decide, la firma no existe en este flujo.

### D1.7 · Estados del ciclo con usuario en el ENUM de `ope_tramites`
- **Decisión (aplicada):** `revision_usuario` y `aceptado_usuario` entran en el ENUM `estado` (ALTER idempotente en `migrate-7seas-esquema.php`) y el anti-duplicado del motor cubre también esos estados (un jugador no abre otro trámite 3/13 mientras el suyo espera su aceptación).
- **Motivo:** Sin ampliar el ENUM, guardar el resultado del ciclo truncaba la columna (Data truncated).

### D1.8 · `ope_temas.tid` AUTO_INCREMENT (el hilo MyBB real se vincula en F4)
- **Decisión (aplicada):** `tid` pasa a AUTO_INCREMENT (sigue siendo PK e identificador de tema; los participantes lo referencian). El thread real del foro se vinculará al registro en F4 (el jugador postea el hilo aparte).
- **Hallazgo técnico (corregido):** En MyBB `insert_id` es **método** (`$db->insert_id()`), no propiedad; `insert_query()` ya devuelve el id. El efecto de apertura de tema usaba `$db->insert_id` (propiedad → null) y creaba participantes con `tema_id = 0`. Corregido.

---

## F2 — Combate (2026-08-27)

### D2.1 · Integración de la Zona B (posteo en dos zonas)
- **Decisión:** **Panel JS bajo el editor MyBB** (hooks `newreply`/`newthread`): un panel compone las cartas del turno en vivo (técnicas de la ficha, consumibles, estados, modificadores), el botón **valida con aviso (nunca bloquea)** y publica la Zona B como bloque bajo el post + persiste `turnos_combate`.
- **Opción elegida:** La recomendada (A) — la experiencia más fiel al manual 11.12 (el rival lee la Zona B y actúa en consecuencia).
- **Sistemas afectados:** Plugin (hooks de posteo), `turnos_combate`, `sala_combate`, editor MyBB.

### D2.2 · Alcance del trámite 62 (muerte) en F2
- **Decisión:** **Núcleo completo + efectos de mundo como esquema**: veredicto del umbral (PV ≤ −(VOL×2) o PE ≤ −RES), banda de calidad (descuidada/digna/leyenda), ficha → reliquia, herencia (PP 60→1.000 · berries 5.000→1M ×0,5/×1/×1,5) y fruta renace con su tabla. Cartel retirado, baja de facción y suceso de ronda quedan **anotados como efectos de esquema** que se aplican cuando F4 exista.
- **Opción elegida:** La recomendada (A).
- **Motivo:** La muerte es el cierre del cap. 11 y su núcleo es transversal (reliquias/herencia); los efectos de mundo dependen de tablas F4 que aún no existen.

### D2.3 · Firma del veredicto de combate
- **Decisión:** **Veredicto independiente firmable en el panel de resolución** (histórico propio por tema); el trámite 2 (cierre de temas) **lo referencia** al computar PP/karma/fama.
- **Opción elegida:** La recomendada (A).
- **Motivo:** Respeto la separación del manual (resolución al cierre, firma staff ↔ IA) sin mezclar el veredicto con la economía del cierre.

### D2.4 · Firmas de resolución en `sala_combate`
- **Decisión (aplicada, reversible):** La firma del veredicto de combate se guarda en columnas propias de `ope_sala_combate` (`resuelto_por`, `resuelto_fecha`, `nota_resolucion`) + `resoluciones_combate` como histórico de bandas (salud del foro).
- **Motivo:** La banda de delta es un dato agregado del foro (se muestra en el panel); el detalle del veredicto queda en `turnos_combate.veredicto` (JSON).

### Hallazgos técnicos F2 (corregidos)
- **Hook de posteo recibe el `PostDataHandler` (objeto), no un array:** `ope7_zonab_on_post` accedía `$post['message']` → fatal al postear el bot (F0) o cualquier hilo. Corregido normalizando objeto/array y resolviendo el personaje por `uid` (activo) en vez de esperar `ope_pid` en el payload.
- **`ope7_resolucion_firmar` no exigía motivo** (solo lo validaba la página): ahora la función lo rechaza directamente (defensa en profundidad; el test lo cubre).

### Cierre F2 (2026-08-27)
- Motor de combate `inc/ope_rol/sistemas/combate.php` (PA, daño, bandas, Tablas 1–3, choque, umbral del dolor, matices, estados I–III, 1vN, sala, resolución de cierre) + seeds cerrados (24 acciones · 34 estados · 10 matices) · trámites 13 y 62 con efectos (técnica → librería + cupo INT/4 + PP por tier; muerte → reliquia + herencia + fruta renace) · herencia reclamable desde el wizard · Zona B (panel bajo el editor, avisos que nunca bloquean, persistencia en `turnos_combate`/`sala_combate`) · panel `resolucion-combate.php` con firma e histórico · enlace desde Zona Staff · tests 66/66 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK.

---

## F3 — Progresión y economía (2026-08-27)

### D3.1 · Ejecución del calendario on-roll
- **Decisión:** **Avance perezoso en hook** (`global_start`): `ope7_calendario_avanzar()` idempotente por día real — con el primer visitante del día avanza +2 días on-roll (ratio 1=2) y registra el avance en `calendario_foro.avances`. Sin tarea MyBB ni cron externo (InfinityFree no garantiza cron real).
- **Opción elegida:** La recomendada (A).
- **Sistemas afectados:** `calendario_foro`, hook `global_start`, trámite 1 (ancla).

### D3.2 · Alcance de tiendas en F3
- **Decisión:** **Núcleo completo con zona libre**: apertura/cierre/reapertura/reposición/boletín de precios + compra/venta con registro de transacciones y anti-abuso, usando la zona como texto libre (zona por defecto: `mundo abierto`, `zona_id=0`). F4 conecta el catálogo de las 17 islas a `precios_mercado.zona_id`.
- **Opción elegida:** La recomendada (A).
- **Sistemas afectados:** `tiendas`, `tienda_items`, `transacciones`, `precios_mercado`, `carteras`, trámites 15–18.

### D3.3 · Fecha inicial del calendario
- **Decisión:** El mundo **arranca hoy**: `fecha_foro_actual` = fecha real del día de la migración (semilla solo si la tabla está vacía); desde ahí avanza ×2. **Reinicio posible a futuro**: borrar la fila de `calendario_foro` y re-ejecutar la migración (idempotente).
- **Opción elegida:** La recomendada (A), con el matiz del usuario: reiniciable.

### D3.4 · Dónde vive el inventario del jugador
- **Decisión:** **Bloque «Equipo y cartera» dentro de la ficha 7 Seas** (entre Técnicas y Solo-staff): ranuras usadas (equipado `3+FUE/10`, mochila `8+FUE/4`, Tontatta ×2), objetos, almacén, cartera/bóveda. Sin página nueva; el hub `tiendas.php` cubre comprar/vender.
- **Opción elegida:** La recomendada (A), según la estructura ya existente de la ficha.

### D3.5 · Corrección del flujo de reserva al manual (7.3)
- **Hallazgo (corregido):** F1 sumaba el bloque a `reserva` **al comprar** y nunca lo colocaba. El manual dice «al terminar los puntos entran en tu reserva para colocarlos donde quieras». Nuevo flujo: compra → PP + cronómetro (reserva intacta) · al vencer → `ope7_pj_finalizar_entrenamientos` mete el bloque en reserva y cuenta para el nivel (10 → +1 con arrastre) · `ope7_pj_colocar_reserva` los coloca respetando el techo.

### Hallazgos técnicos F3 (corregidos)
- **`puntos_comprados` mal inicializado:** el wizard escribía 120 (presupuesto de creación) en una columna que el manual define como «acumulado desde el último nivel» — el primer entrenamiento completado disparaba saltos de nivel masivos. Ahora los nuevos PJs nacen con 0 (7.1).
- **`ope7_tramite_firmar` publicaba en falso:** los efectos con `BLOQUEADO`/«datos incompletos» se ignoraban al firmar (solo `crear` los honraba para ligeros). Ahora la firma rechaza con el motivo (D1.6 extendido a IA/staff — trámites 6, 15, 18…).
- **Producción (efecto 6) escribía `zona='almacen'`** en `inventario_personaje`, cuyo ENUM no lo incluye (bug F0 latente): ahora escribe en la tabla `almacen` (9.7).
- **Apertura de tienda no descontaba el stock** del almacén del dueño (10.6: «el stock sale del inventario real»): ahora sí.

### F3.6 · Panel staff «Calendario» (Anexo A.3)
- Página `calendario-staff.php` (scope `body.ope-pg-calendario`, card en Zona Staff): fecha on-roll actual con histórico de avances, presentes activos con su ancla y jugadores congelados (y aviso si falta la instantánea), histórico de aperturas/cierres (últimos 20 temas) y **avisos de coherencia** (pasados anclados en el futuro · presentes con duración on-roll > ventana real ×2).
- **Correcciones colaterales:** (a) el efecto 1 **acepta la fecha declarada de los pasados** (7.5: ≤ actual on-roll, futuro → bloqueado) — antes anclaba todo en hoy; (b) **bug F0 latente:** `ope7_tramite_crear` aceptaba el 6º argumento (resultado inicial) pero nunca lo guardaba (`resultado_json => null`) — el wizard y los ligeros perdían los datos; ahora se persiste.
- Verificado por test (bloque [4b]): panel con presente activo + congelado, pasado futuro bloqueado, pasado coherente anclado en su fecha, histórico de cierres. 41/41 + batería completa.

### D4.1–D4.4 · Alcance de F4 (Mundo Vivo)
- **D4.1 Sub-fases:** F4 se entrega por piezas verificables. **F4.1 = semilla del mundo + ronda + panel**; los trámites 19–44 (facciones, bajo mundo, conquista, navegación, barcos) se montan en sub-fases posteriores con su semilla y sus efectos.
- **D4.2 Ronda mensual:** motor de ronda (crear/cerrar, cola de presentes, aplicar lo firmado: islas con motivo, recompensas, precios dentro de banda, periódico en borrador) + **panel staff «Mundo Vivo»** (dashboard, cola de análisis, matriz de islas). La IA propone (skill-mundo-vivo), el staff firma; la visibilidad del periódico es manual (nada se publica solo — 15.2).
- **D4.3 Trámites 19–44:** cada sub-fase lleva su semilla y sus trámites con efecto real. Los que dependen de IA quedan con prompt + firma (el motor ya lo hace).
- **D4.4 Tienda ↔ isla:** la zona de la tienda pasa a ser **su isla del catálogo** (5.14): apertura exige isla + territorio (ubicación del PJ) + Comerciante; `zona_id` = isla; el precio de venta se ancla al mercado **de su zona** (10.4); al cambiar de manos el territorio (conquista, F4.2), `ope7_tiendas_suspender_en_isla()` suspende las tiendas del bando anterior (5.15/16.6). Resuelve el pendiente D3.2.
- **Hallazgo técnico F4.1:** el esquema de `tipos_barcos` tenía `precio` BIGINT para 3 precios (N1–N3); se añade `precios` JSON con el array completo (5.17) manteniendo `precio` = N1. Además, `oraculos_catalogo` y `transportes` no tienen columna `nombre` (clave por `tipo`) — el seed los trata aparte.

### Cierre F3 (2026-08-27)
- Calendario on-roll `sistemas/progresion.php` (semilla = hoy reiniciable, avance perezoso ×2 idempotente en hook `global_start`, histórico de avances) + finalización de entrenamientos y colocación de reserva · **skill-cierre-temas** (fórmula Base×7 factores con bandas cerradas, techo/suelo, redondeo a favor del jugador, banda de tiempo ampliada; el trámite 2 referencia el veredicto de combate D2.3 y libera la congelación `salio_en`) · catálogo `objetos` sembrado (34: armas por calidad, armaduras/escudos, consumibles, herramientas, diales, rarezas) + `economia_config` · ranuras/carga y equipar con cupos Meitou (trámites 6 y 14) · cartera al crear PJ + movimientos cartera/bóveda · **tiendas de jugador** (15–18: apertura con Comerciante/local/capital/bélicos, cierre/reapertura, reposición, boletín con banda 0,5×–2×) + compra/venta con transacciones y anti-abuso · `tienda-general.php` y `tiendas-personales.php` funcionales · bloque «Equipo y cartera» en la ficha · tests 36/36 + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK.

### F4.2-bis · Bloque «Reserva de puntos» en la ficha (7.3)
- La ficha 7 Seas (`ope7_ficha_html`) muestra, para el dueño/staff, un panel **«Reserva de puntos»** entre «Equipo y cartera» y «Solo staff»: puntos pendientes en la cabecera, **stepper por atributo** (botones −/+, input numérico) con el **techo del nivel** (`ope7_pj_techo_atributo`), máximo por atributo (techo − valor actual) y suma live en JS (rojo si supera la reserva; botón deshabilitado si no hay distribución válida). El botón envía `gaccion=reserva` → `ope7_pj_colocar_reserva()` en `ficha.php` (rama 7 Seas, verificado con `verify_post_check`), con flash de éxito/error y redirect. Sin reserva, el bloque muestra el aviso de que los puntos entran al terminar el entrenamiento (7.3).
- Verificado por test (bloque [4c]): bloque con steppers, techo 20 (nv1), máximos por atributo, suma live y form POST · 47/47 + batería completa.

### F4.3 · Cronómetro de dominios (5.3/4.4 + D4.5)
- **Compra de dominio** por el trámite 4 (ligero automático, rama `dominio_id`+`nivel`): adquisición nueva (nv2–nv5, nivel mínimo de PJ 10/20/35/45, cupo INT 1 adicional por 50) o **subida de nivel en nivel** (sin saltos). Coste = base del nivel objetivo × multiplicador anclado (D4.5). Descuenta PP, registra el gasto en `historico_pp` con concepto («Compra de dominio: «X» → nv2 (×1,50)») y arranca el **cronómetro de 15 días** (independiente del de atributos, 4.4; un solo dominio a la vez). Al vencer, `ope7_pj_finalizar_dominios()` (integrado en `ope7_progresion_cron`) sube al nivel objetivo y limpia; notifica al jugador.
- **UI:** bloque de Dominios de la ficha 7 Seas ampliado — cronómetro en curso visible («entrenando → nvN», termina fecha) + formulario de compra/subida para el dueño (select `did:nivel` con el coste y el multiplicador anclado por dominio, `gaccion=dominio` en `ficha.php` con `verify_post_check`, flash + redirect). **Panel staff «Progresión»**: los cronómetros de dominio aparecen en el bloque de cronómetros junto a los de atributos.
- Verificado por test (bloque [4d]): subida de creación ×1,0 = 60 PP, cronómetro → nv2, al vencer sube y limpia, 1.º adicional ×1,5 = 90 PP con cupo INT 1/1, 2.º adicional bloqueado por cupo, gasto −90 con concepto en el libro, panel y ficha muestran el entrenamiento · 58/58 + batería completa.

### F4.3 · Trámite 38 — Navegación/travesía (5.16/17)
- **Módulo `inc/ope_rol/sistemas/navegacion.php`** (trámite 38, naturaleza `ia`, registrado en bootstrap y en el motor):
  - **Validación (17.2):** origen = `personajes.ubicacion_isla_id` (no editable) · un-presente (5.6, la travesía ES el presente) · destino del catálogo 5.14 (≠ origen) · barco de la flota o transporte con pago desde cartera · utensilio declarado debe estar en inventario/almacén.
  - **Límite de mar por barco + madera (18.5):** la madera del casco habilita los mares (Pino→Blue, Roble→+Paraíso, Adán→+NM, Eva→+ZR) y el tipo exige su `madera_minima`. Ruta = tramos por región (Blue→Paraíso = 72+48 h; Blue→NM cruza Paraíso = 3 tramos).
  - **IRT interno (17.3):** base del mar (1–4) + peligrosidad del destino (1–50: 0/+1/+2/+3) + estado del Mundo Vivo (0–2, techo; propuesta staff, dato estructurado pendiente) − mitigadores (Navegante nv1 −1/nv2 −2, Timonel nv3+ −1, Cartógrafo nv4 −1, barco −1 a −3, utensilio −1). Solo-staff, nunca se publica.
  - **Oráculos (17.4):** generación determinista por banda del IRT (0–2 tranquila → 0–1 menor · 3–5 → 1–2 · 6–8 → 2–3 con grave · 9+ → 3+ con daño asegurado); si el staff propone lista, se valida contra el catálogo y la banda. Transporte = ruta segura (máx 1 menor, 17.6).
  - **Tiempo off-roll (17.5):** 72/48/36/36 h por tramo sumables −12 h utensilio −25 % Maestre + horas de incidentes +24 h transporte = plazo real del tema.
  - **Víveres (17.6):** 1 ración/persona/día on-roll +1/+2/+3 por oráculo; se consumen del inventario/almacén al cierre; sin stock → veredicto empeora.
  - **Cierre (17.6):** integrado en el efecto 2 — consume raciones, aplica el daño al barco por grado máximo de los oráculos, `ubicacion = destino` y resuelve la travesía con `incidentes_travesia` logueados.
  - **Vencimiento (17.5):** `ope7_travesias_vencidas()` en el cron — plazo agotado (tema `fecha_real_apertura + tiempo_disponible_h`) → travesía `vencida` y tema cerrado.
- **Seed:** 4 utensilios (Brújula Común, Log Pose, Eternal Pose, Log Pose del NM — 17.7) en `seed-7seas-progresion.php` (34 → 38 objetos, idempotente).
- **Hallazgos:** (a) bug MyBB null→'' en INT (conocido F4.1) reproducido en `travesias` — los opcionales van como 0 en `insert_query`; (b) **incoherencia de número sagrado:** el manual 17.6 dice ración 50 ฿ pero el seed la tiene a 100 ฿ — **anotado, pendiente de consulta** (principio 8); (c) el «+1 si la ruta cruza zona en guerra» del IRT queda como hook (0 por defecto) hasta que el Mundo Vivo tenga el dato estructurado; (d) la matriz 5.14 en el prompt de la skill (17.8) queda como enriquecimiento pendiente (el prompt 38 ya pasa origen/destino/barco/acompañantes/utensilio).
- Verificado por test (bloque [4e], 25 checks): un-presente bloquea · Blue→Blue con Bote de pino OK · IRT 2 → 1 menor · utensilio mitiga −1 IRT y −12 h (IRT 1, 0 oráculos, 60 h) · Pino/Roble bloquean Paraíso/NM · Roble→Skypiea 144 h (media +24) · cierre: 12 raciones consumidas, barco dañado moderado, ubicación = Skypiea · Adán→Dressrosa 120 h (2 oráculos) · vencida por plazo · transporte civil (pago, +24 h, ruta segura) · 83/83 + batería completa verde.

### F4.3 · Facciones — trámites 19–24 (5.12/13) + panel «Facciones» (A.3)
- **Pregunta (D4.7, 13.3/13.4):** dos números/flujo de facciones. **Decisión:** (a) **cupos de cúspide por facción** (recomendados del manual): Emperador del Mar 4, Almirante 3, Director de sección 5, Estado Mayor 5, Señor del bajo mundo 3, Leyenda 5, Cabeza de casa noble 6; Shichibukai 7 (13.2); resto de rangos sin cupo; (b) **`rep_min` duro** en los rangos (requisito que bloquea el ascenso si no se alcanza, no solo sugiere).
- **Migración:** `faccion_personaje` gana `infiltracion_faccion_id`/`infiltracion_rango_id`/`infiltracion_activa` (capa oculta, 13.7/13.8) · seed: `cupo` por rango y `requisitos` con `rep_min` en la cúspide.
- **Módulo `inc/ope_rol/sistemas/facciones.php`** (trámites 20–24, naturaleza `ia`):
  - **20 · Ascenso (13.4):** sin saltos (solo el inmediato siguiente) · requisito duro `rep_min` · **cupo del rango** (espera de cupo si lleno/sobrecupo) · termómetro propuesto por la skill en el mensaje · histórico inmutable (`cambios_faccion`).
  - **21 · Subfacción élite (13.2/13.8):** Shichibukai (cupo 7) concesión/revocación; revocación por romper condiciones → Wanted ×1,5 (5.13). Gorosei es NPC (bloqueado).
  - **22 · Cambio de facción (13.7):** exige baja de la actual, entra por el rango inicial, anti-abuso (un personaje por facción por jugador, 13.7).
  - **23 · Deserción (13.7):** hostil → criminal (Wanted piso 5M + infamia +1, 5.13); pacífica → Aventurero libre.
  - **24 · Infiltración (13.7/13.8):** capa oculta — visible = facción tapadera, real = `infiltracion_*` (staff); revocación restaura la lealtad real. Solo el staff inicia/infiltra.
- **Panel staff «Facciones» (`facciones-staff.php`, scope `body.ope-pg-facciones-staff`, card en Zona Staff)**: `ope7_facciones_panel_html()` — tablero de rangos por facción con cupos y ocupación, cúspide marcada, Shichibukai activos, histórico de cambios inmutable, Wanted por personaje.
- **Hallazgos:** (a) bug MyBB null→'' en INT (conocido) al restaurar `cupo => null` en el test — SQL crudo; (b) **bug real corregido en `ope7_faccion_cupo_libre`**: devolvía −1 tanto para «ilimitado» como para «sobrecupo» (cupo 1 − 2 ocupantes = −1), y el efecto solo bloqueaba `=== 0` — ahora nunca devuelve negativo (lleno/sobrecupo = 0 plazas).
- Verificado por test (bloque [4g], 22 checks): ascenso bloqueado sin rep mínima · espera de cupo con cupo lleno · ascenso OK con termómetro y histórico · cambio Marines→Piratas entra Novato con baja+alta · anti-abuso de duplicado · deserción hostil → criminal (Wanted 5M + infamia) · infiltración con capa oculta y revocación · Shichibukai cupo 7 concesión/duplicado/revocación (Wanted 7,5M) · panel renderiza tablero/élite/histórico sin inline styles · **109/109 + batería completa** (idempotente, 2 corridas).

### F4.3 · Conquista — trámites 34–37 (5.15/cap. 16) + panel «Conquista» (A.3)
- **Módulo `inc/ope_rol/sistemas/conquista.php`** (trámites 34–37, registrado en bootstrap y motor):
  - **34 · Anuncio (16.2/16.3):** valida el **control previo** (salvaje → 0 rondas: declarar + ocupar · guarnición → asedio por fases · territorio de jugador → disputa con defensor invitado), la **presencia justificada** (16.2: `ubicacion_isla_id` o texto que menciona la isla) y el objetivo (isla o zona del catálogo 5.14); **anti-abuso** (no hay conquista activa duplicada sobre la misma isla/zona); calcula las **rondas de asedio requeridas** (16.3: salvaje 0 · nv1–15: 1 · nv16–30: 2 · nv31–45: 3 · nv46–50: 4+; fortificaciones +1) y publica el **suceso público** (hook anuncio → `sucesos` tipo conquista).
  - **35 · Responder al asedio (16.4, ligero):** el defensor declara su defensa activa (guarnición NPC o personaje); pasa a fase `asedio` y crea el **log de asedio por ronda** (`asedios`: acciones, desgaste, veredictos). Sin participación del defensor no hay veredicto.
  - **36 · Resolver/registrar (16.8, staff):** verifica la **duración mínima** (16.3: nunca se resuelve en la ronda del anuncio, salvo salvaje); si gana el atacante: `ope7_isla_actualizar` (afiliación → mixta, fuerza defensiva, quien manda) **con motivo (fuente `conquista`)**, **suspensión de tiendas del anterior dueño** (16.6 → `ope7_tiendas_suspender_en_isla`, el anzuelo D4.4), estado `ganada` + `ganador_id` + `resuelta_ronda`, suceso y periódico/rumores; si gana el defensor: `perdida`.
  - **37 · Reconquista (16.5):** nueva disputa con las mismas cinco fases; exige una **conquista previa** sobre la isla (anti-abuso); sin bonus artificiales para el defensor.
- **Ejércitos y hordas (16.7):** `ope7_conquista_contratar_unidad` (Infantería 10.000/1.000 · Élite 50.000/5.000 · Especialistas 25.000/2.500 — contrato/mantenimiento por ronda; **máx 4 por bando**; más de 2 exige rango alto, D4.8) y `ope7_conquista_contratar_horda` (Mara 10.000 · Masa 50.000 · Marea 200.000; **una sola vez por asedio**; las genera el Mundo Vivo o un bando). **Crones:** `ope7_conquista_mantenimientos()` (sin pago → retirada, 16.7) y `ope7_conquista_abandonos()` (16.5: 2 rondas sin actividad → propuesta; 3.ª → revuelta aplicada con motivo, afiliación → local) integrados en `ope7_progresion_cron`.
- **Panel staff «Conquista» (`conquista-staff.php`, scope `ope-pg-conquista-staff`, card en Zona Staff)**: `ope7_conquista_panel_html()` — conquistas activas por isla (objetivo, fase, rondas requeridas/transcurridas, atacante/defensor, ejércitos con mantenimiento), histórico con motivo (ganada/perdida/abandonada) y aviso del flujo.
- **Migración:** columnas `ganador_id`/`motivo`/`resuelta_ronda`/`ultima_actividad_ronda` en `conquistas` + `conquista_id`/`bando` en `unidades` + `conquista_id`/`contratada_por` en `hordas` (idempotente).
- **Hallazgos:** (a) bug MyBB null→'' en INT (conocido) al insertar `conquistas.zona_id`/`defensor_id` NULL → SQL crudo en el insert; (b) el catálogo tenía 34 como `quien=jugador` + `naturaleza=staff` (incoherente con el flujo del manual: lo inicia el jugador y firma el staff) — se mantiene la naturaleza `staff` (solo el staff firma); (c) el test pilló que tras deserción el PJ queda como cúspide de «Aventurero libre» (único rango → `es_cuspide`) — detalle del seed, no bug.
- **D4.8 (confirmado por el usuario):** «más de 2 unidades exige rango alto o un imperio» (16.7) → cúspide de facción (`es_cuspide`) **o** nivel de personaje ≥ 30 (imperio en ciernes).
- Verificado por test (bloque [4h], 37 checks): rondas requeridas (salvaje 0 · gobierno fd14 → 1) · anuncio con suceso público · anti-abuso de activa duplicada · sin presencia bloquea · defensa activa + log de asedio · unidades (contrato, límite rango alto nv1→bloquea, nv30→OK, máx 4) · horda una vez por asedio · resolución con motivo (afiliación mixta, quien manda, histórico fuente conquista, **tienda suspendida**) · duración mínima (mismo día bloquea) · reconquista con previa / bloqueada sin previa · abandono (1 ronda no propone, 2 propone, 3 aplica + afiliación local) · mantenimiento sin saldo → retiradas · panel sin inline styles · **146/146 + batería completa** (idempotente, 2 corridas).

### F4.3 · Barcos — trámites 39–44 (5.17/cap. 18) + panel «Barcos» (A.3)
- **Módulo `inc/ope_rol/sistemas/barcos.php`** (trámites 39–44, registrado en bootstrap y motor):
  - **39 · Compra/adquisición (ligero):** primer barco **gratis** (bote de remos, 18.4) · compra con precio N1 del tipo + **madera por clase** (18.5: ×0,5 botes/balandros/goletas · ×2 corbetas · ×3 galeones · ×5 acorazados; +25 % mano de obra sin Carpintero/Astillero) · verifica la **madera mínima del tipo** (18.4: `madera_minima` = nombre de la madera del catálogo, no un mar) · **acorazado solo con imperio** (18.4, D4.10).
  - **40 · Construcción (ia):** exige Carpintero con rama Astillero (5.3) + materiales; construye a N1.
  - **41 · Mejora N1→N2→N3 (ia):** un paso a la vez (sin saltos ni retrocesos), coste = diferencia de precio + madera; actualiza la ficha (casco/maniobra/ranuras/plazas).
  - **42 · Módulos instalar/quitar (ia):** ranuras del tipo/nivel (18.6), requisitos de oficio (tienda→Comerciante · resina→Astillero nv4 · kairoseki→Mercado Negro · cocina/lab/enfermería→su oficio · velas→Maquinista Naval), pago del módulo; quitar libera la ranura.
  - **43 · Reparación (ia):** grados leve/moderado/grave (18.7) con Carpintero/Astillero + materiales (madera 5.8 por grado); log en `reparaciones`.
  - **44 · Venta/desguace/baja (ligero):** venta al **50 % del valor de compra** (D4.9) o desguace en materiales (50 % de la madera); el barco sale de flota (`estado = vendido`); `ope7_barco_hundir()` (veredicto) → suceso de mundo «naufragio».
- **Ficha 18.2:** `ope7_barco_crear` calcula casco/maniobra/ranuras/cañones/plazas del tipo×nivel; `ope7_barco_espacio_raza` (18.3: Tontatta 0 · Humana/Skypiean/Mink 1 · Lunarian 2 · Oni/Bucaner 3 · Gigante 5); `ope7_barco_danio_canon` (×30–×150).
- **Panel staff «Barcos» (`barcos-staff.php`, scope `ope-pg-barcos-staff`, card en Zona Staff)**: `ope7_barcos_panel_html()` — flota por jugador con ficha y estados de daño, catálogo de 10 módulos con requisitos, log de reparaciones recientes.
- **Hallazgos:** (a) **bug real heredado de navegación (corregido en ambos)**: `madera_minima` del tipo es el NOMBRE de la madera (18.5), no un mar — `ope7_nav_madera_max_nivel` recibía un nombre de mar y el límite de madera mínima nunca se disparaba; ahora se resuelve contra `maderas_casco`; (b) el ENUM de `barcos.estado` no incluía `vendido` (trámite 44) — migración idempotente lo amplía; (c) bug MyBB null→'' en INT no aplicó aquí (los insert usan `dueno_id` entero, sin NULLs).
- **D4.9 (decidido):** venta de barco = **50 % del valor de compra** (mismo ratio que tiendas NPC); desguace = 50 % en materiales (madera).
- **D4.10 (decidido):** comprar un acorazado (`es_faccion_npc`) exige **cúspide de facción o nivel ≥ 30** (misma regla que D4.8 — «un peón no manda un ejército»).
- Verificado por test (bloque [4i], 29 checks): tipos/maderas sembrados · primer barco gratis (cartera intacta) · balandro 50.000 descontados · carabela con pino bloqueada (madera mínima) / con roble OK · acorazado con nv30 OK (D4.10) · espacio por raza (Mink 1 / Gigante 5 / Tontatta 0) · mejora N1→N2→N3 sin saltos ni retrocesos · módulo tienda sin Comerciante bloqueado / instalado 1/1 / sin ranuras bloquea / retirado · daño leve (450/500) → reparación (activo 100 %) con log · venta al 50 % (25.000) y fuera de flota · panel sin inline styles · **175/175 + batería completa** (idempotente, 2 corridas).

### UX · Hub de trámites del jugador (2026-08-27)
- **D4.11 (decidido por el usuario):** cada trámite con página propia → **56 ficheros `tramite-NN.php`** (wrappers que delegan en el nuevo `inc/ope_rol/tramites/paginas.php`; generador idempotente `scripts/generate-tramite-paginas.php`). Los **11 solo-staff** (18, 21, 24, 30, 36, 49, 54, 55, 59, 60, 61) no tienen página: badge en el hub → bandeja.
- **D4.12 (decidido):** catálogo agrupado en **6 áreas** (Personaje y progreso 1–14 · Economía 15–19 · Mundo Vivo 20–37 · Viaje 38–44 · Poderes 45–51/56–61 · Grupos 52–55/62–67) con tarjetas enlazadas y filtros (Puedo iniciar por defecto · Automáticos · IA + firma · Ver todo).
- **D4.13 (decidido):** al pedir un trámite → **ligeros implementados (1, 4, 14, 17) se ejecutan al instante**; el resto crea la solicitud en la bandeja. El motor ahora **no auto-publica ligeros sin efecto** (marcador `[PENDIENTE]` en el mensaje del efecto → trámite a bandeja), y el guard de solo-staff mira `quien` (el 34 lo inicia el jugador aunque su naturaleza sea `staff`).
- Formularios por trámite con opciones dinámicas por personaje (barco, tienda, dominio, objeto, isla, utensilio filtrados por `data-pj`) y nota «qué pasa al pedir».
- Bug real corregido: `stripos('independiente', 'pendiente')` rompía las compras de dominio (subcadena) → marcador explícito. Verificado: 175/175 + batería completa · `check-inline-styles` limpio · `sync-theme verify` OK.

### F4.2 · Panel staff «Progresión» (Anexo A.3)
- Página `progresion-staff.php` (scope `body.ope-pg-progresion-staff`, card en Zona Staff) con `ope7_progresion_panel_html()` en `sistemas/progresion_panel.php`: **cronómetros de entrenamiento** por jugador (bloque 5/10, fin real + días restantes, aviso de que al vencer entran en reserva — 7.3) · **saldos y reservas** (tabla por PJ: nivel, PP saldo, reserva, comprados desde el último nivel, lo que falta para el siguiente) · **gastos de PP por concepto** (agregación del libro `historico_pp`: atributos, dominios, técnicas, cierres) · **histórico reciente** (últimos 25 movimientos con fecha y motivo).
- **Completado del libro de PP:** el efecto 4 (compra de PP, ligero) **no registraba el gasto** en `historico_pp` — ahora registra `cantidad`/`concepto` («Compra de PP: bloque de N en atributo», con `tramite_id` del ligero). El cierre de tema (efecto 2) también completa su fila con `cantidad`/`concepto` para que el panel agregue ingresos y gastos por concepto coherentemente.
- Verificado por test (bloque [4b]): compra registra el gasto con concepto, panel muestra el cronómetro del PJ, el gasto en negativo y la tabla de saldos · 42/42 + batería completa.

### D4.6 · Precio de la ración (17.6) y panel «Navegación» (A.3)
- **Precio de la ración (número sagrado):** el manual 17.6 dice 50 ฿ y el seed la tenía a 100 ฿. **Decisión: 50 ฿ (manual).** `seed-7seas-progresion.php` corregido (idempotente) con nota (D4.6); el ejemplo del manual (4 personas, Blue→Paraíso, 2 medios ≈ 2.800 ฿) cuadra con 50.
- **Panel staff «Navegación» (`navegacion-staff.php`, scope `body.ope-pg-navegacion-staff`, card en Zona Staff)**: `ope7_navegacion_panel_html()` en `sistemas/navegacion.php` — travesías activas por jugador (ruta con regiones, medio barco/transporte, plazo + días on-roll, **vencimiento** real con aviso <48 h, oráculos por tema, víveres al cierre) · aviso del flujo (ficha editable en la bandeja, IRT solo-staff) · histórico de resueltas/vencidas/abortadas con veredicto (víveres, daño al barco, notas).
- Verificado por test (bloque [4f]): panel con la travesía activa (PJ + ruta + oráculos) y la resuelta en el histórico, sin estilos inline · 87/87 + batería completa.

### D4.5 · Multiplicador de dominios adicionales (5.3/4.4)
- **Pregunta (F4.3):** el manual permite dos lecturas del multiplicador de coste de dominios adicionales. **Decisión:** anclado al dominio adicional — ×1,5 el 1.º adicional, ×2 el 2.º+; se fija al adquirir (`coste_mult` en `dominios_personaje`) y aplica también a las subidas de ese dominio. Los dominios de creación (wizard) siempre ×1,0. La creación de personaje no se ve afectada (presupuesto de creación, no de progresión).
- Implementado en `ope7_dominio_mult_adicional()` + `ope7_efecto_compra_dominio()` (trámite 4, ligero, rama dominios): coste = base del nivel objetivo (60/120/240/400) × multiplicador; valida nivel mínimo del PJ (10/20/35/45), cupo INT (1 adicional por 50 INT) y un solo dominio en entrenamiento a la vez (cronómetro independiente del de atributos, 4.4). Arranca el cronómetro de 15 días; al vencer, `ope7_pj_finalizar_dominios()` sube al nivel objetivo y limpia.

### Cierre F4.1 (2026-08-27)
- **Semilla del mundo** (`scripts/seed-7seas-mundo.php`, idempotente): 7 mares · **17 islas del catálogo 5.14** con ficha viva (13 parámetros: peligrosidad, afiliación, fuerza defensiva con quien manda, desarrollo, población, recursos, oferta/demanda, clima/log pose, lugares clave, sucesos, hitos, tesoros, facciones) + histórico de arranque · 34 zonas clave · 8 tipos de barco × N1–N3 · 5 maderas · 10 módulos · 7 oráculos · 3 transportes · **8 facciones jugables con escalera de rangos** (44 rangos).
- **Motor de ronda** (`inc/ope_rol/sistemas/mundo.php`): `ope7_ronda_abrir_siguiente()` · cola de presentes (`ope7_ronda_temas_pendientes`) · `ope7_ronda_cambiar_estado()` (abierta→análisis→cerrada) · **`ope7_ronda_aplicar_cierre()`** aplica lo firmado: cambios de isla con motivo e histórico (`isla_estado_historico`, fuente `ronda`), recompensas (`recompensas_historico`), precios dentro de banda 0,5×–2× (`precios_mercado` con ronda y fecha foro) y periódico **en borrador** (visibilidad manual, 15.2). Helpers de mundo: `ope7_isla_por_slug/id`, `ope7_isla_ficha`, `ope7_isla_zonas`, `ope7_isla_actualizar` (con motivo e histórico).
- **Panel staff «Mundo Vivo»** (`mundo-vivo.php`, scope `body.ope-pg-mundo-vivo`, card en Zona Staff): ronda actual con estado, cola de análisis (temas presentes), matriz de islas con ficha viva y aviso del flujo (skill propone → staff firma → motor aplica).
- **Tienda ↔ isla (D4.4):** apertura exige isla + territorio + Comerciante; `zona_id` = isla; precio anclado al mercado de su zona; `ope7_tiendas_suspender_en_isla()` (la usará la conquista F4.2). `tiendas-personales.php` con selector de isla del catálogo.
- **Tests `test-7seas-f4.php` 36/36** (semilla, ronda completa, cierre firmado con motivo, tienda↔isla, suspensión, panel) + batería completa verde · `check-inline-styles` limpio · `sync-theme verify` OK. *(Con el panel «Progresión» F4.2: 42/42 · con la reserva en la ficha F4.2-bis: 47/47 · con el cronómetro de dominios F4.3: 58/58 · con navegación (trámite 38 + panel + D4.6): 87/87 · con facciones (19–24 + panel + D4.7): 109/109 · con conquista (34–37 + panel + D4.8): 146/146 · con barcos (39–44 + panel + D4.9/D4.10): 175/175.)*

---

## Mapeo BD existente → Anexo A (auditoría F0)

| Tabla vieja (conservada) | Estado | Destino en el esquema nuevo (`mybb_ope_*`) |
|---|---|---|
| `mybb_rol_personajes` (JSON) | Conservada para el código viejo | `mybb_ope_personajes` normalizada (atributos en columnas) |
| `mybb_rol_tramites` (4 estados, sin firma/prompt) | Conservada | `mybb_ope_tramites` + `mybb_ope_tramites_historico` (estados 5.21, firma, prompt, histórico) |
| `mybb_rol_tiradas` (**dados**) | Conservada pero **fuera** del sistema nuevo | No hay equivalente (principio 1: sin dados) |
| `mybb_rol_cuentas` (staff_level, narrador) | Conservada y **reutilizada** para permisos (D0.5) | Sin tabla nueva de permisos |
| `mybb_rol_*` restantes (~50) | Conservadas | Equivalente `mybb_ope_*` según Anexo A.1 por sistema |
| `mybb_rol_mv_*` (mundo vivo v2–v6) | Conservadas | `mares`/`islas`/`isla_estado`/`rondas`… |

**Incoherencias detectadas (sin aplicar cambios a reglas):**
- El Anexo B del Manual del Staff lista 7 skills en su tabla, pero el cap. 21 anuncia «7 skills completas» y el cap. 23 añade la 8.ª (`skill-adaptacion-cibernetica`). El maestro del proyecto habla de **8 skills**; se adopta el catálogo de 8 (el Anexo B queda desactualizado en ese punto — anotado, no corregido en el manual).
- `mybb_rol_tiradas` (dados) existe en la BD vieja: se excluye del nuevo sistema por el principio 1; la exclusión se documenta aquí, no se borra la tabla.

---

## Pendientes abiertos (con dueño y fase)

| Pendiente | Dueño | Fase |
|---|---|---|
| Retirada/archivado de las tablas `mybb_rol_*` cuando el código viejo deje de usarlas | Usuario (decisión) + implementador | Post-F1 |
| 67 trámites: efectos «al publicar» específicos por sistema (hoy solo están los transversales: firma, histórico, posteo, notificación) | Implementador | F1–F6 |
| Hooks de A.2: registro progresivo por sistema (F0 solo implementa el motor llamable, no hooks de eventos) | Implementador | F1–F6 |
