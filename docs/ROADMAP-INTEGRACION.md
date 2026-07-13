# Roadmap de Integración: I-Forge-Sistema → I-Forge-RPG

> Guión completo para presentar el estado del proyecto. Cada sistema del diseño está auditado feature por feature contra el código real.
> **Última actualización**: Julio 2026
> **Fuente diseño**: `I-Forge-Sistema\one-piece-eternal-sistemas\`
> **Fuente código**: `I-Forge-RPG\`

---

## ÍNDICE

1. [La foto global](#1-la-foto-global)
2. [Bloque A: Lo que YA funciona](#2-bloque-a--lo-que-ya-puede-hacer-un-jugador-verde)
3. [Bloque B: Lo que funciona A MEDIAS](#3-bloque-b--lo-que-funciona-a-medias-amarillo)
4. [Bloque C: Lo que NO existe todavía](#4-bloque-c--lo-que-no-existe-todavía-rojo)
5. [Bloque D: Diferencias críticas diseño vs código](#5-bloque-d--diferencias-críticas-diseño-vs-código)
6. [Auditoría completa sistema por sistema](#6-auditoría-completa-sistema-por-sistema)
7. [Cómo contarlo: guión narrativo](#7-cómo-contarlo-guión-narrativo)
8. [Orden de ataque: plan de implementación](#8-orden-de-ataque-plan-de-implementación)
9. [Dependencias entre sistemas](#9-dependencias-entre-sistemas)
10. [Diapositiva resumen](#10-diapositiva-resumen)
11. [Métricas para defender prioridades (FAQ)](#11-métricas-para-defender-prioridades-faq-ampliado)

---

## 1. LA FOTO GLOBAL

**27 sistemas diseñados en 9,325 líneas de Markdown. El código tiene 37 tablas BD y ~800 archivos.**

```
█████████ IMPLEMENTADO (9)  ─ funcional, con tablas, páginas y lógica de negocio
█████████ PARCIAL     (9)  ─ infraestructura OK pero faltan mecánicas de ejecución
█████████ AUSENTE     (9)  ─ solo en papel o en guías HTML estáticas, cero código
```

**El núcleo mínimo viable está funcionando.** Un jugador puede:
- Crearse ficha de personaje con 9 razas, híbridos, 12 stats, virtudes/defectos
- Rolear y ganar PP automáticamente por palabras escritas
- Crear cartas de técnica con 6 categorías de tags y 5 tiers
- Solicitar viajes con oráculo D100 que genera clima/encuentros/hallazgos/peligros
- Formar tripulaciones vía sistema de trámites
- Ver el tablero de Mundo Vivo con 8 zonas, 10 métricas, facciones y periódico mensual

**Los agujeros están en 3 categorías:**
1. Sistemas "de lujo" nunca iniciados (cyborgs, inframundo, conquistas, herencia)
2. Automatización de combate (hoy todo lo hace el DJ a mano)
3. Sistemas de retención de jugadores (rachas, logros, onboarding)

---

## 2. BLOQUE A — "Lo que YA puede hacer un jugador" (VERDE)

### INI-01 — Creación de Personaje (~80%)

**Archivos**: `inc/ope_rol_data.php`, `crear-personaje.php`, `ficha.php`, `personajes.php`, `gestionar-personaje.php`, `revisar-personaje.php`, `biblioteca-personajes.php`, `rol-backend/src/Controllers/FichaController.php`, `rol-backend/src/Controllers/CuentaController.php`, `rol-backend/src/Services/CharacterCreationService.php`, migraciones `001_cuentas_personajes.sql` y `002_stats_virtudes_equipo.sql`, modelos `Cuenta.php`, `Personaje.php`, `FichaAtributo.php`, `Stat.php`, `Virtud.php`, `Defecto.php`

| # | Feature | Estado | Archivo:Línea |
|---|---------|--------|---------------|
| 1 | 9 razas (Humano, Gyojin, Skypiean, Gigante, Mink, Lunarian, Sirena, Bucaneer, Tontatta) | OK | `inc/ope_rol_data.php:180-322` |
| 2 | Subespecies humanas (Ashinaga, Tenaga, Kubinaga, Kuja) | OK | `inc/ope_rol_data.php:195-223` |
| 3 | Gigante Ancestral (Cuerpo Devastador) | OK | `inc/ope_rol_data.php:254-268` |
| 4 | 12 stats en 3 pilares (FUE,DES,VIG,AGI,INT,ING,CON,PER,CAR,CTR,VOL,SEN) | OK | `inc/ope_rol_data.php:18-51` |
| 5 | Escala rangos F a M+ (12 niveles) | OK | `inc/ope_rol_data.php:66-88` |
| 6 | Pasivas raciales primaria + secundaria | OK | `inc/ope_rol_data.php:180-322` (⚠️ deltas fijos, no %) |
| 7 | Hibridación (2 razas máx, validación) | OK | `crear-personaje.php:116-124` |
| 8 | 6 PC para virtudes/defectos | OK | `inc/ope_rol_data.php:600-602` |
| 9 | Catálogo virtudes (7 categorías, ~40 ítems) | OK | `inc/ope_rol_data.php:442-515` |
| 10 | Catálogo defectos (7 categorías, ~50 ítems) | OK | `inc/ope_rol_data.php:518-598` |
| 11 | 6 facciones seleccionables | OK | `inc/ope_rol_data.php:325-361` |
| 12 | 5 packs de equipo inicial | OK | `inc/ope_rol_data.php:376-440` (⚠️ texto, no ítems en inventario) |
| 13 | 50,000 berries iniciales | OK | `inc/ope_rol_data.php:604-605` |
| 14 | Multi-personaje (slots) | OK | `rol_cuentas.slots INT DEFAULT 3`, `crear-personaje.php:65-78` |
| 15 | Sin fruta ni Haki al inicio | OK | `crear-personaje.php:627` |
| 16 | Raza inmutable | OK | Sin UI para cambiar |
| 17 | Periodo gracia PvP 15 días | PARCIAL | `crear-personaje.php:593` solo texto, sin enforcement |
| 18 | 30 PS repartibles sobre base 5 | AUSENTE | Código usa bumps de +1, no reparto numérico |
| 19 | Cap stat ≤ 20 en creación | AUSENTE | Máximo es M+ (valor 10 en escala 1-10) |
| 20 | Fórmula Nivel = Suma/10 | AUSENTE | Código usa umbrales: `ope_rol_rank_from_sum()` |

**Páginas**: `crear-personaje.php` (wizard), `ficha.php` (visualización), `personajes.php` (listado), `gestionar-personaje.php` (panel usuario), `revisar-personaje.php` (staff), `biblioteca-personajes.php` (pública)

**Endpoints API**: `GET /cuenta/mi-cuenta`, `GET/POST /cuenta/personaje-activo`, `GET/POST/PUT /personajes/*`

### INI-02 — Acciones y PA (~55%)

**Archivos**: `inc/ope_rol_system.php`, `inc/plugins/ope_rol.php`, `scripts/migrate-oleada2.php`

| # | Feature | Estado | Archivo:Línea |
|---|---------|--------|---------------|
| 1 | PV = (FUE+VIG)×5 + (VOL+CON)×2 | OK | `inc/ope_rol_system.php:428-435` |
| 2 | EN máxima por rango | OK | `inc/ope_rol_system.php:417-423` |
| 3 | PA = AGI + max(INT,ING,CAR) + bono_rango | OK | `inc/ope_rol_system.php:463-471` (⚠️ diseño divide por 10) |
| 4 | Bono PA por rango (0 a +3) | OK | `inc/ope_rol_system.php:449-458` |
| 5 | Sistema heridas (6 partes, umbrales 10%/20%/35%, acumulación) | OK | `inc/ope_rol_system.php:526-576` |
| 6 | 19 estados en BD | OK | `scripts/migrate-oleada2.php:95-114` |
| 7 | BBCode `[combate]`, `[accion]`, `[tecnica]`, `[estado]`, `[dado]` | OK | `inc/plugins/ope_rol.php:1603` |
| 8 | Tabla costes acción (Moverse=1PA, etc.) | PARCIAL | En cartas y guías, sin enforcement global |
| 9 | Consumibles PA | AUSENTE | Sin lógica |
| 10 | Movimiento terrestre (tabla distancias/AGI) | AUSENTE | Sin código |
| 11 | Movimiento acuático (×0.5/×2.0/×3.0) | AUSENTE | Sin multiplicadores por raza |
| 12 | Esprintar | AUSENTE | Sin implementación |
| 13 | Defensa narrativa sin dados | PARCIAL | Delega al narrador |

### INI-03 — Cartas de Técnica (~75%)

**Archivos**: `inc/ope_rol_data.php:634-772`, `crear-cartas.php`, `asignar-cartas.php`, `scripts/migrate-rol-tecnicas.php`

| # | Feature | Estado | Archivo:Línea |
|---|---------|--------|---------------|
| 1 | 6 categorías tags (Estilo, Tipo, Alcance, Elemento, Estado, Ejecución) | OK | `inc/ope_rol_data.php:634-754` |
| 2 | 3 estilos, 6 tipos, 7 alcances, 13 elementos, 14 estados, 8 ejecuciones | OK | `ope_rol_tecnica_tags()` |
| 3 | Mínimo 1 tag por categoría, máx 3 estilos/estados | OK | `ope_rol_tecnica_valida_tags()` |
| 4 | Tiers I-V (5, 8, 12, 18, 25 PP) | OK | `inc/ope_rol_data.php:757-772` |
| 5 | Reposo 0-3 posts | OK | `rol_cartas.reposo`, `rol_tecnicas.reposo` |
| 6 | Estructura YAML completa | OK | `crear-cartas.php` |
| 7 | Biblioteca independiente + deck por personaje | OK | Tablas `rol_cartas` + `rol_tecnicas` |
| 8 | Render naipes visuales | OK | `ope_rol_tecnica_card_html()` |
| 9 | Prompt IA para diseñar cartas | OK | `inc/plugins/ope_rol.php:2146+` |
| 10 | Técnica Insignia (evolución ilimitada) | PARCIAL | Campo `es_insignia` sin mecánica evolución |
| 11 | Evolución carta común (subir tier) | AUSENTE | Sin lógica |
| 12 | Aprendizaje con PP | PARCIAL | Costes definidos pero `progresion.php` no gasta en cartas |

### INI-04 — Progresión (~50%)

**Archivos**: `inc/ope_rol_system.php:146-407`, `inc/ope_rol_data.php:66-153`, `progresion.php`, `scripts/migrate-oleada1.php`

| # | Feature | Estado |
|---|---------|--------|
| 1 | PP por palabras (0-300=1, 301-700=2, 701-1200=3, 1201+=4) | OK |
| 2 | PP automático por post (idempotente) | OK |
| 3 | PP Misión (+3 a +10), Arco (+15 a +30), Evento, Staff | OK |
| 4 | Gasto PP en stats con costes por rango | OK |
| 5 | Tablas `rol_pp_saldo` + `rol_pp_log` | OK |
| 6 | Haki Busoshoku (4 niveles, PP) | AUSENTE (solo tag decorativo) |
| 7 | Haki Kenbunshoku (4 niveles) | AUSENTE |
| 8 | Haki Haoshoku (tirada d100, PL) | AUSENTE |
| 9 | Puntos de Leyenda (PL) | AUSENTE |
| 10 | Frutas: Ruleta, Compra por PL, Obtención narrativa | AUSENTE |
| 11 | Despertar (CTR 80+) | AUSENTE |
| 12 | Wanted/Recompensa (tracking bounty) | AUSENTE |
| 13 | Reputación Facción (-100 a +100) | AUSENTE |
| 14 | Inactividad 30 días → NPC | AUSENTE |

### INI-05 — Equipo y Objetos (~25%)

| # | Feature | Estado |
|---|---------|--------|
| 1 | 5 packs iniciales, 50K berries | OK |
| 2 | Tienda UI (`tienda.php`, `rol_tienda_items`) | PARCIAL (sin flujo de compra real) |
| 3 | Inventario JSON 12 slots con mochila en postbit | PARCIAL (sin UI gestión) |
| 4 | 15 armas cuerpo a cuerpo, 10 distancia, 8 municiones | AUSENTE |
| 5 | 5 calidades armas (Común→Saijo O Wazamono) | AUSENTE |
| 6 | 11 Dials, 6 barcos, 6 Den Den Mushi | AUSENTE |
| 7 | 6 consumibles, 7 armaduras | AUSENTE |

---

## 3. BLOQUE B — "Lo que funciona A MEDIAS" (AMARILLO)

### AV-01 — Combate (~40%)

**Archivos**: `inc/ope_rol_system.php:417-576`, `inc/plugins/ope_rol.php:1603`, `rol-backend/src/Controllers/DadosController.php`, `DiceService.php`, `scripts/migrate-oleada2.php:95-114`

**Qué existe:**

| Componente | Ubicación |
|---|---|
| Fórmulas PV/EN/PA + recálculo automático | `inc/ope_rol_system.php:417-504` |
| Sistema heridas (6 partes, umbrales, acumulación 2→1) | `inc/ope_rol_system.php:526-576` |
| 19 estados en BD (Aturdido, Quemado...) | `scripts/migrate-oleada2.php:95-114` |
| BBCode `[combate]`, `[dado]`, `[tecnica]` | `inc/plugins/ope_rol.php:1603` |
| API dados server-side | `rol-backend/src/Controllers/DadosController.php` |
| Tabla `rol_tiradas` para auditoría | `scripts/migrate-rol-tables.php:130` |

**Qué falta (cada item sin código PHP):**

| Feature del diseño | Nota |
|---|---|
| Motor de turnos automático | DJ hace tracking manual de PV/PA/EN por ronda |
| Logia/Intangibilidad (0% daño sin Haki) | Solo en `guias.php:736-744` como texto |
| Kairoseki Tipo 1 (toque), Tipo 2 (stats 50%), Tipo 3 (drenaje -5 EN/ronda) | Solo en `guias.php:747-755` |
| Haki en combate (verificación real de nivel) | Solo tags decorativos `[Estilo: Haki]` |
| Defensa Pasiva Física (CON/2) y Elemental (VOL/2) | Sin código |
| Defensas Activas (Bloqueo FUE/DES, Evasión AGI) | Solo en guías |
| Agua vs Fruta (5 niveles inmersión, -25% a -100% stats) | Solo en `guias.php:724-734` |
| Combate submarino, Islas del Cielo, Daño Ambiental | Sin código |
| Regeneración EN (+5 normal, +10 forzada) | Sin tracking |
| Armadura (PV adicionales blindaje) | Sin código |

### AV-04 — Estilos de Lucha (~15%)

**Qué existe**: Catálogo `rol_estilos` en BD (`scripts/migrate-catalogos.php:158`), página `biblioteca-estilos.php`, lectura `inc/ope_rol_catalogos.php:130`, tags de estilo en cartas.

**Qué falta (25 estilos, ninguno con mecánica):**

| Feature | Detalle |
|---|---|
| Puntos de Uso (+1 por combate, +2 predominante) | Sin código |
| 5 rangos Maestría (Iniciado→Gran Maestro: 0/5/15/35/75 pts) | Sin código |
| Desbloqueo Tiers I-V por maestría | Sin código |
| Pasivas por estilo (25 estilos) | Sin código |
| Ittoryu, Nitoryu, Santoryu, Kyotoryu, Mutoryu, Rokushiki, Gyojin Karate, Gyojin Jujutsu, Black Leg, Hasshoken, Okama Kempo, Electro, Ninjutsu, Seimei Kikan, otros 11 | Sin mecánicas (solo nombres en catálogo) |
| Requisitos aprendizaje (FUE 30+, AGI 40+, raza...) | Sin código |
| Maestros en Mundo Vivo | Sin código |
| Bloqueo vías alternativas (Ittoryu bloquea Nitoryu) | Sin código |

### AV-05 — Facciones / Proezas y Stats (~45-55%)

**Qué existe**: 6 facciones (`inc/ope_rol_data.php:325-361`), métricas mundiales (REP,COH,MIL,POL,ECO,MOR,ALC en `inc/ope_rol_mundo.php:436-447`), tensión 15 pares × 8 zonas (`inc/ope_rol_mundo.php:80-98`), 12 stats + costes PP.

**Qué falta:**

| Feature | Detalle |
|---|---|
| Tablas rangos (9 Marine, 9 Pirata, 7 Rev, 7 Gob, 7 Caza) | Solo `rango_faccion` texto libre |
| Reputación por personaje (-100 a +100) | Sin tabla, sin lógica |
| Membresía/ramas/jerarquías | Sin código |
| Beneficios automáticos por rango | Sin perks |
| Cambio facción (límite 2, penalizaciones) | Sin código |
| Diplomacia (pactos, alianzas, tratados) | Sin código |
| Guerras (TEN≥85 declaración, fases, resolución) | Solo en prompt IA (`:905-907`) |
| Tablas proezas métricas (FUE: levantamiento, AGI: velocidad) | Solo en diseño |
| Regla comparación stats (<10%=empate, 10-50%=ventaja, >50%=dominio) | Sin código |

### AV-08 — Bestias y NPCs (~40%)

**Qué existe**: Catálogo `rol_bestiario` + `biblioteca-bestiario.php`, NPC mayor con `es_npc=1` + doble capa JSON (`datos_publicos`/`datos_internos` en `seed-mv-demo.php:208-209`), `biblioteca-npc.php`, `crear-npc.php` (wizard 7 pasos), `gestionar-npc.php`, NPCs menores (`rol_mv_npc_menores`), tracking ciclo (salud, moral, plan_activo en `inc/ope_rol_mundo.php:296-306`), sincronización ubicación (`inc/ope_rol_mundo.php:595-620`).

**Qué falta:**

| Feature | Detalle |
|---|---|
| 6 ejes personalidad estándar (AGR 0-100, VAL, HON, LEA, AMB, INT) | Seed usa claves diferentes |
| Metas NPC como objetos (tipo/progreso/objetivo) | Array de strings, no objetos |
| Motor decisión determinista con 10 triggers | Solo en prompt IA (`:733-778`) |
| Doma criaturas (CAR+SEN vs VOL) | Sin código |
| Mascotas (virtud 3 PC, 8 tiers progresión) | Sin árbol, sin costes |
| Encuentros aleatorios por región (d20) | Sin código |
| Combate criatura vs barco | Sin código |
| NPCs enemigos rápidos (Secuaz→Jefe, 5 niveles con tablas) | Sin código |
| Personalidad rápida NPC menor (d10) | Sin código |
| Grupos enemigos (Patrulla, Escuadrón...) | Sin código |

### AV-14 — Frutas del Diablo (~40%)

**Qué existe**: Catálogo `rol_akuma` (`inc/ope_rol_catalogos.php:96-117`), biblioteca pública con filtros JS (`biblioteca-akuma.php`, 140 líneas), badges rareza, campos completos (nombre, tipo, rareza, descripción, despertar, debilidad, imagen, usuario).

**Qué falta (TODO el sistema mecánico):**

| Feature | Detalle |
|---|---|
| 5 métodos asignación (ruleta 1PL, compra 3PL, mercado negro, derrotar usuario, evento) | Sin código |
| Cartas `[Akuma]` vinculadas a fruta | Sin integración |
| Despertar (75 pts uso, CTR SS, efectos por tipo) | Sin tracking |
| Combate Zoan (transformaciones) | Sin código |
| Combate Logia (intangibilidad real) | Depende de AV-01 |
| Objetos Zoan, moderación custom, restricciones raciales | Sin código |

### AV-15 — Guía del Narrador (~20%)

**Qué existe**: Permisos staff/narrador en BD (`inc/plugins/ope_rol.php:185-276`), `zona-staff.php`, `guias.php` (1793 líneas estáticas).

**Qué falta**: Creador de villanos con plantilla, escalado automático dificultad, arbitraje de combate con workflow, creación de misiones por staff (UI), misiones de facción, reporte estructurado misión→Mundo Vivo, gestión de eventos del foro, cálculo automático recompensas por PE, premios narrador (Estrella del Tema, Momento Épico, Sacrificio Narrativo), manejo de conductas problemáticas integrado.

### AV-16 — Recompensas por Actividad (~5%)

**Qué existe**: Solo PP por palabras (`inc/ope_rol_system.php:150-407`) + documentación aspiracional en `guias.php:1450-1490`.

**Qué falta (3 capas completas):** Rachas 48h con tabla día 1-30, jornal semanal (lunes) con bonus volumen/calidad/racha, temporadas 90 días con reset, cofres (Básico→Legendario), modo vacaciones (congelar racha), logros/achievements, anti-abuso (límites diarios/semanales), cap 1 rango stat/30 días.

### ANX-01 — Ejemplo de Combate (~40%)

Fórmulas y catálogos existen. Para ejecutar el ejemplo falta: Bot Oráculo automático de combate, tracking estados activos por personaje, gestión automática turnos, bloqueo Haki vs Logia automatizado, tracking inmersión agua, cartas `[tecnica]` que descuenten PA/EN.

### ANX-04 — Inicio Rápido (~30%)

Existe `guias.php` estático y wizard `crear-personaje.php`. Falta: onboarding interactivo 5 pasos, Tema de Bienvenida automático (AV-18), página "Primer Post" con ejemplo, panel simplificado novatos.

---

## 4. BLOQUE C — "Lo que NO existe todavía" (ROJO)

### AV-03 — Batallas Navales (0%)

Nada implementado. Solo documentación HTML en `guias.php:827-859`. El diseño pide: distancias narrativas, Puntos de Ruptura (PR), zonas de barco, puestos de combate naval, resolución ataques, abordaje, rendición/huida, recompensas, stats de barcos, tipos de barco.

### AV-09 — Cyborgs (~5%)

Solo 3 virtudes/defectos como datos estáticos en `inc/ope_rol_data.php:500,530-532,579-580` (Iron Heart, Cuerpo Puro, Incompatible) + guía HTML en `guias.php:1133-1188`. Cero mecánicas. El diseño pide 18 implantes mecánicos, 10 biológicos, slots, rechazo, reparación, compatibilidad con Frutas, durabilidad, tiradas de instalación.

### AV-10 — Inframundo (~10%)

Solo métrica INF en zonas, sección "mercado_negro" vacía en tienda, 2 virtudes (Eres del Inframundo, Doble Vida) + guía HTML. El diseño pide: jerarquía criminal 5 niveles, derrocamiento PvP, mercado negro rotativo 30 días, subastas, contactos, exposición %, secuaces, información privilegiada, descuentos, doble vida con facciones.

### AV-11 — Conquistas (~5%)

Solo métricas de zona/facción en tablero + guía HTML. El diseño pide: registro de islas, 8 métodos adquisición, 4 fases conquista, valor islas 0-100, ingresos pasivos, edificios exclusivos, mejoras, defensa automática, diplomacia territorial, pérdida territorio, ataques facciones NPC.

### AV-12 — Herencia (~2%)

Solo defecto "Vinimos a Jugar" en `inc/ope_rol_data.php:582` + guía HTML. El diseño pide: muerte narrativa vs mecánica, 5 tiers calidad muerte, tabla maestra (% PP/berries/objetos/REP/PL), restricciones (Fruta NO, Haki NO), creación heredero (7 días), plantilla solicitud, Registro de Caídos, epitafios, periódico mundial, anti-farmeo (2 muertes/año, 3 meses entre muertes).

### AV-17 — Eventos Comunitarios (0%)

Nada implementado. Torneos, cazas del tesoro, gestión participantes. No existe en el código.

### AV-18 — Temas de Bienvenida (0%)

Nada implementado. Tema automático con checklist 5 pasos. No existe en el código.

### ANX-02 — Normativa (~5%)

Solo warnings MyBB genéricos (`warnings.php`). El diseño pide: 22 infracciones (I-01 a I-22), strikes con caducidad 30 días, baneos graduados, apelación 7 pasos, jurisprudencia (J-001+), panel moderación anonimizado, notificaciones con ID infracción, indulto 6 meses.

---

## 5. BLOQUE D — "Diferencias críticas diseño vs código"

NO son features ausentes. Son decisiones de implementación que se desviaron del diseño. Si se quiere fidelidad al documento, hay que decidir si se reescribe código o se adapta diseño.

### D-1: Escala de stats incompatible

| | Diseño | Código real |
|---|---|---|
| Sistema | Valores 5-100+ | Rangos F-M+ (1-10) |
| Creación | 30 PS repartibles (Humanos 40) | Bumps +1 por paso |
| Progresión | Costes por tramos (1PP/pto 5-20, 2PP/pto 21-40...) | Costes por rango (F→E=5PP, E→D=10PP...) |
| Nivel | Suma/10 | Umbrales fijos (sum≥66→M+) |
| Cap inicial | Ningún stat > 20 | Máximo M+ (valor 10) |
| Cap máximo | (Nivel×1.5)+20 | Sin validación |

**Impacto**: Incompatible. Migrar requiere reescribir `ope_rol_data.php`, `crear-personaje.php`, `progresion.php` y recalcular personajes existentes.

### D-2: Fórmula PA diferente

| | Diseño | Código real |
|---|---|---|
| Fórmula | `(AGI/10) + (mejorMental/10)` | `AGI + max(INT, ING, CAR) + bono_rango` |
| Rango típico | 1-4 PA | 6-16 PA |

**Impacto**: PA 5× más altos en código. Cambia economía de acciones por turno.

### D-3: Fórmula EN diferente

| | Diseño | Código real |
|---|---|---|
| Fórmula | `(VOL×3)+(CON×2)` | Tabla precalculada por rango (F→20...M+→170) |

### D-4: Pasivas raciales: % vs deltas fijos

| | Diseño | Código real |
|---|---|---|
| Tipo | +25% FUE, +20% VIG | FUE+2, VIG+1 |
| Escalabilidad | Raza escala con nivel | Bono estático, se diluye |

### D-5: Haki inexistente como sistema

Busoshoku (4 niveles PP), Kenbunshoku (4 niveles), Haoshoku (tirada d100, PL). En código: solo tag `[Estilo: Haki]` decorativo.

### D-6: Wanted/Recompensa ausente

Tracking de bounty en berries, triggers por umbral, notoriedad. En código: solo texto en pasivas de Lunarian (100M) y Bucaneer (50M).

### D-7: PL (Puntos de Leyenda) inexistente

Moneda premium para Voluntad de D., Linaje Especial, Fruta Específica, Arma Suprema. Sin tabla, sin tracking, sin tienda.

---

## 6. AUDITORÍA COMPLETA SISTEMA POR SISTEMA

### Resumen tablas BD

**Existentes (37 `rol_*`):**

| Tabla | Sistema |
|---|---|
| `rol_cuentas`, `rol_personajes`, `rol_ficha_atributos`, `rol_stats`, `rol_virtudes`, `rol_defectos`, `rol_relaciones` | INI-01 |
| `rol_pp_saldo`, `rol_pp_log` | INI-04 |
| `rol_cartas`, `rol_tecnicas` | INI-03 |
| `rol_tienda_items`, `rol_transacciones` | INI-05 |
| `rol_estados`, `rol_tiradas` | AV-01 |
| `rol_viajes` | AV-02 |
| `rol_estilos` | AV-04 |
| `rol_tripulaciones`, `rol_tripulacion_miembros` | AV-06 |
| `rol_bestiario` | AV-08 |
| `rol_mv_ciclos`, `rol_mv_zonas`, `rol_mv_facciones`, `rol_mv_tension`, `rol_mv_arcos`, `rol_mv_eventos`, `rol_mv_misiones`, `rol_mv_mision_asignaciones`, `rol_mv_npc_menores`, `rol_mv_noticias`, `rol_mv_audit` | AV-13 |
| `rol_akuma` | AV-14 |
| `rol_cronologia`, `rol_forum_meta`, `rol_mensajes`, `rol_alertas`, `rol_post_templates`, `rol_thread_meta`, `rol_calendario`, `rol_post_snapshot`, `rol_tramites` | General |

**Tablas que DEBERÍAN existir según diseño y NO existen (~15):**

| Tabla necesaria | Sistema | Propósito |
|---|---|---|
| `rol_combate_activo` | AV-01 | Tracking PV/PA/EN en vivo |
| `rol_combate_estados_activos` | AV-01 | Estados aplicados por personaje |
| `rol_sanciones`, `rol_strikes`, `rol_baneos`, `rol_jurisprudencia` | ANX-02 | Disciplina |
| `rol_npc_mayores` | AV-08 | NPCs con 6 ejes personalidad |
| `rol_batallas_navales` | AV-03 | Combate barco vs barco |
| `rol_implantes` / `rol_cyborg` | AV-09 | Implantes cibernéticos |
| `rol_inframundo`, `rol_subastas`, `rol_secuaces` | AV-10 | Mercado negro |
| `rol_territorios`, `rol_edificios` | AV-11 | Conquistas |
| `rol_herencia`, `rol_legado` | AV-12 | Legado |
| `rol_eventos_comunitarios` | AV-17 | Torneos |
| `rol_rachas`, `rol_logros` | AV-16 | Actividad |

### Nota sobre código huérfano

Archivos en I-Forge-RPG sin correspondencia directa en el diseño de I-Forge-Sistema:
- `alertas.php`, `mensajes.php` — infraestructura general, soporta todos los sistemas
- `scripts/_theme-sync-lib.php`, `_tmp_schema.php` — tooling de desarrollo
- Posible solapamiento: `calendario-onrol.php` vs `gestionar-calendario.php` — revisar cuál es el activo

El código huérfano es mínimo. Casi todos los `.php` customizados mapean a sistemas del diseño.

---

## 7. CÓMO CONTARLO: GUIÓN NARRATIVO

**Tiempo total**: 12-15 min (completo) o 5 min (exprés: fases 1, 2, 6).

### Fase 1 — El Titular (1 min)

"De 27 sistemas del diseño, 9 completos, 9 a medias, 9 sin empezar. El foro es jugable. Mundo Vivo es lo más sólido."

### Fase 2 — Demo: Lo que un jugador YA puede hacer (3 min)

1. **Crear personaje**: `crear-personaje.php` — 9 razas + híbridos, facción, stats, virtudes/defectos, pack. 15 minutos.
2. **Rolear**: PP automático por palabras (1-4 por post). Idempotente.
3. **Crear cartas**: 6 categorías tags, costes PA/EN, tier I-V. Asignar a personaje.
4. **Viajar**: Oráculo D100 por tramo. Post animado GSAP+confetti.
5. **Formar tripulación**: Fundar, invitar vía trámites.
6. **Ver el mundo**: Tablero 8 zonas, 10 métricas. Periódico mensual automático.

### Fase 3 — Lo que está a medias (2 min)

Enfocar 3 sistemas más visibles:
- **Combate**: Fórmulas y heridas OK. Sin motor de turnos. DJ manual.
- **Frutas**: Catálogo OK. Sin conseguir, usar, ni despertar.
- **Facciones**: Definidas OK. Sin progresión de rango automática.

### Fase 4 — Las diferencias críticas (2 min)

"El código tomó 6 decisiones que se desviaron del diseño. No son bugs. Si queremos fidelidad al documento, hay que decidir."

1. Stats: 5-100+ vs F-M+
2. PA: división por 10 vs valores crudos
3. Pasivas: % vs deltas fijos
4. EN: fórmula vs tabla precalculada
5. Haki: sistema central vs tag decorativo
6. Wanted/PL: dos monedas completas que no existen

**Decisión clave**: "¿Reescribimos código o adaptamos diseño?"

### Fase 5 — Lo que falta (1 min)

9 sistemas agrupados:
- **Naval**: AV-03 Batallas navales
- **Cuerpo**: AV-09 Cyborgs
- **Sociedad**: AV-10 Inframundo, AV-11 Conquistas
- **Legado**: AV-12 Herencia
- **Comunidad**: AV-17 Eventos, AV-18 Bienvenida, ANX-02 Normativa

### Fase 6 — El Plan (2 min)

Explicar oleadas 0-3 (ver sección 8).

---

## 8. ORDEN DE ATAQUE: PLAN DE IMPLEMENTACIÓN

### Oleada 0 — Cimentar (semanas 1-2)

Resolver las 6 diferencias críticas antes de añadir nada.

| # | Decisión | Opción A (seguir diseño) | Opción B (adaptar diseño) |
|---|----------|--------------------------|---------------------------|
| D-1 | Escala stats | Reescribir `ope_rol_data.php`, `crear-personaje.php`, `progresion.php` | Documentar F-M+ como canónica |
| D-2 | Fórmula PA | Cambiar a `(AGI/10)+(mental/10)` | Ajustar diseño a valores crudos |
| D-3 | Fórmula EN | Cambiar a `VOL×3+CON×2` | Mantener tabla por rango |
| D-4 | Pasivas raciales | Cambiar a multiplicadores % | Mantener deltas fijos |
| D-5 | Haki | Implementar 4 niveles con PP | Definir alcance mínimo viable |
| D-6 | Wanted/PL | Implementar tracking completo | Aplazar a oleada 3 |

**Entregable**: Documento de decisión firmado + issues GitHub.

### Oleada 1 — Completar parciales (semanas 3-6)

| Prioridad | Sistema | Esfuerzo | Razón |
|---|---|---|---|
| P1 | **AV-01** Motor combate automático | 1-2 sem | 100% jugadores lo usan |
| P1 | **AV-16** Rachas y recompensas | 1-2 sem | Retención jugadores nuevos |
| P2 | **AV-05** Facciones jugables (REP, rangos, perks) | 1-2 sem | Propósito a largo plazo |
| P2 | **AV-14** Asignación frutas (ruleta, narrativa) | 1 sem | Sistema icónico One Piece |
| P3 | **AV-15** Herramientas narrador | 1-2 sem | Staff necesita tools |
| P3 | **AV-04** Estilos lucha (mecánicas) | 2-3 sem | Build diversity |
| P3 | **AV-08** Motor decisión NPCs | 1-2 sem | Vida al mundo |
| P4 | **ANX-01** Automatizar ejemplo combate | 1 sem | Depende de AV-01 |
| P4 | **ANX-04** Onboarding | 1 sem | Depende de AV-18 |

### Oleada 2 — Sistemas nuevos alto impacto (semanas 7-14)

| Prioridad | Sistema | Esfuerzo | Razón |
|---|---|---|---|
| P1 | **AV-03** Batallas navales | 2-3 sem | Extensión natural viajes+combate |
| P1 | **AV-12** Herencia | 2-3 sem | Cierra ciclo vida personaje |
| P2 | **AV-09** Cyborgs | 2-3 sem | Desbloquea arquetipo |
| P2 | **ANX-02** Normativa | 1-2 sem | Comunidad creciendo |

### Oleada 3 — Lujo y retención (semanas 15-22)

| Sistema | Esfuerzo |
|---|---|
| AV-10 Inframundo | 3-4 sem |
| AV-11 Conquistas | 3-4 sem |
| AV-17 Eventos comunitarios | 2-3 sem |
| AV-18 Onboarding/Bienvenida | 1-2 sem |

---

## 9. DEPENDENCIAS ENTRE SISTEMAS

```
                    ┌──────────────────────────────────────┐
                    │        INI-01 CREACIÓN PERSONAJE      │
                    └────────────┬─────────────────────────┘
                                 │
          ┌──────────────────────┼──────────────────────┐
          ▼                      ▼                      ▼
   ┌──────────────┐      ┌──────────────┐      ┌──────────────┐
   │ INI-03 CARTAS │      │ INI-04 PROG. │      │ INI-05 EQUIPO│
   └──────┬───────┘      └──────┬───────┘      └──────┬───────┘
          │                     │                     │
          ▼                     ▼                     ▼
   ┌──────────────┐      ┌──────────────┐      ┌──────────────┐
   │  AV-01 COMB. │      │ AV-04 ESTILOS│      │ AV-09 CYBORG │
   └──────┬───────┘      └──────────────┘      └──────────────┘
          │
          ▼
   ┌──────────────┐      ┌──────────────┐
   │ AV-03 NAVAL  │      │ AV-06 GRUPOS │
   └──────────────┘      └──────┬───────┘
                                │
                                ▼
                         ┌──────────────┐
                         │ AV-11 CONQ.  │
                         └──────────────┘

   ┌──────────────────────────────────────────────────────────┐
   │                     AV-13 MUNDO VIVO                      │
   │  (transversal: afecta AV-05, AV-08, AV-10, AV-11, AV-12) │
   └──────────────────────────────────────────────────────────┘
```

**Sin dependencias (cualquier orden)**:
- AV-16 Recompensas, AV-17 Eventos, AV-18 Bienvenida, ANX-02 Normativa, ANX-04 Inicio rápido

**Bloqueantes (deben hacerse antes)**:
- INI-01 → todo
- AV-01 → AV-03
- AV-13 → AV-05, AV-08, AV-10, AV-11, AV-12

---

## 10. DIAPOSITIVA RESUMEN

```
╔══════════════════════════════════════════════════════════════════╗
║    I-FORGE-RPG — ESTADO DE INTEGRACIÓN (Julio 2026)             ║
║    27 sistemas  |  37 tablas BD  |  ~800 archivos               ║
╠══════════════════════════════════════════════════════════════════╣
║                                                                  ║
║  ████████░░ 80% INI-01 Crear Personaje   ███████░░░ 75% INI-03 Cartas    ║
║  █████░░░░░ 50% INI-04 Progresión PP     ██████░░░░ 60% AV-02 Viajes     ║
║  █████████░ 90% AV-13 Mundo Vivo v4      █████████░ 90% AV-07 Virtudes   ║
║  ███░░░░░░░ 25% INI-05 Tienda/Equipo    ██░░░░░░░░ 25% AV-06 Tripulacion ║
║                                                                  ║
║  ████░░░░░░ 40% AV-01 Combate            █████░░░░░ 45% AV-05 Facciones  ║
║  ████░░░░░░ 40% AV-08 NPCs/Bestias       ████░░░░░░ 40% AV-14 Frutas     ║
║  ██░░░░░░░░ 15% AV-04 Estilos Lucha      ██░░░░░░░░ 20% AV-15 Narrador   ║
║  ░░░░░░░░░░  0% AV-03 Batallas Navales   ░░░░░░░░░░  0% AV-09 Cyborgs    ║
║  ░░░░░░░░░░  0% AV-10 Inframundo         ░░░░░░░░░░  0% AV-11 Conquistas ║
║  ░░░░░░░░░░  0% AV-12 Herencia           ░░░░░░░░░░  0% AV-17 Eventos    ║
║  █░░░░░░░░░  5% AV-16 Recompensas        ░░░░░░░░░░  0% ANX-02 Normativa ║
║                                                                  ║
╠══════════════════════════════════════════════════════════════════╣
║  9 IMPLEMENTADOS (33%) │ 9 PARCIALES (33%) │ 9 AUSENTES (33%)   ║
║                                                                  ║
║  ⚠️ 6 diferencias críticas diseño vs código                      ║
║  ⚠️ AV-13 (Mundo Vivo) = 2338 líneas, sistema más completo       ║
║  ⚠️ guias.php contiene HTML de sistemas NO implementados         ║
║  ⚠️ ~15 tablas BD necesarias y ausentes                           ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## 11. MÉTRICAS PARA DEFENDER PRIORIDADES (FAQ)

| Si preguntan... | Responder con... |
|---|---|
| **"¿Se puede jugar ya?"** | Sí. Crear personaje, rolear con PP automático, cartas, viajar con oráculo, tripulaciones y Mundo Vivo funcionan. El combate requiere DJ humano, pero es jugable. |
| **"¿Qué es lo MÁS urgente?"** | Las 6 diferencias diseño vs código (Bloque D). Construir sobre cimientos inconsistentes es deuda técnica que explota. La decisión más crítica es la escala de stats. |
| **"¿Cuál es la decisión más difícil?"** | Stats: ¿reescribir `ope_rol_data.php`, `crear-personaje.php` y `progresion.php` para seguir el diseño (5-100+), o documentar F-M+ como canónico? Afecta a 4 archivos core y a todos los personajes existentes. |
| **"¿Por qué combate antes que cyborgs?"** | El combate lo usa el 100% de jugadores desde el día 1. Los cyborgs son un arquetipo que quizá use el 10%. |
| **"¿Por qué rachas antes que herencia?"** | Las rachas enganchan jugadores nuevos desde la semana 1 y no requieren que nadie muera. La herencia es importante pero se activa tras meses de juego. |
| **"¿Y el Haki?"** | Es la diferencia más grande entre diseño y código. El diseño lo trata como sistema central con 4 niveles, PP, tiradas y PL. En código solo es un tag decorativo. Requiere decisión de diseño en Oleada 0 antes de implementar. |
| **"¿Cuánto queda?"** | 9 sistemas funcionales de 27. A 2-3 semanas por sistema, ~36-54 semanas para el 100%. Pero con prioridades, en 4-6 semanas se pueden cerrar los 9 parciales (Oleada 1). El restante son sistemas nuevos (Oleadas 2-3). |
| **"¿Qué sistema es el más completo?"** | AV-13 Mundo Vivo "La Balanza" v4: 2338 líneas en `inc/ope_rol_mundo.php`, 11 tablas BD, panel staff 6 pestañas, pipeline automático, periódico "Eternal News", migraciones v2-v6. Es la joya del proyecto. |
| **"¿Qué me recomiendas atacar primero?"** | 1) Decidir las 6 diferencias. 2) Implementar motor de combate (AV-01) + rachas (AV-16) en paralelo. 3) Facciones jugables (AV-05). Con eso en 6 semanas tienes un foro donde crear personaje, combatir automáticamente, progresar en facción y recibir recompensas por actividad diaria. |
| **"¿Cuál es el mayor riesgo?"** | `guias.php` contiene HTML de sistemas que no existen (Cyborgs, Inframundo, Conquistas, Herencia). Si un jugador lee esas guías y espera poder usarlas, se lleva una decepción. Hay que marcarlas como "próximamente" o implementarlas. |
| **"¿Por qué el Mundo Vivo está tan adelantado?"** | Porque es el diferenciador principal del foro frente a otros RPGs. Un mundo que se mueve solo, con métricas, facciones y periódico mensual generado por IA. Es lo que hace único al proyecto. |
