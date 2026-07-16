# MIGRACIÓN TÉCNICA — DE GRANBLUE FANTASY: ETERNAL / I-FORGE A UN FORO GRANBLUE

> **Qué es este documento:** guía técnica de desarrollo para reconvertir el codebase actual (Granblue Fantasy: Eternal, codename `ope`, restos de `iforge`) en un foro de rol ambientado en el **universo de Granblue Fantasy** con **historia y personajes propios**, conservando las **fortalezas mecánicas y comunitarias de los foros de One Piece**.
>
> **No es** un documento de lore ni de diseño visual final: es el plan de **ingeniería** (qué borrar, qué renombrar, qué crear) + glosarios de referencia + proceso de purga verificable.
>
> **Documentos hermanos:** `PLAN-MAESTRO-GRANBLUE-ETERNAL.md` (visión/producto).
>
> **Estado:** v1 · Julio 2026 · Basado en inventario real del repo.

---

## 0. Índice

1. [Principio rector](#1-principio-rector)
2. [Identidad de marca](#2-identidad-de-marca)
3. [Glosario A — Lo que tomamos de Granblue Fantasy](#3-glosario-a--lo-que-tomamos-de-granblue-fantasy)
4. [Glosario B — Fortalezas de One Piece que conservamos](#4-glosario-b--fortalezas-de-one-piece-que-conservamos)
5. [Inventario técnico de la huella actual](#5-inventario-técnico-de-la-huella-actual)
6. [Decisiones de nomenclatura](#6-decisiones-de-nomenclatura)
7. [Proceso de purga — fase por fase](#7-proceso-de-purga--fase-por-fase)
8. [Archivos a borrar / renombrar / crear](#8-archivos-a-borrar--renombrar--crear)
9. [Base de datos](#9-base-de-datos)
10. [Validación y criterios de aceptación](#10-validación-y-criterios-de-aceptación)
11. [Orden de ejecución recomendado](#11-orden-de-ejecución-recomendado)

---

## 1. Principio rector

**Tres capas, tres tratamientos distintos:**

| Capa | Origen | Tratamiento |
|---|---|---|
| **Mundo / lore / terminología** | Granblue Fantasy | **ADOPTAR** (Skydoms, Skyfarers, Primal Beasts, razas GBF…) con historia y PJs originales |
| **Mecánicas / estructura de juego** | One Piece (foros tipo OPG) | **CONSERVAR** lo que las hace fuertes (crew, bounty, poder único, progresión por tiers, mundo vivo) |
| **Marca antigua / codename** | Granblue Fantasy: Eternal (`ope`) + I-Forge (`iforge`) | **PURGAR** todo rastro del código, DB y docs |

> Regla de oro: *el motor es agnóstico; solo el "skin" (datos, copy, CSS, lore) es One Piece. Purgamos el skin, reutilizamos el motor, y le ponemos el skin de Granblue.*

---

## 2. Identidad de marca

### 2.1 Concepto

Foro PBP en el **mundo de Granblue Fantasy** (el cielo de islas flotantes de Phantagrande y más allá), pero con **cronología, tramas y personajes inventados** — igual que un foro de One Piece usa el mundo de Oda con PJs propios. No usamos a Gran/Djeeta/Lyria como PJs jugables; son referencias del mundo, no protagonistas.

### 2.2 Nombre de marca (decisión abierta — recomendación)

Se necesita un nombre propio (evitar "Granblue Fantasy" literal por marca de Cygames). Recomendaciones:

- **Skyfarers Eternal** *(recomendado — hereda el "Eternal" del proyecto)*
- **Estalucia** (la tierra prometida de GBF, como marca aspiracional)
- **Skydom Chronicles**
- **Grand Sky**

> **Decisión pendiente:** nombre final → determina `bbname`, dominio, logo y codename.

### 2.3 Codename técnico (interno)

El codename actual es `ope` (Granblue Fantasy: Eternal). El nuevo codename recomendado: **`sky`** (corto, neutro, ownable). Se usará como:
- Prefijo de funciones PHP: `sky_rol_*`
- Prefijo de clases CSS y scopes: `sky-pg-*`, `sky-*`
- Constantes: `SKY_*`
- Datacache: `sky_home`

### 2.4 Tono y vibra (resumen — el detalle irá al DESIGN.md)

| Eje | Granblue Fantasy: Eternal (antiguo) | Skyfarers Eternal (nuevo) |
|---|---|---|
| Escena | Mar de noche, cubierta pirata | Amanecer en el cielo, islas flotantes |
| Paleta | Azul abisal + oro bounty + rojo marina | Cielo índigo→dorado + turquesa Éter + latón |
| Tipografía display | Pirata One (western/wanted) | Serif de aventura (Cinzel/Marcellus) |
| Sensación | Oscuro, íntimo, peligroso | Luminoso, vasto, épico |
| Motivo | Cartel de recompensa | Carta de navegación celeste / brújula |

---

## 3. Glosario A — Lo que tomamos de Granblue Fantasy

Referencia canónica de términos GBF a introducir. La columna "Motor" indica dónde vive en el código reutilizado.

| Término GBF | Significado | Uso en el foro | Motor (sistema existente) |
|---|---|---|---|
| **Skydom** | Región del cielo con islas | Categorías del foro = Skydoms | `rol_mv_zonas` |
| **Phantagrande** | Skydom principal del juego | Zona inicial jugable | zona Mundo Vivo |
| **Skyfarer** | Aventurero del cielo | El rol base de todo PJ | Personaje |
| **Estalucia** | Isla de los Astrales (meta mítica) | Objetivo de leyenda (end-game) | arco Mundo Vivo |
| **Primal Beast / Primarch** | Bestias divinas invocables | Poder único + jefes | Pacto (ex-Fruta) + NPCs mayores |
| **Astral** | Raza precursora casi extinta | Antagonistas ancestrales / lore | NPCs / lore |
| **Erune** | Raza de orejas animales | Raza jugable (INT/PER) | `sky_rol_razas()` |
| **Draph** | Raza de cuernos, fuertes | Raza jugable (FUE/VIG) | `sky_rol_razas()` |
| **Harvin** | Raza pequeña, mágica | Raza jugable (ING/CAR) | `sky_rol_razas()` |
| **Human** | Raza equilibrada | Raza jugable base | `sky_rol_razas()` |
| **Draconic / Dhoromir** | Sangre de dragón | Raza jugable (afinidad elemental) | `sky_rol_razas()` |
| **Airship / Grandcypher** | Aeronave de la tripulación | La nave de la crew (pilar) | `rol_tripulaciones` + `nave_json` |
| **Crew** | Tripulación de la nave | Tripulación jugable | `rol_tripulaciones` |
| **Class / Job** | Profesión de combate | Clases (ex-Haki reskin) | `rol_haki` → `rol_clases` |
| **Weapon Grid** | Rejilla de armas | Simplificado: arma principal + pasivas | inventario + campo nuevo |
| **Summon** | Invocación de apoyo | 1 uso/combate vía acompañante | `rol_npcs_secundarios` + flag |
| **Charge Attack** | Ataque cargado | Técnica tier-S por acumulación | parser + regla nueva |
| **Chain Burst** | Combo de party | Buff de crew mismo elemento | regla nueva |
| **Elemento (6)** | Fuego/Agua/Tierra/Viento/Luz/Oscuridad | Afinidad + triángulo | tags `elemento` (ya existe) |
| **Ether / Magoi** | Energía mágica | Reserva de combate | EN (`gbe_combat_calc_en`) |
| **Rupies** | Moneda | Moneda de tienda | Berries → Rupies |
| **The Society / Erste Empire** | Facciones de poder | Facciones jugables | `sky_rol_facciones()` |
| **Sierokarte (Shopkeep)** | Comerciante icónica | NPC/bot de tienda | bot sistema |
| **Lyria** | Guía narrativa | Bot narrador / periódico | bot sistema |
| **Fate Episode** | Historia de personaje | Bio/trasfondo de ficha | `ficha.php` (bio) |

---

## 4. Glosario B — Fortalezas de One Piece que conservamos

Lo que hace **adictivos y comunitarios** a los foros de One Piece (OPG y similares) y que mantenemos **mecánicamente**, reskineado a Granblue.

| Fortaleza OP | Por qué funciona | Cómo se conserva en Granblue | Motor |
|---|---|---|---|
| **Tripulación (nakama)** | Vínculo social, retención | Crew de la aeronave | `rol_tripulaciones` |
| **Bounty / Wanted** | Progresión visible, estatus, rivalidad | **Renombre de Skyfarer** (fama por Skydom) | `gbe_rol_wanted.php` → `sky_rol_renombre.php` |
| **Fruta del Diablo (poder único)** | Identidad de PJ, "build" memorable | **Pacto Primal** (vínculo con Primal Beast) | tabla nueva `rol_pactos` |
| **Haki (progresión por tiers)** | Sensación de crecimiento de poder | **Clases / Mastery** (ramas × niveles) | `rol_haki` → `rol_clases` |
| **Escala de poder / rangos** | Jerarquía clara aspiracional | Rangos de Skyfarer (Rookie→Legend) | `gbe_rol_nivel_label()` reskin |
| **Grand Line / islas por acto** | Progresión geográfica del mundo | Skydoms por dificultad (Phantagrande→Alto Cielo) | `rol_mv_zonas` |
| **Mundo vivo que reacciona** | El mundo importa, no es decorado | Mundo Vivo "El Equilibrio del Cielo" | `rol_mv_*` |
| **Libertad / horizonte / sueño** | Motor emocional del rol | "¿Por qué vuelas?" en cada ficha | ficha |
| **El barco como símbolo** | Apego (Going Merry) | La aeronave con ficha propia | `nave_json` (nuevo) |
| **Misterio central (One Piece)** | Gancho de largo plazo | Estalucia + el secreto de los Astrales | arco Mundo Vivo |
| **Combate con reglas claras** | Justicia percibida, anti-godmod | PV/EN/PA + heridas + snapshots | `gbe_rol_system.php` |
| **Tiradas anti-trampa** | Confianza en el sistema | Dados bloqueados `[dado]` | parser |

---

## 5. Inventario técnico de la huella actual

Medición real del repo (grep, julio 2026). Sirve para dimensionar el esfuerzo de purga.

### 5.1 Marca / codename

| Token | Naturaleza | Alcance aprox. | Acción |
|---|---|---|---|
| `gbe_` / `gbe-` / `GBE_` | Codename Granblue Fantasy: Eternal (funciones, CSS, constantes, datacache) | **Cientos de ocurrencias en ~60 archivos PHP + CSS + XML** | Renombrar → `sky_` |
| `iforge` / `IFORGE` / `iforge-` | Codename previo (residual, ~130 archivos incl. docs/refs) | Restos en PHP, CSS, XML, docs, themes | Renombrar/eliminar |
| `One Piece` / `Granblue Fantasy: Eternal` | Nombre de marca en copy, docs, bot | `bbname`, docs, guías, bot `GBEternal` | Reemplazar por marca nueva |
| `rol_` (prefijo de tablas BD) | Genérico ("roleplay"), **no OP-específico** | ~30 tablas | **Conservar** (ver §6.3) |

> ⚠️ Nota histórica: el proyecto ya migró `iforge → ope` con `scripts/rename-ope.php`. Ahora repetimos el patrón `ope → sky` y limpiamos los restos de `iforge`.

### 5.2 Lore One Piece explícito (contenido, no motor)

Términos OP en `.php`: `haki`, `akuma`/`fruta`, `wanted`/`berries`, `marine`/`pirata`, `yonko`, `logia`, `kairoseki`, `grand line`, etc. Concentrados en:

- **Catálogos:** `inc/gbe_rol_data.php` (razas, facciones, armas, packs), `inc/gbe_rol_catalogos.php`
- **Sistemas de poder:** `inc/gbe_rol_haki.php`, `haki.php`, `inc/gbe_rol_wanted.php`, `inc/gbe_rol_pl.php`
- **Bibliotecas de lore:** `biblioteca-akuma.php`, `biblioteca-lore.php`, `biblioteca-bestiario.php`, `biblioteca-npc.php`, `biblioteca-personajes.php`
- **Seeds de lore OP:** `scripts/seed-yonko.php`, `scripts/seed-marines.php`, `scripts/seed-isabella.php`, `scripts/seed-lore.php`, `scripts/seed-civiles.php`, `scripts/seed-crew.php`, `scripts/seed-npc.php`
- **Guías:** `guias.php` (127 refs OP)
- **Mundo:** `inc/gbe_rol_mundo.php`, `inc/gbe_rol_viajes.php`

### 5.3 Design docs (estado tras purga Jul 2026)

**Fuente de verdad:** `docs/DESIGN-GRANBLUE-ETERNAL.md`, `docs/themes/gbe.css`, `docs/themes/README.md`.

**Eliminados** (obsoletos OP / duplicados): `DESIGN-ONE-PIECE-ETERNAL.md`, planes `docs/superpowers/` OP/brutalista, `docs/references/onepiecegaiden.com/`, prototipos `NeoBrutalism/`.

**Conservados:** `docs/Prototypes/Granblue/`, `docs/references/relink.granbluefantasy.jp/`.

---

## 6. Decisiones de nomenclatura

### 6.1 Codename PHP/CSS: `gbe_` → `sky_`

Reemplazo masivo ordenado (reutilizar el patrón de `scripts/rename-ope.php`):

```
gbe_        → sky_         # funciones, globales, claves de caché, columnas de codename
gbe-        → sky-         # clases CSS, prefijos de plantilla (gbe-pg-* → sky-pg-*)
GBE_        → SKY_         # constantes
gbe.css     → sky.css      # hoja de estilos
images/ope/ → images/sky/  # assets
```

### 6.2 Restos de `iforge`

Eliminar los residuos que quedaron tras la migración anterior:

```
iforge-     → sky-
IFORGE_     → SKY_
iforge_     → sky_
iforge.css  → sky.css
images/iforge/ → images/sky/
```

> ⚠️ **NO tocar** la ruta de instalación web `/iforge/` (bburl `http://localhost/iforge`) si el servidor local sigue usándola — es infraestructura, no marca. Confirmar antes.

### 6.3 Prefijo de tablas BD `rol_`: **CONSERVAR**

`rol_` = "roleplay", genérico y no OP-específico. Renombrar 30 tablas es alto riesgo (foreign keys, snapshots, migraciones) y bajo valor. **Decisión: mantener `rol_`.** Solo se renombran tablas con nombre OP-específico (ver §9).

### 6.4 Convención nueva (documentar en AGENTS.md)

- Codename objetivo: `gbe` (ver `PLAN-MAESTRO` §0.1)
- Funciones: `gbe_rol_*` post-F1 (hoy `gbe_rol_*`)
- CSS scope: `body.gbe-pg-<pagina>`; portada `body.gbe-index`
- Agentes: `AGENTS.md` + `docs/AGENTES-Y-HERRAMIENTAS.md` (anti-portado parcial)
- Datacache: `gbe_home` post-F1 (hoy `gbe_home`)
- Bot sistema: `Lyria` en vez de `GBEternal`

---

## 7. Proceso de purga — fase por fase

> Trabajar en rama dedicada (`git checkout -b migracion-granblue`). Cada fase = commit atómico. Dry-run antes de aplicar.

### Fase P0 — Preparación y seguridad
1. `git checkout -b migracion-granblue`
2. Backup de BD completo (`mysqldump`).
3. Backup de `cache/themes/` y `docs/themes/`.
4. Confirmar decisión de marca, codename (`sky`), y si se toca la ruta `/iforge/`.

### Fase P1 — Purga de codename (automatizada)
1. **Crear** `scripts/rename-sky.php` clonando `scripts/rename-ope.php` con las reglas de §6.1 y §6.2 (invertidas: `ope*`→`sky*`, `iforge*`→`sky*`).
   - Mantener exclusiones: `.git`, `cache`, `node_modules`, `vendor`, `docs/references`, `.impeccable`.
   - Extensiones: `php`, `css`, `xml`.
2. `php scripts/rename-sky.php` (dry-run) → revisar informe.
3. `php scripts/rename-sky.php --apply`.
4. **Renombrar archivos físicos** cuyo nombre lleva codename:
   - `inc/gbe_rol_*.php` → `inc/sky_rol_*.php` (13+ archivos)
   - `inc/gbe_functions.php` → `inc/sky_functions.php`
   - `inc/gbe_user_init.php` → `inc/sky_user_init.php`
   - `docs/themes/ope*.xml` → `docs/themes/sky*.xml`
   - `docs/themes/gbe.css` → `docs/themes/sky.css`
   - `inc/plugins/gbe_rol.php` → `inc/plugins/sky_rol.php` (⚠️ actualizar registro del plugin en MyBB)
5. Actualizar `require_once`/`include` que apunten a rutas renombradas (grep `gbe_rol_`, `gbe_functions`, `gbe_user_init`).
6. `php scripts/sync-theme.php import && php scripts/sync-theme.php verify` → CSS en sync.

### Fase P2 — Purga de lore One Piece (contenido)
1. **Borrar** archivos de lore/seed 100% OP (ver §8 lista de borrado).
2. **Reescribir catálogos** en `inc/sky_rol_data.php`:
   - `sky_rol_razas()` → Human, Erune, Draph, Harvin, Draconic (§3)
   - `sky_rol_facciones()` → Skyfarer, Empire, Society, Guild, Order, Merchant
   - `sky_rol_armas()`, `sky_rol_packs_equipo()` → equipo skyfarer
   - Quitar `V-ESP-03 Potencial de Fruta` y refs a frutas/haki
3. **Sellos de poder:** renombrar Haki → Clases (`sky_rol_clases.php`), lore GBF.
4. **Renombre:** `sky_rol_wanted.php` → `sky_rol_renombre.php` (misma lógica).
5. **Guías:** reescribir `guias.php` (reglas Granblue, no OP).
6. **Bibliotecas:** reconvertir o borrar `biblioteca-akuma.php` (→ `biblioteca-primales.php`), `biblioteca-lore.php`, `biblioteca-bestiario.php`.
7. **Mundo:** reseed `rol_mv_zonas` con Skydoms; `sky_rol_mundo.php` con geografía celeste.

### Fase P3 — Purga de marca (BD y ajustes)
1. **Crear** `scripts/rebrand-sky.php` (clon de `rebrand-opeternal.php`):
   - `bbname` → nombre de marca nuevo.
   - datacache `gbe_home` → `sky_home` con curiosidades/lore Granblue.
2. **Bot sistema:** renombrar usuario/PJ `GBEternal` → `Lyria` (BD `users` + `rol_personajes`, ver `gbe_system_uid()`/`gbe_system_pid()` → `sky_system_*`).
3. **Ajustes MyBB:** revisar `inc/settings.php` y admin settings por refs a marca.

### Fase P4 — Purga de docs ✅ (Jul 2026)
1. ~~`DESIGN-ONE-PIECE-ETERNAL.md` → `DESIGN-GRANBLUE-ETERNAL.md`.~~
2. `docs/PRODUCT.md` → actualizar a marca nueva (pendiente).
3. ~~Referencias OP obsoletas~~ (`onepiecegaiden`, planes brutalistas, NeoBrutalism, HTML de planificación).
4. ~~`AGENTS.md`~~ → apunta a `DESIGN-GRANBLUE-ETERNAL.md` §5.
5. Actualizar `README.md` raíz (pendiente).

### Fase P5 — Construcción del skin Granblue
(Cubierto por `PLAN-MAESTRO-GRANBLUE-ETERNAL.md` y `DESIGN-GRANBLUE-ETERNAL.md`.)
- Tema CSS `sky.css` (paleta cielo).
- Sistemas nuevos: ventaja elemental, nave, Chain/Charge, Pactos, summons.

---

## 8. Archivos a borrar / renombrar / crear

### 8.1 BORRAR (lore OP sin valor reutilizable)

| Archivo | Motivo |
|---|---|
| `scripts/seed-yonko.php` | Seed de Yonkos (OP puro) |
| `scripts/seed-marines.php` | Seed de Marines (OP puro) |
| `scripts/seed-isabella.php` | NPC OP específico ("Reina Pirata") |
| `biblioteca-akuma.php` | Biblioteca de Frutas del Diablo | *(o reconvertir a Primales)* |
| `scripts/seed-lore.php` (contenido) | Lore OP — reescribir seed con lore GBF |
| `scripts/seed-crew.php`, `seed-civiles.php`, `seed-npc.php` (contenido) | Reseed con PJs GBF originales |
| `scripts/seed-mv-demo.php`, `seed-mundo-vivo-demo.php` | Demos con lore OP |

> Las **migraciones históricas** (`migrate-oleada*.php`, `migrate-mundo-vivo-v*.php`, etc.) **NO se borran**: son historial de esquema. Se dejan como están.

### 8.2 RENOMBRAR (codename + lore)

| De | A |
|---|---|
| `inc/gbe_rol_data.php` | `inc/sky_rol_data.php` (+ reescribir catálogos) |
| `inc/gbe_rol_system.php` | `inc/sky_rol_system.php` |
| `inc/gbe_rol_haki.php` | `inc/sky_rol_clases.php` |
| `inc/gbe_rol_wanted.php` | `inc/sky_rol_renombre.php` |
| `inc/gbe_rol_pl.php` | `inc/sky_rol_cristales.php` |
| `inc/gbe_rol_mundo.php` | `inc/sky_rol_mundo.php` |
| `inc/gbe_rol_viajes.php` | `inc/sky_rol_viajes.php` |
| `inc/gbe_rol_catalogos.php` | `inc/sky_rol_catalogos.php` |
| `inc/gbe_rol_oraculo*.php` | `inc/sky_rol_oraculo*.php` |
| `inc/gbe_functions.php`, `inc/gbe_user_init.php` | `inc/sky_*` |
| `inc/plugins/gbe_rol.php` | `inc/plugins/sky_rol.php` (⚠️ re-registrar plugin) |
| `haki.php` | `clases.php` |
| `docs/themes/ope*.xml`, `gbe.css` | `sky*.xml`, `sky.css` |

### 8.3 CREAR (skin Granblue — detalle en hoja de ruta)

| Archivo | Propósito |
|---|---|
| `scripts/rename-sky.php` | Script de purga de codename (P1) |
| `scripts/rebrand-sky.php` | Rebrand de BD (P3) |
| `docs/themes/sky.css` | Tema visual Granblue |
| `inc/sky_rol_clases.php` | Clases (ex-Haki) |
| `inc/sky_rol_pactos.php` | Pactos Primal (ex-Fruta) |
| `inc/sky_rol_nave.php` | Ficha y combate de aeronave |
| `astillero.php` | Gestión de nave |
| `docs/DESIGN-SKYFARERS.md` | Design doc formal nuevo |

---

## 9. Base de datos

### 9.1 Tablas — conservar prefijo `rol_`, renombrar solo las OP-específicas

| Tabla actual | Acción |
|---|---|
| `rol_personajes`, `rol_cuentas`, `rol_tripulaciones`, `rol_tramites`, `rol_estados`, `rol_tecnicas`, `rol_cartas`, `rol_post_snapshot`, `rol_objetos`, `rol_npcs_secundarios`, `rol_mv_*`, `rol_calendario` | **Conservar** (genéricas) |
| `rol_haki` | Renombrar → `rol_clases` |
| `rol_wanted` | Renombrar → `rol_renombre` |
| `rol_pl` / `rol_pl_log` | Renombrar → `rol_cristales` / `rol_cristales_log` |

### 9.2 Columnas nuevas

```sql
ALTER TABLE rol_personajes    ADD elemento VARCHAR(16) NULL;
ALTER TABLE rol_personajes    ADD arma_principal_id INT NULL;
ALTER TABLE rol_tripulaciones ADD nave_json TEXT NULL;
ALTER TABLE rol_npcs_secundarios ADD es_summon TINYINT DEFAULT 0;
ALTER TABLE rol_npcs_secundarios ADD rol_crew VARCHAR(24) NULL;
CREATE TABLE rol_pactos (pid INT, primal_slug VARCHAR(48), nivel INT, notas TEXT);
```

### 9.3 Datos a migrar/limpiar

- `datacache`: `gbe_home` → `sky_home`.
- `settings`: `bbname`.
- `users` + `rol_personajes`: bot `GBEternal` → `Lyria`.
- Contenido de catálogos (razas/facciones): re-seed, no migrar personajes OP existentes si es reinicio de comunidad (decisión abierta §11).

---

## 10. Validación y criterios de aceptación

La migración está **completa** cuando todos estos checks pasan:

### 10.1 Cero rastro de codename/marca

```bash
# No debe devolver NADA en código propio (excluyendo core MyBB, refs, backups):
rg -i "one piece|grand line|yonko|kairoseki|logia|nakama" --glob '*.php' --glob '!inc/3rdparty/**'
rg "gbe_|gbe-|GBE_" --glob '*.php' --glob '*.css' --glob '*.xml'
rg -i "iforge" --glob '*.php' --glob '*.css'
rg -i "berries|haki|akuma|fruta del diablo|wanted|marine|pirata" --glob '*.php'
```
> Resultado esperado: 0 coincidencias en código propio (las de `docs/references/_archive/` se ignoran).

### 10.2 Integridad del motor

- [ ] `php scripts/check-inline-styles.php` limpio.
- [ ] `php scripts/sync-theme.php verify` → `OK CSS: in sync`.
- [ ] Plugin re-registrado y activo (nombre nuevo).
- [ ] Wizard de creación carga catálogos GBF (razas, facciones).
- [ ] Combate: parser renderiza técnicas; snapshots se generan.
- [ ] Navbar, tripulación, tienda, ficha cargan sin error PHP.
- [ ] `py -m graphify update .` ejecutado (grafo al día).

### 10.3 Marca

- [ ] `bbname` = marca nueva en toda la UI.
- [ ] Bot sistema aparece como `Lyria`.
- [ ] Home muestra curiosidades/lore Granblue.
- [ ] Cero mención "One Piece" / "I-Forge" en UI visible.

---

## 11. Orden de ejecución recomendado

```
1. P0  Rama + backups + decisiones (marca, codename sky, ruta /iforge)
2. P1  rename-sky.php (codename) + renombrar archivos + fix requires + sync theme
3. P3  rebrand-sky.php (bbname, datacache, bot) — pronto, para ver marca nueva
4. P2  Reescribir catálogos + borrar seeds OP + reseed Skydoms + guías
5. P4  Docs (DESIGN-SKYFARERS.md, PRODUCT, AGENTS, archivar refs OPG)
6. P5  Construir skin Granblue (tema CSS, nave, elementos, pactos) → hoja de ruta
7. Validación §10 completa → merge a main
```

### Decisiones abiertas (cerrar en P0)

1. **Nombre de marca final** (afecta bbname, dominio, logo).
2. **Codename** `sky` u otro.
3. **¿Se toca la ruta web `/iforge/`?** (infra, no marca).
4. **¿Reinicio de comunidad o migración de PJs existentes?** — si es reinicio, se pueden truncar `rol_personajes` OP; si no, hay que mapear razas/facciones antiguas a las nuevas.
5. **¿`rol_` se mantiene?** (recomendado sí).

---

> **Siguiente paso:** cerrar las 5 decisiones de §11, crear la rama `migracion-granblue` y escribir `scripts/rename-sky.php`. A partir de ahí, la purga P1–P4 es mayormente mecánica y verificable con los greps de §10.
